# Sprint 2 - FIFO Engine Quick Reference

## Core Concepts

### FIFO (First-In-First-Out)
- **Rule**: When you sell crypto, you sell the oldest coins first
- **Why**: Required by tax authorities for capital gains calculation
- **Example**: 
  - Buy 1 BTC @ R100,000 (Jan 1)
  - Buy 1 BTC @ R150,000 (Feb 1)
  - Sell 1 BTC @ R200,000 (Mar 1)
  - **FIFO**: You sold the Jan 1 lot → Gain = R200,000 - R100,000 = R100,000

---

## Key Classes

### 1. `BalanceLot`
**Purpose**: Represents a single "parcel" of cryptocurrency

**Key Properties**:
- `amount` - How many coins in this lot
- `costPerUnit` - Cost per coin (ZAR) including fees
- `acquisitionDate` - When this lot was acquired

**Key Methods**:
- `consume(float $amount)` - Reduce lot by specified amount
- `isFullyConsumed()` - Check if lot is empty
- `getTotalCostBase()` - Get total cost of this lot

**Example**:
```php
$lot = new BalanceLot(
    1.0,           // amount
    500000.0,      // cost per unit (R500,000/BTC)
    new DateTime('2024-01-01'),
    'BTC',
    'Luno',
    1              // original transaction line
);

$lot->consume(0.3); // Consume 0.3 BTC
// Now lot has 0.7 BTC remaining
```

---

### 2. `CoinBalance`
**Purpose**: Maintains FIFO queue of lots for a specific currency/wallet

**Key Properties**:
- `currency` - Cryptocurrency (e.g., 'BTC')
- `wallet` - Wallet identifier (e.g., 'Luno')
- `lots` - Array of `BalanceLot` objects (FIFO queue)
- `totalBalance` - Sum of all lot amounts

**Key Methods**:
- `addLot(BalanceLot $lot)` - Add new lot to end of queue (BUY)
- `consumeLots(float $amount)` - Consume from front of queue (SELL)
- `getTotalCostBase()` - Total cost base across all lots
- `getAverageCostPerUnit()` - Weighted average cost

**Example**:
```php
$balance = new CoinBalance('BTC', 'Luno');

// Add lots (BUY transactions)
$balance->addLot($lot1); // 1.0 BTC @ R500,000
$balance->addLot($lot2); // 0.5 BTC @ R550,000

// Consume lots (SELL transaction)
$records = $balance->consumeLots(0.8); // Consumes from oldest first
// $records contains details of which lots were used
```

---

### 3. `FIFOEngine`
**Purpose**: Main calculation engine - processes transactions and calculates gains

**Key Methods**:
- `processTransactions(array $transactions)` - Process all transactions
- `getBalances()` - Get current FIFO balances
- `getTransactionBreakdowns()` - Get per-transaction gain/loss details
- `getSummary()` - Get aggregate statistics

**Private Methods** (Internal):
- `handleBuy()` - Create and add FIFO lot
- `handleSell()` - Consume lots and calculate gain/loss
- `handleTrade()` - Internally: SELL + BUY

**Example**:
```php
$engine = new FIFOEngine();
$result = $engine->processTransactions($transactions);

// Access results
$totalGain = $result['summary']['netCapitalGain'];
$breakdowns = $result['breakdowns'];
$balances = $result['balances'];
```

---

## Transaction Processing Flow

### BUY Transaction
```
Input: BUY 1.0 BTC @ R500,000 (fee: R5,000)

Processing:
1. Calculate total cost = R500,000 + R5,000 = R505,000
2. Calculate cost per unit = R505,000 / 1.0 = R505,000/BTC
3. Create BalanceLot(1.0, R505,000, date, 'BTC', wallet)
4. Add lot to CoinBalance queue

Output:
- New lot added to FIFO queue
- No capital gain (acquisitions don't trigger tax)
```

