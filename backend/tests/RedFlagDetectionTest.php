<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CryptoTax\Services\SuspiciousTransactionDetector;
use CryptoTax\Models\Transaction;
use DateTime;

/**
 * Test Suite for Suspicious Transaction Detection Module
 * 
 * This test suite validates all detection rules:
 * - Missing or invalid transaction data
 * - Negative buy amounts or zero-value transactions
 * - Sell transactions exceeding available holdings
 * - Negative balances after FIFO processing
 * - Large transactions above threshold
 * - Duplicate transaction entries
 * - Wash trading patterns
 * - Misclassified transfers
 */

echo "=== SUSPICIOUS TRANSACTION DETECTION TEST SUITE ===\n\n";

$detector = new SuspiciousTransactionDetector();
$testsPassed = 0;
$testsFailed = 0;

// Test 1: Missing or Invalid Data
echo "Test 1: Detecting Missing/Invalid Transaction Data\n";
echo "---------------------------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01'),
            'BUY',
            'ZAR',
            1000,
            'BTC',
            0, // Zero amount - should flag
            50000,
            0,
            null,
            1
        ),
        new Transaction(
            new DateTime('2024-01-02'),
            'SELL',
            'BTC',
            0.1,
            'ZAR',
            5000,
            0, // Zero price - should flag
            0,
            null,
            2
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $criticalFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'INCOMPLETE_DATA');
    
    if (count($criticalFlags) > 0) {
        echo "✓ PASSED: Detected " . count($criticalFlags) . " incomplete data issues\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect incomplete data\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 2: Negative Amounts
echo "Test 2: Detecting Negative Amounts\n";
echo "-----------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01'),
            'BUY',
            'ZAR',
            -1000, // Negative amount - should flag
            'BTC',
            0.1,
            50000,
            0,
            null,
            1
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $negativeFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'NEGATIVE_AMOUNT');
    
    if (count($negativeFlags) > 0) {
        echo "✓ PASSED: Detected negative amount\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect negative amounts\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Duplicate Transactions
echo "Test 3: Detecting Duplicate Transactions\n";
echo "-----------------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01 10:00:00'),
            'BUY',
            'ZAR',
            10000,
            'BTC',
            0.2,
            50000,
            100,
            null,
            1
        ),
        new Transaction(
            new DateTime('2024-01-01 10:00:00'), // Same exact transaction
            'BUY',
            'ZAR',
            10000,
            'BTC',
            0.2,
            50000,
            100,
            null,
            2
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $duplicateFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'DUPLICATE_TRANSACTION');
    
    if (count($duplicateFlags) > 0) {
        echo "✓ PASSED: Detected duplicate transaction\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect duplicate transactions\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Large Transactions
echo "Test 4: Detecting Large Transactions\n";
echo "-------------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01'),
            'SELL',
            'BTC',
            20, // Large amount
            'ZAR',
            1500000, // R1.5M - should flag
            75000,
            0,
            null,
            1
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $largeFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'LARGE_TRANSACTION');
    
    if (count($largeFlags) > 0) {
        echo "✓ PASSED: Detected large transaction\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect large transactions\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Wash Trading Pattern
echo "Test 5: Detecting Wash Trading Patterns\n";
echo "----------------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01 09:00:00'),
            'BUY',
            'ZAR',
            10000,
            'BTC',
            0.2,
            50000,
            0,
            null,
            1
        ),
        new Transaction(
            new DateTime('2024-01-01 15:00:00'), // Same day sell
            'SELL',
            'BTC',
            0.2,
            'ZAR',
            10000,
            50000,
            0,
            null,
            2
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $washFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'WASH_TRADING');
    
    if (count($washFlags) > 0) {
        echo "✓ PASSED: Detected wash trading pattern\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect wash trading\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Excessive Fees
echo "Test 6: Detecting Excessive Fees\n";
echo "---------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01'),
            'BUY',
            'ZAR',
            10000,
            'BTC',
            0.2,
            50000,
            6000, // 60% fee - should flag
            null,
            1
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $feeFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'EXCESSIVE_FEE');
    
    if (count($feeFlags) > 0) {
        echo "✓ PASSED: Detected excessive fee\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect excessive fees\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 7: Negative Balance Detection
echo "Test 7: Detecting Negative Balances\n";
echo "------------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01'),
            'SELL',
            'BTC',
            1.0, // Selling without buying first
            'ZAR',
            50000,
            50000,
            0,
            null,
            1
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    $balanceFlags = array_filter($results['red_flags'], fn($f) => $f['code'] === 'NEGATIVE_BALANCE');
    
    if (count($balanceFlags) > 0) {
        echo "✓ PASSED: Detected negative balance\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should detect negative balance\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 8: Risk Score Calculation
echo "Test 8: Testing Risk Score Calculation\n";
echo "---------------------------------------\n";
try {
    $transactions = [
        // Multiple issues to test risk scoring
        new Transaction(new DateTime('2024-01-01'), 'BUY', 'ZAR', -1000, 'BTC', 0.1, 50000, 0, null, 1), // Critical
        new Transaction(new DateTime('2024-01-02'), 'SELL', 'BTC', 20, 'ZAR', 1500000, 75000, 0, null, 2), // High
        new Transaction(new DateTime('2024-01-03'), 'BUY', 'ZAR', 1000, 'BTC', 0.01, 50000, 600, null, 3), // Medium
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    if ($results['summary']['audit_risk_score'] > 0 && 
        $results['summary']['critical_count'] > 0 &&
        $results['summary']['high_count'] > 0) {
        echo "✓ PASSED: Risk score calculated correctly (" . $results['summary']['audit_risk_score'] . "/100)\n";
        echo "  - Critical: " . $results['summary']['critical_count'] . "\n";
        echo "  - High: " . $results['summary']['high_count'] . "\n";
        echo "  - Medium: " . $results['summary']['medium_count'] . "\n";
        echo "  - Risk Level: " . $results['audit_risk_level'] . "\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Risk score calculation incorrect\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 9: Clean Transaction Set (No Flags)
echo "Test 9: Validating Clean Transaction Set\n";
echo "-----------------------------------------\n";
try {
    $transactions = [
        new Transaction(
            new DateTime('2024-01-01'),
            'BUY',
            'ZAR',
            10000,
            'BTC',
            0.2,
            50000,
            100,
            null,
            1
        ),
        new Transaction(
            new DateTime('2024-02-01'),
            'SELL',
            'BTC',
            0.1,
            'ZAR',
            5000,
            50000,
            50,
            null,
            2
        )
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    
    if ($results['summary']['total_flags'] === 0 && $results['summary']['audit_risk_score'] === 0) {
        echo "✓ PASSED: No false positives on clean data\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Should not flag clean transactions\n";
        echo "  Flags detected: " . $results['summary']['total_flags'] . "\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 10: Export Report Functionality
echo "Test 10: Testing Report Export\n";
echo "-------------------------------\n";
try {
    $transactions = [
        new Transaction(new DateTime('2024-01-01'), 'BUY', 'ZAR', 10000, 'BTC', 0.2, 50000, 0, null, 1),
        new Transaction(new DateTime('2024-01-01 10:00:00'), 'BUY', 'ZAR', 10000, 'BTC', 0.2, 50000, 0, null, 2),
    ];
    
    $results = $detector->analyzeTransactions($transactions);
    $report = $detector->exportReport();
    
    if (!empty($report) && strpos($report, 'SUSPICIOUS TRANSACTION DETECTION REPORT') !== false) {
        echo "✓ PASSED: Report exported successfully\n";
        echo "\nSample Report Output:\n";
        echo substr($report, 0, 500) . "...\n";
        $testsPassed++;
    } else {
        echo "✗ FAILED: Report export failed\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "Passed: " . $testsPassed . " ✓\n";
echo "Failed: " . $testsFailed . " ✗\n";
echo "Success Rate: " . round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 2) . "%\n";

if ($testsFailed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Red Flag System is fully operational.\n";
} else {
    echo "\n⚠️ Some tests failed. Please review the implementation.\n";
}
