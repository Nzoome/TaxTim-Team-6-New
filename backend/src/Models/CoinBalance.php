<?php

namespace CryptoTax\Models;

/**
 * CoinBalance - Maintains a FIFO queue of BalanceLots for a specific cryptocurrency
 * 
 * FIFO Queue Behavior:
 * - New acquisitions (BUY) are added to the end of the queue
 * - Disposals (SELL) consume lots from the front of the queue (earliest first)
 * - Partially consumed lots remain in the queue until fully depleted
 * - Empty lots are automatically removed from the queue
 * 
 * Wallet Support:
 * - If wallet tracking is enabled, each wallet maintains separate queues
 * - Transfers between wallets are handled separately (future sprint)
 */
class CoinBalance
{
    private string $currency;      // Cryptocurrency symbol (e.g., BTC, ETH)
    private ?string $wallet;       // Wallet identifier (null = all wallets)
    private array $lots;           // FIFO queue of BalanceLot objects
    private float $totalBalance;   // Cached total balance across all lots

    public function __construct(string $currency, ?string $wallet = null)
    {
        $this->currency = strtoupper($currency);
        $this->wallet = $wallet;
        $this->lots = [];
        $this->totalBalance = 0.0;
    }

    /**
     * Get the cryptocurrency symbol
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Get the wallet identifier
     */
    public function getWallet(): ?string
    {
        return $this->wallet;
    }

    /**
     * Get the total balance across all lots
     */
    public function getTotalBalance(): float
    {
        return $this->totalBalance;
    }

    /**
     * Get all lots in the FIFO queue
     * 
     * @return BalanceLot[]
     */
    public function getLots(): array
    {
        return $this->lots;
    }

    /**
     * Get the count of lots in the queue
     */
    public function getLotCount(): int
    {
        return count($this->lots);
    }

    /**
     * Add a new lot to the end of the FIFO queue (BUY operation)
     * 
     * @param BalanceLot $lot The lot to add
     */
    public function addLot(BalanceLot $lot): void
    {
        // Validate the lot currency matches this balance
        if ($lot->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException(
                "Cannot add {$lot->getCurrency()} lot to {$this->currency} balance"
            );
        }

        // Validate the lot wallet matches this balance (if wallet tracking is used)
        if ($this->wallet !== null && $lot->getWallet() !== $this->wallet) {
            throw new \InvalidArgumentException(
                "Cannot add lot from wallet '{$lot->getWallet()}' to wallet '{$this->wallet}' balance"
            );
        }

        $this->lots[] = $lot;
        $this->totalBalance += $lot->getAmount();
    }

    /**
     * Consume lots from the front of the FIFO queue (SELL operation)
     * 
     * Returns an array of consumption records showing which lots were used
     * Each record contains: [lot, amountConsumed, costBase]
     * 
     * @param float $amountToConsume Amount to consume
     * @return array Array of consumption records
     * @throws \RuntimeException If insufficient balance
     */
    public function consumeLots(float $amountToConsume): array
    {
        // Define epsilon for floating-point comparison (tolerance of 0.00000001)
        $epsilon = 0.00000001;
        
        // Check if we have sufficient balance (with floating-point tolerance)
        if ($amountToConsume > ($this->totalBalance + $epsilon)) {
            throw new \RuntimeException(
                "Insufficient balance: trying to consume {$amountToConsume} {$this->currency}, " .
                "but only {$this->totalBalance} available"
            );
        }

        $consumptionRecords = [];
        $remainingToConsume = $amountToConsume;

        // Consume lots from the front of the queue (FIFO)
        $lotsToRemove = [];
        foreach ($this->lots as $index => $lot) {
            if ($remainingToConsume <= 0.00000001) {
                break; // Done consuming
            }

            // Consume from this lot
            $amountConsumed = $lot->consume($remainingToConsume);
            $costBase = $amountConsumed * $lot->getCostPerUnit();

            // Record the consumption
            $consumptionRecords[] = [
                'lot' => clone $lot, // Clone to preserve state before consumption
                'amountConsumed' => $amountConsumed,
                'costBase' => $costBase,
                'acquisitionDate' => $lot->getAcquisitionDate(),
                'transactionLineNumber' => $lot->getTransactionLineNumber()
            ];

            $remainingToConsume -= $amountConsumed;

            // Mark fully consumed lots for removal
            if ($lot->isFullyConsumed()) {
                $lotsToRemove[] = $index;
            }
        }

        // Remove fully consumed lots (in reverse order to maintain indices)
        foreach (array_reverse($lotsToRemove) as $index) {
            array_splice($this->lots, $index, 1);
        }

        // Update total balance
        $this->totalBalance -= $amountToConsume;

        return $consumptionRecords;
    }

    /**
     * Get the total cost base across all remaining lots
     */
    public function getTotalCostBase(): float
    {
        $totalCostBase = 0.0;
        foreach ($this->lots as $lot) {
            $totalCostBase += $lot->getTotalCostBase();
        }
        return $totalCostBase;
    }

    /**
     * Get the weighted average cost per unit across all lots
     */
    public function getAverageCostPerUnit(): float
    {
        if ($this->totalBalance <= 0) {
            return 0.0;
        }
        return $this->getTotalCostBase() / $this->totalBalance;
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'wallet' => $this->wallet,
            'totalBalance' => $this->totalBalance,
            'totalCostBase' => $this->getTotalCostBase(),
            'averageCostPerUnit' => $this->getAverageCostPerUnit(),
            'lotCount' => $this->getLotCount(),
            'lots' => array_map(fn($lot) => $lot->toArray(), $this->lots)
        ];
    }
}