### SELL Transaction
```
Input: SELL 0.6 BTC @ R650,000 (fee: R4,000)

Existing Lots (FIFO queue):
  Lot 1: 1.0 BTC @ R505,000 (Jan 1)
  Lot 2: 0.5 BTC @ R555,000 (Jan 5)

Processing:
1. Calculate proceeds = (0.6 * R650,000) - R4,000 = R386,000
2. Consume 0.6 BTC from FIFO queue:
   - Consume 0.6 from Lot 1 (leaving 0.4 remaining)
3. Calculate cost base = 0.6 * R505,000 = R303,000
4. Calculate gain = R386,000 - R303,000 = R83,000

Output:
- Proceeds: R386,000
- Cost Base: R303,000
- Capital Gain: R83,000
- Lots Consumed: [Lot 1: 0.6 BTC @ R505,000]

Remaining Queue:
  Lot 1: 0.4 BTC @ R505,000 (Jan 1)
  Lot 2: 0.5 BTC @ R555,000 (Jan 5)
```

### TRADE Transaction
```
Input: TRADE 0.3 BTC for 5 ETH (ETH @ R35,000, fee: R1,000)

Existing BTC Lots:
  Lot 1: 0.4 BTC @ R505,000

Processing:
PART 1 - SELL (dispose of BTC):
1. Proceeds = 5 ETH * R35,000 = R175,000
2. Consume 0.3 BTC from FIFO queue
3. Cost base = 0.3 * R505,000 = R151,500
4. Gain = R175,000 - R151,500 = R23,500

PART 2 - BUY (acquire ETH):
5. Total cost = R175,000 + R1,000 = R176,000
6. Cost per unit = R176,000 / 5 = R35,200/ETH
7. Create new ETH lot: 5.0 ETH @ R35,200

Output:
- BTC: Disposed 0.3, Gain: R23,500
- ETH: Acquired 5.0 @ R35,200/ETH
- New ETH lot added to queue

Remaining:
  BTC Lot 1: 0.1 BTC @ R505,000
  ETH Lot 1: 5.0 ETH @ R35,200 (NEW)
```

---

## Result Structure

### Summary
```php
[
    'totalProceeds' => 878000.00,       // Total from all sales
    'totalCostBase' => 696900.00,       // Total cost of sold assets
    'totalCapitalGain' => 181100.00,    // Sum of all gains
    'totalCapitalLoss' => 0.00,         // Sum of all losses
    'netCapitalGain' => 181100.00,      // Gains - Losses
    'transactionsProcessed' => 7,
    'buys' => 4,
    'sells' => 2,
    'trades' => 1
]
```

### Breakdown (per transaction)
```php
// BUY
[
    'type' => 'BUY',
    'currency' => 'BTC',
    'amount' => 1.0,
    'totalCost' => 505000.00,
    'costPerUnit' => 505000.00,
    'fee' => 5000.00,
    'proceeds' => null,
    'costBase' => null,
    'capitalGain' => null
]

// SELL
[
    'type' => 'SELL',
    'currency' => 'BTC',
    'amount' => 0.6,
    'proceeds' => 386000.00,
    'costBase' => 303000.00,
    'capitalGain' => 83000.00,
    'lotsConsumed' => [
        [
            'amountConsumed' => 0.6,
            'costBase' => 303000.00,
            'costPerUnit' => 505000.00,
            'acquisitionDate' => '2024-01-01 00:00:00',
            'originalTransaction' => 1
        ]
    ]
]

// TRADE
[
    'type' => 'TRADE',
    'fromCurrency' => 'BTC',
    'fromAmount' => 0.3,
    'toCurrency' => 'ETH',
    'toAmount' => 5.0,
    'proceeds' => 175000.00,
    'costBase' => 151500.00,
    'capitalGain' => 23500.00,
    'newLotCostPerUnit' => 35200.00,
    'lotsConsumed' => [...]
]
```

