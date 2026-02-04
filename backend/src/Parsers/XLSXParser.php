<?php

namespace CryptoTax\Parsers;

use CryptoTax\Exceptions\ParseException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * XLSX Parser
 * Parses Excel files into raw transaction rows (same format as CSV)
 */
class XLSXParser
{
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

            // Extract and normalize headers
            $headerRow = array_shift($allRows);
            $headers = $this->normalizeHeaders($headerRow);
            
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
     * Normalize header names (same logic as CSV parser)
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function($header) {
            if ($header === null) {
                return '';
            }
            
            // Convert to lowercase and replace spaces/dashes with underscores
            $normalized = strtolower(trim($header));
            $normalized = preg_replace('/[\s\-]+/', '_', $normalized);
            
            // Handle common variations
            $mappings = [
                'transaction_type' => 'type',
                'transaction_date' => 'date',
                'datetime' => 'date',
                'timestamp' => 'date',
                'from_coin' => 'from_currency',
                'from_crypto' => 'from_currency',
                'to_coin' => 'to_currency',
                'to_crypto' => 'to_currency',
                'from_qty' => 'from_amount',
                'from_quantity' => 'from_amount',
                'to_qty' => 'to_amount',
                'to_quantity' => 'to_amount',
                'price_zar' => 'price',
                'price_per_unit' => 'price',
                'unit_price' => 'price',
                'fees' => 'fee',
                'transaction_fee' => 'fee'
            ];
            
            return $mappings[$normalized] ?? $normalized;
        }, $headers);
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
