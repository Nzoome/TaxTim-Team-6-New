<?php

namespace CryptoTax\Services;

use CryptoTax\Models\Transaction;
use CryptoTax\Models\BalanceLot;
use CryptoTax\Models\CoinBalance;
use CryptoTax\Services\TaxYearResolver;
use DateTime;

/**
 * FIFOEngine - Processes transactions and calculates capital gains using FIFO method
 * 
 * CANONICAL DATA FLOW (Sprint 2):
 * 1. Read ordered Transaction objects (from Sprint 1)
 * 2. Maintain FIFO queues per coin (and wallet)
 * 3. Consume lots on SELL / TRADE
 * 4. Calculate cost base and proceeds
 * 5. Produce per-transaction breakdown
 * 
 * This order is MANDATORY and must never change.
 * 
 * Transaction Processing:
 * - BUY: Creates new FIFO lot and adds to queue
 * - SELL: Consumes FIFO lots and calculates gain/loss
 * - TRADE: Internally splits into SELL + BUY operations
 * 
 * No Tax Year Logic: Sprint 2 does not implement tax year allocation or CGT exclusions.
 * That comes in Sprint 3+.
 */
class FIFOEngine
{
    /**
     * @var array<string, CoinBalance> Map of currency => CoinBalance
     */
    private array $balances;

    /**
     * @var array Transaction breakdown results
     */
    private array $transactionBreakdowns;

    /**
     * @var array Summary statistics
     */
    private array $summary;
    /**
     * @var TaxYearResolver
     */
    private TaxYearResolver $taxYearResolver;

    /**
     * @var array<string, array> Snapshots of balances at tax-year boundaries keyed by tax-year label
     */
    private array $taxYearSnapshots;

    public function __construct()
    {
        $this->balances = [];
        $this->transactionBreakdowns = [];
        $this->summary = [
            'totalProceeds' => 0.0,
            'totalCostBase' => 0.0,
            'totalCapitalGain' => 0.0,
            'totalCapitalLoss' => 0.0,
            'netCapitalGain' => 0.0,
            'transactionsProcessed' => 0,
            'buys' => 0,
            'sells' => 0,
            'trades' => 0
        ];
        $this->taxYearResolver = new TaxYearResolver();
        $this->taxYearSnapshots = [];
    }

    /**
     * Process an array of ordered transactions
     * 
     * @param Transaction[] $transactions Array of transactions (must be chronologically sorted)
     * @return array Processing results with breakdowns and summary
     */
    public function processTransactions(array $transactions, array $options = []): array
    {
        $snapshotBoundaries = $options['snapshotTaxYearBoundaries'] ?? true;

        $currentTaxYearStart = null;
        foreach ($transactions as $transaction) {
            // Determine tax year for this transaction
            $txTaxYearStart = $this->taxYearResolver->getTaxYearStartYear($transaction->getDate());

            if ($currentTaxYearStart === null) {
                $currentTaxYearStart = $txTaxYearStart;
            }

            // If we cross a tax-year boundary, snapshot current balances at end of previous tax year
            if ($snapshotBoundaries && $txTaxYearStart !== $currentTaxYearStart) {
                $label = sprintf('%04d/%04d', $currentTaxYearStart, $currentTaxYearStart + 1);
                $this->taxYearSnapshots[$label] = $this->getBalancesArray();
                $currentTaxYearStart = $txTaxYearStart;
            }

            $this->processTransaction($transaction);
        }

        // After processing all transactions snapshot the final tax year end state as well
        if ($snapshotBoundaries && $currentTaxYearStart !== null) {
            $label = sprintf('%04d/%04d', $currentTaxYearStart, $currentTaxYearStart + 1);
            $this->taxYearSnapshots[$label] = $this->getBalancesArray();
        }

        return [
            'breakdowns' => $this->transactionBreakdowns,
            'summary' => $this->summary,
            'balances' => $this->getBalancesArray()
        ];
    }

    /**
     * Process a single transaction
     * 
     * This is the main dispatcher that routes to type-specific handlers:
     * - BUY => handleBuy()
     * - SELL => handleSell()
     * - TRADE => handleTrade() [internally: SELL + BUY]
     * 
     * @param Transaction $transaction
     */
    private function processTransaction(Transaction $transaction): void
    {
        $type = $transaction->getType();

        switch ($type) {
            case 'BUY':
                $this->handleBuy($transaction);
                $this->summary['buys']++;
                break;

            case 'SELL':
                $this->handleSell($transaction);
                $this->summary['sells']++;
                break;

            case 'TRADE':
                $this->handleTrade($transaction);
                $this->summary['trades']++;
                break;

            default:
                throw new \InvalidArgumentException("Unknown transaction type: {$type}");
        }

        $this->summary['transactionsProcessed']++;
    }

