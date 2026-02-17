<?php

namespace CryptoTax\Services;

/**
 * Format Normalizer
 * Normalizes various number and currency formats (especially South African ZAR format)
 */
class FormatNormalizer
{
    /**
     * Normalize a numeric value that may have various formats
     * 
     * @param mixed $value Value to normalize
     * @return float Normalized numeric value
     */
    public function normalizeNumber($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        // Remove currency symbols and spaces
        $normalized = $this->removeCurrencySymbols($value);
        
        // Handle South African / European format (comma as decimal separator)
        $normalized = $this->normalizeDecimalSeparator($normalized);
        
        // Remove any remaining non-numeric characters except decimal point
        $normalized = preg_replace('/[^0-9.]/', '', $normalized);
        
        return $normalized !== '' ? (float)$normalized : 0.0;
    }

    /**
     * Remove currency symbols from a string
     * 
     * @param string $value Value with potential currency symbols
     * @return string Value without currency symbols
     */
    private function removeCurrencySymbols(string $value): string
    {
        // Common currency symbols
        $symbols = [
            'R',      // South African Rand
            'ZAR',
            '$',
            '€',
            '£',
            '¥',
            'USD',
            'EUR',
            'GBP',
            'BTC',
            'ETH'
        ];

        $value = trim($value);
        
        // Remove currency symbols (case-insensitive)
        foreach ($symbols as $symbol) {
            $value = str_ireplace($symbol, '', $value);
        }

        return trim($value);
    }

    /**
     * Normalize decimal separator (handle comma vs period)
     * 
     * @param string $value Value with potential comma decimal separator
     * @return string Value with period decimal separator
     */
    private function normalizeDecimalSeparator(string $value): string
    {
        $value = trim($value);
        
        // Count commas and periods
        $commaCount = substr_count($value, ',');
        $periodCount = substr_count($value, '.');
        
        // If both exist, determine which is the decimal separator
        if ($commaCount > 0 && $periodCount > 0) {
            // Find the last occurrence of each
            $lastComma = strrpos($value, ',');
            $lastPeriod = strrpos($value, '.');
            
            // The one that appears last is likely the decimal separator
            if ($lastComma > $lastPeriod) {
                // Comma is decimal separator (e.g., "1.000,50" or "R 100 000,00")
                $value = str_replace('.', '', $value);  // Remove thousands separator
                $value = str_replace(',', '.', $value); // Convert decimal separator
            } else {
                // Period is decimal separator (e.g., "1,000.50")
                $value = str_replace(',', '', $value);  // Remove thousands separator
            }
        }
        // If only commas exist
        elseif ($commaCount > 0) {
            // Check if this looks like a decimal separator or thousands separator
            $lastComma = strrpos($value, ',');
            $afterComma = substr($value, $lastComma + 1);
            
            // If there are 2 or more digits after comma, it's likely a decimal separator
            // e.g., "0,1000000" or "100 000,00"
            if (strlen($afterComma) >= 2) {
                // Could be decimal separator
                $beforeComma = substr($value, 0, $lastComma);
                
                // If there are multiple commas, remove all except the last one
                if ($commaCount > 1) {
                    $beforeComma = str_replace(',', '', $beforeComma);
                    $value = $beforeComma . '.' . $afterComma;
                } else {
                    $value = str_replace(',', '.', $value);
                }
            } else {
                // Short number after comma, probably thousands separator (e.g., "1,000")
                $value = str_replace(',', '', $value);
            }
        }
        // If only periods exist with multiple occurrences, they're thousands separators
        elseif ($periodCount > 1) {
            // Remove all periods except the last one
            $lastPeriod = strrpos($value, '.');
            $beforePeriod = substr($value, 0, $lastPeriod);
            $afterPeriod = substr($value, $lastPeriod);
            $value = str_replace('.', '', $beforePeriod) . $afterPeriod;
        }

        // Remove any spaces (used as thousands separators in some formats)
        $value = str_replace(' ', '', $value);
        
        return $value;
    }

    /**
     * Normalize a currency code (uppercase, trim)
     * 
     * @param string $currency Currency code
     * @return string Normalized currency code
     */
    public function normalizeCurrency(string $currency): string
    {
        return strtoupper(trim($currency));
    }

    /**
     * Normalize a transaction type value
     * 
     * @param string $type Transaction type value
     * @return string Normalized type (BUY, SELL, or TRADE)
     */
    public function normalizeType(string $type): string
    {
        $type = strtoupper(trim($type));

        // Map to BUY
        $buyTypes = ['BUY', 'BID', 'PURCHASE', 'CREDIT', 'TRUE', '1'];
        if (in_array($type, $buyTypes)) {
            return 'BUY';
        }

        // Map to SELL
        $sellTypes = ['SELL', 'ASK', 'DISPOSE', 'DEBIT', 'FALSE', '0'];
        if (in_array($type, $sellTypes)) {
            return 'SELL';
        }

        // Map to TRADE
        $tradeTypes = ['TRADE', 'SWAP', 'CONVERT', 'EXCHANGE'];
        if (in_array($type, $tradeTypes)) {
            return 'TRADE';
        }

        // If already valid, return as-is
        if (in_array($type, ['BUY', 'SELL', 'TRADE'])) {
            return $type;
        }

        // Default to original value if no mapping found
        return $type;
    }

    /**
     * Normalize a boolean value from various formats
     * 
     * @param mixed $value Boolean value in various formats
     * @return bool Normalized boolean
     */
    public function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', 'yes', '1', 'y', 't']);
        }

        return (bool)$value;
    }
}
