# Red Flag System - Visual Examples & Scenarios

## What You See vs. What It Means

### Scenario 1: Simple BUY Transaction

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15,BUY,ZAR,250000,BTC,5,50000
```

#### What The System Detects:
```
✅ Status: Transaction is VALID and CONSISTENT

Breakdown:
• You spent: R250,000
• You got: 5 BTC
• Rate: R50,000 per BTC

Verification:
  250,000 ZAR ÷ 5 BTC = 50,000 ZAR/BTC ✓
  5 BTC × 50,000 ZAR/BTC = 250,000 ZAR ✓

Displayed As:
┌─────────────────────────────┐
│ Type:        BUY            │
│ From:        ZAR 250,000    │ ← What you sent
│ To:          BTC 5.0        │ ← What you got
│ Price/Unit:  R50,000        │ ← Exchange rate
│ Date:        2024-01-15     │ ← When
└─────────────────────────────┘

No flags = No problems with this transaction
```

---

### Scenario 2: SELL Transaction with LARGE_TRANSACTION Flag

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-20,SELL,BTC,25,ZAR,1250000,50000
```

#### What The System Detects:
```
⚠️ Status: VALID but FLAGGED for large value

Breakdown:
• You sold: 25 BTC
• You received: R1,250,000
• Rate: R50,000 per BTC

Calculation:
  25 BTC × 50,000 ZAR/BTC = 1,250,000 ZAR ✓
  1,250,000 ZAR exceeds R1,000,000 threshold

Displayed As:
🚩 RED FLAG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: ⚠️ HIGH
Code: LARGE_TRANSACTION
Message: Large transaction detected: R1,250,000 
         (threshold: R1,000,000)

Transaction Details:
┌─────────────────────────────┐
│ Type:        SELL           │
│ From:        BTC 25.0       │ ← What you sold
│ To:          ZAR 1,250,000  │ ← What you got
│ Price/Unit:  R50,000        │ ← Rate per BTC
│ Date:        2024-01-20     │ ← When
└─────────────────────────────┘

Metadata:
  value_zar: 1250000

Action:
🟡 Informational flag only
✓ No data error
✓ Keep documentation ready (SARS will scrutinize)
✓ This can be legitimate - just needs supporting evidence
```

---

### Scenario 3: TRADE Transaction (Crypto to Crypto)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-25,TRADE,BTC,1,ETH,15,50000
```

#### What The System Detects:
```
✅ Status: Transaction is VALID

Breakdown:
• You gave: 1 BTC
• You received: 15 ETH
• BTC value: R50,000 per unit

Note: Price shown is in ZAR (BTC/ZAR reference rate)

Displayed As:
┌──────────────────────────┐
│ Type:        TRADE       │
│ From:        BTC 1.0     │ ← Crypto you gave
│ To:          ETH 15.0    │ ← Crypto you got
│ Price/Unit:  R50,000     │ ← BTC ref rate
│ Date:        2024-01-25  │ ← When
└──────────────────────────┘

What This Means:
• You traded 1 Bitcoin for 15 Ethereum
• At that time, 1 BTC ≈ R50,000
• For tax purposes, this might be:
  - At-market value: 1 BTC = ~15 ETH
  - Or: 1 BTC worth 50,000 ZAR = 15 ETH worth 50,000 ZAR
  - Each ETH therefore ≈ 3,333 ZAR at that rate

Status: ✓ No problems detected
```

---

### Scenario 4: NEGATIVE_AMOUNT Flag (Critical Error)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15,BUY,BTC,-5,ZAR,250000,50000
```

#### What The System Detects:
```
🚨 Status: CRITICAL ERROR - Data is Invalid

Problem:
• FromAmount = -5 BTC
• Cannot have negative purchases!
• Either:
  - Data entry error (accidental minus sign)
  - System malfunction
  - File corruption

Displayed As:
🚩 RED FLAG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: 🚨 CRITICAL ← Must fix!
Code: NEGATIVE_AMOUNT
Message: Transaction has negative source amount: -5

Transaction Details:
┌──────────────────────────┐
│ Type:        BUY         │
│ From:        BTC -5.0    │ ← PROBLEM: Negative!
│ To:          ZAR 250,000 │
│ Price/Unit:  R50,000     │
│ Date:        2024-01-15  │
└──────────────────────────┘

Action: 🔴 MUST FIX
1. Check your CSV source file
2. Remove the minus sign: BTC 5 (not -5)
3. Re-upload the corrected file
```

---

### Scenario 5: INCOMPLETE_DATA Flag (Critical)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
,BUY,BTC,1,ZAR,,50000
```

#### What The System Detects:
```
🚨 Status: CRITICAL ERROR - Missing Required Data

Problems Found:
• Date: EMPTY ← No date!
• ToAmount: EMPTY ← No destination amount!

This makes audit trail impossible.

