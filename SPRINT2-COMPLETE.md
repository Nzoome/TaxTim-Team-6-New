# Sprint 2 - FIFO Calculation Engine - COMPLETE ✓

**Sprint Goal**: Implement a correct, traceable FIFO engine that calculates cost base, proceeds, and capital gains per disposal.

**Completion Date**: January 29, 2026

---

## Sprint 2 Implementation Summary

### ✅ Core Components Delivered

#### 1. **FIFO Data Structures** (`BalanceLot` and `CoinBalance`)

**File**: `backend/src/Models/BalanceLot.php`
- Represents a single FIFO lot (parcel) of cryptocurrency
- Tracks: amount, cost per unit, acquisition date, currency, wallet, transaction reference
- Supports partial consumption with `consume()` method
- Automatically detects when fully consumed

**File**: `backend/src/Models/CoinBalance.php`
- Maintains a FIFO queue of `BalanceLot` objects per cryptocurrency
- Supports wallet-level tracking (separate queues per wallet)
- `addLot()` - Adds new lots to the end of the queue (BUY)
- `consumeLots()` - Consumes lots from the front (FIFO) with partial lot support
- Tracks total balance and cost base across all lots

#### 2. **FIFO Engine** (`FIFOEngine`)

**File**: `backend/src/Services/FIFOEngine.php`

**Canonical Data Flow** (Strictly Enforced):
1. Read ordered Transaction objects
2. Maintain FIFO queues per coin (and wallet)
3. Consume lots on SELL / TRADE
4. Calculate cost base and proceeds
5. Produce per-transaction breakdown

**Transaction Handlers**:

- **`handleBuy()`**
  - Creates new `BalanceLot` with amount and cost per unit
  - Includes fees in cost base (increases cost per unit)
  - Adds lot to the end of the FIFO queue

- **`handleSell()`**
  - Consumes lots from front of FIFO queue (earliest first)
  - Supports partial lot consumption
  - Calculates proceeds (sale price - fees)
  - Calculates cost base from consumed lots
  - Calculates capital gain/loss (proceeds - cost base)
  - Records which lots were consumed for traceability

- **`handleTrade()`**
  - Internally splits into SELL + BUY operations
  - SELL: Disposes of "from" currency (calculates gain/loss)
  - BUY: Acquires "to" currency (creates new lot)
  - Proceeds = value of currency received
  - New lot cost = proceeds + fees

#### 3. **Comprehensive Test Suite**

**File**: `backend/tests/FIFOEngineTest.php`

**Tests Implemented** (9 tests, 79 assertions - ALL PASSING ✓):
1. ✓ BUY creates lot
2. ✓ Multiple BUYs stack correctly in FIFO order
3. ✓ SELL consumes earliest lot (FIFO)
4. ✓ Partial lot consumption
5. ✓ SELL consuming multiple lots
6. ✓ TRADE behaves as SELL + BUY composition
7. ✓ Capital loss calculation
8. ✓ Insufficient balance throws exception
9. ✓ Complex scenario with mixed transactions

#### 4. **API Integration**

**File**: `backend/public/transactions.php`

Updated to:
- Convert stored transaction data to `Transaction` objects
- Run FIFO engine on all transactions
- Return FIFO results in API response:
  - `analytics.total_proceeds` - Total proceeds from all disposals
  - `analytics.total_cost_base` - Total cost base of disposed assets
  - `analytics.capital_gain` - Net capital gain/loss
  - `analytics.fifo_breakdowns` - Detailed per-transaction breakdown
  - `analytics.current_balances` - Remaining FIFO lots by currency/wallet

---

## Sprint 2 Completion Criteria - Verification

