<?php

namespace CryptoTax\Models;

use DateTime;

/**
 * Transaction Model
 * Represents a normalized cryptocurrency transaction
 */
class Transaction
{
    private DateTime $date;
    private string $type; // BUY, SELL, TRADE
    private string $fromCurrency;
    private float $fromAmount;
    private string $toCurrency;
    private float $toAmount;
    private float $price; // Price per unit in ZAR
    private float $fee;
    private ?string $wallet;
    private int $originalLineNumber;

    public function __construct(
        DateTime $date,
        string $type,
        string $fromCurrency,
        float $fromAmount,
        string $toCurrency,
        float $toAmount,
        float $price,
        float $fee = 0.0,
        ?string $wallet = null,
        int $originalLineNumber = 0
    ) {
        $this->date = $date;
        $this->type = strtoupper($type);
        $this->fromCurrency = strtoupper($fromCurrency);
        $this->fromAmount = $fromAmount;
        $this->toCurrency = strtoupper($toCurrency);
        $this->toAmount = $toAmount;
        $this->price = $price;
        $this->fee = $fee;
        $this->wallet = $wallet;
        $this->originalLineNumber = $originalLineNumber;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFromCurrency(): string
    {
        return $this->fromCurrency;
    }

    public function getFromAmount(): float
    {
        return $this->fromAmount;
    }

    public function getToCurrency(): string
    {
        return $this->toCurrency;
    }

    public function getToAmount(): float
    {
        return $this->toAmount;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getFee(): float
    {
        return $this->fee;
    }

    public function getWallet(): ?string
    {
        return $this->wallet;
    }

    public function getOriginalLineNumber(): int
    {
        return $this->originalLineNumber;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date->format('Y-m-d H:i:s'),
            'type' => $this->type,
            'fromCurrency' => $this->fromCurrency,
            'fromAmount' => $this->fromAmount,
            'toCurrency' => $this->toCurrency,
            'toAmount' => $this->toAmount,
            'price' => $this->price,
            'fee' => $this->fee,
            'wallet' => $this->wallet,
            'originalLineNumber' => $this->originalLineNumber
        ];
    }
}
