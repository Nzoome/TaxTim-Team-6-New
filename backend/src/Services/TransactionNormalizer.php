<?php

namespace CryptoTax\Services;

use CryptoTax\Models\Transaction;
use DateTime;

/**
 * Transaction Normalizer
 * Converts validated raw rows into Transaction objects
 */
class TransactionNormalizer
{
    /**
     * Normalize raw rows into Transaction objects
     * 
     * @param array $rows Validated raw rows
     * @return Transaction[]
     */
    public function normalize(array $rows): array
    {
        $transactions = [];

        foreach ($rows as $row) {
            $transactions[] = $this->normalizeRow($row);
        }

        return $transactions;
    }

    /**
     * Normalize a single row into a Transaction object
     */
    private function normalizeRow(array $row): Transaction
    {
        // Parse date
        $date = new DateTime($row['date']);

        // Standardize type
        $type = strtoupper(trim($row['type']));

        // Get currencies and amounts
        $fromCurrency = strtoupper(trim($row['from_currency']));
        $fromAmount = (float)$row['from_amount'];
        $toCurrency = strtoupper(trim($row['to_currency']));
        $toAmount = (float)$row['to_amount'];

        // Get price
        $price = (float)$row['price'];

        // Get optional fee
        $fee = isset($row['fee']) && $row['fee'] !== '' ? (float)$row['fee'] : 0.0;

        // Get optional wallet
        $wallet = isset($row['wallet']) && $row['wallet'] !== '' ? trim($row['wallet']) : null;

        // Get original line number
        $lineNumber = $row['line_number'] ?? 0;

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
}
