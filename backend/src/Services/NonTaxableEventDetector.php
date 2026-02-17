<?php

namespace CryptoTax\Services;

use CryptoTax\Models\Transaction;
use DateTime;

/**
 * Non-Taxable Event Detector
 * 
 * Identifies transactions that are NOT taxable under South African tax law.
 * 
 * Non-Taxable Events in South Africa:
 * 1. Buying cryptocurrency with ZAR - No taxable event
 * 2. Holding cryptocurrency - No taxable event
 * 3. Internal wallet transfers - No taxable event
 * 
 * Taxable Events:
 * 1. Selling for ZAR - CGT or Income Tax
 * 2. Crypto-to-crypto swaps/trades - CGT or Income Tax
 * 3. Spending crypto - CGT or Income Tax
 * 4. Gifting crypto - CGT or Income Tax (up to R100,000 exempt)
 * 5. Mining, staking, airdrops - Income Tax
 * 6. Getting paid in crypto - Income Tax
 * 7. NFTs: selling/trading/creating - CGT or Income Tax
 * 8. DeFi activities - Likely Income Tax
 * 9. Losses or theft - Capital Loss Deduction
 * 10. Donating crypto - Up to R100,000 exempt
 */
class NonTaxableEventDetector
{
    /**
     * @var array Detected non-taxable events
     */
    private array $nonTaxableEvents = [];

    /**
     * @var array Detected taxable events
     */
    private array $taxableEvents = [];

    /**
     * @var array Summary statistics
     */
    private array $summary = [
        'total_transactions' => 0,
        'non_taxable_count' => 0,
        'taxable_count' => 0,
        'buy_with_zar' => 0,
        'internal_transfers' => 0,
        'sells' => 0,
        'trades' => 0,
        'other_taxable' => 0
    ];

    /**
     * Analyze transactions to identify taxable vs non-taxable events
     * 
     * @param Transaction[] $transactions Array of transactions
     * @return array Detection results
     */
    public function analyzeTransactions(array $transactions): array
    {
        $this->nonTaxableEvents = [];
        $this->taxableEvents = [];
        $this->resetSummary();

        foreach ($transactions as $index => $transaction) {
            $this->summary['total_transactions']++;
            
            $taxStatus = $this->evaluateTaxStatus($transaction, $index);
            
            if ($taxStatus['is_taxable']) {
                $this->taxableEvents[] = $taxStatus;
                $this->summary['taxable_count']++;
                $this->updateTaxableCategory($taxStatus['tax_type']);
            } else {
                $this->nonTaxableEvents[] = $taxStatus;
                $this->summary['non_taxable_count']++;
                $this->updateNonTaxableCategory($taxStatus['reason']);
            }
        }

        return [
            'non_taxable_events' => $this->nonTaxableEvents,
            'taxable_events' => $this->taxableEvents,
            'summary' => $this->summary,
            'tax_obligation_exists' => $this->summary['taxable_count'] > 0
        ];
    }

    /**
     * Evaluate if a transaction is taxable or not
     * 
     * @param Transaction $transaction
     * @param int $index
     * @return array Tax status information
     */
    private function evaluateTaxStatus(Transaction $transaction, int $index): array
    {
        $type = $transaction->getType();
        $fromCurrency = $transaction->getFromCurrency();
        $toCurrency = $transaction->getToCurrency();
        
        $baseInfo = [
            'transaction_index' => $index,
            'line_number' => $transaction->getOriginalLineNumber(),
            'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
            'type' => $type,
            'from' => $fromCurrency . ' ' . $transaction->getFromAmount(),
            'to' => $toCurrency . ' ' . $transaction->getToAmount(),
            'price' => $transaction->getPrice(),
            'wallet' => $transaction->getWallet()
        ];

        // NON-TAXABLE: Buying cryptocurrency with ZAR
        if ($type === 'BUY' && $fromCurrency === 'ZAR') {
            return array_merge($baseInfo, [
                'is_taxable' => false,
                'reason' => 'BUY_WITH_ZAR',
                'explanation' => 'Buying cryptocurrency with ZAR is not a taxable event in South Africa. No tax obligation at purchase.',
                'note' => 'Keep records for future cost-basis calculations when you sell.'
            ]);
        }

        // NON-TAXABLE: Internal wallet transfers (TRANSFER type)
        if ($type === 'TRANSFER') {
            return array_merge($baseInfo, [
                'is_taxable' => false,
                'reason' => 'INTERNAL_TRANSFER',
                'explanation' => 'Transferring crypto between your own wallets is not a taxable event. No disposal has occurred.',
                'note' => 'Ensure both wallets belong to you. Transfers to others may be gifts (taxable).'
            ]);
        }

        // TAXABLE: Selling cryptocurrency for ZAR
        if ($type === 'SELL' && $toCurrency === 'ZAR') {
            return array_merge($baseInfo, [
                'is_taxable' => true,
                'tax_type' => 'CGT_OR_INCOME',
                'explanation' => 'Selling cryptocurrency for ZAR triggers Capital Gains Tax (CGT) or Income Tax.',
                'note' => 'Capital gains are subject to CGT with R40,000 annual exclusion and 40% inclusion rate.',
                'sars_requirement' => 'Must be reported on your tax return.'
            ]);
        }

        // TAXABLE: Crypto-to-crypto trades/swaps
        if ($type === 'TRADE' || ($type === 'SELL' && $toCurrency !== 'ZAR')) {
            return array_merge($baseInfo, [
                'is_taxable' => true,
                'tax_type' => 'CGT_OR_INCOME',
                'explanation' => 'Crypto-to-crypto swaps are treated as disposal of the original crypto (taxable event).',
                'note' => 'Both legs of the trade may have tax implications. FIFO method applies.',
                'sars_requirement' => 'Must be reported on your tax return.'
            ]);
        }

        // TAXABLE: Buying crypto with crypto (implies you sold the source crypto)
        if ($type === 'BUY' && $fromCurrency !== 'ZAR') {
            return array_merge($baseInfo, [
                'is_taxable' => true,
                'tax_type' => 'CGT_OR_INCOME',
                'explanation' => 'Buying crypto with crypto is a disposal of the source cryptocurrency (taxable event).',
                'note' => 'You are effectively selling ' . $fromCurrency . ' to buy ' . $toCurrency . '.',
                'sars_requirement' => 'Must be reported on your tax return.'
            ]);
        }

        // Default: Treat as potentially taxable if we're not sure
        return array_merge($baseInfo, [
            'is_taxable' => true,
            'tax_type' => 'REVIEW_REQUIRED',
            'explanation' => 'This transaction type requires review to determine tax status.',
            'note' => 'Consult with a tax professional to classify this transaction correctly.'
        ]);
    }

