<?php

namespace CryptoTax\Validators;

use CryptoTax\Exceptions\ValidationException;
use DateTime;

/**
 * Transaction Validator
 * Validates file structure and transaction data
 */
class TransactionValidator
{
    private array $requiredColumns = [
        'date',
        'type',
        'from_currency',
        'from_amount',
        'to_currency',
        'to_amount',
        'price'
    ];

    private array $validTypes = ['BUY', 'SELL', 'TRADE'];
    private array $errors = [];

    /**
     * Validate raw transaction rows
     * 
     * @param array $rows Raw transaction rows
     * @throws ValidationException if validation fails
     */
    public function validate(array $rows): void
    {
        $this->errors = [];

        if (empty($rows)) {
            $this->addError('general', 'No transactions found in file');
            $this->throwIfErrors();
        }

        // Validate structure of first row to ensure required columns exist
        $this->validateRequiredColumns($rows[0]);
        
        // Validate each row
        foreach ($rows as $index => $row) {
            $this->validateRow($row);
        }

        $this->throwIfErrors();
    }

    /**
     * Validate required columns exist
     */
    private function validateRequiredColumns(array $row): void
    {
        $missingColumns = [];
        
        foreach ($this->requiredColumns as $column) {
            if (!array_key_exists($column, $row)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $this->addError('structure', 
                'Missing required columns: ' . implode(', ', $missingColumns)
            );
        }
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row): void
    {
        $lineNumber = $row['line_number'] ?? 'unknown';

        // Validate date
        if (empty($row['date'])) {
            $this->addError("row_{$lineNumber}", "Date is required");
        } elseif (!$this->isValidDate($row['date'])) {
            $this->addError("row_{$lineNumber}", "Invalid date format: {$row['date']}");
        }

        // Validate type
        if (empty($row['type'])) {
            $this->addError("row_{$lineNumber}", "Transaction type is required");
        } elseif (!in_array(strtoupper($row['type']), $this->validTypes)) {
            $this->addError("row_{$lineNumber}", 
                "Invalid transaction type: {$row['type']}. Must be BUY, SELL, or TRADE"
            );
        }

        // Validate from_currency
        if (empty($row['from_currency'])) {
            $this->addError("row_{$lineNumber}", "From currency is required");
        }

        // Validate from_amount
        if (!isset($row['from_amount']) || $row['from_amount'] === '') {
            $this->addError("row_{$lineNumber}", "From amount is required");
        } elseif (!is_numeric($row['from_amount']) || $row['from_amount'] < 0) {
            $this->addError("row_{$lineNumber}", 
                "From amount must be a positive number: {$row['from_amount']}"
            );
        }

        // Validate to_currency
        if (empty($row['to_currency'])) {
            $this->addError("row_{$lineNumber}", "To currency is required");
        }

        // Validate to_amount
        if (!isset($row['to_amount']) || $row['to_amount'] === '') {
            $this->addError("row_{$lineNumber}", "To amount is required");
        } elseif (!is_numeric($row['to_amount']) || $row['to_amount'] < 0) {
            $this->addError("row_{$lineNumber}", 
                "To amount must be a positive number: {$row['to_amount']}"
            );
        }

        // Validate price
        if (!isset($row['price']) || $row['price'] === '') {
            $this->addError("row_{$lineNumber}", "Price is required");
        } elseif (!is_numeric($row['price']) || $row['price'] < 0) {
            $this->addError("row_{$lineNumber}", 
                "Price must be a positive number: {$row['price']}"
            );
        }

        // Validate fee (optional but must be numeric if present)
        if (isset($row['fee']) && $row['fee'] !== '' && !is_numeric($row['fee'])) {
            $this->addError("row_{$lineNumber}", "Fee must be a number: {$row['fee']}");
        }
    }

    /**
     * Check if date string is valid
     */
    private function isValidDate(string $date): bool
    {
        try {
            new DateTime($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add an error
     */
    private function addError(string $key, string $message): void
    {
        if (!isset($this->errors[$key])) {
            $this->errors[$key] = [];
        }
        $this->errors[$key][] = $message;
    }

    /**
     * Throw exception if there are errors
     */
    private function throwIfErrors(): void
    {
        if (!empty($this->errors)) {
            throw new ValidationException('Validation failed', $this->errors);
        }
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
