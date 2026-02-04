<?php

namespace CryptoTax\Services;

use CryptoTax\Models\Transaction;

/**
 * Transaction Sorter
 * Sorts transactions chronologically with deterministic ordering
 */
class TransactionSorter
{
    /**
     * Sort transactions chronologically
     * Uses date as primary sort, original line number as tie-breaker
     * 
     * @param Transaction[] $transactions
     * @return Transaction[]
     */
    public function sort(array $transactions): array
    {
        usort($transactions, function(Transaction $a, Transaction $b) {
            // Primary sort: by date
            $dateComparison = $a->getDate() <=> $b->getDate();
            
            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            // Tie-breaker: by original line number (maintains file order)
            return $a->getOriginalLineNumber() <=> $b->getOriginalLineNumber();
        });

        return $transactions;
    }
}
