<?php

namespace CryptoTax\Services;

use CryptoTax\Models\Transaction;
use CryptoTax\Exceptions\ParseException;
use DateTime;

/**
 * Transaction Normalizer
 * Converts validated raw rows into Transaction objects
 * Handles different shapes (A, B, C) and normalizes data formats
 */
class TransactionNormalizer
{
    private FormatNormalizer $formatNormalizer;
    private PairParser $pairParser;

    public function __construct(
        FormatNormalizer $formatNormalizer = null,
        PairParser $pairParser = null
    ) {
        $this->formatNormalizer = $formatNormalizer ?? new FormatNormalizer();
        $this->pairParser = $pairParser ?? new PairParser();
    }

    /**
     * Normalize raw rows into Transaction objects
     * 
     * @param array $rows Validated raw rows
     * @param string $shape Data shape ('A', 'B', or 'C')
     * @return Transaction[]
     */
    public function normalize(array $rows, string $shape = 'A'): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            $transactions[] = $this->normalizeRow($row, $shape);
        }

        return $transactions;
    }

    /**
     * Normalize a single row into a Transaction object
     */
    private function normalizeRow(array $row, string $shape): Transaction
    {
        // Parse date
        $date = new DateTime($row['date']);

        // Get original line number
        $lineNumber = $row['line_number'] ?? 0;

        // Process based on shape
        switch ($shape) {
            case 'A':
                return $this->normalizeShapeA($row, $date, $lineNumber);
            case 'B':
                return $this->normalizeShapeB($row, $date, $lineNumber);
            case 'C':
                return $this->normalizeShapeC($row, $date, $lineNumber);
            default:
                throw new ParseException("Unknown shape: {$shape}");
        }
    }

    /**
     * Normalize Shape A data (from_* / to_* format)
     */
    private function normalizeShapeA(array $row, DateTime $date, int $lineNumber): Transaction
    {
        // Normalize type
        $type = $this->formatNormalizer->normalizeType($row['type']);

        // Get currencies and amounts with format normalization
        $fromCurrency = $this->formatNormalizer->normalizeCurrency($row['from_currency']);
        $fromAmount = $this->formatNormalizer->normalizeNumber($row['from_amount']);
        $toCurrency = $this->formatNormalizer->normalizeCurrency($row['to_currency']);
        $toAmount = $this->formatNormalizer->normalizeNumber($row['to_amount']);

        // Calculate or validate price
        $price = $this->calculatePrice($type, $fromAmount, $toAmount, $row['price'] ?? null);

        // Get optional fee
        $fee = isset($row['fee']) && $row['fee'] !== '' 
            ? $this->formatNormalizer->normalizeNumber($row['fee']) 
            : 0.0;

        // Get optional wallet
        $wallet = isset($row['wallet']) && $row['wallet'] !== '' ? trim($row['wallet']) : null;

        return new Transaction(
            $date,
            $type,
            $fromCurrency,
            $fromAmount,
            $toCurrency,
            $toAmount,
            $price,
            $fee,
            $wallet,
            $lineNumber
        );
    }

    /**
     * Normalize Shape B data (pair + side + amounts)
     */
    private function normalizeShapeB(array $row, DateTime $date, int $lineNumber): Transaction
    {
        // Parse trading pair
        $pair = $this->pairParser->parse($row['symbol']);
        $base = $pair['base'];
        $quote = $pair['quote'];

        // Normalize type
        $type = $this->formatNormalizer->normalizeType($row['type']);

        // Get amounts
        $baseAmount = $this->formatNormalizer->normalizeNumber($row['base_amount']);
        $quoteAmount = $this->formatNormalizer->normalizeNumber($row['quote_amount']);

        // Map to from/to based on BUY or SELL
        if ($type === 'BUY') {
            // Buying base with quote
            $fromCurrency = $quote;
            $fromAmount = $quoteAmount;
            $toCurrency = $base;
            $toAmount = $baseAmount;
        } else {
            // Selling base for quote
            $fromCurrency = $base;
            $fromAmount = $baseAmount;
            $toCurrency = $quote;
            $toAmount = $quoteAmount;
        }

        // Calculate price
        $price = $this->calculatePrice($type, $fromAmount, $toAmount, $row['price'] ?? null);

        // Get optional fee
        $fee = isset($row['fee']) && $row['fee'] !== '' 
            ? $this->formatNormalizer->normalizeNumber($row['fee']) 
            : 0.0;

        // Get optional wallet
        $wallet = isset($row['wallet']) && $row['wallet'] !== '' 
            ? trim($row['wallet']) 
            : 'exchange_import';

        return new Transaction(
            $date,
            $type,
            $fromCurrency,
            $fromAmount,
            $toCurrency,
            $toAmount,
            $price,
            $fee,
            $wallet,
            $lineNumber
        );
    }

    /**
     * Normalize Shape C data (pair + side + base amount + price)
     */
    private function normalizeShapeC(array $row, DateTime $date, int $lineNumber): Transaction
    {
        // Parse trading pair
        $pair = $this->pairParser->parse($row['symbol']);
        $base = $pair['base'];
        $quote = $pair['quote'];

        // Normalize type
        $type = $this->formatNormalizer->normalizeType($row['type']);

        // Get base amount and price
        $baseAmount = $this->formatNormalizer->normalizeNumber($row['base_amount']);
        $price = $this->formatNormalizer->normalizeNumber($row['price']);

        // Calculate quote amount
        $quoteAmount = $baseAmount * $price;

        // Map to from/to based on BUY or SELL
        if ($type === 'BUY') {
            // Buying base with quote
            $fromCurrency = $quote;
            $fromAmount = $quoteAmount;
            $toCurrency = $base;
            $toAmount = $baseAmount;
        } else {
            // Selling base for quote
            $fromCurrency = $base;
            $fromAmount = $baseAmount;
            $toCurrency = $quote;
            $toAmount = $quoteAmount;
        }

        // Get optional fee
        $fee = isset($row['fee']) && $row['fee'] !== '' 
            ? $this->formatNormalizer->normalizeNumber($row['fee']) 
            : 0.0;

        // Get optional wallet
        $wallet = isset($row['wallet']) && $row['wallet'] !== '' 
            ? trim($row['wallet']) 
            : 'exchange_import';

        return new Transaction(
            $date,
            $type,
            $fromCurrency,
            $fromAmount,
            $toCurrency,
            $toAmount,
            $price,
            $fee,
            $wallet,
            $lineNumber
        );
    }

    /**
     * Calculate or validate price
     * 
     * @param string $type Transaction type
     * @param float $fromAmount From amount
     * @param float $toAmount To amount
     * @param mixed $sourcePrice Price from source (if any)
     * @return float Calculated or validated price
     */
    private function calculatePrice(string $type, float $fromAmount, float $toAmount, $sourcePrice = null): float
    {
        // Calculate derived price based on canonical formula
        if (in_array($type, ['BUY', 'TRADE'])) {
            $derivedPrice = $toAmount > 0 ? $fromAmount / $toAmount : 0;
        } else {
            // SELL
            $derivedPrice = $fromAmount > 0 ? $toAmount / $fromAmount : 0;
        }

        // If no source price provided, use derived
        if ($sourcePrice === null || $sourcePrice === '') {
            return $derivedPrice;
        }

        // Normalize source price
        $sourcePrice = $this->formatNormalizer->normalizeNumber($sourcePrice);

        // Validate source price against derived (within 1% tolerance)
        if ($derivedPrice > 0) {
            $difference = abs($sourcePrice - $derivedPrice) / $derivedPrice;
            
            if ($difference <= 0.01) {
                // Source price is valid, use it
                return $sourcePrice;
            }
        }

        // Use derived price if source price is invalid
        return $derivedPrice;
    }
}