    /**
     * Handle BUY transaction
     * 
     * BUY Logic:
     * 1. Create a new BalanceLot with the acquired amount and cost per unit
     * 2. Include fees in the cost base (fees increase cost per unit)
     * 3. Add the lot to the appropriate CoinBalance FIFO queue
     * 
     * @param Transaction $transaction
     */
    private function handleBuy(Transaction $transaction): void
    {
        $currency = $transaction->getToCurrency();
        $amount = $transaction->getToAmount();
        $totalCost = $transaction->getPrice() * $amount + $transaction->getFee();
        $costPerUnit = $totalCost / $amount;

        // Get or create coin balance
        $balance = $this->getOrCreateBalance($currency, $transaction->getWallet());

        // Create new lot
        $lot = new BalanceLot(
            $amount,
            $costPerUnit,
            $transaction->getDate(),
            $currency,
            $transaction->getWallet(),
            $transaction->getOriginalLineNumber()
        );

        // Add lot to FIFO queue
        $balance->addLot($lot);

        // Record breakdown
        $this->transactionBreakdowns[] = [
            'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
            'type' => 'BUY',
            'currency' => $currency,
            'amount' => $amount,
            'totalCost' => $totalCost,
            'costPerUnit' => $costPerUnit,
            'fee' => $transaction->getFee(),
            'wallet' => $transaction->getWallet(),
            'lineNumber' => $transaction->getOriginalLineNumber(),
            'proceeds' => null,
            'costBase' => null,
            'capitalGain' => null,
            'lotsConsumed' => null
        ];
    }

    /**
     * Handle SELL transaction
     * 
     * SELL Logic (FIFO Consumption):
     * 1. Consume lots from the front of the FIFO queue (earliest first)
     * 2. Support partial lot consumption if a lot is bigger than needed
     * 3. Calculate proceeds from the sale
     * 4. Calculate cost base from consumed lots
     * 5. Calculate capital gain/loss (proceeds - cost base)
     * 6. Update summary statistics
     * 
     * @param Transaction $transaction
     */
    private function handleSell(Transaction $transaction): void
    {
        $currency = $transaction->getFromCurrency();
        $amount = $transaction->getFromAmount();
        $proceeds = $transaction->getPrice() * $amount - $transaction->getFee();

        // Get balance
        $balance = $this->getBalance($currency, $transaction->getWallet());
        if (!$balance) {
            throw new \RuntimeException(
                "Cannot sell {$amount} {$currency}: no balance found for wallet '{$transaction->getWallet()}'"
            );
        }

        // Consume FIFO lots
        $consumptionRecords = $balance->consumeLots($amount);

        // Calculate cost base from consumed lots
        $costBase = 0.0;
        foreach ($consumptionRecords as $record) {
            $costBase += $record['costBase'];
        }

        // Calculate capital gain/loss
        $capitalGain = $proceeds - $costBase;

        // Update summary
        $this->summary['totalProceeds'] += $proceeds;
        $this->summary['totalCostBase'] += $costBase;
        if ($capitalGain >= 0) {
            $this->summary['totalCapitalGain'] += $capitalGain;
        } else {
            $this->summary['totalCapitalLoss'] += abs($capitalGain);
        }
        $this->summary['netCapitalGain'] += $capitalGain;

        $taxYearLabel = $this->taxYearResolver->resolveTaxYearLabel($transaction->getDate());

        // Record breakdown
        $this->transactionBreakdowns[] = [
            'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
            'type' => 'SELL',
            'currency' => $currency,
            'amount' => $amount,
            'proceeds' => $proceeds,
            'costBase' => $costBase,
            'capitalGain' => $capitalGain,
            'fee' => $transaction->getFee(),
            'wallet' => $transaction->getWallet(),
            'lineNumber' => $transaction->getOriginalLineNumber(),
            'lotsConsumed' => $this->formatConsumptionRecords($consumptionRecords, $transaction->getDate()),
            'taxYear' => $taxYearLabel
        ];
    }