Displayed As:
🚩 RED FLAG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: 🚨 CRITICAL ← Must fix!
Code: INCOMPLETE_DATA
Message: Transaction has incomplete or invalid data: 
         missing date, zero destination amount

Transaction Details:
┌──────────────────────────┐
│ Type:        BUY         │
│ From:        BTC 1.0     │
│ To:          ZAR ??      │ ← Missing!
│ Price/Unit:  R50000      │
│ Date:        [MISSING]   │ ← Missing!
└──────────────────────────┘

Action: 🔴 MUST FIX
1. Get your original receipt/exchange data
2. Fill in missing date: 2024-01-15
3. Calculate toAmount: 1 BTC × 50,000 = 250,000
4. Updated CSV: 2024-01-15,BUY,BTC,1,ZAR,250000,50000
5. Re-upload
```

---

### Scenario 6: DUPLICATE_TRANSACTION Flag (High)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15,BUY,BTC,1,ZAR,50000,50000
2024-01-15,BUY,BTC,1,ZAR,50000,50000
```

#### What The System Detects:
```
⚠️ Status: HIGH PRIORITY - Duplicate Detected

Problem:
• Two identical transactions on same date
• Likely import error or accidental copy-paste
• Doubles your reported gains/losses
• Creates false audit trail

Displayed As:
🚩 RED FLAG #1
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: ⚠️ HIGH ← Should fix!
Code: DUPLICATE_TRANSACTION
Message: Potential duplicate transaction detected. 
         Original at line 2

Transaction Details:
┌──────────────────────────┐
│ Type:        BUY         │
│ From:        BTC 1.0     │
│ To:          ZAR 50,000  │
│ Price/Unit:  R50,000     │
│ Date:        2024-01-15  │ ← Line 3
└──────────────────────────┘

Metadata:
  duplicate_of_line: 2

🚩 RED FLAG #2
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
[Similar flag, referencing line 3]

Action: 🟡 SHOULD FIX
1. Review your CSV file
2. Check lines 2 and 3 - they're identical
3. Delete one duplicate line
4. Updated CSV:
   2024-01-15,BUY,BTC,1,ZAR,50000,50000
   [Line deleted]
5. Re-upload
```

---

### Scenario 7: WASH_TRADING Flag (Medium)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15 09:00,BUY,BTC,5,ZAR,250000,50000
2024-01-15 17:00,SELL,BTC,5,ZAR,260000,52000
```

#### What The System Detects:
```
⚡ Status: MEDIUM PRIORITY - Suspicious Pattern

Problem:
• Bought 5 BTC at 09:00
• Sold 5 BTC at 17:00 (same day!)
• Within 24-hour window
• Looks like market manipulation

Displayed As:
🚩 RED FLAG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: ⚡ MEDIUM
Code: WASH_TRADING
Message: Potential wash trading detected for BTC: 
         buy and sell within 24 hours

Transaction Details (BUY):
┌──────────────────────────┐
│ Type:        BUY         │
│ From:        BTC 5.0     │
│ To:          ZAR 250,000 │
│ Price/Unit:  R50,000     │
│ Date:        2024-01-15 09:00 │
└──────────────────────────┘

Also flagged:
Transaction Details (SELL):
┌──────────────────────────┐
│ Type:        SELL        │
│ From:        BTC 5.0     │
│ To:          ZAR 260,000 │
│ Price/Unit:  R52,000     │
│ Date:        2024-01-15 17:00 │
└──────────────────────────┘

Action: 🟡 REVIEW & DOCUMENT
1. This is NOT always illegal
2. Day traders and market makers do this legitimately
3. You need to document business purpose:
   - Were you actively trading?
   - Was this market making?
   - Did you have a trading strategy?
4. If yes, keep your trading records
5. If no, consider if this was tax-motivated
```

---

### Scenario 8: NEGATIVE_BALANCE Flag (Critical)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-01,BUY,BTC,5,ZAR,250000,50000
2024-01-15,SELL,BTC,10,ZAR,500000,50000
```

#### What The System Detects:
```
🚨 Status: CRITICAL ERROR - Impossible Transaction

Problem:
• On 2024-01-01: You own 5 BTC (after buy)
• On 2024-01-15: Trying to sell 10 BTC
• You only have 5 BTC, can't sell 10!
• Either:
  - Missing historical buy transactions
  - Wrong amounts in your data
  - Selling from account not in this file

Displayed As:
🚩 RED FLAG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: 🚨 CRITICAL ← Must fix!
Code: NEGATIVE_BALANCE
Message: Transaction causes negative balance for BTC: -5.00000000

Transaction Details:
┌──────────────────────────┐
│ Type:        SELL        │
│ From:        BTC 10.0    │ ← Problem: Don't have this!
│ To:          ZAR 500,000 │
│ Price/Unit:  R50,000     │
│ Date:        2024-01-15  │
└──────────────────────────┘

Metadata:
  balance: -5.00000000  ← Would end up with -5 BTC (impossible!)

Action: 🔴 MUST INVESTIGATE & FIX
1. Check if you had BTC before 2024-01-01:
   - Do you have transactions from 2023?
   - Were you holding BTC from before?
2. Add missing BTC purchase transactions
3. Or: Reduce the sell amount to 5 BTC
4. Update and re-upload

Possible solutions:

Solution A - Add missing buy:
2023-12-15,BUY,BTC,5.5,ZAR,275000,50000
2024-01-01,BUY,BTC,5,ZAR,250000,50000
2024-01-15,SELL,BTC,10,ZAR,500000,50000
Now you have 10.5 BTC available ✓

Solution B - Fix sell amount:
2024-01-01,BUY,BTC,5,ZAR,250000,50000
2024-01-15,SELL,BTC,5,ZAR,250000,50000
Now it's mathematically possible ✓
```

