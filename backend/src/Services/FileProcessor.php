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
 */
class FileProcessor
{
    private CSVParser $csvParser;
    private XLSXParser $xlsxParser;
    private TransactionValidator $validator;
    private TransactionNormalizer $normalizer;
    private TransactionSorter $sorter;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->csvParser = new CSVParser();
        $this->xlsxParser = new XLSXParser();
        $this->validator = new TransactionValidator();
        $this->normalizer = new TransactionNormalizer();
        $this->sorter = new TransactionSorter();
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
        
        if ($extension === 'csv') {
            $this->logger->info("Parsing CSV file");
            $rawRows = $this->csvParser->parse($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            $this->logger->info("Parsing XLSX file");
            $rawRows = $this->xlsxParser->parse($filePath);
        } else {
            throw new ParseException("Unsupported file format: {$extension}. Please upload CSV or XLSX files.");
        }

        $this->logger->info("Parsed " . count($rawRows) . " raw rows");

        // Step 2: Validate rows
        $this->logger->info("Validating transactions");
        $this->validator->validate($rawRows);
        $this->logger->info("Validation successful");

        // Step 3: Normalize into Transaction objects
        $this->logger->info("Normalizing transactions");
        $transactions = $this->normalizer->normalize($rawRows);
        $this->logger->info("Normalized " . count($transactions) . " transactions");

        // Step 4: Sort chronologically
        $this->logger->info("Sorting transactions chronologically");
        $transactions = $this->sorter->sort($transactions);
        $this->logger->info("Sorting complete");

        // Step 5: Generate summary
        $summary = $this->generateSummary($transactions);

        // Convert transactions to array format
        $transactionArray = array_map(function($transaction) {
            return $transaction->toArray();
        }, $transactions);

        $this->logger->info("Processing complete");

        return [
            'transactions' => $transactionArray,
            'summary' => $summary
        ];
    }

    /**
     * Generate summary statistics
     */
    private function generateSummary(array $transactions): array
    {
        $summary = [
            'total_transactions' => count($transactions),
            'transaction_types' => [],
            'currencies' => [],
            'date_range' => [
                'earliest' => null,
                'latest' => null
            ]
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
