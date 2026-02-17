# Red Flag System - Amount Display & Consistency Guide

## The Problem You Reported

> "It doesn't display correct amount and it is not consistent"

This guide explains **exactly** what amounts are displayed and **why** they're displayed that way.

---

## Understanding Transaction Amounts

Every cryptocurrency transaction involves **multiple related amounts**. The system displays all of them because each serves a different purpose:

### The Four Transaction Amounts

```
Transaction: Buy 5 Bitcoin for R250,000
               ↓         ↓
         Amount 1    Amount 2

Displayed in Red Flag System:

From:           BTC 5.0       ← Amount you sent (the "from" asset)
To:             ZAR 250,000   ← Amount you received (the "to" asset)
Price/Unit:     R50,000       ← ZAR value per 1 unit (not exchange rate)
Fee:            R1,500        ← Cost of transaction (shown in metadata)

⚠️ CRITICAL: ALL AMOUNTS ARE ALREADY IN RANDS (ZAR)
   The system has already converted all values to ZAR.
   "Price per Unit" shows the Rand value of 1 unit of the asset.
   You don't need to convert anything - it's done for you.

NONE of these are "wrong" - they're all correct, just different!
```

---

## What Each Amount Represents

### 1. FROM AMOUNT (What You Sent)
```
Field Name: "From"
Display: "BTC 5.0"

Meaning: You sent/used 5.0 Bitcoin in this transaction

When it applies:
- BUY transactions: Amount of base currency used (e.g., fiat sent)
- SELL transactions: Amount of crypto sold (e.g., BTC sold)
- TRADE transactions: Amount of first asset given up

Example:
   BUY | From: BTC 5.0    ← You're using 5 BTC (wait, that's backwards?)
   
Actually, in typical CSV format:
   BUY | FromCurrency: ZAR | FromAmount: 250000 | ToCurrency: BTC | ToAmount: 5.0
   
Meaning: You're exchanging R250,000 for 5 BTC
Display: From: ZAR 250000 → To: BTC 5.0 ✓
```

### 2. TO AMOUNT (What You Received)
```
Field Name: "To"
Display: "ZAR 250,000" or "BTC 5.0"

Meaning: You received this amount in this transaction

When it applies:
- BUY transactions: Amount of crypto received (e.g., BTC received)
- SELL transactions: Amount of fiat received (e.g., ZAR received)
- TRADE transactions: Amount of second asset received

Example:
   SELL | To: ZAR 250,000   ← You received R250,000
   
This is what you walked away with.
```

### 3. PRICE PER UNIT
```
Field Name: "Price per Unit"
Display: "R50,000"

Meaning: The ZAR (Rand) value of 1 unit of the asset

⚠️ IMPORTANT: This is NOT an exchange rate to convert.
   All amounts are ALREADY in Rands.
   This shows what 1 BTC was worth in Rands at transaction time.

Formula: From Amount ÷ To Amount = Price per Unit
Example: 250,000 ZAR ÷ 5 BTC = R50,000/BTC

This is used to calculate:
- Capital gains: (Selling price - Buying price) × Amount
- Transaction values for large transaction detection
- Fee percentages
- Verification that amounts are consistent

Why it matters:
Bitcoin price fluctuates in Rand terms:
- R40,000/BTC (if bought during a dip)
- R50,000/BTC (normal market rate)
- R60,000/BTC (if bought at a peak)

All these are ZAR values - no conversion needed.
```

### 4. FEE AMOUNT
```
Field Name: "Fee"
Display: In metadata section (if flagged for excessive fee)

Meaning: The cost charged by the exchange/platform

Example:
   Your transaction costs R1,500 in fees
   → Displayed as: "fee: 1500" in metadata
   
This is separate from the main transaction amounts.
```

---

## Why Multiple Amounts Are Shown (Consistency Check)

The system shows all related amounts so you can **verify they're consistent**:

