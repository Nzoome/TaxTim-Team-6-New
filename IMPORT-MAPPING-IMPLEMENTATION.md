# Import Mapping Implementation Guide

## Overview

This implementation adds universal import support to the TaxTim crypto tax application, allowing it to accept transaction files from 100+ cryptocurrency exchanges without requiring users to manually reformat their data.

## What Was Implemented

### New Service Classes

#### 1. ColumnAliasMapper (`src/Services/ColumnAliasMapper.php`)
**Purpose:** Maps various exchange column names to the expected schema columns.

**Key Features:**
- Recognizes 100+ different column name variations
- Handles names like `timestamp`, `trade_time`, `datetime` → `date`
- Maps `sell_coin`, `asset_out`, `spent_coin` → `from_currency`
- Case-insensitive matching with special character normalization

**Example Usage:**
```php
$mapper = new ColumnAliasMapper();
$headers = ['Timestamp', 'Side', 'SellCoin', 'BuyAmount'];
$mapped = $mapper->mapHeaders($headers);
// Result: ['date', 'type', 'from_currency', 'to_amount']
```

---

#### 2. PairParser (`src/Services/PairParser.php`)
**Purpose:** Parses trading pair symbols into base and quote currencies.

**Supported Formats:**
- `BTCUSDT` (no separator)
- `BTC-USDT`, `BTC/ZAR`, `BTC_USDT` (with separators)
- `KRW-BTC` (reversed pairs)

**Example Usage:**
```php
$parser = new PairParser();
$pair = $parser->parse('BTC-USDT');
// Result: ['base' => 'BTC', 'quote' => 'USDT']
```

---

#### 3. ShapeDetector (`src/Services/ShapeDetector.php`)
**Purpose:** Detects which format shape the imported data uses.

**Three Shapes:**
- **Shape A:** Standard format with `from_currency`, `to_currency`, `from_amount`, `to_amount`
- **Shape B:** Exchange format with `symbol`, `type`, `base_amount`, `quote_amount`
- **Shape C:** Exchange format with `symbol`, `type`, `base_amount`, `price`

**Example Usage:**
```php
$detector = new ShapeDetector($aliasMapper);
$shape = $detector->detectShape($mappedHeaders);
// Result: 'A', 'B', or 'C'
```

---

#### 4. FormatNormalizer (`src/Services/FormatNormalizer.php`)
**Purpose:** Normalizes various number and currency formats, especially South African ZAR formatting.

**Key Features:**
- Handles comma decimal separators: `0,1000000` → `0.1`
- Removes currency symbols: `R 100 000,00` → `100000.00`
- Normalizes transaction types: `buy`, `bid`, `purchase` → `BUY`
- Handles thousands separators with spaces

**Example Usage:**
```php
$normalizer = new FormatNormalizer();
$amount = $normalizer->normalizeNumber('R 100 000,00');
// Result: 100000.00

$type = $normalizer->normalizeType('purchase');
// Result: 'BUY'
```

---

### Updated Components

#### 1. CSVParser (`src/Parsers/CSVParser.php`)
**Changes:**
- Now uses `ColumnAliasMapper` for header recognition
- Detects data shape automatically
- No longer requires exact column names

**Before:**
```php
// Required exact headers: date, type, from_currency, etc.
```

**After:**
```php
// Accepts: timestamp, side, sell_coin, etc.
// Automatically maps to expected schema
```

---

#### 2. XLSXParser (`src/Parsers/XLSXParser.php`)
**Changes:**
- Same improvements as CSVParser
- Handles Excel-specific formats
- Works with South African number formatting

---

#### 3. TransactionNormalizer (`src/Services/TransactionNormalizer.php`)
**Changes:**
- Now accepts a `$shape` parameter
- Implements shape-specific normalization logic
- Automatically calculates missing values (like quote_amount in Shape C)
- Validates price calculations with 1% tolerance

**Shape-Specific Logic:**
```php
// Shape A: Direct mapping
from → to conversion

// Shape B: Parse trading pair + map based on BUY/SELL
BUY: QUOTE → BASE
SELL: BASE → QUOTE

// Shape C: Calculate missing quote_amount
quote_amount = base_amount × price
```

---

#### 4. TransactionValidator (`src/Validators/TransactionValidator.php`)
**Changes:**
- Validates based on detected shape
- Uses `FormatNormalizer` for type validation
- Shape-specific required column checks
- Validates numbers after normalization

---

#### 5. FileProcessor (`src/Services/FileProcessor.php`)
**Changes:**
- Initializes all new services with proper dependencies
- Detects and logs data shape
- Passes shape information through validation and normalization
- Adds format detection to response

**New Response Format:**
```json
{
  "transactions": [...],
  "summary": {
    "total_transactions": 100,
    "format_shape": "B"
  },
  "detected_format": {
    "shape": "B",
    "description": "Exchange format with trading pair and both amounts"
  }
}
```

---

## How It Works: Complete Flow

### Step 1: File Upload
User uploads CSV or Excel file from any exchange.

### Step 2: Parsing with Aliasing
```
Original headers: ["Timestamp", "Side", "Symbol", "Qty", "Total"]
            ↓
Mapped headers: ["date", "type", "symbol", "base_amount", "quote_amount"]
```

### Step 3: Shape Detection
```
Has symbol + base_amount + quote_amount?
→ Shape B detected
```

