<?php

namespace CryptoTax\Parsers;

use CryptoTax\Exceptions\ParseException;
use CryptoTax\Services\ColumnAliasMapper;
use CryptoTax\Services\ShapeDetector;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * XLSX Parser
 * Parses Excel files into raw transaction rows with intelligent header mapping
 */
class XLSXParser
{
    private ColumnAliasMapper $aliasMapper;
    private ShapeDetector $shapeDetector;
    private string $detectedShape = 'A';

    public function __construct(
        ColumnAliasMapper $aliasMapper = null,
        ShapeDetector $shapeDetector = null
    ) {
        $this->aliasMapper = $aliasMapper ?? new ColumnAliasMapper();
        $this->shapeDetector = $shapeDetector ?? new ShapeDetector($this->aliasMapper);
    }

    /**
     * Parse XLSX file into raw rows
     * 
     * @param string $filePath Path to XLSX file
     * @return array Array of raw transaction rows
     * @throws ParseException
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new ParseException("File not found: {$filePath}");
        }

        try {
            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Get all rows
            $allRows = $worksheet->toArray();
            
            if (empty($allRows)) {
                throw new ParseException("Worksheet is empty");
            }

            // Extract and map headers
            $headerRow = array_shift($allRows);
            $headers = $this->mapHeaders($headerRow);
            
            // Detect data shape
            $this->detectedShape = $this->shapeDetector->detectShape($headers);
            
            // Parse data rows
            $rows = [];
            $lineNumber = 2; // Start at 2 (header is line 1)
            
            foreach ($allRows as $row) {
                // Skip empty rows
                if ($this->isEmptyRow($row)) {
                    $lineNumber++;
                    continue;
                }

                // Parse and convert the row
                $parsedRow = $this->parseRow($headers, $row, $lineNumber, $worksheet);
                $rows[] = $parsedRow;
                
                $lineNumber++;
            }

            return $rows;
            
        } catch (\Exception $e) {
            throw new ParseException("XLSX parsing failed: " . $e->getMessage());
        }
    }

    /**
     * Get the detected shape of the data
     * 
     * @return string Shape identifier ('A', 'B', or 'C')
     */
    public function getDetectedShape(): string
    {
        return $this->detectedShape;
    }

    /**
     * Map headers using alias mapper
     */
    private function mapHeaders(array $headers): array
    {
        return $this->aliasMapper->mapHeaders($headers);
    }

    /**
     * Check if row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string)$cell) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse a single row with Excel-specific conversions
     */
    private function parseRow(array $headers, array $row, int $lineNumber, $worksheet): array
    {
        $parsed = [
            'line_number' => $lineNumber
        ];

        foreach ($headers as $index => $header) {
            $value = $row[$index] ?? '';
            
            // Handle Excel date conversion
            if ($header === 'date' && is_numeric($value)) {
                try {
                    $dateTime = Date::excelToDateTimeObject($value);
                    $value = $dateTime->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // Keep original value if conversion fails
                }
            }
            
            // Convert to string and trim
            $parsed[$header] = trim((string)$value);
        }

        return $parsed;
    }
}
