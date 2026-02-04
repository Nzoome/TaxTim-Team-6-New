<?php

namespace CryptoTax\Tests;

use PHPUnit\Framework\TestCase;
use CryptoTax\Services\FIFOEngine;
use CryptoTax\Models\Transaction;
use DateTime;

/**
 * Tax Year Integration Test Suite
 * 
 * Tests Sprint 3 tax year functionality:
 * - Tax year allocation to disposals
 * - Snapshots at tax year boundaries
 * - Per-coin per-tax-year gain calculations
 * - CGT exclusion application
 * - Inclusion rate application
 */
class TaxYearIntegrationTest extends TestCase
{
    /**
     * Test: Disposals are tagged with correct tax year
     */
    public function testDisposalsTaggedWithTaxYear(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY in tax year 2023/2024
            new Transaction(
                new DateTime('2023-06-01'),
                'BUY',
                'ZAR',
                20000.0,
                'BTC',
                1.0,
                20000.0,
                0.0,
                null,
                1
            ),
            // SELL in tax year 2023/2024
            new Transaction(
                new DateTime('2024-01-15'),
                'SELL',
                'BTC',
                0.5,
                'ZAR',
                25000.0,
                50000.0,
                0.0,
                null,
                2
            ),
            // SELL in tax year 2024/2025
            new Transaction(
                new DateTime('2024-06-01'),
                'SELL',
                'BTC',
                0.3,
                'ZAR',
                21000.0,
                70000.0,
                0.0,
                null,
                3
            ),
        ];

        $result = $engine->processTransactions($transactions);

        // Check first SELL (2023/2024)
        $sell1 = $result['breakdowns'][1];
        $this->assertEquals('SELL', $sell1['type']);
        $this->assertEquals('2023/2024', $sell1['taxYear']);

