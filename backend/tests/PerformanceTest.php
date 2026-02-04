<?php

/**
 * Sprint 4 - Performance Testing
 * 
 * This test suite validates performance with large datasets
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use CryptoTax\Services\FIFOEngine;
use CryptoTax\Models\Transaction;

class PerformanceTest extends TestCase
{
    private FIFOEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new FIFOEngine();
    }

    /**
     * Test: Process 1,000 transactions in reasonable time
     */
    public function testProcess1000Transactions()
    {
        $transactions = $this->generateRandomTransactions(1000);
        
        $startTime = microtime(true);
        $result = $this->engine->processTransactions($transactions);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        // Should complete in less than 5 seconds
        $this->assertLessThan(5.0, $executionTime, "Processing 1000 transactions took {$executionTime}s");
        $this->assertEquals(1000, $result['summary']['transactionsProcessed']);
        
        echo "\n✓ Processed 1,000 transactions in " . number_format($executionTime, 3) . " seconds\n";
    }

    /**
     * Test: Process 5,000 transactions
     */
    public function testProcess5000Transactions()
    {
        $transactions = $this->generateRandomTransactions(5000);
        
        $startTime = microtime(true);
        $result = $this->engine->processTransactions($transactions);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        // Should complete in less than 25 seconds
        $this->assertLessThan(25.0, $executionTime, "Processing 5000 transactions took {$executionTime}s");
        $this->assertEquals(5000, $result['summary']['transactionsProcessed']);
        
        echo "\n✓ Processed 5,000 transactions in " . number_format($executionTime, 3) . " seconds\n";
    }

    /**
     * Test: Memory usage stays reasonable with large datasets
     */
    public function testMemoryUsageWithLargeDataset()
    {
        $memoryBefore = memory_get_usage(true);
        
        $transactions = $this->generateRandomTransactions(2000);
        $result = $this->engine->processTransactions($transactions);
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        // Should use less than 50MB for 2000 transactions
        $this->assertLessThan(50, $memoryUsed, "Memory usage: {$memoryUsed}MB");
        
        echo "\n✓ Memory used for 2,000 transactions: " . number_format($memoryUsed, 2) . " MB\n";
    }

    /**
     * Test: Deep FIFO queue performance (many lots)
     */
    public function testDeepFIFOQueue()
    {
        // Create 500 BUY transactions followed by 1 large SELL
        $transactions = [];
        $totalAmount = 0;
        
        for ($i = 0; $i < 500; $i++) {
            $amount = 0.1;
            $totalAmount += $amount;
            $transactions[] = $this->createTransaction(
                "2024-01-" . str_pad($i % 28 + 1, 2, '0', STR_PAD_LEFT),
                'BUY',
                'BTC',
                $amount,
                100000 + ($i * 100)
            );
        }
        
        // Sell all at once - forces consumption of all 500 lots
        $transactions[] = $this->createTransaction(
            '2024-06-01',
            'SELL',
            'BTC',
            $totalAmount,
            150000
        );
        
        $startTime = microtime(true);
        $result = $this->engine->processTransactions($transactions);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        // Should handle deep FIFO queue efficiently
        $this->assertLessThan(2.0, $executionTime, "Deep FIFO processing took {$executionTime}s");
        
        // Verify all lots were consumed
        $sellBreakdown = $result['breakdowns'][500];
        $this->assertCount(500, $sellBreakdown['lotsConsumed']);
        
        echo "\n✓ Processed deep FIFO queue (500 lots) in " . number_format($executionTime, 3) . " seconds\n";
    }

    /**
     * Test: Multiple currencies with many transactions each
     */
    public function testMultipleCurrenciesPerformance()
    {
        $currencies = ['BTC', 'ETH', 'LTC', 'XRP', 'ADA'];
        $transactions = [];
        
        // 200 transactions per currency = 1000 total
        // Start with buys, then do sells
        foreach ($currencies as $currency) {
            // First 150 are buys
            for ($i = 0; $i < 150; $i++) {
                $transactions[] = $this->createTransaction(
                    "2024-" . str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT) . "-01 " . str_pad($i % 24, 2, '0', STR_PAD_LEFT) . ":00:00",
                    'BUY',
                    $currency,
                    rand(10, 100) / 10,
                    rand(10000, 100000)
                );
            }
            
            // Next 50 are sells
            for ($i = 150; $i < 200; $i++) {
                $transactions[] = $this->createTransaction(
                    "2024-" . str_pad(($i % 12) + 1, 2, '0', STR_PAD_LEFT) . "-01 " . str_pad($i % 24, 2, '0', STR_PAD_LEFT) . ":00:00",
                    'SELL',
                    $currency,
                    rand(1, 50) / 10,
                    rand(10000, 100000)
                );
            }
        }
        
        // Sort chronologically
        usort($transactions, function($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });
        
        $startTime = microtime(true);
        $result = $this->engine->processTransactions($transactions);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(5.0, $executionTime, "Multi-currency processing took {$executionTime}s");
        
        echo "\n✓ Processed 1,000 transactions across 5 currencies in " . number_format($executionTime, 3) . " seconds\n";
    }

    /**
     * Test: CSV export performance with large dataset
     */
    public function testCSVExportPerformance()
    {
        $transactions = $this->generateRandomTransactions(1000);
        $result = $this->engine->processTransactions($transactions);
        
        $startTime = microtime(true);
        
        // Simulate CSV generation
        $csvLines = [];
        $csvLines[] = 'Date,Type,Currency,Amount,Proceeds,Cost Base,Capital Gain,Tax Year';
        
        foreach ($result['breakdowns'] as $breakdown) {
            $csvLines[] = sprintf(
                '%s,%s,%s,%.8f,%.2f,%.2f,%.2f,%s',
                $breakdown['date'],
                $breakdown['type'],
                $breakdown['currency'],
                $breakdown['amount'],
                $breakdown['proceeds'] ?? 0,
                $breakdown['costBase'] ?? 0,
                $breakdown['capitalGain'] ?? 0,
                $breakdown['taxYear'] ?? ''
            );
        }
        
        $csvContent = implode("\n", $csvLines);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should generate CSV quickly
        $this->assertLessThan(1.0, $executionTime, "CSV generation took {$executionTime}s");
        $this->assertGreaterThan(1000, strlen($csvContent));
        
        echo "\n✓ Generated CSV for 1,000 transactions in " . number_format($executionTime, 3) . " seconds\n";
        echo "  CSV size: " . number_format(strlen($csvContent) / 1024, 2) . " KB\n";
    }

    // Helper methods

    private function generateRandomTransactions(int $count): array
    {
        $transactions = [];
        $currencies = ['BTC', 'ETH', 'LTC'];
        $balances = []; // Track balances per currency to avoid invalid sells
        
        $baseDate = new DateTime('2023-01-01');
        
        for ($i = 0; $i < $count; $i++) {
            $date = clone $baseDate;
            $date->modify("+{$i} hours");
            
            $currency = $currencies[array_rand($currencies)];
            
            // Initialize balance if not exists
            if (!isset($balances[$currency])) {
                $balances[$currency] = 0;
            }
            
            // Decide type: more buys early, balanced later
            $canSell = $balances[$currency] > 0.01;
            $shouldBuy = !$canSell || ($i < $count * 0.7 && rand(0, 100) < 70);
            $type = $shouldBuy ? 'BUY' : 'SELL';
            
            $amount = rand(1, 100) / 100; // 0.01 to 1.00
            
            // Ensure we don't sell more than we have
            if ($type === 'SELL') {
                $amount = min($amount, $balances[$currency] * 0.9); // Only sell up to 90% of balance
            }
            
            $price = rand(10000, 200000);
            
            try {
                $tx = $this->createTransaction(
                    $date->format('Y-m-d H:i:s'),
                    $type,
                    $currency,
                    $amount,
                    $price
                );
                $transactions[] = $tx;
                
                // Update balance
                if ($type === 'BUY') {
                    $balances[$currency] += $amount;
                } else {
                    $balances[$currency] -= $amount;
                }
            } catch (Exception $e) {
                // Skip invalid transactions
                continue;
            }
        }
        
        return $transactions;
    }

    private function createTransaction(
        string $date,
        string $type,
        string $currency,
        float $amount,
        float $price
    ): Transaction {
        $dateObj = new DateTime($date);
        
        if ($type === 'BUY') {
            return new Transaction(
                $dateObj,
                'BUY',
                'ZAR',
                $price * $amount,
                $currency,
                $amount,
                $price,
                0
            );
        } elseif ($type === 'SELL') {
            return new Transaction(
                $dateObj,
                'SELL',
                $currency,
                $amount,
                'ZAR',
                $price * $amount,
                $price,
                0
            );
        }
        
        throw new InvalidArgumentException("Unknown type: $type");
    }
}