    /**
     * Handle TRADE transaction
     * 
     * TRADE Logic:
     * A TRADE is internally split into two operations:
     * 1. SELL: Dispose of the "from" currency (calculate gain/loss)
     * 2. BUY: Acquire the "to" currency (create new lot)
     * 
     * The SELL and BUY happen at the same timestamp and are linked in the breakdown.
     * 
     * @param Transaction $transaction
     */
    private function handleTrade(Transaction $transaction): void
    {
        // Step 1: SELL the "from" currency
        $fromCurrency = $transaction->getFromCurrency();
        $fromAmount = $transaction->getFromAmount();
        
        // For TRADE, proceeds = value of what we're getting
        $proceeds = $transaction->getPrice() * $transaction->getToAmount();
        
        // Get balance and consume lots
        $balance = $this->getBalance($fromCurrency, $transaction->getWallet());
        if (!$balance) {
            throw new \RuntimeException(
                "Cannot trade {$fromAmount} {$fromCurrency}: no balance found"
            );
        }

        $consumptionRecords = $balance->consumeLots($fromAmount);

        // Calculate cost base from consumed lots
        $costBase = 0.0;
        foreach ($consumptionRecords as $record) {
            $costBase += $record['costBase'];
        }

        // Calculate capital gain/loss on the SELL portion
        $capitalGain = $proceeds - $costBase;

        // Update summary for SELL portion
        $this->summary['totalProceeds'] += $proceeds;
        $this->summary['totalCostBase'] += $costBase;
        if ($capitalGain >= 0) {
            $this->summary['totalCapitalGain'] += $capitalGain;
        } else {
            $this->summary['totalCapitalLoss'] += abs($capitalGain);
        }
        $this->summary['netCapitalGain'] += $capitalGain;

        // Step 2: BUY the "to" currency
        $toCurrency = $transaction->getToCurrency();
        $toAmount = $transaction->getToAmount();
        
        // Cost for BUY = proceeds from SELL + fees
        $totalCost = $proceeds + $transaction->getFee();
        $costPerUnit = $totalCost / $toAmount;

        // Get or create balance for "to" currency
        $toBalance = $this->getOrCreateBalance($toCurrency, $transaction->getWallet());

        // Create new lot
        $lot = new BalanceLot(
            $toAmount,
            $costPerUnit,
            $transaction->getDate(),
            $toCurrency,
            $transaction->getWallet(),
            $transaction->getOriginalLineNumber()
        );

        // Add lot to FIFO queue
        $toBalance->addLot($lot);

        $taxYearLabel = $this->taxYearResolver->resolveTaxYearLabel($transaction->getDate());

        // Record breakdown (combined SELL + BUY)
        $this->transactionBreakdowns[] = [
            'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
            'type' => 'TRADE',
            'fromCurrency' => $fromCurrency,
            'fromAmount' => $fromAmount,
            'toCurrency' => $toCurrency,
            'toAmount' => $toAmount,
            'proceeds' => $proceeds,
            'costBase' => $costBase,
            'capitalGain' => $capitalGain,
            'newLotCostPerUnit' => $costPerUnit,
            'fee' => $transaction->getFee(),
            'wallet' => $transaction->getWallet(),
            'lineNumber' => $transaction->getOriginalLineNumber(),
            'lotsConsumed' => $this->formatConsumptionRecords($consumptionRecords, $transaction->getDate()),
            'taxYear' => $taxYearLabel
        ];
    }

    /**
     * Get or create a CoinBalance for a specific currency and wallet
     * 
     * @param string $currency
     * @param string|null $wallet
     * @return CoinBalance
     */
    private function getOrCreateBalance(string $currency, ?string $wallet): CoinBalance
    {
        $key = $this->getBalanceKey($currency, $wallet);
        
        if (!isset($this->balances[$key])) {
            $this->balances[$key] = new CoinBalance($currency, $wallet);
        }

        return $this->balances[$key];
    }

    /**
     * Get a CoinBalance for a specific currency and wallet
     * 
     * @param string $currency
     * @param string|null $wallet
     * @return CoinBalance|null
     */
    private function getBalance(string $currency, ?string $wallet): ?CoinBalance
    {
        $key = $this->getBalanceKey($currency, $wallet);
        return $this->balances[$key] ?? null;
    }

    /**
     * Generate a unique key for a currency/wallet combination
     * 
     * @param string $currency
     * @param string|null $wallet
     * @return string
     */
    private function getBalanceKey(string $currency, ?string $wallet): string
    {
        return strtoupper($currency) . '|' . ($wallet ?? 'DEFAULT');
    }

    /**
     * Get all balances as an array
     * 
     * @return array
     */
    private function getBalancesArray(): array
    {
        $result = [];
        foreach ($this->balances as $key => $balance) {
            $result[] = $balance->toArray();
        }
        return $result;
    }

