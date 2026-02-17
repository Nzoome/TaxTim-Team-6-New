# Import Mapping Quick Reference

## File Structure Created

```
backend/src/
├── Services/
│   ├── ColumnAliasMapper.php       ← NEW: Maps 100+ header variations
│   ├── PairParser.php              ← NEW: Parses trading pairs (BTC-USDT)
│   ├── ShapeDetector.php           ← NEW: Detects data format shape
│   ├── FormatNormalizer.php        ← NEW: Handles ZAR & number formats
│   ├── TransactionNormalizer.php   ← UPDATED: Shape-aware normalization
│   └── FileProcessor.php           ← UPDATED: Orchestrates new services
├── Parsers/
│   ├── CSVParser.php               ← UPDATED: Intelligent header mapping
│   └── XLSXParser.php              ← UPDATED: Excel format support
└── Validators/
    └── TransactionValidator.php    ← UPDATED: Shape-aware validation
```

---

## Three Data Shapes Supported

### Shape A: Standard Format (Your Original)
```csv
date, type, from_currency, from_amount, to_currency, to_amount, price
```

### Shape B: Exchange Format (Most Common)
```csv
timestamp, side, symbol, executedQty, cummulativeQuoteQty
```
**Converts:** Trading pair → from/to based on BUY/SELL

### Shape C: Exchange with Price
```csv
timestamp, side, symbol, qty, price
```
**Calculates:** quote_amount = qty × price

---

## Supported Column Name Variations

### Date Column Accepts:
`date`, `time`, `datetime`, `timestamp`, `trade_time`, `created_at`, `executed_at`, `fill_time`

### Type Column Accepts:
`type`, `side`, `action`, `transaction_type`, `direction`, `buy_sell`

### Amount Columns Accept:
- **From Amount:** `from_amount`, `sell_amount`, `spent_amount`, `amount_out`
- **To Amount:** `to_amount`, `buy_amount`, `received_amount`, `amount_in`
- **Base Amount:** `amount`, `qty`, `quantity`, `size`, `executedQty`
- **Quote Amount:** `quoteQty`, `funds`, `cost`, `total`

### Currency Columns Accept:
- **From Currency:** `from_currency`, `sell_coin`, `spent_coin`, `asset_out`
- **To Currency:** `to_currency`, `buy_coin`, `received_coin`, `asset_in`

### Trading Pair Accepts:
`symbol`, `pair`, `market`, `product_id`, `trading_pair`, `currency_pair`

### Price Accepts:
`price`, `rate`, `unit_price`, `fill_price`, `avg_price`, `buypricepercoin`

### Fee Accepts:
`fee`, `commission`, `trading_fee`, `fee_amount`

---

## Type Value Normalization

### These Become "BUY":
`buy`, `bid`, `purchase`, `credit`, `true`, `1`

### These Become "SELL":
`sell`, `ask`, `dispose`, `debit`, `false`, `0`

### These Become "TRADE":
`trade`, `swap`, `convert`, `exchange`

---

## Number Format Support

### South African / European Format:
```
R 100 000,00  →  100000.00
0,1000000     →  0.1
1.000,50      →  1000.50
```

### International Format:
```
$100,000.00   →  100000.00
1,000.50      →  1000.50
```

**Automatic detection and conversion!**

---

## Trading Pair Parsing

### Supported Formats:
```
BTCUSDT       →  base: BTC,  quote: USDT
BTC-USDT      →  base: BTC,  quote: USDT
BTC/ZAR       →  base: BTC,  quote: ZAR
BTC_USDT      →  base: BTC,  quote: USDT
KRW-BTC       →  base: BTC,  quote: KRW
```

---

## Price Calculation Logic

### For BUY or TRADE:
```
price = from_amount / to_amount
(How much you paid per unit)
```

### For SELL:
```
price = to_amount / from_amount
(How much you received per unit)
```

### Validation:
- If source provides price, compare with calculated price
- If within 1% tolerance → use source price
- Otherwise → use calculated price

---

## Example Transformations

### Example 1: Binance Export (Shape B)
**Input:**
```csv
Time,Side,Symbol,Executed,Total,Fee
2024-01-15 10:30:00,BUY,BTCUSDT,0.5,10000,10
```

**Output:**
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

### Example 2: South African Excel (Shape A)
**Input:**
```csv
Date,Type,SellCoin,SellAmount,BuyCoin,BuyAmount,BuyPricePerCoin
2024-01-15,BUY,ZAR,"R 100 000,00",BTC,"0,5","R 200 000,00"
```

**Output:**
```php
[
  'date' => '2024-01-15',
  'type' => 'BUY',
  'from_currency' => 'ZAR',
  'from_amount' => 100000.00,
  'to_currency' => 'BTC',
  'to_amount' => 0.5,
  'price' => 200000.00,
  'fee' => 0.00,
  'wallet' => 'excel_import'
]
```

