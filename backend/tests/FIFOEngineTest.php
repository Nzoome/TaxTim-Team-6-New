<?php

namespace CryptoTax\Tests;

use PHPUnit\Framework\TestCase;
use CryptoTax\Models\BalanceLot;
use CryptoTax\Models\CoinBalance;
use CryptoTax\Services\FIFOEngine;
use CryptoTax\Models\Transaction;
use DateTime;

/**
 * FIFO Engine Test Suite
 * 
 * Tests the complete FIFO calculation logic including:
 * - BUY: Creating and adding FIFO lots
 * - SELL: FIFO consumption with partial lots
 * - TRADE: Composition of SELL + BUY
 * - Capital gain/loss calculation
 * - Edge cases and error handling
 */
class FIFOEngineTest extends TestCase
{
    /**
     * Test: BUY creates a new FIFO lot
     */
    public function testBuyCreatesLot(): void
    {
        $engine = new FIFOEngine();
        
        $transaction = new Transaction(
            new DateTime('2024-01-01'),
            'BUY',
            'ZAR',
            10000.0,
            'BTC',
            0.5,
            20000.0, // R20,000 per BTC
            100.0,   // R100 fee
            'Wallet1',
            1
        );

        $result = $engine->processTransactions([$transaction]);

        // Verify breakdown
        $this->assertCount(1, $result['breakdowns']);
        $breakdown = $result['breakdowns'][0];
        $this->assertEquals('BUY', $breakdown['type']);
        $this->assertEquals('BTC', $breakdown['currency']);
        $this->assertEquals(0.5, $breakdown['amount']);
        $this->assertEquals(10100.0, $breakdown['totalCost']); // 10000 + 100 fee
        $this->assertEquals(20200.0, $breakdown['costPerUnit']); // 10100 / 0.5

        // Verify balance
        $balances = $engine->getBalances();
        $this->assertCount(1, $balances);
        $balance = reset($balances);
        $this->assertEquals(0.5, $balance->getTotalBalance());
        $this->assertEquals(1, $balance->getLotCount());
    }

    /**
     * Test: Multiple BUYs stack correctly in FIFO order
     */
    public function testMultipleBuysStackCorrectly(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            new Transaction(
                new DateTime('2024-01-01'),
                'BUY',
                'ZAR',
                10000.0,
                'BTC',
                0.5,
                20000.0,
                0.0,
                null,
                1
            ),
            new Transaction(
                new DateTime('2024-01-02'),
                'BUY',
                'ZAR',
                15000.0,
                'BTC',
                0.5,
                30000.0,
                0.0,
                null,
                2
            ),
            new Transaction(
                new DateTime('2024-01-03'),
                'BUY',
                'ZAR',
                20000.0,
                'BTC',
                0.5,
                40000.0,
                0.0,
                null,
                3
            )
        ];

        $result = $engine->processTransactions($transactions);

        // Verify 3 BUYs processed
        $this->assertEquals(3, $result['summary']['buys']);
        $this->assertCount(3, $result['breakdowns']);

        // Verify balance
        $balances = $engine->getBalances();
        $balance = reset($balances);
        $this->assertEquals(1.5, $balance->getTotalBalance()); // 0.5 + 0.5 + 0.5
        $this->assertEquals(3, $balance->getLotCount());

