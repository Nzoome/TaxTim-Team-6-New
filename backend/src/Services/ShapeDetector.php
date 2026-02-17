<?php

namespace CryptoTax\Services;

/**
 * Shape Detector
 * Detects the format shape of transaction data
 * Shape A: from_* / to_* format
 * Shape B: pair + side + base + quote amounts
 * Shape C: pair + side + base + price
 */
class ShapeDetector
{
    private ColumnAliasMapper $aliasMapper;

    public function __construct(ColumnAliasMapper $aliasMapper)
    {
        $this->aliasMapper = $aliasMapper;
    }

    /**
     * Detect which shape the data is in
     * 
     * @param array $mappedHeaders Headers after alias mapping
     * @return string 'A', 'B', or 'C'
     */
    public function detectShape(array $mappedHeaders): string
    {
        // Shape A: Has from_* and to_* columns
        if ($this->isShapeA($mappedHeaders)) {
            return 'A';
        }

        // Shape B: Has symbol, side, base_amount, quote_amount
        if ($this->isShapeB($mappedHeaders)) {
            return 'B';
        }

        // Shape C: Has symbol, side, base_amount, price (but not quote_amount)
        if ($this->isShapeC($mappedHeaders)) {
            return 'C';
        }

        // Default to Shape A (will require all standard columns)
        return 'A';
    }

    /**
     * Check if data is in Shape A format
     * 
     * @param array $headers Mapped headers
     * @return bool
     */
    private function isShapeA(array $headers): bool
    {
        $requiredColumns = [
            'from_currency',
            'from_amount',
            'to_currency',
            'to_amount'
        ];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if data is in Shape B format
     * 
     * @param array $headers Mapped headers
     * @return bool
     */
    private function isShapeB(array $headers): bool
    {
        $requiredColumns = [
            'symbol',
            'type',
            'base_amount',
            'quote_amount'
        ];

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if data is in Shape C format
     * 
     * @param array $headers Mapped headers
     * @return bool
     */
    private function isShapeC(array $headers): bool
    {
        $requiredColumns = [
            'symbol',
            'type',
            'base_amount',
            'price'
        ];

        // Must have all required columns
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                return false;
            }
        }

        // Must NOT have quote_amount (otherwise it would be Shape B)
        if (in_array('quote_amount', $headers)) {
            return false;
        }

        return true;
    }

    /**
     * Get required columns for detected shape
     * 
     * @param string $shape Shape identifier ('A', 'B', or 'C')
     * @return array List of required column names
     */
    public function getRequiredColumns(string $shape): array
    {
        switch ($shape) {
            case 'A':
                return [
                    'date',
                    'type',
                    'from_currency',
                    'from_amount',
                    'to_currency',
                    'to_amount'
                ];
            
            case 'B':
                return [
                    'date',
                    'symbol',
                    'type',
                    'base_amount',
                    'quote_amount'
                ];
            
            case 'C':
                return [
                    'date',
                    'symbol',
                    'type',
                    'base_amount',
                    'price'
                ];
            
            default:
                return [];
        }
    }

    /**
     * Get description of a shape
     * 
     * @param string $shape Shape identifier
     * @return string Human-readable description
     */
    public function getShapeDescription(string $shape): string
    {
        switch ($shape) {
            case 'A':
                return 'Standard format (from/to currencies and amounts)';
            
            case 'B':
                return 'Exchange format with trading pair and both amounts';
            
            case 'C':
                return 'Exchange format with trading pair, amount, and price';
            
            default:
                return 'Unknown format';
        }
    }
}
