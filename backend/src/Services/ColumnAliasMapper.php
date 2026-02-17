<?php

namespace CryptoTax\Services;

/**
 * Column Alias Mapper
 * Maps various exchange column names to expected schema columns
 */
class ColumnAliasMapper
{
    private array $aliasMap = [
        'date' => [
            'date', 'time', 'datetime', 'timestamp', 'ts',
            'trade_time', 'tradetime', 'created_at', 'createdat',
            'executed_at', 'exec_time', 'exectime', 'fill_time',
            'filltime', 'transacttime', 'transaction_date', 'transaction_time'
        ],
        'type' => [
            'type', 'side', 'action', 'transaction_type', 'direction',
            'buy_sell', 'bid_ask', 'isbuyer', 'is_buyer',
            'order_type', 'trade_type'
        ],
        'from_currency' => [
            'from_currency', 'sell_coin', 'spent_coin', 'sellcoin',
            'asset_out', 'currency_out', 'out_currency', 'pay_currency',
            'from_coin', 'from_crypto'
        ],
        'from_amount' => [
            'from_amount', 'sell_amount', 'spent_amount', 'sellamount',
            'amount_out', 'out_amount', 'value_out',
            'from_qty', 'from_quantity'
        ],
        'to_currency' => [
            'to_currency', 'buy_coin', 'received_coin', 'buycoin',
            'asset_in', 'currency_in', 'in_currency', 'get_currency',
            'to_coin', 'to_crypto'
        ],
        'to_amount' => [
            'to_amount', 'buy_amount', 'received_amount', 'buyamount',
            'amount_in', 'in_amount', 'value_in',
            'to_qty', 'to_quantity'
        ],
        'price' => [
            'price', 'rate', 'unit_price', 'fill_price', 'fillpx',
            'execprice', 'avg_price', 'average_price',
            'price_zar', 'price_per_unit', 'buypricepercoin'
        ],
        'fee' => [
            'fee', 'commission', 'trading_fee', 'fee_amount',
            'execfee', 'filled_fees', 'fees', 'transaction_fee'
        ],
        'wallet' => [
            'wallet', 'exchange', 'platform', 'account',
            'account_id', 'subaccount', 'portfolio', 'source'
        ],
        // Exchange-specific fields for pair-based formats
        'symbol' => [
            'symbol', 'pair', 'market', 'product_id', 'instid',
            'instrument', 'trading_pair', 'currency_pair'
        ],
        'base_amount' => [
            'amount', 'qty', 'quantity', 'size', 'volume',
            'executedqty', 'filled_amount', 'fillsz', 'execqty',
            'orig_qty', 'cumqty', 'cum_exec_qty', 'executed',
            'exec_qty', 'base_qty', 'base_amount'
        ],
        'quote_amount' => [
            'quoteqty', 'quote_qty', 'funds', 'cost', 'total',
            'execvalue', 'fillquotesz', 'turnover', 'cummulative_quote_qty',
            'quote_amount', 'quote_total', 'quote_value'
        ]
    ];

    /**
     * Map source headers to expected column names
     * 
     * @param array $sourceHeaders Original header names from file
     * @return array Mapped header names
     */
    public function mapHeaders(array $sourceHeaders): array
    {
        $mappedHeaders = [];
        
        foreach ($sourceHeaders as $header) {
            $normalized = $this->normalizeHeaderName($header);
            $mappedHeaders[] = $this->findMapping($normalized);
        }
        
        return $mappedHeaders;
    }

    /**
     * Get the mapping result for a single header
     * 
     * @param string $header Source header name
     * @return string Mapped header name or original if no mapping found
     */
    public function findMapping(string $header): string
    {
        $normalized = $this->normalizeHeaderName($header);
        
        foreach ($this->aliasMap as $targetColumn => $aliases) {
            if (in_array($normalized, $aliases)) {
                return $targetColumn;
            }
        }
        
        // Return original normalized header if no mapping found
        return $normalized;
    }

    /**
     * Normalize header name for comparison
     * 
     * @param string $header Original header name
     * @return string Normalized header name
     */
    private function normalizeHeaderName(string $header): string
    {
        // Convert to lowercase
        $normalized = strtolower(trim($header));
        
        // Replace spaces, dashes, and dots with underscores
        $normalized = preg_replace('/[\s\-\.]+/', '_', $normalized);
        
        // Remove special characters except underscores
        $normalized = preg_replace('/[^a-z0-9_]/', '', $normalized);
        
        return $normalized;
    }

    /**
     * Check if header exists in source headers after mapping
     * 
     * @param array $sourceHeaders Original headers
     * @param string $targetColumn Target column to check for
     * @return bool
     */
    public function hasColumn(array $sourceHeaders, string $targetColumn): bool
    {
        $mappedHeaders = $this->mapHeaders($sourceHeaders);
        return in_array($targetColumn, $mappedHeaders);
    }

    /**
     * Get all possible aliases for a target column
     * 
     * @param string $targetColumn Target column name
     * @return array List of aliases
     */
    public function getAliases(string $targetColumn): array
    {
        return $this->aliasMap[$targetColumn] ?? [];
    }
}
