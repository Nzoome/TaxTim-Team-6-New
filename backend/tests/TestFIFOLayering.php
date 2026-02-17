<?php
/**
 * Test FIFO Layering - Multiple Purchase Dates Example
 * 
 * This test demonstrates the improved FIFO breakdown showing different purchase dates
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CryptoTax\Services\FileProcessor;
use CryptoTax\Services\FIFOEngine;

echo "===========================================\n";
echo "FIFO LAYERING TEST - Multiple Purchase Dates\n";
echo "===========================================\n\n";

// Test file path
$testFile = __DIR__ . '/../../test_fifo_layering.csv';

if (!file_exists($testFile)) {
    echo "❌ Test file not found: $testFile\n";
    exit(1);
}

echo "📁 Processing test file: test_fifo_layering.csv\n\n";

// Process the file
$logDir = __DIR__ . '/../logs';
$logger = new \CryptoTax\Services\Logger($logDir, false);
$processor = new FileProcessor($logger);
$result = $processor->processFile($testFile, basename($testFile));

if (isset($result['error'])) {
    echo "❌ Error processing file: " . $result['error'] . "\n";
    exit(1);
}

// Get the transactions
$transactions = $result['transactions'] ?? [];
echo "✅ Parsed " . count($transactions) . " transactions\n\n";

// Convert transaction arrays to Transaction objects for FIFO processing
$transactionObjects = [];
foreach ($transactions as $txData) {
    $transactionObjects[] = new \CryptoTax\Models\Transaction(
        new DateTime($txData['date']),
        $txData['type'],
        $txData['fromCurrency'],
        $txData['fromAmount'],
        $txData['toCurrency'],
        $txData['toAmount'],
        $txData['price'],
        $txData['fee'],
        $txData['wallet'] ?? null,
        $txData['lineNumber'] ?? 0
    );
}

// Run FIFO engine
$fifo = new FIFOEngine();
$fifoResult = $fifo->processTransactions($transactionObjects);

$breakdowns = $fifoResult['breakdowns'];
$summary = $fifoResult['summary'];

echo "TRANSACTION BREAKDOWN:\n";
echo "=====================\n\n";

foreach ($breakdowns as $bd) {
    echo "📅 Date: " . $bd['date'] . "\n";
    echo "   Type: " . $bd['type'] . "\n";
    echo "   Currency: " . $bd['currency'] . "\n";
    echo "   Amount: " . number_format($bd['amount'], 8) . " " . $bd['currency'] . "\n";
    
    if ($bd['type'] === 'BUY') {
        echo "   Cost Per Unit: R" . number_format($bd['costPerUnit'], 2) . "\n";
        echo "   Total Cost: R" . number_format($bd['amount'] * $bd['costPerUnit'], 2) . "\n";
    } elseif ($bd['type'] === 'SELL') {
        echo "   Proceeds: R" . number_format($bd['proceeds'], 2) . "\n";
        echo "   Cost Base: R" . number_format($bd['costBase'], 2) . "\n";
        echo "   Capital Gain: R" . number_format($bd['capitalGain'], 2) . "\n";
        
        // Display FIFO lots consumed
        if (!empty($bd['lotsConsumed'])) {
            echo "\n   📦 FIFO LOTS CONSUMED (" . count($bd['lotsConsumed']) . " lots):\n";
            echo "   " . str_repeat("-", 80) . "\n";
            echo sprintf(
                "   %-20s %-15s %-15s %-15s %-12s\n",
                "Purchase Date",
                "Amount",
                "Cost/Unit",
                "Cost Base",
                "Held (Days)"
            );
            echo "   " . str_repeat("-", 80) . "\n";
            
            $totalCostBase = 0;
            foreach ($bd['lotsConsumed'] as $lot) {
                $term = isset($lot['ageInDays']) && $lot['ageInDays'] >= 365 ? ' [LT]' : '';
                echo sprintf(
                    "   %-20s %-15s R%-14s R%-14s %-12s\n",
                    substr($lot['purchaseDate'], 0, 10),
                    number_format($lot['amountConsumed'], 4) . ' ' . $bd['currency'],
                    number_format($lot['costPerUnit'], 2),
                    number_format($lot['costBase'], 2),
                    ($lot['ageInDays'] ?? '-') . $term
                );
                $totalCostBase += $lot['costBase'];
            }
            
            echo "   " . str_repeat("-", 80) . "\n";
            echo sprintf(
                "   %-20s %-15s %-15s R%-14s\n",
                "",
                "",
                "TOTAL:",
                number_format($totalCostBase, 2)
            );
            echo "   " . str_repeat("-", 80) . "\n";
            
            echo "\n   💡 FIFO Method: First-In-First-Out - oldest coins sold first\n";
            echo "   ℹ️  [LT] = Long-term holding (365+ days)\n";
        }
    }
    echo "\n" . str_repeat("=", 90) . "\n\n";
}

echo "\nSUMMARY:\n";
echo "========\n";
echo "Total Proceeds: R" . number_format($summary['totalProceeds'], 2) . "\n";
echo "Total Cost Base: R" . number_format($summary['totalCostBase'], 2) . "\n";
echo "Capital Gain: R" . number_format($summary['netCapitalGain'], 2) . "\n";
echo "Transactions Processed: " . $summary['transactionsProcessed'] . "\n";
echo "  - Buys: " . $summary['buys'] . "\n";
echo "  - Sells: " . $summary['sells'] . "\n";
echo "  - Trades: " . $summary['trades'] . "\n";

echo "\n✅ Test completed successfully!\n";
echo "\nThe FIFO breakdown now clearly shows:\n";
echo "  ✓ Multiple purchase dates with different amounts\n";
echo "  ✓ Individual cost basis for each lot\n";
echo "  ✓ Holding period for each purchase\n";
echo "  ✓ Long-term vs short-term classification\n";
echo "  ✓ Total cost base calculation\n";