    /**
     * Update non-taxable category counts
     */
    private function updateNonTaxableCategory(string $reason): void
    {
        switch ($reason) {
            case 'BUY_WITH_ZAR':
                $this->summary['buy_with_zar']++;
                break;
            case 'INTERNAL_TRANSFER':
                $this->summary['internal_transfers']++;
                break;
        }
    }

    /**
     * Update taxable category counts
     */
    private function updateTaxableCategory(string $taxType): void
    {
        // Extract the transaction type from the last processed taxable event
        if (!empty($this->taxableEvents)) {
            $lastEvent = end($this->taxableEvents);
            $type = $lastEvent['type'];
            
            if ($type === 'SELL') {
                $this->summary['sells']++;
            } elseif ($type === 'TRADE') {
                $this->summary['trades']++;
            } else {
                $this->summary['other_taxable']++;
            }
        }
    }

    /**
     * Reset summary statistics
     */
    private function resetSummary(): void
    {
        $this->summary = [
            'total_transactions' => 0,
            'non_taxable_count' => 0,
            'taxable_count' => 0,
            'buy_with_zar' => 0,
            'internal_transfers' => 0,
            'sells' => 0,
            'trades' => 0,
            'other_taxable' => 0
        ];
    }

    /**
     * Generate a text report of taxable vs non-taxable events
     * 
     * @return string
     */
    public function generateReport(): string
    {
        $report = "=== TAX STATUS REPORT ===\n\n";
        
        $report .= "SUMMARY:\n";
        $report .= "Total Transactions: {$this->summary['total_transactions']}\n";
        $report .= "Non-Taxable Events: {$this->summary['non_taxable_count']}\n";
        $report .= "Taxable Events: {$this->summary['taxable_count']}\n\n";

        $report .= "NON-TAXABLE BREAKDOWN:\n";
        $report .= "- Buying with ZAR: {$this->summary['buy_with_zar']}\n";
        $report .= "- Internal Transfers: {$this->summary['internal_transfers']}\n\n";

        $report .= "TAXABLE BREAKDOWN:\n";
        $report .= "- Sells (to ZAR): {$this->summary['sells']}\n";
        $report .= "- Trades (crypto-to-crypto): {$this->summary['trades']}\n";
        $report .= "- Other Taxable: {$this->summary['other_taxable']}\n\n";

        if ($this->summary['taxable_count'] > 0) {
            $report .= "⚠️  TAX OBLIGATION: You have {$this->summary['taxable_count']} taxable event(s) that must be reported to SARS.\n";
        } else {
            $report .= "✅  NO TAX OBLIGATION: All transactions are non-taxable events (buying and holding).\n";
        }

        return $report;
    }

    /**
     * Get non-taxable events
     */
    public function getNonTaxableEvents(): array
    {
        return $this->nonTaxableEvents;
    }

    /**
     * Get taxable events
     */
    public function getTaxableEvents(): array
    {
        return $this->taxableEvents;
    }

    /**
     * Get summary
     */
    public function getSummary(): array
    {
        return $this->summary;
    }
}