### Example Transaction #1: Simple BUY

```
Original Data:
Date: 2024-01-15
Type: BUY
FromCurrency: ZAR
FromAmount: 250000
ToCurrency: BTC
ToAmount: 5.0
Price: 50000

Displayed as Red Flag:
┌─────────────────────────────┐
│ Type:         BUY            │
│ From:         ZAR 250,000    │
│ To:           BTC 5.0        │
│ Price/Unit:   R50,000        │
│ Date:         2024-01-15     │
└─────────────────────────────┘

Consistency Check:
From Amount / To Amount = Price per Unit?
250,000 ZAR / 5 BTC = 50,000 ZAR/BTC ✓

Result: ✅ CONSISTENT
All amounts make sense together.
```

### Example Transaction #2: SELL

```
Original Data:
Date: 2024-01-20
Type: SELL
FromCurrency: BTC
FromAmount: 2.5
ToCurrency: ZAR
ToAmount: 130,000
Price: 52000

Displayed as Red Flag:
┌─────────────────────────────┐
│ Type:         SELL           │
│ From:         BTC 2.5        │
│ To:           ZAR 130,000    │
│ Price/Unit:   R52,000        │
│ Date:         2024-01-20     │
└─────────────────────────────┘

Consistency Check:
From Amount × Price per Unit = To Amount?
2.5 BTC × 52,000 ZAR/BTC = 130,000 ZAR ✓

Result: ✅ CONSISTENT
All amounts make sense together.
```

### Example Transaction #3: TRADE (Crypto to Crypto)

```
Original Data:
Date: 2024-01-25
Type: TRADE
FromCurrency: BTC
FromAmount: 1.0
ToCurrency: ETH
ToAmount: 15.0
Price: 50000  (BTC price in ZAR)

Displayed as Red Flag:
┌─────────────────────────────┐
│ Type:         TRADE          │
│ From:         BTC 1.0        │
│ To:           ETH 15.0       │
│ Price/Unit:   R50,000        │
│ Date:         2024-01-25     │
└─────────────────────────────┘

Consistency Check:
This shows the BTC price (R50,000).
1 BTC worth ~15 ETH at current rate.
Amounts are separate, but both shown for record-keeping.

Result: ✅ CONSISTENT
(For crypto trades, amounts are recorded for audit trail)
```

---

## When Amounts Seem "Inconsistent"

### Problem #1: Large Transaction Display

```
Your CSV shows:
2024-01-15,BUY,ZAR,1000000,BTC,20,50000

Red Flag shows:
From:       ZAR 1,000,000
To:         BTC 20
Price/Unit: R50,000

This might seem like different numbers, but:
1,000,000 ÷ 20 = 50,000 ✓ Mathematically consistent!

It's just shown in three ways for clarity:
- "What you spent" (From)
- "What you got" (To)
- "Rate of exchange" (Price)
```

### Problem #2: Fee Not Shown Prominently

```
Your CSV shows:
2024-01-15,BUY,ZAR,250000,BTC,5,50000,1500

Red Flag shows:
From:       ZAR 250,000
To:         BTC 5.0
Price/Unit: R50,000
[In metadata]
fee: 1500

The fee appears in metadata section, not main display.
This is correct - the fee is ADDITIONAL to the amounts shown.

Full transaction cost:
Main purchase: 250,000 ZAR
Plus fee:      1,500 ZAR
Total:         251,500 ZAR
```

### Problem #3: Multiple Red Flags on Same Transaction

```
Example: A transaction flagged for both:
- INCOMPLETE_DATA (missing date)
- NEGATIVE_AMOUNT (negative fee)

Displayed as:

Transaction Details:
From:       BTC -5.0        ← Shows negative (the problem!)
To:         ZAR 250,000
Price/Unit: R50,000

Red Flag 1: "NEGATIVE_AMOUNT - Transaction has negative source amount: -5"
Red Flag 2: "INCOMPLETE_DATA - Transaction has incomplete data: missing date"

This is CORRECT because:
1. The system found TWO problems
2. Both are displayed with their own flag codes
3. All amounts shown so you can see the issues
```