    /**
     * Format consumption records for breakdown output
     * 
     * @param array $consumptionRecords
     * @return array
     */
    private function formatConsumptionRecords(array $consumptionRecords, ?\DateTime $disposalDate = null): array
    {
        $formatted = [];
        foreach ($consumptionRecords as $record) {
            $formatted[] = [
                'amountConsumed' => $record['amountConsumed'],
                'costBase' => $record['costBase'],
                'costPerUnit' => $record['lot']->getCostPerUnit(),
                'acquisitionDate' => $record['acquisitionDate']->format('Y-m-d H:i:s'),
                'originalTransaction' => $record['transactionLineNumber'],
                'taxYear' => $disposalDate ? $this->taxYearResolver->resolveTaxYearLabel($disposalDate) : null
            ];
        }
        return $formatted;
    }

    /**
     * Get current balances
     * 
     * @return array<string, CoinBalance>
     */
    public function getBalances(): array
    {
        return $this->balances;
    }

    /**
     * Get transaction breakdowns
     * 
     * @return array
     */
    public function getTransactionBreakdowns(): array
    {
        return $this->transactionBreakdowns;
    }

    /**
     * Get snapshots captured at tax-year boundaries
     *
     * @return array<string, array> Label => balances array
     */
    public function getTaxYearSnapshots(): array
    {
        return $this->taxYearSnapshots;
    }

    /**
     * Group disposals (SELL and TRADE disposals) by tax year and coin
     *
     * @return array
     */
    public function allocateDisposalsByTaxYear(): array
    {
        $groups = [];
        foreach ($this->transactionBreakdowns as $bd) {
            if (!in_array($bd['type'], ['SELL', 'TRADE'])) {
                continue;
            }

            $taxYear = $bd['taxYear'] ?? $this->taxYearResolver->resolveTaxYearLabel(new DateTime($bd['date']));

            // Determine coin for this disposal
            $coin = $bd['type'] === 'SELL' ? ($bd['currency'] ?? null) : ($bd['fromCurrency'] ?? null);
            if ($coin === null) {
                continue;
            }

            if (!isset($groups[$taxYear])) {
                $groups[$taxYear] = [];
            }
            if (!isset($groups[$taxYear][$coin])) {
                $groups[$taxYear][$coin] = [];
            }
            $groups[$taxYear][$coin][] = $bd;
        }

        return $groups;
    }

    /**
     * Calculate gross gains per coin per tax year and produce tax-year summaries
     * Applies annual exclusion (configurable) once per tax year and then inclusion rate
     *
     * @param float $annualExclusion Amount in ZAR to exclude per tax year (default 40000)
     * @param float $inclusionRate Fraction to include (default 0.4 = 40%)
     * @return array
     */
    public function calculateGainsPerCoinPerTaxYear(float $annualExclusion = 40000.0, float $inclusionRate = 0.4): array
    {
        $groups = $this->allocateDisposalsByTaxYear();
        $report = [];

        foreach ($groups as $taxYear => $coins) {
            $report[$taxYear] = [
                'coins' => [],
                'netGrossGain' => 0.0,
                'netAfterExclusion' => 0.0,
                'taxableAfterInclusion' => 0.0,
                'annualExclusionApplied' => 0.0
            ];

            // Sum per coin
            foreach ($coins as $coin => $breakdowns) {
                $sum = 0.0;
                foreach ($breakdowns as $bd) {
                    $sum += (float)($bd['capitalGain'] ?? 0.0);
                }

                $report[$taxYear]['coins'][$coin] = [
                    'grossGain' => $sum,
                    'breakdowns' => $breakdowns
                ];

                $report[$taxYear]['netGrossGain'] += $sum;
            }

            // Apply annual exclusion once per tax year
            $net = $report[$taxYear]['netGrossGain'];
            if ($net > 0) {
                $afterExclusion = max(0.0, $net - $annualExclusion);
                $report[$taxYear]['annualExclusionApplied'] = min($annualExclusion, $net);
            } else {
                // For net losses or zero, exclusion doesn't increase losses
                $afterExclusion = $net;
                $report[$taxYear]['annualExclusionApplied'] = 0.0;
            }

            $report[$taxYear]['netAfterExclusion'] = $afterExclusion;

            // Apply inclusion rate
            $report[$taxYear]['taxableAfterInclusion'] = $afterExclusion * $inclusionRate;
        }

        return $report;
    }

    /**
     * Get summary statistics
     * 
     * @return array
     */
    public function getSummary(): array
    {
        return $this->summary;
    }
}
