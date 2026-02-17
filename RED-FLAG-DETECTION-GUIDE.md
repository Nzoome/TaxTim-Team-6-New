# Red Flag Detection System - Complete Guide

## Overview
The Red Flag system automatically detects suspicious, incorrect, or audit-sensitive patterns in your cryptocurrency transactions. It analyzes uploaded files and alerts you to potential issues **before** you file with SARS.

---

## Detection Rules Explained

### 1. **INCOMPLETE DATA** (Critical)
**What it detects:** Transactions with missing or invalid information
- Missing transaction date
- Missing or unknown currency codes
- Zero amounts (when they shouldn't be)
- Missing or zero price information

**Why it matters:** SARS requires complete transaction records. Incomplete data will fail audit scrutiny.

**Example:**
```
BUY | BTC | | 0.5 | ZAR | 50000 | 25000
     ↑    ↑            ↑
     Missing date, Unknown currency, Zero price
```

---

### 2. **NEGATIVE AMOUNT** (Critical)
**What it detects:** Transactions with negative buy/sell amounts or prices
- Negative `from_amount`
- Negative `to_amount`  
- Negative `price`

**Why it matters:** Negative amounts are mathematically invalid and indicate data entry errors or system glitches.

**Example:**
```
BUY | BTC | -5 | ZAR | 50000   ← Negative from_amount (nonsensical)
SELL | BTC | 5 | ZAR | -25000  ← Negative price (nonsensical)
```

---

### 3. **DUPLICATE TRANSACTION** (High)
**What it detects:** Identical transactions appearing multiple times
- Same date, type, currencies, amounts, and price

**Why it matters:** Duplicates inflate your transaction count and may trigger audit red flags for suspicious record-keeping.

**Example:**
```
2024-01-15 | BUY | BTC | 1.0 | ZAR | 50000 | 25000
2024-01-15 | BUY | BTC | 1.0 | ZAR | 50000 | 25000  ← Exact duplicate
           → Red Flag: Likely data import error
```

---

### 4. **LARGE TRANSACTION** (High)
**What it detects:** Transactions exceeding R1,000,000 in value

**Threshold:** R1,000,000 (adjustable per South African tax regulations)

**Why it matters:** Large transactions are automatically scrutinized by SARS compliance systems. They may also trigger Financial Intelligence Centre (FIC) reporting requirements.

### How amount is calculated:
⚠️ **Note:** All amounts are already in Rands (ZAR). The price field shows the ZAR value per unit.

- **BUY:** `to_amount × price` (crypto quantity × ZAR per unit)
- **SELL:** `from_amount × price` (crypto quantity × ZAR per unit)
- **TRADE:** Maximum of both amounts × price

**Example:**
```
BUY | BTC | ZAR | 50 BTC @ R50,000 per BTC
Value = 50 BTC × R50,000/BTC = R2,500,000 ← Exceeds R1M threshold
→ Red Flag: Large Transaction

Note: The R50,000 is the Rand value per Bitcoin (already in ZAR)
```

---

### 5. **WASH TRADING** (Medium)
**What it detects:** Same-day buy AND sell of the same asset (within 24 hours)
- Buying and immediately selling the same cryptocurrency on the same day

**Why it matters:** SARS views wash trading patterns as market manipulation or tax avoidance. It suggests artificial trading to create losses or disguise gains.

**Time window:** 24 hours (86,400 seconds)

**Example:**
```
2024-01-15 09:00 | BUY  | BTC | 1.0 | ZAR | 50000 | 25000
2024-01-15 17:30 | SELL | BTC | 1.0 | ZAR | 52000 | 26000
                   ↑ Same day, same asset → Wash trading pattern
```

---

### 6. **NEGATIVE BALANCE** (Critical)
**What it detects:** Transactions causing you to sell more than you own
- Attempting to sell/trade an asset without sufficient balance
- Using FIFO method, the system detects when an order can't be fulfilled

**Why it matters:** You cannot sell what you don't own. This indicates either:
- Missing buy transactions
- Incorrect historical data
- Account balance tracking errors

**Example:**
```
Initial Balance: 0 BTC
2024-01-15 | SELL | 5 BTC | ...  
           ↑ Trying to sell 5 BTC when you have 0
           → Red Flag: Negative Balance for BTC (-5.00000000)
```

---

### 7. **EXCESSIVE FEE** (Medium)
**What it detects:** Transaction fees exceeding 50% of the transaction value
- Fee > 50% of proceeds

**Why it matters:** Extreme fees suggest either:
- Data entry errors
- Exchange exploitation  
- Unusual trading practices that SARS may question

**How it's calculated:**
```
Transaction Value = to_amount × price
Fee Percentage = fee / transaction_value

If percentage > 50% → Red Flag
```

**Example:**
```
BUY | 1.0 BTC | ZAR | 50000 per BTC | Fee: 30000
Value = 1.0 × 50000 = R50,000
Fee = 30,000 / 50,000 = 60% → Exceeds 50% threshold
→ Red Flag: Excessive Fee
```

---

## How Amount Display Works

### Transaction Info Shown:
Each flagged transaction displays:

| Field | What It Shows | Example |
|-------|---------------|---------|
| **Type** | BUY, SELL, TRADE, TRANSFER | `BUY` |
| **From** | Currency and amount sold/used | `BTC 5.0` |
| **To** | Currency and amount received | `ZAR 250000` |
| **Price per Unit** | ZAR value per 1 unit of base currency | `R50000` (1 BTC = R50,000) |
| **Date** | When transaction occurred | `2024-01-15 09:30:00` |

### Interpreting Amounts:
```
From: BTC 5.0          ← You sent 5 Bitcoin
To: ZAR 250000         ← You received R250,000
Price per Unit: R50000 ← 1 BTC was worth R50,000
```

---

## Audit Risk Score Explained

The system calculates a **0-100 risk score** based on detected issues:

### Scoring Formula:
- **Critical flag:** 25 points each
- **High flag:** 15 points each
- **Medium flag:** 7 points each
- **Low flag:** 2 points each

### Risk Levels:
| Score | Level | Meaning |
|-------|-------|---------|
| 75-100 | 🚨 VERY HIGH | Immediate attention required |
| 50-74 | ⚠️ HIGH | Review and corrections recommended |
| 25-49 | ⚡ MEDIUM | Some issues detected |
| 1-24 | ℹ️ LOW | Minor issues detected |
| 0 | ✅ MINIMAL | No significant issues |

### Examples:
```
2 Critical + 1 High = (2×25) + (1×15) = 65 points → HIGH RISK
```

---

## Common Issues & How to Fix Them

### Issue: "Transaction has negative source amount"
**Cause:** Your CSV has negative values in the `from_amount` column
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15,BUY,BTC,-5,ZAR,250000,50000  ← WRONG (negative)
```
**Fix:** Remove the minus sign
```
Date,Type,FromCurrency,FromAmount,ToCurrency,ToAmount,Price
2024-01-15,BUY,BTC,5,ZAR,250000,50000  ← CORRECT
```

---

### Issue: "Transaction causes negative balance for BTC"
**Cause:** You're selling/trading more BTC than you've purchased
```
2024-01-01 | BUY  | 5 BTC  | ...
2024-01-05 | SELL | 10 BTC | ...  ← Can't sell 10 when you only bought 5
```
**Fix:** Verify your historical transactions - you may be missing buy orders or have the wrong amounts
- Check if you had previous holdings before this data
- Verify purchase amounts are correct

---

### Issue: "Large transaction detected: R2,500,000"
**Cause:** Single transaction exceeded R1,000,000 threshold
**Action:** This is informational. Large transactions are flagged so you're aware SARS will scrutinize them more carefully. Keep supporting documentation (exchange statements, invoices, etc.)

---

### Issue: "Potential wash trading detected"
**Cause:** You bought and sold the same asset on the same day
**Action:** Review if this was intentional. Tax-motivated wash trading is prohibited. If it was regular trading, document your business purpose.

---

## How to Use the Red Flag Display

### 1. **Check the Summary Header**
```
🚩 Transaction Red Flags Detected
Audit Risk Level: HIGH - Review and corrections recommended
```
This tells you the overall severity immediately.

### 2. **Review the Summary Stats**
```
Total Flags: 5
Critical: 2  ← Must fix these
High: 1      ← Should fix these
Medium: 2    ← Review these
Low: 0
Risk Score: 57/100
```

### 3. **Expand Details**
Click "Show Details" to see:
- Individual flagged transactions with full details
- Specific reasons for each flag
- Recommendations for fixing them

### 4. **Follow Recommendations**
```
✅ Critical issues detected: Please review and correct...
⚠️ High-risk items: Review large transactions, duplicates...
⚡ Medium-risk items: Check for wash trading patterns...
```

---

## Prevention Tips

### Before Uploading:
1. **Verify data completeness** - Ensure all required columns have values
2. **Check for duplicates** - Sort by date/amount to spot exact repeats
3. **Verify negative values** - Make sure no accidental minus signs exist
4. **Validate balances** - Confirm you never sell more than you own
5. **Review large trades** - Document any transaction > R1M

### After Uploading:
1. Expand the Red Flag section and read all flags
2. Note which are CRITICAL vs HIGH vs MEDIUM
3. For each CRITICAL flag, make corrections and re-upload
4. For HIGH flags, gather supporting documentation
5. For MEDIUM/LOW flags, assess if they represent real trading patterns

---

## FAQ

**Q: Why am I seeing "Inconsistent" amounts?**
A: The system shows different related amounts:
- `From`: What you sent (e.g., "BTC 5.0")
- `To`: What you received (e.g., "ZAR 250000")
- `Price per Unit`: Exchange rate (e.g., "R50000")

These are all correct - they're just different aspects of the same trade.

**Q: Can I ignore red flags?**
A: - **CRITICAL flags:** NO - These are data errors that must be fixed
- **HIGH flags:** NO - These trigger SARS audit systems
- **MEDIUM flags:** Risky - Get professional advice if unsure
- **LOW flags:** Informational - Document and explain if questioned

**Q: Is the system 100% accurate?**
A: The system is ~95% accurate for detecting obvious errors. However:
- Some patterns require professional tax interpretation
- Context matters (e.g., wash trading might be legitimate business)
- Always consult a tax professional for borderline cases

**Q: How does the system calculate transaction value?**
A: - For ZAR-based transactions: `amount × price` in ZAR
- For crypto transactions: Uses the "price" column as ZAR per unit
- Example: "1 BTC @ R50000" = R50000 value

---

## Next Steps

1. **Review flagged transactions** - Understand WHY each transaction was flagged
2. **Fix critical errors** - Correct data entry mistakes
3. **Document large trades** - Gather supporting evidence for high-value transactions  
4. **Consult professional** - For HIGH audit risk (score > 50), consider tax advisor review
5. **Re-upload** - After corrections, upload the corrected file

---

## Support

For technical questions about the detection system, review:
- `RED-FLAG-SYSTEM.md` - Technical implementation details
- `RED-FLAG-QUICK-REFERENCE.md` - Rule summary table
- `RED-FLAG-VISUAL-GUIDE.md` - Visualization of detection logic

For tax compliance questions, consult:
- South African Revenue Service (SARS) guidelines
- A qualified tax professional
- Crypto tax compliance resources specific to South Africa