---

## Step-By-Step: Reading a Red Flag Display

### Your CSV Line:
```
2024-01-15,BUY,ZAR,250000,BTC,5.0,50000,1500
```

### How It Appears as a Red Flag:

```
🚩 RED FLAG DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SEVERITY: ⚠️ HIGH
CODE: LARGE_TRANSACTION
MESSAGE: Large transaction detected: R250,000,000 
(threshold: R1,000,000)

TRANSACTION DETAILS:
┌──────────────────────────┐
│ Type: BUY                │
│ From: ZAR 250,000        │
│ To: BTC 5.0              │
│ Price per Unit: R50,000  │
│ Date: 2024-01-15         │
└──────────────────────────┘

METADATA:
  value_zar: 250,000

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━


WHAT THIS MEANS:
• You bought 5 BTC
• You paid R250,000 for it
• That's R50,000 per BTC
• Total value: R250,000 (matches your purchase!)
• The system flagged it because R250,000 is large
• This is informational - you don't need to change it
• But SARS will pay attention to this transaction
```

---

## The Consistency Formula

Every transaction should satisfy this formula:

```
FROM_AMOUNT × PRICE_PER_UNIT = TO_AMOUNT (approximately)

If this is false → Red Flag for "INCOMPLETE_DATA"

Examples:

✅ CONSISTENT:
250,000 ZAR × ? = 5 BTC
? = 250,000 ÷ 5 = 50,000 ZAR/BTC ✓

✅ CONSISTENT:
5 BTC × 50,000 ZAR/BTC = 250,000 ZAR ✓

❌ INCONSISTENT:
5 BTC × 50,000 ZAR/BTC = 250,000 ZAR
But your CSV shows: 5 BTC × 50,000 ZAR/BTC = 300,000 ZAR
→ Flagged as data error
```

---

## Why Amounts Appear in Different Orders

Your transaction might be displayed different ways depending on transaction type:

### BUY Transaction
```
CSV Perspective:
- FromCurrency: ZAR
- FromAmount: 250,000 (what you're paying)
- ToCurrency: BTC  
- ToAmount: 5 (what you're getting)

Display: "From: ZAR 250,000 → To: BTC 5.0"
Meaning: You're paying R250,000 for 5 BTC
```

### SELL Transaction
```
CSV Perspective:
- FromCurrency: BTC
- FromAmount: 5 (what you're selling)
- ToCurrency: ZAR
- ToAmount: 250,000 (what you're getting)

Display: "From: BTC 5.0 → To: ZAR 250,000"
Meaning: You're selling 5 BTC for R250,000
```

**Both show the same transaction information, just from different perspectives!**

---

## How to Verify Amounts Are Correct

### Step 1: Get Your CSV Line
```
2024-01-15,BUY,ZAR,250000,BTC,5,50000
```

### Step 2: Map to Display
```
Type:        BUY              ← [Type]
From:        ZAR 250,000      ← [FromCurrency] [FromAmount]
To:          BTC 5.0          ← [ToCurrency] [ToAmount]
Price/Unit:  R50,000          ← [Price]
Date:        2024-01-15       ← [Date]
```

### Step 3: Verify Math
```
From Amount ÷ To Amount = Price per Unit?
250,000 ÷ 5 = 50,000 ✓

Or reverse:
To Amount × Price per Unit = From Amount?
5 × 50,000 = 250,000 ✓
```

### Step 4: Check for Flags
If amounts don't match this formula, you'll see:
- **INCOMPLETE_DATA** - One value is missing/zero
- **NEGATIVE_AMOUNT** - One value is negative
- **EXCESSIVE_FEE** - Fee exceeds 50% of transaction value

---