---

### Scenario 9: EXCESSIVE_FEE Flag (Medium)

#### Your CSV:
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price,Fee
2024-01-15,BUY,ZAR,250000,BTC,5,50000,150000
```

#### What The System Detects:
```
⚡ Status: MEDIUM PRIORITY - Suspicious Fee

Problem:
• Transaction value: 5 BTC × R50,000 = R250,000
• Fee charged: R150,000
• Fee percentage: 150,000 / 250,000 = 60%
• Normal fees: 0.1% - 1% of transaction value
• This is 60x the normal rate!

Displayed As:
🚩 RED FLAG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Severity: ⚡ MEDIUM
Code: EXCESSIVE_FEE
Message: Transaction fee (R150,000.00) exceeds 50% 
         of transaction value (R250,000.00)

Transaction Details:
┌──────────────────────────┐
│ Type:        BUY         │
│ From:        ZAR 250,000 │
│ To:          BTC 5.0     │
│ Price/Unit:  R50,000     │
│ Date:        2024-01-15  │
└──────────────────────────┘

Metadata:
  fee: 150000

Action: 🟡 CHECK & DOCUMENT
1. This might be a data entry error:
   - Did you mean R1,500 (0.6% fee)?
   - Did you mean R15,000 (6% fee)?
   - Missing decimal point?
2. Or this was legitimate:
   - Wrong exchange used?
   - Early withdrawal penalty?
   - Exchange rate manipulation?
3. Either way, check your exchange statement
4. Correct amount if it's an error, or document if legitimate
```

---

## Amount Display Consistency Matrix

### What You Upload vs. What Gets Displayed

| Scenario | Your CSV | Displayed As | Consistent? |
|----------|----------|--------------|------------|
| Normal buy | BUY\|ZAR 250k\|BTC 5 @ R50k | From: ZAR 250k, To: BTC 5, Price: R50k | ✅ YES |
| Normal sell | SELL\|BTC 5\|ZAR 250k @ R50k | From: BTC 5, To: ZAR 250k, Price: R50k | ✅ YES |
| Trade | TRADE\|BTC 1\|ETH 15 @ R50k | From: BTC 1, To: ETH 15, Price: R50k | ✅ YES |
| With fee | BUY\|ZAR 250k\|BTC 5 @ R50k\|Fee 1500 | From: ZAR 250k, To: BTC 5, Fee in metadata | ✅ YES |
| Large tx | BUY\|ZAR 1.2M\|BTC 25 @ R50k | From: ZAR 1.2M, To: BTC 25 (Flagged as HIGH) | ✅ YES |

---

## Summary: What's "Correct" and What's Not

### ✅ These Are CORRECT and Consistent:
- Different decimal places (5.0 vs 5.00)
- Large numbers with commas (1,250,000 vs 1250000)
- Amounts in different currencies (BTC vs ZAR)
- Amounts shown as From/To pairs (reflects transaction flow)
- Multiple amounts shown together (helps verify math)

### ❌ These Indicate PROBLEMS:
- Negative amounts (-5, -250000)
- Zero amounts (0, 0.0) when they shouldn't be
- Amounts that don't multiply/divide correctly
- Missing amounts (blank/null fields)
- Reversed From/To for the transaction type

### 🟡 These Need INVESTIGATION:
- Large transactions (>R1M) - informational but needs documentation
- Wash trades (same-day buys/sells) - might be legitimate
- Excessive fees (>50% of value) - might be data entry error
- Duplicate entries - usually import errors

---

## Final Verification Checklist

When reviewing Red Flags about amounts:

```
For each flagged transaction, verify:

☐ Is the math correct?
   From Amount × Price = To Amount (approximately)

☐ Are values in expected format?
   Positive numbers, correct decimal places

☐ Does it match your CSV source?
   Compare displayed amounts to your CSV file

☐ Is this realistic for that date?
   Was BTC actually that price on 2024-01-15?

☐ Does the transaction type make sense?
   BUY should show receiving crypto, not sending

☐ Are all required fields present?
   Date, currency codes, amounts, price

If ✅ to all: Your amounts are CONSISTENT and CORRECT
If ❌ to any: There's a real problem that needs fixing
```

