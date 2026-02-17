<?php

namespace CryptoTax\Services;

use CryptoTax\Parsers\CSVParser;
use CryptoTax\Parsers\XLSXParser;
use CryptoTax\Validators\TransactionValidator;
use CryptoTax\Exceptions\ParseException;
use CryptoTax\Exceptions\ValidationException;

/**
 * File Processor
 * Main service that orchestrates file parsing, validation, normalization and sorting
 * Now supports multiple exchange formats and shapes
 */
class FileProcessor
{
    private CSVParser $csvParser;
    private XLSXParser $xlsxParser;
    private TransactionValidator $validator;
    private TransactionNormalizer $normalizer;
    private TransactionSorter $sorter;
    private ColumnAliasMapper $aliasMapper;
    private ShapeDetector $shapeDetector;
    private SuspiciousTransactionDetector $suspiciousDetector;
    private NonTaxableEventDetector $nonTaxableDetector;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        // Initialize services with dependencies
        $this->aliasMapper = new ColumnAliasMapper();
        $this->shapeDetector = new ShapeDetector($this->aliasMapper);
        
        $this->csvParser = new CSVParser($this->aliasMapper, $this->shapeDetector);
        $this->xlsxParser = new XLSXParser($this->aliasMapper, $this->shapeDetector);
        $this->validator = new TransactionValidator($this->shapeDetector);
        $this->normalizer = new TransactionNormalizer();
        $this->sorter = new TransactionSorter();
        $this->suspiciousDetector = new SuspiciousTransactionDetector();
        $this->nonTaxableDetector = new NonTaxableEventDetector();
        $this->logger = $logger;
    }

    /**
     * Process uploaded file
     * 
     * @param string $filePath Path to uploaded file
     * @param string $fileName Original filename
     * @return array Processed result with transactions and summary
     * @throws ParseException|ValidationException
     */
    public function processFile(string $filePath, string $fileName): array
    {
        $this->logger->info("Processing file: {$fileName}");

        // Step 1: Parse file based on extension
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $detectedShape = 'A';
        
        if ($extension === 'csv') {
            $this->logger->info("Parsing CSV file");
            $rawRows = $this->csvParser->parse($filePath);
            $detectedShape = $this->csvParser->getDetectedShape();
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            $this->logger->info("Parsing XLSX/Excel file");
            $rawRows = $this->xlsxParser->parse($filePath);
            $detectedShape = $this->xlsxParser->getDetectedShape();
        } else {
            throw new ParseException("Unsupported file format: {$extension}. Please upload CSV or XLSX files.");
        }

        $this->logger->info("Parsed " . count($rawRows) . " raw rows");
        $this->logger->info("Detected format: Shape {$detectedShape} - " . 
            $this->shapeDetector->getShapeDescription($detectedShape));

        // Step 2: Validate rows based on detected shape
        $this->logger->info("Validating transactions for Shape {$detectedShape}");
        $this->validator->validate($rawRows, $detectedShape);
        $this->logger->info("Validation successful");

        // Step 3: Normalize into Transaction objects with shape-specific logic
        $this->logger->info("Normalizing transactions");
        $transactions = $this->normalizer->normalize($rawRows, $detectedShape);
        $this->logger->info("Normalized " . count($transactions) . " transactions");

        // Step 4: Sort chronologically
        $this->logger->info("Sorting transactions chronologically");
        $transactions = $this->sorter->sort($transactions);
        $this->logger->info("Sorting complete");

        // Step 5: Run suspicious transaction detection
        $this->logger->info("Running suspicious transaction detection");
        $detectionResults = $this->suspiciousDetector->analyzeTransactions($transactions);
        $this->logger->info(sprintf("Detection complete - Found %d red flags (Risk Score: %d/100)",
            $detectionResults['summary']['total_flags'],
            $detectionResults['summary']['audit_risk_score']
        ));

        // Step 6: Generate summary
        $summary = $this->generateSummary($transactions, $detectedShape);

        // Convert transactions to array format
        $transactionArray = array_map(function($transaction) {
            return $transaction->toArray();
        }, $transactions);

        // Analyze taxable vs non-taxable events
        $this->logger->info("Analyzing tax status of transactions");
        $taxStatusResults = $this->nonTaxableDetector->analyzeTransactions($transactions);

        $this->logger->info("Processing complete");

        return [
            'transactions' => $transactionArray,
            'summary' => $summary,
            'detected_format' => [
                'shape' => $detectedShape,
                'description' => $this->shapeDetector->getShapeDescription($detectedShape)
            ],
            'red_flags' => $detectionResults['red_flags'],
            'red_flag_summary' => $detectionResults['summary'],
            'has_critical_issues' => $detectionResults['has_critical_issues'],
            'audit_risk_level' => $detectionResults['audit_risk_level'],
            'tax_status' => [
                'non_taxable_events' => $taxStatusResults['non_taxable_events'],
                'taxable_events' => $taxStatusResults['taxable_events'],
                'summary' => $taxStatusResults['summary'],
                'tax_obligation_exists' => $taxStatusResults['tax_obligation_exists']
            ]
        ];
    }

    /**
     * Generate summary statistics
     */
    private function generateSummary(array $transactions, string $shape = 'A'): array
    {
        $summary = [
            'total_transactions' => count($transactions),
            'transaction_types' => [],
            'currencies' => [],
            'date_range' => [
                'earliest' => null,
                'latest' => null
            ],
            'format_shape' => $shape
        ];

        if (empty($transactions)) {
            return $summary;
        }

        $currencies = [];
        $types = [];

        foreach ($transactions as $transaction) {
            // Count types
            $type = $transaction->getType();
            $types[$type] = ($types[$type] ?? 0) + 1;

            // Collect currencies
            $currencies[$transaction->getFromCurrency()] = true;
            $currencies[$transaction->getToCurrency()] = true;

            // Track date range
            $date = $transaction->getDate();
            if ($summary['date_range']['earliest'] === null || $date < $summary['date_range']['earliest']) {
                $summary['date_range']['earliest'] = $date;
            }
            if ($summary['date_range']['latest'] === null || $date > $summary['date_range']['latest']) {
                $summary['date_range']['latest'] = $date;
            }
        }

        $summary['transaction_types'] = $types;
        $summary['currencies'] = array_keys($currencies);
        $summary['date_range']['earliest'] = $summary['date_range']['earliest']->format('Y-m-d');
        $summary['date_range']['latest'] = $summary['date_range']['latest']->format('Y-m-d');

        return $summary;
    }
}
