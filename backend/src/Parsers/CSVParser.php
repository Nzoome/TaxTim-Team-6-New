<?php

namespace CryptoTax\Parsers;

use CryptoTax\Exceptions\ParseException;
use CryptoTax\Services\ColumnAliasMapper;
use CryptoTax\Services\ShapeDetector;

/**
 * CSV Parser
 * Parses CSV files into raw transaction rows with intelligent header mapping
 */
class CSVParser
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

            // Map headers using alias mapper
            $headers = $this->mapHeaders($headerRow);
            
            // Detect data shape
            $this->detectedShape = $this->shapeDetector->detectShape($headers);
            
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