### Balance (remaining lots)
```php
[
    'currency' => 'BTC',
    'wallet' => 'Luno',
    'totalBalance' => 0.8,
    'totalCostBase' => 449000.00,
    'averageCostPerUnit' => 561250.00,
    'lotCount' => 3,
    'lots' => [
        [
            'amount' => 0.1,
            'costPerUnit' => 505000.00,
            'acquisitionDate' => '2024-01-01 00:00:00'
        ],
        [
            'amount' => 0.5,
            'costPerUnit' => 555000.00,
            'acquisitionDate' => '2024-01-05 00:00:00'
        ],
        [
            'amount' => 0.2,
            'costPerUnit' => 605000.00,
            'acquisitionDate' => '2024-03-10 00:00:00'
        ]
    ]
]
```

---

## Common Patterns

### Check if sufficient balance before SELL
```php
$balance = $engine->getBalance('BTC', 'Luno');
if ($balance->getTotalBalance() < $amountToSell) {
    throw new RuntimeException('Insufficient balance');
}
```

### Get cost base of next disposal (without consuming)
```php
$balance = $engine->getBalance('BTC', null);
$lots = $balance->getLots();
$nextLotCost = $lots[0]->getCostPerUnit(); // Oldest lot
```

### Calculate unrealized gain
```php
$balance = $engine->getBalance('BTC', null);
$totalCostBase = $balance->getTotalCostBase();
$currentValue = $balance->getTotalBalance() * $currentMarketPrice;
$unrealizedGain = $currentValue - $totalCostBase;
```

---

## Testing Commands

### Run all FIFO tests:
```bash
cd backend
php vendor/bin/phpunit tests/FIFOEngineTest.php --testdox
```

### Run demonstration:
```bash
php backend/tests/demo_fifo.php
```

### Run specific test:
```bash
php vendor/bin/phpunit tests/FIFOEngineTest.php --filter testPartialLotConsumption
```

---

## Edge Cases Handled

✅ **Partial lot consumption** - Lot can be split across multiple sales  
✅ **Multiple lots consumed in one sale** - Sale can span multiple lots  
✅ **Insufficient balance** - Throws exception if trying to sell more than owned  
✅ **Floating-point precision** - Uses epsilon for comparisons  
✅ **Wallet separation** - Each wallet has independent FIFO queues  
✅ **Fee handling** - Fees increase cost base (BUY) or reduce proceeds (SELL)  
✅ **Capital losses** - Correctly handles negative gains  

---

## What's NOT in Sprint 2

❌ Tax year allocation  
❌ CGT exclusion (R40,000 annual)  
❌ Year-end reports  
❌ CSV/PDF exports  
❌ UI for FIFO visualization  

These come in Sprint 3+.

---

## Quick Debugging

### Check current lots:
```php
$balances = $engine->getBalances();
foreach ($balances as $balance) {
    echo "{$balance->getCurrency()}: {$balance->getTotalBalance()}\n";
    print_r($balance->getLots());
}
```

### Verify FIFO order:
```php
$lots = $balance->getLots();
foreach ($lots as $i => $lot) {
    echo "Lot $i: {$lot->getAmount()} @ {$lot->getCostPerUnit()} on {$lot->getAcquisitionDate()->format('Y-m-d')}\n";
}
```

### Trace consumption:
```php
$breakdown = $result['breakdowns'][3]; // 4th transaction
if ($breakdown['type'] === 'SELL') {
    foreach ($breakdown['lotsConsumed'] as $consumed) {
        echo "Consumed {$consumed['amountConsumed']} from lot acquired {$consumed['acquisitionDate']}\n";
    }
}
```

---

## Key Formulas

### Cost Per Unit (BUY)
```
costPerUnit = (totalPaid + fees) / amountReceived
```

### Proceeds (SELL)
```
proceeds = (amountSold * pricePerUnit) - fees
```

### Cost Base (SELL)
```
costBase = Σ(amountConsumed * lotCostPerUnit)
           for each lot consumed
```

### Capital Gain/Loss
```
capitalGain = proceeds - costBase
```

---

**Sprint 2 Complete** ✅  
**FIFO Engine: Operational and Tested**