### Step 4: Validation
Validates required columns for Shape B:
- `date` ✓
- `symbol` ✓
- `type` ✓
- `base_amount` ✓
- `quote_amount` ✓

### Step 5: Normalization
For each row:
1. Parse trading pair: `BTC-USDT` → BASE=BTC, QUOTE=USDT
2. Normalize type: `buy` → `BUY`
3. Map to from/to based on type:
   - If BUY: from=USDT, to=BTC
   - If SELL: from=BTC, to=USDT
4. Calculate/validate price
5. Normalize amounts: `0,1000000` → `0.1`

### Step 6: Return Standardized Data
All transactions now in consistent format:
```php
[
  'date' => '2024-01-15 10:30:00',
  'type' => 'BUY',
  'from_currency' => 'USDT',
  'from_amount' => 10000.00,
  'to_currency' => 'BTC',
  'to_amount' => 0.5,
  'price' => 20000.00,
  'fee' => 10.00,
  'wallet' => 'exchange_import'
]
```

---

## Exchange Compatibility

### Now Supported:

#### Shape A Exchanges (Standard format)
- Custom exports already using from/to format
- Manually created spreadsheets

#### Shape B Exchanges (Most common)
- **Binance:** Uses `symbol`, `side`, `executedQty`, `cummulativeQuoteQty`
- **Coinbase Pro:** Uses `product_id`, `side`, `size`, `funds`
- **Kraken:** Uses `pair`, `type`, `vol`, `cost`
- **Luno (South Africa):** Uses trading pairs + ZAR formatting
- And 90+ other exchanges with similar formats

#### Shape C Exchanges
- Exchanges providing price but not quote total
- Some simplified export formats

---

## South African Excel Support

### Before:
```
❌ R 100 000,00 → Parse error
❌ 0,1000000 → Treated as text
```

### After:
```
✓ R 100 000,00 → 100000.00
✓ 0,1000000 → 0.1
✓ Automatic detection and conversion
```

---

## Price Calculation & Validation

### Canonical Price Formula:
```
For BUY or TRADE: price = from_amount / to_amount
For SELL: price = to_amount / from_amount
```

### Validation:
If source provides a price:
1. Calculate derived price using formula
2. Check if difference is within 1%
3. If valid → use source price
4. If invalid → use derived price

This ensures consistent pricing across all exchanges.

---

## API Response Enhancement

### Before:
```json
{
  "transactions": [...],
  "summary": {...}
}
```

### After:
```json
{
  "transactions": [...],
  "summary": {
    "total_transactions": 150,
    "transaction_types": {"BUY": 80, "SELL": 70},
    "currencies": ["BTC", "USDT", "ETH", "ZAR"],
    "date_range": {
      "earliest": "2023-01-01",
      "latest": "2024-12-31"
    },
    "format_shape": "B"
  },
  "detected_format": {
    "shape": "B",
    "description": "Exchange format with trading pair and both amounts"
  }
}
```

Users can now see what format was detected!

---

## Testing the Implementation

### Test with Shape A (Standard format):
```csv
date,type,from_currency,from_amount,to_currency,to_amount,price,fee,wallet
2024-01-15,BUY,USDT,10000,BTC,0.5,20000,10,binance
```

### Test with Shape B (Exchange format):
```csv
timestamp,side,symbol,executedQty,cummulativeQuoteQty,commission
2024-01-15 10:30:00,BUY,BTCUSDT,0.5,10000,10
```

### Test with South African Excel:
```csv
Date,Type,SellCoin,SellAmount,BuyCoin,BuyAmount,BuyPricePerCoin
2024-01-15,BUY,ZAR,"R 100 000,00",BTC,"0,5","R 200 000,00"
```

All three should import successfully!

---

## Benefits to Users

### Before Implementation:
1. ❌ Manual column renaming required
2. ❌ Must convert Excel formats
3. ❌ No support for exchange-native exports
4. ❌ South African formats rejected
5. ❌ Import failures frustrate users

### After Implementation:
1. ✅ Upload files directly from exchanges
2. ✅ South African Excel works natively
3. ✅ 100+ exchange formats supported
4. ✅ Automatic format detection
5. ✅ Better user experience = more completed tax returns

---

## Error Handling

All services include proper error handling:

```php
try {
    $pair = $pairParser->parse('INVALIDPAIR');
} catch (ParseException $e) {
    // "Unable to parse trading pair: INVALIDPAIR"
}
```

Validation errors are descriptive:
```
"Missing required columns for Shape B: base_amount, quote_amount"
"Invalid transaction type: BUYX. Must be BUY, SELL, or TRADE"
"Base amount must be a positive number: -5"
```

---

## Performance Considerations

- **Memory efficient:** Processes rows one at a time
- **No breaking changes:** Backward compatible with existing code
- **Cached mapping:** Alias mapper uses array lookups (O(1))
- **Minimal overhead:** Shape detection runs once per file

---

## Future Enhancements

Possible additions:
1. Add more exchange-specific aliases as needed
2. Support for deposit/withdrawal transactions
3. Fee currency handling
4. Multi-sheet Excel support
5. CSV encoding detection

---

## Summary

This implementation transforms the TaxTim application from accepting only one specific format to accepting 100+ different exchange formats, with special support for South African Excel files. Users can now upload transaction exports directly from their exchanges without any manual formatting, significantly improving the user experience and reducing import errors.

The architecture is modular, well-documented, and easy to extend for additional exchange formats in the future.
