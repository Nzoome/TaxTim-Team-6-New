<?php

namespace CryptoTax\Models;

use DateTime;

/**
 * BalanceLot - Represents a single FIFO lot (parcel) of cryptocurrency
 * 
 * Each lot tracks:
 * - The amount of coins acquired
 * - The cost per unit (in ZAR) at acquisition
 * - The date of acquisition
 * - The original transaction reference
 * 
 * FIFO Logic:
 * - Lots are consumed in chronological order (earliest first)
 * - Lots can be partially consumed (remaining amount tracked)
 * - Once fully consumed, lots are removed from the queue
 */
class BalanceLot
{
    private float $amount;              // Remaining amount in this lot
    private float $costPerUnit;         // Cost per unit in ZAR (including fees)
    private DateTime $acquisitionDate;  // Date this lot was acquired
    private string $currency;           // Cryptocurrency symbol (e.g., BTC, ETH)
    private ?string $wallet;            // Wallet identifier (optional)
    private int $transactionLineNumber; // Reference to original transaction

    public function __construct(
        float $amount,
        float $costPerUnit,
        DateTime $acquisitionDate,
        string $currency,
        ?string $wallet = null,
        int $transactionLineNumber = 0
    ) {
        $this->amount = $amount;
        $this->costPerUnit = $costPerUnit;
        $this->acquisitionDate = $acquisitionDate;
        $this->currency = strtoupper($currency);
        $this->wallet = $wallet;
        $this->transactionLineNumber = $transactionLineNumber;
    }

    /**
     * Get the remaining amount in this lot
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Get the cost per unit (ZAR) for this lot
     */
    public function getCostPerUnit(): float
    {
        return $this->costPerUnit;
    }

    /**
     * Get the total cost base for the remaining amount in this lot
     */
    public function getTotalCostBase(): float
    {
        return $this->amount * $this->costPerUnit;
    }

    /**
     * Get the acquisition date for this lot
     */
    public function getAcquisitionDate(): DateTime
    {
        return $this->acquisitionDate;
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
     * Get the original transaction line number
     */
    public function getTransactionLineNumber(): int
    {
        return $this->transactionLineNumber;
    }

    /**
     * Consume (reduce) the amount in this lot
     * Returns the amount actually consumed
     * 
     * @param float $amountToConsume Amount to consume from this lot
     * @return float Amount actually consumed
     */
    public function consume(float $amountToConsume): float
    {
        $consumed = min($amountToConsume, $this->amount);
        $this->amount -= $consumed;
        return $consumed;
    }

    /**
     * Check if this lot is fully consumed
     */
    public function isFullyConsumed(): bool
    {
        return $this->amount <= 0.00000001; // Use small epsilon for float comparison
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'costPerUnit' => $this->costPerUnit,
            'totalCostBase' => $this->getTotalCostBase(),
            'acquisitionDate' => $this->acquisitionDate->format('Y-m-d H:i:s'),
            'currency' => $this->currency,
            'wallet' => $this->wallet,
            'transactionLineNumber' => $this->transactionLineNumber
        ];
    }
}