## Common "Inconsistency" Concerns

### Concern #1: "Why does it show both BTC and ZAR?"
**Answer:** Because your transaction involved BOTH currencies!
- You sent ZAR (fiat money)
- You received BTC (cryptocurrency)
- Both are relevant to your tax record

### Concern #2: "The amounts are different each time I look!"
**Answer:** They shouldn't be. If they are, something's wrong:
- Browser cache issue → Refresh the page
- File was re-uploaded → Use the latest version
- Different filters applied → Check filter settings

### Concern #3: "Some transactions show fees, others don't"
**Answer:** Fees only show in metadata IF they exist AND are problematic:
- Fee = R500 on R250,000 transaction → Ignored (normal)
- Fee = R150,000 on R250,000 transaction → Flagged (excessive)
- Fees are often absorbed in the "to_amount" already

---

## Examples of "Correct" Inconsistency

These look inconsistent but ARE correct:

### Example 1: Precision Differences
```
CSV: FromAmount: 5.0, ToAmount: 250000.00, Price: 50000

Display shows:
From: BTC 5.0
To: ZAR 250,000.00
Price/Unit: R50,000.00

Same data, just formatted differently for readability!
```

### Example 2: Rounding in Crypto Trades
```
CSV: FromAmount: 0.12345678, ToAmount: 1.5, Price: 1215000

Display shows:
From: BTC 0.12345678
To: ETH 1.5
Price/Unit: R1,215,000

Different decimal places, but all correct!
The system preserves precision in backend calculations.
```

### Example 3: Fee Absorption
```
CSV shows:
FromAmount: 250000, ToAmount: 5, Fee: 1500

But you actually received:
5 BTC (the to_amount)

The fee was paid SEPARATELY to the exchange.
You didn't lose BTC, you lost ZAR.

Display shows ToAmount as 5 BTC (correct!)
Fee shown separately in metadata
```

---

## Verification Checklist

When reviewing Red Flags, verify amounts are consistent:

```
☐ Does From Amount × Price = To Amount? (approximately)
☐ Is date present and valid format?
☐ Are currencies valid (BTC, ETH, ZAR, etc)?
☐ Are amounts positive (no negative numbers)?
☐ Does the transaction type match the amounts?
  (BUY should have crypto in To, not From)
☐ Are decimal places reasonable? (BTC can go to 8 decimals)
☐ Is the price reasonable for that date?
☐ Does the fee seem legitimate? (< 1% is normal)
```

If all these check out, your amounts are **consistent and correct**.

---

## Getting Help

### If Amounts Still Seem Inconsistent:

1. **Check your source data** - Verify your CSV file
2. **Manually calculate** - Do the math: From × Price = To?
3. **Check the flag message** - It often explains the specific issue
4. **Look at metadata** - Flags include extra details like actual vs expected
5. **Try re-uploading** - Sometimes format issues resolve

### If You Found A Real Bug:

Look for the exact issue in this order:
1. **Negative amounts** - Math can't work with negatives
2. **Missing values** - Zero values break calculations
3. **Wrong currency** - ZAR vs BTC confusion
4. **Decimal errors** - 1.0 vs 0.1 BTC mistake
5. **Transposed data** - From/To columns reversed

---

## Summary

**Why multiple amounts are shown:**
- Shows all aspects of the transaction for verification
- Helps you identify which amount is wrong (if any)
- Creates an audit trail for SARS
- Makes tax calculations accurate

**Why they seem "inconsistent":**
- Different perspectives (BUY vs SELL)
- Different currencies (ZAR vs BTC)
- Different precision (8 decimals vs 2 decimals)
- All are correct simultaneously!

**How to verify:**
- Use the formula: From × Price = To
- Check each value against your CSV source
- Look at flag messages for specific issues

**If still confused:**
- Each flag includes the problem explanation
- Red Flag messages are specific
- Metadata section shows exact values used
- Compare with your original CSV file