        // Verify lots are in FIFO order (earliest first)
        $lots = $balance->getLots();
        $this->assertEquals(20000.0, $lots[0]->getCostPerUnit());
        $this->assertEquals(30000.0, $lots[1]->getCostPerUnit());
        $this->assertEquals(40000.0, $lots[2]->getCostPerUnit());
    }

    /**
     * Test: SELL consumes earliest lot (FIFO)
     */
    public function testSellConsumesEarliestLot(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY: 0.5 BTC @ R20,000/BTC on Jan 1
            new Transaction(
                new DateTime('2024-01-01'),
                'BUY',
                'ZAR',
                10000.0,
                'BTC',
                0.5,
                20000.0,
                0.0,
                null,
                1
            ),
            // BUY: 0.5 BTC @ R30,000/BTC on Jan 2
            new Transaction(
                new DateTime('2024-01-02'),
                'BUY',
                'ZAR',
                15000.0,
                'BTC',
                0.5,
                30000.0,
                0.0,
                null,
                2
            ),
            // SELL: 0.5 BTC @ R50,000/BTC on Jan 3
            new Transaction(
                new DateTime('2024-01-03'),
                'SELL',
                'BTC',
                0.5,
                'ZAR',
                25000.0,
                50000.0,
                0.0,
                null,
                3
            )
        ];

        $result = $engine->processTransactions($transactions);

        // Verify SELL breakdown
        $sellBreakdown = $result['breakdowns'][2];
        $this->assertEquals('SELL', $sellBreakdown['type']);
        $this->assertEquals(0.5, $sellBreakdown['amount']);
        $this->assertEquals(25000.0, $sellBreakdown['proceeds']);
        $this->assertEquals(10000.0, $sellBreakdown['costBase']); // From first lot @ R20,000
        $this->assertEquals(15000.0, $sellBreakdown['capitalGain']); // 25000 - 10000

        // Verify lots consumed
        $this->assertCount(1, $sellBreakdown['lotsConsumed']);
        $this->assertEquals(0.5, $sellBreakdown['lotsConsumed'][0]['amountConsumed']);
        $this->assertEquals(20000.0, $sellBreakdown['lotsConsumed'][0]['costPerUnit']);

        // Verify remaining balance (only second lot remains)
        $balances = $engine->getBalances();
        $balance = reset($balances);
        $this->assertEquals(0.5, $balance->getTotalBalance());
        $this->assertEquals(1, $balance->getLotCount());
        
        $lots = $balance->getLots();
        $this->assertEquals(30000.0, $lots[0]->getCostPerUnit()); // Second lot
    }

    /**
     * Test: Partial lot consumption
     */
    public function testPartialLotConsumption(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY: 1.0 BTC @ R20,000/BTC
            new Transaction(
                new DateTime('2024-01-01'),
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
            // SELL: 0.3 BTC @ R50,000/BTC (partial consumption)
            new Transaction(
                new DateTime('2024-01-02'),
                'SELL',
                'BTC',
                0.3,
                'ZAR',
                15000.0,
                50000.0,
                0.0,
                null,
                2
            )
        ];

        $result = $engine->processTransactions($transactions);

        // Verify SELL
        $sellBreakdown = $result['breakdowns'][1];
        $this->assertEquals(15000.0, $sellBreakdown['proceeds']);
        $this->assertEquals(6000.0, $sellBreakdown['costBase']); // 0.3 * 20000
        $this->assertEquals(9000.0, $sellBreakdown['capitalGain']); // 15000 - 6000

        // Verify partial lot consumption
        $this->assertCount(1, $sellBreakdown['lotsConsumed']);
        $this->assertEquals(0.3, $sellBreakdown['lotsConsumed'][0]['amountConsumed']);

        // Verify remaining balance (0.7 BTC left in first lot)
        $balances = $engine->getBalances();
        $balance = reset($balances);
        $this->assertEquals(0.7, $balance->getTotalBalance());
        $this->assertEquals(1, $balance->getLotCount());
        
        $lots = $balance->getLots();
        $this->assertEquals(0.7, $lots[0]->getAmount());
        $this->assertEquals(20000.0, $lots[0]->getCostPerUnit());
    }

    /**
     * Test: SELL consuming multiple lots
     */
    public function testSellConsumingMultipleLots(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY: 0.5 BTC @ R20,000/BTC
            new Transaction(
                new DateTime('2024-01-01'),
                'BUY',
                'ZAR',
                10000.0,
                'BTC',
                0.5,
                20000.0,
                0.0,
                null,
                1
            ),
            // BUY: 0.3 BTC @ R30,000/BTC
            new Transaction(
                new DateTime('2024-01-02'),
                'BUY',
                'ZAR',
                9000.0,
                'BTC',
                0.3,
                30000.0,
                0.0,
                null,
                2
            ),
            // SELL: 0.7 BTC @ R50,000/BTC (consumes both lots, partial on second)
            new Transaction(
                new DateTime('2024-01-03'),
                'SELL',
                'BTC',
                0.7,
                'ZAR',
                35000.0,
                50000.0,
                0.0,
                null,
                3
            )
        ];

        $result = $engine->processTransactions($transactions);

        // Verify SELL
        $sellBreakdown = $result['breakdowns'][2];
        $this->assertEquals(35000.0, $sellBreakdown['proceeds']);
        
        // Cost base: (0.5 * 20000) + (0.2 * 30000) = 10000 + 6000 = 16000
        $this->assertEquals(16000.0, $sellBreakdown['costBase']);
        $this->assertEquals(19000.0, $sellBreakdown['capitalGain']); // 35000 - 16000

        // Verify two lots consumed
        $this->assertCount(2, $sellBreakdown['lotsConsumed']);
        $this->assertEquals(0.5, $sellBreakdown['lotsConsumed'][0]['amountConsumed']); // First lot fully consumed
        $this->assertEqualsWithDelta(0.2, $sellBreakdown['lotsConsumed'][1]['amountConsumed'], 0.0001); // Second lot partially consumed

        // Verify remaining balance (0.1 BTC left in second lot)
        $balances = $engine->getBalances();
        $balance = reset($balances);
        $this->assertEqualsWithDelta(0.1, $balance->getTotalBalance(), 0.0001);
        $this->assertEquals(1, $balance->getLotCount());
        
        $lots = $balance->getLots();
        $this->assertEqualsWithDelta(0.1, $lots[0]->getAmount(), 0.0001);
        $this->assertEquals(30000.0, $lots[0]->getCostPerUnit());
    }

    /**
     * Test: TRADE behaves as SELL + BUY
     */
    public function testTradeAsComposition(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY: 1.0 BTC @ R20,000/BTC
            new Transaction(
                new DateTime('2024-01-01'),
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
            // TRADE: 0.5 BTC for 10 ETH (BTC @ R50,000, ETH @ R2,500)
            new Transaction(
                new DateTime('2024-01-02'),
                'TRADE',
                'BTC',
                0.5,
                'ETH',
                10.0,
                2500.0, // ETH price
                100.0,  // Fee
                null,
                2
            )
        ];

        $result = $engine->processTransactions($transactions);

        // Verify TRADE breakdown
        $tradeBreakdown = $result['breakdowns'][1];
        $this->assertEquals('TRADE', $tradeBreakdown['type']);
        $this->assertEquals('BTC', $tradeBreakdown['fromCurrency']);
        $this->assertEquals(0.5, $tradeBreakdown['fromAmount']);
        $this->assertEquals('ETH', $tradeBreakdown['toCurrency']);
        $this->assertEquals(10.0, $tradeBreakdown['toAmount']);

        // SELL portion: proceeds = value of ETH acquired = 10 * 2500 = 25000
        $this->assertEquals(25000.0, $tradeBreakdown['proceeds']);
        $this->assertEquals(10000.0, $tradeBreakdown['costBase']); // 0.5 * 20000
        $this->assertEquals(15000.0, $tradeBreakdown['capitalGain']); // 25000 - 10000

        // BUY portion: new ETH lot cost per unit = (25000 + 100) / 10 = 2510
        $this->assertEquals(2510.0, $tradeBreakdown['newLotCostPerUnit']);

        // Verify balances
        $balances = $engine->getBalances();
        $this->assertCount(2, $balances); // BTC and ETH

        // Check BTC balance (0.5 remaining)
        $btcBalance = null;
        $ethBalance = null;
        foreach ($balances as $balance) {
            if ($balance->getCurrency() === 'BTC') {
                $btcBalance = $balance;
            } elseif ($balance->getCurrency() === 'ETH') {
                $ethBalance = $balance;
            }
        }

        $this->assertNotNull($btcBalance);
        $this->assertEquals(0.5, $btcBalance->getTotalBalance());

        // Check ETH balance (10.0 acquired)
        $this->assertNotNull($ethBalance);
        $this->assertEquals(10.0, $ethBalance->getTotalBalance());
        $ethLots = $ethBalance->getLots();
        $this->assertEquals(2510.0, $ethLots[0]->getCostPerUnit());
    }

    /**
     * Test: Capital loss calculation
     */
    public function testCapitalLossCalculation(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY: 1.0 BTC @ R50,000/BTC
            new Transaction(
                new DateTime('2024-01-01'),
                'BUY',
                'ZAR',
                50000.0,
                'BTC',
                1.0,
                50000.0,
                0.0,
                null,
                1
            ),
            // SELL: 1.0 BTC @ R30,000/BTC (loss)
            new Transaction(
                new DateTime('2024-01-02'),
                'SELL',
                'BTC',
                1.0,
                'ZAR',
                30000.0,
                30000.0,
                0.0,
                null,
                2
            )
        ];

        $result = $engine->processTransactions($transactions);

        // Verify capital loss
        $sellBreakdown = $result['breakdowns'][1];
        $this->assertEquals(30000.0, $sellBreakdown['proceeds']);
        $this->assertEquals(50000.0, $sellBreakdown['costBase']);
        $this->assertEquals(-20000.0, $sellBreakdown['capitalGain']); // Loss

        // Verify summary
        $summary = $result['summary'];
        $this->assertEquals(30000.0, $summary['totalProceeds']);
        $this->assertEquals(50000.0, $summary['totalCostBase']);
        $this->assertEquals(0.0, $summary['totalCapitalGain']);
        $this->assertEquals(20000.0, $summary['totalCapitalLoss']);
        $this->assertEquals(-20000.0, $summary['netCapitalGain']);
    }

    /**
     * Test: Insufficient balance error
     */
    public function testInsufficientBalanceThrowsException(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // BUY: 0.5 BTC
            new Transaction(
                new DateTime('2024-01-01'),
                'BUY',
                'ZAR',
                10000.0,
                'BTC',
                0.5,
                20000.0,
                0.0,
                null,
                1
            ),
            // SELL: 1.0 BTC (more than available!)
            new Transaction(
                new DateTime('2024-01-02'),
                'SELL',
                'BTC',
                1.0,
                'ZAR',
                50000.0,
                50000.0,
                0.0,
                null,
                2
            )
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $engine->processTransactions($transactions);
    }

    /**
     * Test: Complex scenario with mixed transactions
     */
    public function testComplexScenario(): void
    {
        $engine = new FIFOEngine();
        
        $transactions = [
            // Day 1: Buy 1.0 BTC @ R20,000
            new Transaction(new DateTime('2024-01-01'), 'BUY', 'ZAR', 20000.0, 'BTC', 1.0, 20000.0, 0.0, null, 1),
            
            // Day 2: Buy 0.5 BTC @ R25,000
            new Transaction(new DateTime('2024-01-02'), 'BUY', 'ZAR', 12500.0, 'BTC', 0.5, 25000.0, 0.0, null, 2),
            
            // Day 3: Sell 0.8 BTC @ R40,000 (consumes all of first lot + 0.3 of second)
            new Transaction(new DateTime('2024-01-03'), 'SELL', 'BTC', 0.8, 'ZAR', 32000.0, 40000.0, 0.0, null, 3),
            
            // Day 4: Buy 10 ETH @ R2,000
            new Transaction(new DateTime('2024-01-04'), 'BUY', 'ZAR', 20000.0, 'ETH', 10.0, 2000.0, 0.0, null, 4),
            
            // Day 5: Trade 0.2 BTC for 5 ETH
            new Transaction(new DateTime('2024-01-05'), 'TRADE', 'BTC', 0.2, 'ETH', 5.0, 8000.0, 0.0, null, 5),
            
            // Day 6: Sell 8 ETH @ R3,000
            new Transaction(new DateTime('2024-01-06'), 'SELL', 'ETH', 8.0, 'ZAR', 24000.0, 3000.0, 0.0, null, 6)
        ];

        $result = $engine->processTransactions($transactions);

        // Verify transaction counts
        $this->assertEquals(6, $result['summary']['transactionsProcessed']);
        $this->assertEquals(3, $result['summary']['buys']);
        $this->assertEquals(2, $result['summary']['sells']);
        $this->assertEquals(1, $result['summary']['trades']);

        // Verify final balances
        $balances = $engine->getBalances();
        $this->assertCount(2, $balances); // BTC and ETH

        $btcBalance = null;
        $ethBalance = null;
        foreach ($balances as $balance) {
            if ($balance->getCurrency() === 'BTC') {
                $btcBalance = $balance;
            } elseif ($balance->getCurrency() === 'ETH') {
                $ethBalance = $balance;
            }
        }

        // BTC: Started with 1.5, sold 0.8, traded 0.2 => 0.5 remaining
        $this->assertEqualsWithDelta(0.5, $btcBalance->getTotalBalance(), 0.0001);

        // ETH: Bought 10, received 5 from trade, sold 8 => 7 remaining
        $this->assertEqualsWithDelta(7.0, $ethBalance->getTotalBalance(), 0.0001);

        // Verify net capital gain is positive
        $this->assertGreaterThan(0, $result['summary']['netCapitalGain']);
    }
}
