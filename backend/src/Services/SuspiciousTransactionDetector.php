<?php

namespace CryptoTax\Services;

use CryptoTax\Models\Transaction;
use DateTime;

/**
 * Suspicious Transaction Detector
 * 
 * A rule-based risk engine designed to identify incorrect, inconsistent, 
 * or audit-sensitive cryptocurrency transactions within uploaded user files.
 * 
 * Detection Categories:
 * - CRITICAL: Severe issues that prevent accurate tax calculation
 * - HIGH: Likely errors or SARS audit triggers
 * - MEDIUM: Suspicious patterns requiring review
 * - LOW: Minor inconsistencies or informational flags
 */
class SuspiciousTransactionDetector
{
    // Risk severity levels
    const SEVERITY_CRITICAL = 'CRITICAL';
    const SEVERITY_HIGH = 'HIGH';
    const SEVERITY_MEDIUM = 'MEDIUM';
    const SEVERITY_LOW = 'LOW';

    // Large transaction threshold (in ZAR)
    const LARGE_TRANSACTION_THRESHOLD = 1000000; // R1,000,000

    // Wash trading time window (seconds)
    const WASH_TRADE_WINDOW = 86400; // 24 hours

    /**
     * @var array Detected red flags
     */
    private array $redFlags = [];

    /**
     * @var array Summary statistics
     */
    private array $summary = [
        'total_flags' => 0,
        'critical_count' => 0,
        'high_count' => 0,
        'medium_count' => 0,
        'low_count' => 0,
        'audit_risk_score' => 0 // 0-100 scale
    ];

    /**
     * Analyze transactions and detect suspicious patterns
     * 
     * @param Transaction[] $transactions Array of transactions
     * @param array $balances Current balance state (optional)
     * @return array Detection results with flags and summary
     */
    public function analyzeTransactions(array $transactions, array $balances = []): array
    {
        $this->redFlags = [];
        $this->resetSummary();

        // Run detection rules
        $this->detectMissingData($transactions);
        $this->detectInvalidAmounts($transactions);
        $this->detectDuplicateTransactions($transactions);
        $this->detectLargeTransactions($transactions);
        $this->detectWashTrading($transactions);
        $this->detectNegativeBalances($transactions, $balances);

        // Calculate audit risk score
        $this->calculateAuditRiskScore();

        return [
            'red_flags' => $this->redFlags,
            'summary' => $this->summary,
            'has_critical_issues' => $this->summary['critical_count'] > 0,
            'audit_risk_level' => $this->getAuditRiskLevel()
        ];
    }

    /**
     * Detect transactions with missing or invalid data
     */
    private function detectMissingData(array $transactions): void
    {
        foreach ($transactions as $index => $transaction) {
            $issues = [];

            // Check for missing date
            if (!$transaction->getDate()) {
                $issues[] = 'missing date';
            }

            // Check for invalid currencies
            if (empty($transaction->getFromCurrency()) || $transaction->getFromCurrency() === 'UNKNOWN') {
                $issues[] = 'missing or invalid source currency';
            }
            if (empty($transaction->getToCurrency()) || $transaction->getToCurrency() === 'UNKNOWN') {
                $issues[] = 'missing or invalid destination currency';
            }

            // Check for zero amounts
            if ($transaction->getFromAmount() == 0 && $transaction->getType() !== 'TRANSFER') {
                $issues[] = 'zero source amount';
            }
            if ($transaction->getToAmount() == 0 && $transaction->getType() !== 'TRANSFER') {
                $issues[] = 'zero destination amount';
            }

            // Check for invalid price
            if ($transaction->getPrice() == 0 && $transaction->getType() !== 'TRANSFER') {
                $issues[] = 'zero or missing price';
            }

            if (!empty($issues)) {
                $this->addRedFlag(
                    self::SEVERITY_CRITICAL,
                    'INCOMPLETE_DATA',
                    'Transaction has incomplete or invalid data: ' . implode(', ', $issues),
                    $transaction,
                    $index
                );
            }
        }
    }

