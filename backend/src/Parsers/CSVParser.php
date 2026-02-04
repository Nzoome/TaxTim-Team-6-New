<?php

namespace CryptoTax\Parsers;

use CryptoTax\Exceptions\ParseException;

/**
 * CSV Parser
 * Parses CSV files into raw transaction rows
 */
class CSVParser
{
    private array $requiredColumns = [
        'date',
        'type',
        'from_currency',
        'from_amount',
        'to_currency',
        'to_amount',
        'price'
    ];

    /**
     * Parse CSV file into raw rows
     * 
     * @param string $filePath Path to CSV file
     * @return array Array of raw transaction rows
     * @throws ParseException
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new ParseException("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new ParseException("Unable to open file: {$filePath}");
        }

        try {
            // Read header row
            $headerRow = fgetcsv($handle);
            if ($headerRow === false) {
                throw new ParseException("Unable to read header row");
            }

            // Normalize headers
            $headers = $this->normalizeHeaders($headerRow);
            
            // Parse data rows
            $rows = [];
            $lineNumber = 2; // Start at 2 (header is line 1)
            
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if ($this->isEmptyRow($row)) {
                    $lineNumber++;
                    continue;
                }

                // Convert row to associative array
                $parsedRow = $this->parseRow($headers, $row, $lineNumber);
                $rows[] = $parsedRow;
                
                $lineNumber++;
            }

            fclose($handle);
            return $rows;
            
        } catch (\Exception $e) {
            fclose($handle);
            throw new ParseException("CSV parsing failed: " . $e->getMessage());
        }
    }

    /**
     * Normalize header names
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function($header) {
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
            if (!empty(trim($cell))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse a single row
     */
    private function parseRow(array $headers, array $row, int $lineNumber): array
    {
        $parsed = [
            'line_number' => $lineNumber
        ];

        foreach ($headers as $index => $header) {
            $parsed[$header] = isset($row[$index]) ? trim($row[$index]) : '';
        }

        return $parsed;
    }
}
