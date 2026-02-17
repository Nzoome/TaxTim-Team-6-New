# 💰 IMPORTANT: Currency Handling in TaxTim

## Critical Understanding: All Amounts Are In Rands (ZAR)

### What This Means

**✅ ALL transaction amounts displayed are already converted to South African Rands (ZAR)**

This includes:
- Bitcoin (BTC) values → Converted to ZAR
- Ethereum (ETH) values → Converted to ZAR  
- Any cryptocurrency → Converted to ZAR
- All "From" amounts → Already in ZAR
- All "To" amounts → Already in ZAR
- All "Price per Unit" → Already ZAR value per unit

### What You See vs. What It Means

```
Transaction Display:

Type:         BUY
From:         ZAR 250,000     ← This is Rands you paid
To:           BTC 5.0          ← You got 5 Bitcoin
Price/Unit:   R50,000          ← 1 Bitcoin = R50,000 (in Rands)
Date:         2024-01-15

What This Actually Means:
• You paid R250,000 (already in Rands)
• You received 5 BTC
• At that time, 1 BTC was worth R50,000 (Rand value)
• Total verification: 5 × R50,000 = R250,000 ✓

NO CONVERSION NEEDED - Everything is in Rands!
```

### Common Misconception ❌

**Wrong Thinking:**
"I need to convert BTC to ZAR using the price"

**Correct Thinking:**
"The system already converted everything to ZAR. The price shows what 1 BTC was worth in Rands at that moment."

### Example Breakdown

#### Your CSV File:
```csv
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15,BUY,ZAR,250000,BTC,5,50000
```

#### What Each Field Represents:
| Field | Value | Meaning |
|-------|-------|---------|
| FromCurrency | ZAR | You're using Rands |
| FromAmount | 250000 | R250,000 (already in Rands) |
| ToCurrency | BTC | You're getting Bitcoin |
| ToAmount | 5 | 5 Bitcoin |
| Price | 50000 | R50,000 per BTC (Rand value) |

#### The Math:
```
To verify amounts are consistent:
ToAmount × Price = FromAmount
5 BTC × R50,000/BTC = R250,000 ✓

This confirms:
- 5 Bitcoin at R50,000 each
- Equals R250,000 total
- All in Rands (no conversion)
```

### Why "Price per Unit" Shows Rands

The "Price per Unit" field is:
- ✅ The **ZAR value** of 1 unit of the cryptocurrency
- ✅ Already in Rands
- ✅ Historical price at transaction time
- ❌ NOT a conversion rate you need to apply
- ❌ NOT in USD or any other currency

### Real Transaction Examples

#### Example 1: BUY Transaction
```
You bought Bitcoin:
• Spent: R250,000 (Rands)
• Got: 5 BTC
• Price: R50,000/BTC

Display shows:
From: ZAR 250,000    ← Rands you spent
To: BTC 5.0          ← Bitcoin you got
Price: R50,000       ← Rand value of 1 BTC

All amounts already in ZAR ✓
```

#### Example 2: SELL Transaction
```
You sold Bitcoin:
• Sold: 5 BTC
• Got: R260,000 (Rands)
• Price: R52,000/BTC

Display shows:
From: BTC 5.0        ← Bitcoin you sold
To: ZAR 260,000      ← Rands you got
Price: R52,000       ← Rand value of 1 BTC

All amounts already in ZAR ✓
```

#### Example 3: TRADE Transaction
```
You traded crypto-to-crypto:
• Traded: 1 BTC (worth R50,000)
• Got: 15 ETH (worth R50,000)
• Price: R50,000/BTC

Display shows:
From: BTC 1.0        ← Bitcoin you gave
To: ETH 15.0         ← Ethereum you got
Price: R50,000       ← Rand value of 1 BTC

Both assets valued in ZAR ✓
```

### Large Transaction Detection

When the system flags "Large Transaction", it's calculating Rand values:

```
Transaction: BUY 50 BTC @ R50,000 per BTC

Calculation:
50 BTC × R50,000/BTC = R2,500,000

Result: R2,500,000 exceeds R1,000,000 threshold
→ Flagged as LARGE_TRANSACTION

Note: R50,000 is already the Rand price per Bitcoin
```

### Capital Gains Calculation

The FIFO engine uses these Rand values directly:

```
BUY:  5 BTC @ R50,000/BTC = R250,000 cost base
SELL: 5 BTC @ R52,000/BTC = R260,000 proceeds

Capital Gain = R260,000 - R250,000 = R10,000 gain

All calculations in Rands - no conversion needed!
```

### Fee Handling

Fees are also in Rands:

```
Transaction: BUY 5 BTC @ R50,000 = R250,000
Fee: R1,500

Total Cost = R250,000 + R1,500 = R251,500

Fee shown in metadata as: fee: 1500 (Rands)
```

### Verification Formula (Updated)

```
For BUY transactions:
ToAmount (crypto) × Price (ZAR/unit) = FromAmount (ZAR)
5 BTC × R50,000/BTC = R250,000 ✓

For SELL transactions:
FromAmount (crypto) × Price (ZAR/unit) = ToAmount (ZAR)
5 BTC × R52,000/BTC = R260,000 ✓

For TRADE transactions:
Both sides valued at their ZAR equivalents
```

### Tax Year Compliance

SARS requires all amounts in ZAR:
- ✅ Your data is already compliant
- ✅ No manual conversion needed
- ✅ System handles ZAR valuation
- ✅ Reports are SARS-ready

### Common Questions

**Q: Do I need to convert amounts to Rands?**
A: No! Everything is already in Rands.

**Q: Is the "Price" field a USD price?**
A: No! It's the ZAR (Rand) value per unit.

**Q: Why does "From" show ZAR 250,000 but "To" shows BTC 5.0?**
A: Because you're exchanging 250,000 Rands for 5 Bitcoin. One is currency (ZAR), the other is quantity (BTC). Both are valued in Rands via the Price field.

**Q: How do I know the Price is in Rands?**
A: The system automatically converts to ZAR. The Price field always represents ZAR per unit.

**Q: What if I imported data in USD?**
A: The system converts it to ZAR using historical rates. What you see is the final ZAR value.

**Q: Are the displayed amounts accurate for SARS?**
A: Yes! All amounts are ZAR-based and ready for SARS filing.

### Summary

```
╔═══════════════════════════════════════════════════════╗
║  🎯 KEY TAKEAWAY                                      ║
║                                                       ║
║  ALL AMOUNTS ARE ALREADY IN RANDS (ZAR)              ║
║                                                       ║
║  • From/To amounts → ZAR values                       ║
║  • Price per unit → ZAR per unit                      ║
║  • Fees → ZAR amounts                                 ║
║  • Capital gains → ZAR calculations                   ║
║                                                       ║
║  No conversion needed - it's all done for you! ✅      ║
╚═══════════════════════════════════════════════════════╝
```

### What This Means For Red Flags

When you see a Red Flag with amounts:
- The amounts shown are **final ZAR values**
- The "Price per Unit" is **ZAR per crypto unit**
- The verification formula uses **ZAR values**
- Everything is **SARS-compliant** already

**No confusion about currency conversion - it's all Rands!** 🇿🇦