---

### Example 3: Exchange with Price Only (Shape C)
**Input:**
```csv
timestamp,side,pair,amount,price,fee
2024-01-15 10:30:00,SELL,BTC/USDT,0.5,20000,10
```

**Processing:**
1. Parse pair: BTC/USDT → BASE=BTC, QUOTE=USDT
2. Type: SELL → from=BASE, to=QUOTE
3. Calculate: quote_amount = 0.5 × 20000 = 10000

**Output:**
```php
[
  'date' => '2024-01-15 10:30:00',
  'type' => 'SELL',
  'from_currency' => 'BTC',
  'from_amount' => 0.5,
  'to_currency' => 'USDT',
  'to_amount' => 10000.00,
  'price' => 20000.00,
  'fee' => 10.00,
  'wallet' => 'exchange_import'
]
```

---

## API Response Changes

### New Fields Added:

```json
{
  "transactions": [...],
  "summary": {
    "format_shape": "B"  ← NEW
  },
  "detected_format": {    ← NEW
    "shape": "B",
    "description": "Exchange format with trading pair and both amounts"
  }
}
```

---

## Testing Checklist

- [ ] Test Shape A (standard format)
- [ ] Test Shape B (exchange with pair + amounts)
- [ ] Test Shape C (exchange with pair + price)
- [ ] Test South African number formats
- [ ] Test various column name variations
- [ ] Test type normalization (buy → BUY)
- [ ] Test trading pair parsing
- [ ] Test price calculation and validation
- [ ] Test missing optional fields (fee, wallet)
- [ ] Test error messages for invalid data

---

## Common Exchange Formats

### Binance
- Columns: `Time`, `Side`, `Symbol`, `Executed`, `Total`, `Fee`
- Shape: B
- Type mapping: Side → type

### Coinbase Pro
- Columns: `created at`, `side`, `product`, `size`, `funds`, `fee`
- Shape: B
- Pair format: BTC-USD

### Kraken
- Columns: `time`, `type`, `pair`, `vol`, `cost`, `fee`
- Shape: B
- Pair format: XBTUSD

### Luno (South Africa)
- Uses ZAR currency
- Number format: R 1 000,00
- Shape: A or B depending on export

---

## Error Messages

### Structural Errors:
```
"Missing required columns for Shape B: base_amount, quote_amount"
```

### Data Errors:
```
"Invalid transaction type: BUYX. Must be BUY, SELL, or TRADE"
"Base amount must be a positive number: -5"
"Unable to parse trading pair: INVALIDPAIR"
"Invalid date format: not-a-date"
```

---

## Performance Notes

- Processes files row-by-row (memory efficient)
- Shape detection happens once per file
- Column mapping uses hash lookups (fast)
- No database queries during parsing

---

## Backward Compatibility

✅ All existing functionality preserved
✅ Existing CSV files still work
✅ No breaking changes to API
✅ Old format detection: automatically uses Shape A

---

## Quick Command Reference

### Run Tests:
```powershell
cd backend
vendor/bin/phpunit tests/
```

### Check Logs:
```powershell
Get-Content backend/logs/app.log -Tail 50
```

### Test Upload:
```powershell
# Use the frontend upload or API endpoint
curl -F "file=@transactions.csv" http://localhost:8000/transactions.php
```

---

## Support for Future Exchanges

To add support for a new exchange:

1. **Add column aliases** to `ColumnAliasMapper.php`
2. **Add currency codes** to `PairParser.php` if needed
3. **Test with sample file** to verify shape detection
4. No other changes needed! The system adapts automatically.

---

## Key Classes Summary

| Class | Purpose | Key Method |
|-------|---------|------------|
| `ColumnAliasMapper` | Maps headers | `mapHeaders()` |
| `PairParser` | Parses pairs | `parse()` |
| `ShapeDetector` | Detects format | `detectShape()` |
| `FormatNormalizer` | Normalizes data | `normalizeNumber()` |
| `TransactionNormalizer` | Creates objects | `normalize($rows, $shape)` |
| `TransactionValidator` | Validates data | `validate($rows, $shape)` |
| `FileProcessor` | Orchestrates all | `processFile()` |

---

## Documentation Files

- **IMPORT-MAPPING-IMPLEMENTATION.md** - Full detailed guide
- **IMPORT-MAPPING-QUICK-REFERENCE.md** - This file (quick lookup)
- Code comments in each class

---

## Questions?

Check the implementation guide for detailed explanations of:
- Architecture decisions
- Shape-specific logic
- Price calculation formulas
- South African format handling
- Error handling strategies