    /**
     * Detect negative buy amounts or invalid values
     */
    private function detectInvalidAmounts(array $transactions): void
    {
        foreach ($transactions as $index => $transaction) {
            // Negative amounts
            if ($transaction->getFromAmount() < 0) {
                $this->addRedFlag(
                    self::SEVERITY_CRITICAL,
                    'NEGATIVE_AMOUNT',
                    'Transaction has negative source amount: ' . $transaction->getFromAmount(),
                    $transaction,
                    $index
                );
            }

            if ($transaction->getToAmount() < 0) {
                $this->addRedFlag(
                    self::SEVERITY_CRITICAL,
                    'NEGATIVE_AMOUNT',
                    'Transaction has negative destination amount: ' . $transaction->getToAmount(),
                    $transaction,
                    $index
                );
            }

            // Negative price
            if ($transaction->getPrice() < 0) {
                $this->addRedFlag(
                    self::SEVERITY_CRITICAL,
                    'NEGATIVE_PRICE',
                    'Transaction has negative price: ' . $transaction->getPrice(),
                    $transaction,
                    $index
                );
            }

            // Unusually high fees (more than 50% of transaction value)
            $transactionValue = $transaction->getToAmount() * $transaction->getPrice();
            if ($transaction->getFee() > ($transactionValue * 0.5) && $transactionValue > 0) {
                $this->addRedFlag(
                    self::SEVERITY_MEDIUM,
                    'EXCESSIVE_FEE',
                    sprintf('Transaction fee (R%.2f) exceeds 50%% of transaction value (R%.2f)', 
                        $transaction->getFee(), $transactionValue),
                    $transaction,
                    $index
                );
            }
        }
    }

    /**
     * Detect duplicate transactions
     */
    private function detectDuplicateTransactions(array $transactions): void
    {
        $seen = [];
        
        foreach ($transactions as $index => $transaction) {
            // Create a signature for the transaction
            $signature = sprintf(
                '%s|%s|%s|%.8f|%s|%.8f|%.2f',
                $transaction->getDate()->format('Y-m-d H:i:s'),
                $transaction->getType(),
                $transaction->getFromCurrency(),
                $transaction->getFromAmount(),
                $transaction->getToCurrency(),
                $transaction->getToAmount(),
                $transaction->getPrice()
            );

            if (isset($seen[$signature])) {
                $this->addRedFlag(
                    self::SEVERITY_HIGH,
                    'DUPLICATE_TRANSACTION',
                    'Potential duplicate transaction detected. Original at line ' . ($seen[$signature] + 1),
                    $transaction,
                    $index,
                    ['duplicate_of_line' => $seen[$signature] + 1]
                );
            } else {
                $seen[$signature] = $index;
            }
        }
    }

    /**
     * Detect large transactions that may trigger SARS attention
     */
    private function detectLargeTransactions(array $transactions): void
    {
        foreach ($transactions as $index => $transaction) {
            $valueZAR = 0;

            if ($transaction->getType() === 'BUY') {
                $valueZAR = $transaction->getToAmount() * $transaction->getPrice();
            } elseif ($transaction->getType() === 'SELL') {
                $valueZAR = $transaction->getFromAmount() * $transaction->getPrice();
            } elseif ($transaction->getType() === 'TRADE') {
                // For TRADE: price represents the "to" currency's ZAR value
                // Use the "to" side calculation to get the ZAR value
                $valueZAR = $transaction->getToAmount() * $transaction->getPrice();
            }

            if ($valueZAR >= self::LARGE_TRANSACTION_THRESHOLD) {
                $this->addRedFlag(
                    self::SEVERITY_HIGH,
                    'LARGE_TRANSACTION',
                    sprintf('Large transaction detected: R%.2f (threshold: R%.2f)', 
                        $valueZAR, self::LARGE_TRANSACTION_THRESHOLD),
                    $transaction,
                    $index,
                    ['value_zar' => $valueZAR]
                );
            }
        }
    }

