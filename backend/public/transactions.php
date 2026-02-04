<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use CryptoTax\Services\FIFOEngine;
use CryptoTax\Models\Transaction;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Read from file storage
$dataFile = __DIR__ . '/../logs/latest_transactions.json';

try {
    // Check if we have stored transactions
    if (!file_exists($dataFile)) {
        // Return empty data if no file has been processed yet
        echo json_encode([
            'success' => true,
            'data' => [
                'transactions' => [],
                'summary' => [
                    'total_transactions' => 0,
                    'transaction_types' => [],
                    'currencies' => [],
                    'date_range' => [
                        'earliest' => null,
                        'latest' => null
                    ]
                ],
                'analytics' => [
                    'total_proceeds' => 0,
                    'total_cost_base' => 0,
                    'capital_gain' => 0,
                    'transaction_history' => [],
                    'transaction_breakdown' => []
                ]
            ]
        ]);
        exit();
    }

    // Read and decode the stored data
    $jsonData = file_get_contents($dataFile);
    $storedData = json_decode($jsonData, true);
    
    if (!$storedData || !isset($storedData['transactions']) || !isset($storedData['summary'])) {
        // Return empty data if file is corrupted
        echo json_encode([
            'success' => true,
            'data' => [
                'transactions' => [],
                'summary' => [
                    'total_transactions' => 0,
                    'transaction_types' => [],
                    'currencies' => [],
                    'date_range' => [
                        'earliest' => null,
                        'latest' => null
                    ]
                ],
                'analytics' => [
                    'total_proceeds' => 0,
                    'total_cost_base' => 0,
                    'capital_gain' => 0,
                    'transaction_history' => [],
                    'transaction_breakdown' => []
                ]
            ]
        ]);
        exit();
    }

    $transactions = $storedData['transactions'];
    $summary = $storedData['summary'];

    // Convert transaction arrays back to Transaction objects for FIFO processing
    $transactionObjects = [];
    foreach ($transactions as $txData) {
        $transactionObjects[] = new Transaction(
            new DateTime($txData['date']),
            $txData['type'],
            $txData['fromCurrency'] ?? $txData['from_currency'] ?? null,
            floatval($txData['fromAmount'] ?? $txData['from_amount'] ?? 0),
            $txData['toCurrency'] ?? $txData['to_currency'] ?? null,
            floatval($txData['toAmount'] ?? $txData['to_amount'] ?? 0),
            floatval($txData['price']),
            floatval($txData['fee'] ?? 0.0),
            $txData['wallet'] ?? null,
            intval($txData['originalLineNumber'] ?? $txData['line_number'] ?? 0)
        );
    }

    // Run FIFO engine to calculate capital gains
    $fifoEngine = new FIFOEngine();
    $fifoResults = $fifoEngine->processTransactions($transactionObjects);

    // Prepare analytics using FIFO results
    $analytics = [
        'total_proceeds' => $fifoResults['summary']['totalProceeds'],
        'total_cost_base' => $fifoResults['summary']['totalCostBase'],
        'capital_gain' => $fifoResults['summary']['netCapitalGain'],
        'total_capital_gain' => $fifoResults['summary']['totalCapitalGain'],
        'total_capital_loss' => $fifoResults['summary']['totalCapitalLoss'],
        'transactions_processed' => $fifoResults['summary']['transactionsProcessed'],
        'buys' => $fifoResults['summary']['buys'],
        'sells' => $fifoResults['summary']['sells'],
        'trades' => $fifoResults['summary']['trades'],
        'transaction_history' => calculateTransactionHistory($transactions),
        'transaction_breakdown' => calculateTransactionBreakdown($transactions),
        'fifo_breakdowns' => $fifoResults['breakdowns'],
        'current_balances' => $fifoResults['balances']
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'transactions' => $transactions,
            'summary' => $summary,
            'analytics' => $analytics
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in transactions.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

function calculateTransactionHistory($transactions) {
    if (empty($transactions)) {
        return [];
    }

    $assetCounts = [];
    
    foreach ($transactions as $transaction) {
        $type = strtoupper($transaction['type']);
        
        if ($type === 'SELL') {
            $asset = $transaction['fromCurrency'] ?? $transaction['from_currency'];
            $assetCounts[$asset] = ($assetCounts[$asset] ?? 0) + 1;
        } elseif ($type === 'BUY') {
            $asset = $transaction['toCurrency'] ?? $transaction['to_currency'];
            $assetCounts[$asset] = ($assetCounts[$asset] ?? 0) + 1;
        } else {
            // TRADE
            $assetFrom = $transaction['fromCurrency'] ?? $transaction['from_currency'];
            $assetTo = $transaction['toCurrency'] ?? $transaction['to_currency'];
            $assetCounts[$assetFrom] = ($assetCounts[$assetFrom] ?? 0) + 1;
            $assetCounts[$assetTo] = ($assetCounts[$assetTo] ?? 0) + 1;
        }
    }

    $transactionHistory = [];
    foreach ($assetCounts as $asset => $count) {
        $transactionHistory[] = [
            'name' => $asset,
            'value' => $count
        ];
    }

    usort($transactionHistory, function($a, $b) {
        return $b['value'] - $a['value'];
    });

    return $transactionHistory;
}

function calculateTransactionBreakdown($transactions) {
    if (empty($transactions)) {
        return [];
    }

    $typeCounts = ['BUY' => 0, 'SELL' => 0, 'TRADE' => 0];

    foreach ($transactions as $transaction) {
        $type = strtoupper($transaction['type']);
        $typeCounts[$type]++;
    }

    $totalTypes = array_sum($typeCounts);
    $transactionBreakdown = [];
    
    if ($typeCounts['SELL'] > 0) {
        $transactionBreakdown[] = [
            'name' => 'Selling',
            'value' => round(($typeCounts['SELL'] / $totalTypes) * 100),
            'color' => '#ef4444'
        ];
    }
    if ($typeCounts['BUY'] > 0) {
        $transactionBreakdown[] = [
            'name' => 'Buying',
            'value' => round(($typeCounts['BUY'] / $totalTypes) * 100),
            'color' => '#8b5cf6'
        ];
    }
    if ($typeCounts['TRADE'] > 0) {
        $transactionBreakdown[] = [
            'name' => 'Transfers',
            'value' => round(($typeCounts['TRADE'] / $totalTypes) * 100),
            'color' => '#06b6d4'
        ];
    }

    return $transactionBreakdown;
}