| Criterion | Status | Evidence |
|-----------|--------|----------|
| FIFO queues maintained per coin and wallet | ✅ PASS | `CoinBalance` tracks separate queues per currency/wallet combination |
| BUY creates lots | ✅ PASS | `handleBuy()` creates `BalanceLot` and adds to queue |
| SELL consumes lots FIFO | ✅ PASS | `consumeLots()` processes from front of queue (earliest first) |
| Partial lot consumption works | ✅ PASS | `BalanceLot.consume()` handles partial consumption; tests verify |
| TRADE behaves as SELL + BUY | ✅ PASS | `handleTrade()` internally splits and reuses logic |
| Cost base, proceeds, and gain/loss calculated correctly | ✅ PASS | All calculations verified in tests and demo |
| **No tax year logic exists** | ✅ PASS | NO tax year code implemented (Sprint 3+) |
| **No reporting exists** | ✅ PASS | NO export/report logic implemented (Sprint 3+) |

---

## What Does NOT Exist (By Design)

✅ **Scope Discipline Maintained**:
- ❌ No tax year allocation
- ❌ No CGT exclusion logic
- ❌ No CSV/PDF exports
- ❌ No year-end reports
- ❌ No UI polish for FIFO display

These are correctly deferred to Sprint 3+.

---

## Demonstration Results

**Test Run Output** (`backend/tests/demo_fifo.php`):

### Sample Scenario:
- 7 transactions processed (4 BUYs, 2 SELLs, 1 TRADE)
- Multiple cryptocurrencies (BTC, ETH)
- Multiple wallets (Luno, Binance)
- Partial lot consumption demonstrated

### FIFO Calculation Results:
```
Total Proceeds:       R878,000.00
Total Cost Base:      R696,900.00
----------------------------------------
Total Capital Gain:   R181,100.00
Total Capital Loss:   R0.00
========================================
NET CAPITAL GAIN:     R181,100.00
```

### Current Holdings (Remaining FIFO Lots):
- **BTC** (Luno): 0.8 BTC across 3 lots
  - Lot #1: 0.1 BTC @ R505,000 (oldest)
  - Lot #2: 0.5 BTC @ R555,000
  - Lot #3: 0.2 BTC @ R605,000 (newest)
- **ETH** (Binance): 2.0 ETH @ R30,300
- **ETH** (Luno): 5.0 ETH @ R35,200 (from trade)

---

## Key Technical Decisions

### 1. **Wallet-Level FIFO Queues**
- Each currency/wallet combination maintains a separate queue
- Ensures accurate tracking when assets are held across multiple exchanges
- Balance key format: `{CURRENCY}|{WALLET}`

### 2. **Lot Consumption Traceability**
- Every SELL/TRADE records which specific lots were consumed
- Each consumption record includes:
  - Amount consumed from that lot
  - Cost base of consumed portion
  - Acquisition date of the lot
  - Original transaction line number

### 3. **TRADE as Internal Composition**
- TRADE transactions are NOT separate logic
- Internally decomposed into:
  1. SELL of "from" currency (calculate gain/loss)
  2. BUY of "to" currency (create new lot with appropriate cost base)
- This ensures consistency and reduces code duplication

### 4. **Floating-Point Precision**
- Uses epsilon comparison (0.0001) for balance checks
- `BalanceLot.isFullyConsumed()` uses 0.00000001 threshold
- Test assertions use `assertEqualsWithDelta()` where appropriate

---

## Files Created/Modified

### New Files:
1. `backend/src/Models/BalanceLot.php` - FIFO lot representation
2. `backend/src/Models/CoinBalance.php` - FIFO queue per currency/wallet
3. `backend/src/Services/FIFOEngine.php` - Main FIFO calculation engine
4. `backend/tests/FIFOEngineTest.php` - Comprehensive test suite
5. `backend/tests/demo_fifo.php` - Live demonstration script

### Modified Files:
1. `backend/public/transactions.php` - Integrated FIFO engine into API

---

## Test Results

```bash
PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

FIFOEngine (CryptoTax\Tests\FIFOEngine)
 ✔ Buy creates lot
 ✔ Multiple buys stack correctly
 ✔ Sell consumes earliest lot
 ✔ Partial lot consumption
 ✔ Sell consuming multiple lots
 ✔ Trade as composition
 ✔ Capital loss calculation
 ✔ Insufficient balance throws exception
 ✔ Complex scenario

OK (9 tests, 79 assertions)
```

