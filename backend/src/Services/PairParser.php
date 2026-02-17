<?php

namespace CryptoTax\Services;

use CryptoTax\Exceptions\ParseException;

/**
 * Pair Parser
 * Parses trading pair symbols into base and quote currencies
 */
class PairParser
{
    // Common quote currencies to help with parsing
    private array $commonQuotes = [
        'USDT', 'USDC', 'BUSD', 'DAI', 'USD', 'EUR', 'GBP',
        'BTC', 'ETH', 'BNB', 'ZAR', 'KRW', 'JPY', 'AUD',
        'CAD', 'CHF', 'CNY', 'HKD', 'SGD', 'TRY', 'RUB'
    ];

    // Common separators in trading pairs
    private array $separators = ['/', '-', '_', ':'];

    /**
     * Parse a trading pair symbol into base and quote currencies
     * 
     * @param string $symbol Trading pair symbol (e.g., "BTCUSDT", "BTC-USDT", "BTC/ZAR")
     * @return array ['base' => string, 'quote' => string]
     * @throws ParseException if pair cannot be parsed
     */
    public function parse(string $symbol): array
    {
        $symbol = strtoupper(trim($symbol));

        // Try parsing with explicit separators first
        foreach ($this->separators as $separator) {
            if (strpos($symbol, $separator) !== false) {
                return $this->parseWithSeparator($symbol, $separator);
            }
        }

        // Try parsing without separator (e.g., BTCUSDT)
        return $this->parseWithoutSeparator($symbol);
    }

    /**
     * Parse pair with explicit separator
     * 
     * @param string $symbol Trading pair symbol
     * @param string $separator Separator character
     * @return array ['base' => string, 'quote' => string]
     * @throws ParseException
     */
    private function parseWithSeparator(string $symbol, string $separator): array
    {
        $parts = explode($separator, $symbol);
        
        if (count($parts) !== 2) {
            throw new ParseException("Invalid pair format: {$symbol}");
        }

        $base = trim($parts[0]);
        $quote = trim($parts[1]);

        if (empty($base) || empty($quote)) {
            throw new ParseException("Invalid pair format: {$symbol}");
        }

        return [
            'base' => $base,
            'quote' => $quote
        ];
    }

    /**
     * Parse pair without separator (e.g., BTCUSDT)
     * 
     * @param string $symbol Trading pair symbol
     * @return array ['base' => string, 'quote' => string]
     * @throws ParseException
     */
    private function parseWithoutSeparator(string $symbol): array
    {
        // Try to find a common quote currency
        foreach ($this->commonQuotes as $quote) {
            $quoteLen = strlen($quote);
            
            // Check if symbol ends with this quote currency
            if (substr($symbol, -$quoteLen) === $quote) {
                $base = substr($symbol, 0, -$quoteLen);
                
                if (!empty($base)) {
                    return [
                        'base' => $base,
                        'quote' => $quote
                    ];
                }
            }
            
            // Check if symbol starts with this quote currency (e.g., KRW-BTC)
            if (substr($symbol, 0, $quoteLen) === $quote) {
                $base = substr($symbol, $quoteLen);
                
                if (!empty($base)) {
                    return [
                        'base' => $base,
                        'quote' => $quote
                    ];
                }
            }
        }

        // If no common quote found, try to split at reasonable position
        // Assume base is 3-5 characters, quote is remaining
        if (strlen($symbol) >= 6) {
            // Try common split points
            $splitPoints = [3, 4, 5];
            
            foreach ($splitPoints as $split) {
                if (strlen($symbol) > $split) {
                    $base = substr($symbol, 0, $split);
                    $quote = substr($symbol, $split);
                    
                    // Validate that both parts look reasonable
                    if ($this->looksLikeCurrency($base) && $this->looksLikeCurrency($quote)) {
                        return [
                            'base' => $base,
                            'quote' => $quote
                        ];
                    }
                }
            }
        }

        throw new ParseException("Unable to parse trading pair: {$symbol}");
    }

    /**
     * Check if a string looks like a currency code
     * 
     * @param string $currency Currency string to check
     * @return bool
     */
    private function looksLikeCurrency(string $currency): bool
    {
        // Must be 2-6 characters, all uppercase letters/numbers
        return strlen($currency) >= 2 
            && strlen($currency) <= 6 
            && preg_match('/^[A-Z0-9]+$/', $currency);
    }

    /**
     * Check if a string is a valid trading pair format
     * 
     * @param string $symbol Symbol to check
     * @return bool
     */
    public function isValidPair(string $symbol): bool
    {
        try {
            $this->parse($symbol);
            return true;
        } catch (ParseException $e) {
            return false;
        }
    }
}