        // Check second SELL (2024/2025)
        $sell2 = $result['breakdowns'][2];
        $this->assertEquals('SELL', $sell2['type']);
        $this->assertEquals('2024/2025', $sell2['taxYear']);
    }

    /**
     * Test: TRADE transactions are tagged with correct tax year
     */
    public function testTradeTaggedWithTaxYear(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY BTC
            new Transaction(
                new DateTime('2024-03-15'),
                'BUY',
                'ZAR',
                20000.0,
                'BTC',
                1.0,
                20000.0,
                0.0,
                null,
                1
            ),
            // TRADE BTC for ETH in tax year 2024/2025
            new Transaction(
                new DateTime('2024-10-01'),
                'TRADE',
                'BTC',
                0.5,
                'ETH',
                10.0,
                2500.0,
                0.0,
                null,
                2
            ),
        ];

        $result = $engine->processTransactions($transactions);

        $trade = $result['breakdowns'][1];
        $this->assertEquals('TRADE', $trade['type']);
        $this->assertEquals('2024/2025', $trade['taxYear']);
    }

    /**
     * Test: Snapshots captured at tax year boundaries
     */
    public function testSnapshotsAtTaxYearBoundaries(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY in 2023/2024
            new Transaction(
                new DateTime('2023-06-01'),
                'BUY',
                'ZAR',
                20000.0,
                'BTC',
                1.0,
                20000.0,
                0.0,
                null,
                1
            ),
            // SELL partial in 2023/2024
            new Transaction(
                new DateTime('2024-01-15'),
                'SELL',
                'BTC',
                0.3,
                'ZAR',
                15000.0,
                50000.0,
                0.0,
                null,
                2
            ),
            // --- Crosses into 2024/2025 ---
            // BUY in 2024/2025
            new Transaction(
                new DateTime('2024-06-01'),
                'BUY',
                'ZAR',
                30000.0,
                'BTC',
                1.0,
                30000.0,
                0.0,
                null,
                3
            ),
            // SELL in 2024/2025
            new Transaction(
                new DateTime('2024-12-01'),
                'SELL',
                'BTC',
                0.5,
                'ZAR',
                35000.0,
                70000.0,
                0.0,
                null,
                4
            ),
        ];

        $result = $engine->processTransactions($transactions);
        $snapshots = $engine->getTaxYearSnapshots();

        // Should have snapshots for both tax years
        $this->assertArrayHasKey('2023/2024', $snapshots);
        $this->assertArrayHasKey('2024/2025', $snapshots);

        // Snapshot for 2023/2024 should show 0.7 BTC remaining
        $snapshot2023 = $snapshots['2023/2024'];
        $this->assertNotEmpty($snapshot2023);
        $btcBalance2023 = $snapshot2023[0];
        $this->assertEquals('BTC', $btcBalance2023['currency']);
        $this->assertEqualsWithDelta(0.7, $btcBalance2023['totalBalance'], 0.0001);

        // Snapshot for 2024/2025 should show final balance (0.7 + 1.0 - 0.5 = 1.2 BTC)
        $snapshot2024 = $snapshots['2024/2025'];
        $btcBalance2024 = $snapshot2024[0];
        $this->assertEquals('BTC', $btcBalance2024['currency']);
        $this->assertEqualsWithDelta(1.2, $btcBalance2024['totalBalance'], 0.0001);
    }

    /**
     * Test: Disposals grouped by tax year and coin
     */
    public function testAllocateDisposalsByTaxYear(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BTC transactions
            new Transaction(new DateTime('2023-06-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            new Transaction(new DateTime('2024-01-15'), 'SELL', 'BTC', 0.3, 'ZAR', 15000.0, 50000.0, 0.0, null, 2),
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 0.2, 'ZAR', 14000.0, 70000.0, 0.0, null, 3),
            
            // ETH transactions
            new Transaction(new DateTime('2024-03-01'), 'BUY', 'ZAR', 20000.0, 'ETH', 10.0, 2000.0, 0.0, null, 4),
            new Transaction(new DateTime('2024-12-01'), 'SELL', 'ETH', 5.0, 'ZAR', 15000.0, 3000.0, 0.0, null, 5),
        ];

        $engine->processTransactions($transactions);
        $allocations = $engine->allocateDisposalsByTaxYear();

        // Should have two tax years
        $this->assertArrayHasKey('2023/2024', $allocations);
        $this->assertArrayHasKey('2024/2025', $allocations);

        // Tax year 2023/2024 should have 1 BTC disposal
        $this->assertArrayHasKey('BTC', $allocations['2023/2024']);
        $this->assertCount(1, $allocations['2023/2024']['BTC']);

        // Tax year 2024/2025 should have 1 BTC and 1 ETH disposal
        $this->assertArrayHasKey('BTC', $allocations['2024/2025']);
        $this->assertArrayHasKey('ETH', $allocations['2024/2025']);
        $this->assertCount(1, $allocations['2024/2025']['BTC']);
        $this->assertCount(1, $allocations['2024/2025']['ETH']);
    }

    /**
     * Test: Calculate gross gains per coin per tax year
     */
    public function testCalculateGrossGainsPerCoinPerTaxYear(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY 1.0 BTC @ R20,000
            new Transaction(new DateTime('2023-06-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            
            // SELL 0.3 BTC @ R50,000 in 2023/2024
            // Cost: 0.3 * 20000 = 6000, Proceeds: 0.3 * 50000 = 15000, Gain: 9000
            new Transaction(new DateTime('2024-01-15'), 'SELL', 'BTC', 0.3, 'ZAR', 15000.0, 50000.0, 0.0, null, 2),
            
            // SELL 0.2 BTC @ R70,000 in 2024/2025
            // Cost: 0.2 * 20000 = 4000, Proceeds: 0.2 * 70000 = 14000, Gain: 10000
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 0.2, 'ZAR', 14000.0, 70000.0, 0.0, null, 3),
        ];

        $engine->processTransactions($transactions);
        $report = $engine->calculateGainsPerCoinPerTaxYear(0.0, 1.0); // No exclusion, 100% inclusion for testing

        // Tax year 2023/2024
        $this->assertArrayHasKey('2023/2024', $report);
        $year2023 = $report['2023/2024'];
        
        $this->assertArrayHasKey('BTC', $year2023['coins']);
        $this->assertEquals(9000.0, $year2023['coins']['BTC']['grossGain']);
        $this->assertEquals(9000.0, $year2023['netGrossGain']);
        $this->assertEquals(9000.0, $year2023['taxableAfterInclusion']);

        // Tax year 2024/2025
        $this->assertArrayHasKey('2024/2025', $report);
        $year2024 = $report['2024/2025'];
        
        $this->assertArrayHasKey('BTC', $year2024['coins']);
        $this->assertEquals(10000.0, $year2024['coins']['BTC']['grossGain']);
        $this->assertEquals(10000.0, $year2024['netGrossGain']);
        $this->assertEquals(10000.0, $year2024['taxableAfterInclusion']);
    }

    /**
     * Test: Annual CGT exclusion applied correctly
     */
    public function testAnnualCGTExclusionApplied(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY 1.0 BTC @ R20,000
            new Transaction(new DateTime('2024-03-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            
            // SELL 1.0 BTC @ R120,000 (gain = 100,000)
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 1.0, 'ZAR', 100000.0, 100000.0, 0.0, null, 2),
        ];

        $engine->processTransactions($transactions);
        
        // Apply R40,000 exclusion
        $report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 1.0); // 100% inclusion for clarity

        $year2024 = $report['2024/2025'];
        
        // Gross gain: 100,000 - 20,000 = 80,000
        $this->assertEquals(80000.0, $year2024['netGrossGain']);
        
        // After exclusion: 80,000 - 40,000 = 40,000
        $this->assertEquals(40000.0, $year2024['netAfterExclusion']);
        $this->assertEquals(40000.0, $year2024['annualExclusionApplied']);
        
        // Taxable (100% inclusion): 40,000
        $this->assertEquals(40000.0, $year2024['taxableAfterInclusion']);
    }

    /**
     * Test: Exclusion not applied when net gain is negative (loss)
     */
    public function testExclusionNotAppliedToLoss(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY 1.0 BTC @ R100,000
            new Transaction(new DateTime('2024-03-01'), 'BUY', 'ZAR', 100000.0, 'BTC', 1.0, 100000.0, 0.0, null, 1),
            
            // SELL 1.0 BTC @ R50,000 (loss = -50,000)
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 1.0, 'ZAR', 50000.0, 50000.0, 0.0, null, 2),
        ];

        $engine->processTransactions($transactions);
        $report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);

        $year2024 = $report['2024/2025'];
        
        // Net loss: -50,000
        $this->assertEquals(-50000.0, $year2024['netGrossGain']);
        
        // Exclusion should not be applied to losses
        $this->assertEquals(0.0, $year2024['annualExclusionApplied']);
        $this->assertEquals(-50000.0, $year2024['netAfterExclusion']);
        
        // Taxable (40% of loss): -20,000
        $this->assertEquals(-20000.0, $year2024['taxableAfterInclusion']);
    }

    /**
     * Test: 40% inclusion rate applied correctly
     */
    public function testInclusionRateApplied(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY 1.0 BTC @ R20,000
            new Transaction(new DateTime('2024-03-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            
            // SELL 1.0 BTC @ R120,000 (gain = 100,000)
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 1.0, 'ZAR', 100000.0, 100000.0, 0.0, null, 2),
        ];

        $engine->processTransactions($transactions);
        
        // Apply R40,000 exclusion and 40% inclusion rate
        $report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);

        $year2024 = $report['2024/2025'];
        
        // Gross gain: 100,000 - 20,000 = 80,000
        $this->assertEquals(80000.0, $year2024['netGrossGain']);
        
        // After exclusion: 80,000 - 40,000 = 40,000
        $this->assertEquals(40000.0, $year2024['netAfterExclusion']);
        
        // Taxable (40% inclusion): 40,000 * 0.4 = 16,000
        $this->assertEquals(16000.0, $year2024['taxableAfterInclusion']);
    }

    /**
     * Test: Multiple coins in same tax year
     */
    public function testMultipleCoinsInSameTaxYear(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BTC: Buy @ 20k, Sell @ 50k (gain = 30k)
            new Transaction(new DateTime('2024-03-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 1.0, 'ZAR', 50000.0, 50000.0, 0.0, null, 2),
            
            // ETH: Buy @ 10k, Sell @ 25k (gain = 15k)
            new Transaction(new DateTime('2024-04-01'), 'BUY', 'ZAR', 10000.0, 'ETH', 5.0, 2000.0, 0.0, null, 3),
            new Transaction(new DateTime('2024-07-01'), 'SELL', 'ETH', 5.0, 'ZAR', 25000.0, 5000.0, 0.0, null, 4),
        ];

        $engine->processTransactions($transactions);
        $report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);

        $year2024 = $report['2024/2025'];
        
        // BTC gain: 30,000
        $this->assertEquals(30000.0, $year2024['coins']['BTC']['grossGain']);
        
        // ETH gain: 15,000
        $this->assertEquals(15000.0, $year2024['coins']['ETH']['grossGain']);
        
        // Total gain: 45,000
        $this->assertEquals(45000.0, $year2024['netGrossGain']);
        
        // After exclusion: 45,000 - 40,000 = 5,000
        $this->assertEquals(5000.0, $year2024['netAfterExclusion']);
        
        // Taxable (40%): 5,000 * 0.4 = 2,000
        $this->assertEquals(2000.0, $year2024['taxableAfterInclusion']);
    }

    /**
     * Test: Exclusion applied only once per tax year
     */
    public function testExclusionAppliedOncePerTaxYear(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // Multiple disposals in same tax year
            new Transaction(new DateTime('2024-03-01'), 'BUY', 'ZAR', 10000.0, 'BTC', 1.0, 10000.0, 0.0, null, 1),
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 0.5, 'ZAR', 30000.0, 60000.0, 0.0, null, 2), // Gain: 25k
            new Transaction(new DateTime('2024-09-01'), 'SELL', 'BTC', 0.5, 'ZAR', 30000.0, 60000.0, 0.0, null, 3), // Gain: 25k
        ];

        $engine->processTransactions($transactions);
        $report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);

        $year2024 = $report['2024/2025'];
        
        // Total gain: 50,000
        $this->assertEquals(50000.0, $year2024['netGrossGain']);
        
        // Exclusion applied once: 50,000 - 40,000 = 10,000
        $this->assertEquals(40000.0, $year2024['annualExclusionApplied']);
        $this->assertEquals(10000.0, $year2024['netAfterExclusion']);
    }

    /**
     * Test: Complex scenario spanning multiple tax years
     */
    public function testComplexMultiYearScenario(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // Tax year 2023/2024
            new Transaction(new DateTime('2023-06-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 2.0, 10000.0, 0.0, null, 1),
            new Transaction(new DateTime('2024-01-15'), 'SELL', 'BTC', 1.0, 'ZAR', 50000.0, 50000.0, 0.0, null, 2), // Gain: 40k
            
            // Tax year 2024/2025
            new Transaction(new DateTime('2024-06-01'), 'BUY', 'ZAR', 30000.0, 'BTC', 1.0, 30000.0, 0.0, null, 3),
            new Transaction(new DateTime('2024-12-01'), 'SELL', 'BTC', 1.5, 'ZAR', 120000.0, 80000.0, 0.0, null, 4), // Mixed lots
        ];

        $engine->processTransactions($transactions);
        $report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);

        // Tax year 2023/2024
        $year2023 = $report['2023/2024'];
        $this->assertEquals(40000.0, $year2023['netGrossGain']);
        $this->assertEquals(0.0, $year2023['netAfterExclusion']); // Fully excluded
        $this->assertEquals(0.0, $year2023['taxableAfterInclusion']);

        // Tax year 2024/2025 should have significant gain
        $this->assertArrayHasKey('2024/2025', $report);
        $year2024 = $report['2024/2025'];
        $this->assertGreaterThan(0, $year2024['netGrossGain']);
    }

    /**
     * Test: Snapshots can be disabled
     */
    public function testSnapshotsCanBeDisabled(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            new Transaction(new DateTime('2023-06-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            new Transaction(new DateTime('2024-06-01'), 'SELL', 'BTC', 0.5, 'ZAR', 25000.0, 50000.0, 0.0, null, 2),
        ];

        // Process with snapshots disabled
        $engine->processTransactions($transactions, ['snapshotTaxYearBoundaries' => false]);
        $snapshots = $engine->getTaxYearSnapshots();

        // Should have no snapshots
        $this->assertEmpty($snapshots);
    }
}