---

## Learning Outcomes Achieved

### Backend Developers:
✅ Understand FIFO queue mechanics  
✅ Implemented traceable capital gains calculation  
✅ Validated edge cases (partial lots, insufficient balance, etc.)

### Frontend Developers (When Team Rotates):
✅ Can read and understand FIFO data structures  
✅ Know how breakdowns are generated for UI display  
✅ Understand the flow: Transaction → FIFO → Gain/Loss

### Team-Wide:
✅ Shared mental model of capital gains calculation  
✅ Everyone understands the canonical data flow  
✅ Clear separation between Sprint 2 (FIFO) and Sprint 3+ (Tax Year Logic)

---

## API Response Structure (New Fields)

```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "summary": {...},
    "analytics": {
      "total_proceeds": 878000.00,
      "total_cost_base": 696900.00,
      "capital_gain": 181100.00,
      "total_capital_gain": 181100.00,
      "total_capital_loss": 0.00,
      "transactions_processed": 7,
      "buys": 4,
      "sells": 2,
      "trades": 1,
      "fifo_breakdowns": [
        {
          "date": "2024-01-01 00:00:00",
          "type": "BUY",
          "currency": "BTC",
          "amount": 1.0,
          "totalCost": 505000.00,
          "costPerUnit": 505000.00,
          ...
        },
        {
          "date": "2024-02-01 00:00:00",
          "type": "SELL",
          "currency": "BTC",
          "amount": 0.6,
          "proceeds": 386000.00,
          "costBase": 303000.00,
          "capitalGain": 83000.00,
          "lotsConsumed": [
            {
              "amountConsumed": 0.6,
              "costBase": 303000.00,
              "costPerUnit": 505000.00,
              "acquisitionDate": "2024-01-01 00:00:00",
              "originalTransaction": 1
            }
          ]
        },
        ...
      ],
      "current_balances": [
        {
          "currency": "BTC",
          "wallet": "Luno",
          "totalBalance": 0.8,
          "totalCostBase": 449000.00,
          "averageCostPerUnit": 561250.00,
          "lotCount": 3,
          "lots": [...]
        },
        ...
      ]
    }
  }
}
```

---

## Next Steps (Sprint 3+)

**Sprint 2 is COMPLETE. Do NOT implement these now:**

1. **Tax Year Allocation**
   - Split capital gains by tax year
   - Handle transactions spanning multiple years

2. **CGT Exclusion Logic**
   - Apply annual CGT exclusion (R40,000 in South Africa)
   - Calculate taxable vs. non-taxable gains

3. **Reporting & Exports**
   - Generate CSV/PDF reports
   - Year-end summary statements
   - Per-transaction audit trail exports

4. **UI Enhancements**
   - Display FIFO breakdowns in frontend
   - Show remaining lots per currency
   - Visualize gain/loss per transaction

---

## Sprint 2 Success Statement

✅ **Sprint 2 is SUCCESSFUL**

The FIFO calculation engine is:
- ✅ Correct (all tests pass)
- ✅ Traceable (every gain/loss linked to specific lots)
- ✅ Trusted (comprehensive test coverage)
- ✅ Explainable (detailed breakdowns available)
- ✅ Well-documented (code comments + this document)

**The team now has a solid foundation for Sprint 3+ features.**

---

## Command Reference

### Run All FIFO Tests:
```bash
cd backend
php vendor/bin/phpunit tests/FIFOEngineTest.php --testdox
```

### Run FIFO Demonstration:
```bash
php backend/tests/demo_fifo.php
```

### Test API Integration:
```bash
curl http://localhost:8000/transactions
```

---

**Sprint 2 Complete**: FIFO Engine ✅  
**Next Sprint**: Tax Year Logic & CGT Calculation (Sprint 3)