    /**
     * Detect wash trading patterns (same-day buy and sell)
     */
    private function detectWashTrading(array $transactions): void
    {
        $buysByAsset = [];
        $sellsByAsset = [];

        // Group buys and sells by asset and date
        foreach ($transactions as $index => $transaction) {
            $date = $transaction->getDate()->format('Y-m-d');
            
            if ($transaction->getType() === 'BUY') {
                $asset = $transaction->getToCurrency();
                if (!isset($buysByAsset[$asset])) {
                    $buysByAsset[$asset] = [];
                }
                if (!isset($buysByAsset[$asset][$date])) {
                    $buysByAsset[$asset][$date] = [];
                }
                $buysByAsset[$asset][$date][] = ['transaction' => $transaction, 'index' => $index];
            } elseif ($transaction->getType() === 'SELL') {
                $asset = $transaction->getFromCurrency();
                if (!isset($sellsByAsset[$asset])) {
                    $sellsByAsset[$asset] = [];
                }
                if (!isset($sellsByAsset[$asset][$date])) {
                    $sellsByAsset[$asset][$date] = [];
                }
                $sellsByAsset[$asset][$date][] = ['transaction' => $transaction, 'index' => $index];
            }
        }

        // Check for same-day buy and sell
        foreach ($buysByAsset as $asset => $dateGroups) {
            foreach ($dateGroups as $date => $buys) {
                if (isset($sellsByAsset[$asset][$date])) {
                    $sells = $sellsByAsset[$asset][$date];
                    
                    // Flag potential wash trading
                    foreach ($buys as $buy) {
                        foreach ($sells as $sell) {
                            $timeDiff = abs($buy['transaction']->getDate()->getTimestamp() - 
                                          $sell['transaction']->getDate()->getTimestamp());
                            
                            if ($timeDiff <= self::WASH_TRADE_WINDOW) {
                                $this->addRedFlag(
                                    self::SEVERITY_MEDIUM,
                                    'WASH_TRADING',
                                    sprintf('Potential wash trading detected for %s: buy and sell within %d hours',
                                        $asset, self::WASH_TRADE_WINDOW / 3600),
                                    $buy['transaction'],
                                    $buy['index'],
                                    [
                                        'related_sell_line' => $sell['index'] + 1,
                                        'time_difference_hours' => round($timeDiff / 3600, 2)
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Detect transactions that cause negative balances
     * This should be called after FIFO processing
     */
    private function detectNegativeBalances(array $transactions, array $balances): void
    {
        // Simulate balance tracking if not provided
        if (empty($balances)) {
            $simulatedBalances = [];

            foreach ($transactions as $index => $transaction) {
                $fromCurrency = $transaction->getFromCurrency();
                $toCurrency = $transaction->getToCurrency();

                // Initialize balances
                if (!isset($simulatedBalances[$fromCurrency])) {
                    $simulatedBalances[$fromCurrency] = 0;
                }
                if (!isset($simulatedBalances[$toCurrency])) {
                    $simulatedBalances[$toCurrency] = 0;
                }

                // Update balances based on transaction type
                if ($transaction->getType() === 'BUY') {
                    $simulatedBalances[$fromCurrency] -= $transaction->getFromAmount(); // Subtract ZAR
                    $simulatedBalances[$toCurrency] += $transaction->getToAmount(); // Add crypto
                } elseif ($transaction->getType() === 'SELL') {
                    $simulatedBalances[$fromCurrency] -= $transaction->getFromAmount(); // Subtract crypto
                    $simulatedBalances[$toCurrency] += $transaction->getToAmount(); // Add ZAR
                } elseif ($transaction->getType() === 'TRADE') {
                    $simulatedBalances[$fromCurrency] -= $transaction->getFromAmount();
                    $simulatedBalances[$toCurrency] += $transaction->getToAmount();
                }

                // Check for negative balance (excluding ZAR)
                if ($fromCurrency !== 'ZAR' && $simulatedBalances[$fromCurrency] < -0.00000001) {
                    $this->addRedFlag(
                        self::SEVERITY_CRITICAL,
                        'NEGATIVE_BALANCE',
                        sprintf('Transaction causes negative balance for %s: %.8f',
                            $fromCurrency, $simulatedBalances[$fromCurrency]),
                        $transaction,
                        $index,
                        ['balance' => $simulatedBalances[$fromCurrency]]
                    );
                }
            }
        }
    }

    /**
     * Add a red flag to the detection results
     */
    private function addRedFlag(
        string $severity, 
        string $code, 
        string $message, 
        Transaction $transaction, 
        int $index,
        array $metadata = []
    ): void {
        $this->redFlags[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'transaction_index' => $index,
            'line_number' => $transaction->getOriginalLineNumber(),
            'transaction' => [
                'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
                'type' => $transaction->getType(),
                'from' => $transaction->getFromCurrency() . ' ' . $transaction->getFromAmount(),
                'to' => $transaction->getToCurrency() . ' ' . $transaction->getToAmount(),
                'price' => $transaction->getPrice()
            ],
            'metadata' => $metadata,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Update summary counts
        $this->summary['total_flags']++;
        switch ($severity) {
            case self::SEVERITY_CRITICAL:
                $this->summary['critical_count']++;
                break;
            case self::SEVERITY_HIGH:
                $this->summary['high_count']++;
                break;
            case self::SEVERITY_MEDIUM:
                $this->summary['medium_count']++;
                break;
            case self::SEVERITY_LOW:
                $this->summary['low_count']++;
                break;
        }
    }

    /**
     * Calculate overall audit risk score (0-100)
     */
    private function calculateAuditRiskScore(): void
    {
        $score = 0;

        // Weight by severity
        $score += $this->summary['critical_count'] * 25;  // Critical: 25 points each
        $score += $this->summary['high_count'] * 15;      // High: 15 points each
        $score += $this->summary['medium_count'] * 7;     // Medium: 7 points each
        $score += $this->summary['low_count'] * 2;        // Low: 2 points each

        // Cap at 100
        $this->summary['audit_risk_score'] = min($score, 100);
    }

    /**
     * Get audit risk level description
     */
    private function getAuditRiskLevel(): string
    {
        $score = $this->summary['audit_risk_score'];

        if ($score >= 75) {
            return 'VERY HIGH - Immediate attention required';
        } elseif ($score >= 50) {
            return 'HIGH - Review and corrections recommended';
        } elseif ($score >= 25) {
            return 'MEDIUM - Some issues detected';
        } elseif ($score > 0) {
            return 'LOW - Minor issues detected';
        } else {
            return 'MINIMAL - No significant issues detected';
        }
    }

    /**
     * Reset summary statistics
     */
    private function resetSummary(): void
    {
        $this->summary = [
            'total_flags' => 0,
            'critical_count' => 0,
            'high_count' => 0,
            'medium_count' => 0,
            'low_count' => 0,
            'audit_risk_score' => 0
        ];
    }

    /**
     * Get red flags filtered by severity
     */
    public function getRedFlagsBySeverity(string $severity): array
    {
        return array_filter($this->redFlags, function($flag) use ($severity) {
            return $flag['severity'] === $severity;
        });
    }

    /**
     * Export red flags report as formatted text
     */
    public function exportReport(): string
    {
        $report = "=== SUSPICIOUS TRANSACTION DETECTION REPORT ===\n\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $report .= "SUMMARY:\n";
        $report .= "--------\n";
        $report .= sprintf("Total Flags: %d\n", $this->summary['total_flags']);
        $report .= sprintf("  - Critical: %d\n", $this->summary['critical_count']);
        $report .= sprintf("  - High: %d\n", $this->summary['high_count']);
        $report .= sprintf("  - Medium: %d\n", $this->summary['medium_count']);
        $report .= sprintf("  - Low: %d\n", $this->summary['low_count']);
        $report .= sprintf("Audit Risk Score: %d/100\n", $this->summary['audit_risk_score']);
        $report .= sprintf("Risk Level: %s\n\n", $this->getAuditRiskLevel());

        if (empty($this->redFlags)) {
            $report .= "No suspicious transactions detected.\n";
            return $report;
        }

        $report .= "FLAGGED TRANSACTIONS:\n";
        $report .= "--------------------\n";

        foreach ($this->redFlags as $i => $flag) {
            $report .= sprintf("\n[%d] %s - %s\n", $i + 1, $flag['severity'], $flag['code']);
            $report .= sprintf("    Line: %d\n", $flag['line_number']);
            $report .= sprintf("    Message: %s\n", $flag['message']);
            $report .= sprintf("    Transaction: %s %s -> %s @ R%.2f\n",
                $flag['transaction']['type'],
                $flag['transaction']['from'],
                $flag['transaction']['to'],
                $flag['transaction']['price']
            );
        }

        return $report;
    }
}
