<?php

namespace CryptoTax\Validators;

use CryptoTax\Exceptions\ValidationException;
use CryptoTax\Services\ShapeDetector;
use CryptoTax\Services\FormatNormalizer;
use DateTime;

/**
 * Transaction Validator
 * Validates file structure and transaction data for different shapes
 */
class TransactionValidator
{
    private ShapeDetector $shapeDetector;
    private FormatNormalizer $formatNormalizer;
    private array $validTypes = ['BUY', 'SELL', 'TRADE', 'TRANSFER'];
    private array $errors = [];

    public function __construct(
        ShapeDetector $shapeDetector = null,
        FormatNormalizer $formatNormalizer = null
    ) {
        $this->shapeDetector = $shapeDetector;
        $this->formatNormalizer = $formatNormalizer ?? new FormatNormalizer();
    }

    /**
     * Validate raw transaction rows
     * 
     * @param array $rows Raw transaction rows
     * @param string $shape Detected shape ('A', 'B', or 'C')
     * @throws ValidationException if validation fails
     */
    public function validate(array $rows, string $shape = 'A'): void
    {
        $this->errors = [];

        if (empty($rows)) {
            $this->addError('general', 'No transactions found in file');
            $this->throwIfErrors();
        }

        // Validate structure of first row to ensure required columns exist
        $this->validateRequiredColumns($rows[0], $shape);
        
        // Validate each row
        foreach ($rows as $index => $row) {
            $this->validateRow($row, $shape);
        }

        $this->throwIfErrors();
    }

    /**
     * Validate required columns exist for the detected shape
     */
    private function validateRequiredColumns(array $row, string $shape): void
    {
        $requiredColumns = $this->getRequiredColumnsForShape($shape);
        $missingColumns = [];
        
        foreach ($requiredColumns as $column) {
            if (!array_key_exists($column, $row)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            $this->addError('structure', 
                "Missing required columns for Shape {$shape}: " . implode(', ', $missingColumns)
            );
        }
    }

    /**
     * Get required columns for a specific shape
     */
    private function getRequiredColumnsForShape(string $shape): array
    {
        if ($this->shapeDetector) {
            return $this->shapeDetector->getRequiredColumns($shape);
        }

        // Fallback to Shape A requirements
        return [
            'date',
            'type',
            'from_currency',
            'from_amount',
            'to_currency',
            'to_amount'
        ];
    }

    /**
     * Validate a single row
     */
    private function validateRow(array $row, string $shape): void
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
        } else {
            // Normalize type for validation
            $normalizedType = $this->formatNormalizer->normalizeType($row['type']);
            if (!in_array($normalizedType, $this->validTypes)) {
                $this->addError("row_{$lineNumber}", 
                    "Invalid transaction type: {$row['type']}. Must be BUY, SELL, or TRADE"
                );
            }
        }

        // Validate based on shape
        switch ($shape) {
            case 'A':
                $this->validateShapeA($row, $lineNumber);
                break;
            case 'B':
                $this->validateShapeB($row, $lineNumber);
                break;
            case 'C':
                $this->validateShapeC($row, $lineNumber);
                break;
        }

        // Allow negative or excessive fees - Red Flag system will catch these
        // with better context and recommendations
    }

    /**
     * Validate Shape A specific fields
     * 
     * Note: We allow some data quality issues to pass validation so they can be
     * caught and reported by the Red Flag Detection system with more context.
     */
    private function validateShapeA(array $row, $lineNumber): void
    {
        // Validate from_currency - only check if present (Red Flag will catch quality issues)
        // Empty values are allowed - Red Flag system will detect and report them

        // Validate from_amount - allow negative or missing values for Red Flag detection
        // The Red Flag system will catch these with better context and recommendations

        // Validate to_currency - only check if present
        // Empty values are allowed - Red Flag system will detect and report them

        // Validate to_amount - allow negative or missing values for Red Flag detection
        // The Red Flag system will catch these with better context and recommendations

        // Validate price - allow negative values for Red Flag detection
        // The Red Flag system will catch these with better context

        // Only fail on truly catastrophic structural issues that would prevent processing
        // All data quality issues are handled by the Red Flag Detection system
    }

    /**
     * Validate Shape B specific fields
     * 
     * Note: We allow some data quality issues to pass validation so they can be
     * caught and reported by the Red Flag Detection system with more context.
     */
    private function validateShapeB(array $row, $lineNumber): void
    {
        // Allow missing or invalid values - Red Flag system will detect and report them
        // with better context and recommendations
    }

    /**
     * Validate Shape C specific fields
     * 
     * Note: We allow some data quality issues to pass validation so they can be
     * caught and reported by the Red Flag Detection system with more context.
     */
    private function validateShapeC(array $row, $lineNumber): void
    {
        // Allow missing or invalid values - Red Flag system will detect and report them
        // with better context and recommendations
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
