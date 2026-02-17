# Red Flag System - Quick Start Guide

## In 2 Minutes: How the System Works

### What It Does
Automatically scans your cryptocurrency transactions for:
- **Data errors** (incomplete, negative, or invalid amounts)
- **Suspicious patterns** (wash trading, duplicates)
- **Audit triggers** (large transactions, negative balances)

### What You See

```
┌────────────────────────────────────────────────┐
│ 🚩 Transaction Red Flags Detected              │
│ Audit Risk Level: HIGH                         │
├────────────────────────────────────────────────┤
│ Total Flags: 5                                 │
│ 🚨 Critical: 2  | ⚠️ High: 1                   │
│ ⚡ Medium: 2  | ℹ️ Low: 0                      │
│ Risk Score: 57/100                             │
└────────────────────────────────────────────────┘
```

### What Each Severity Means

| Icon | Severity | What To Do |
|------|----------|-----------|
| 🚨 | CRITICAL | **MUST FIX** - Data error blocking calculation |
| ⚠️ | HIGH | **SHOULD FIX** - Audit risk or duplicate |
| ⚡ | MEDIUM | **REVIEW** - Suspicious pattern, may be legitimate |
| ℹ️ | LOW | **DOCUMENT** - Minor issue or informational |

---

## The 7 Detection Rules (In Plain English)

### 1. INCOMPLETE_DATA 🚨 CRITICAL
```
Problem: Missing or zero values
Example: No date | No currency code | Zero price
Fix: Fill in missing information
```

### 2. NEGATIVE_AMOUNT 🚨 CRITICAL
```
Problem: Negative numbers where they shouldn't be
Example: Buying -5 BTC | Price = -50000 ZAR
Fix: Remove minus sign
```

### 3. DUPLICATE_TRANSACTION ⚠️ HIGH
```
Problem: Exact same transaction appearing twice
Example: Same date, type, amounts, price
Fix: Delete one copy
```

### 4. LARGE_TRANSACTION ⚠️ HIGH
```
Problem: Transaction exceeds R1,000,000
Example: Buying 50 BTC @ R50,000 each = R2.5M
Fix: None - just document and have receipts ready
```

### 5. WASH_TRADING ⚡ MEDIUM
```
Problem: Buying and selling same asset same day
Example: BUY 5 BTC at 09:00, SELL 5 BTC at 17:00
Fix: Document business purpose if questioned
```

### 6. NEGATIVE_BALANCE 🚨 CRITICAL
```
Problem: Selling more than you own
Example: Own 5 BTC but trying to sell 10 BTC
Fix: Add missing buy transactions or reduce sell amount
```

### 7. EXCESSIVE_FEE ⚡ MEDIUM
```
Problem: Fee > 50% of transaction value
Example: Fee R150,000 on R250,000 transaction (60%)
Fix: Check if data entry error, correct if so
```

---

## Understanding Your Amounts

### Every Transaction Has 4 Related Numbers

```
Your CSV:           2024-01-15,BUY,ZAR,250000,BTC,5,50000

Displayed as:
• Type:         BUY (what kind of transaction)
• From:         ZAR 250,000 (what you paid in Rands)
• To:           BTC 5.0 (what you received)  
• Price/Unit:   R50,000 (ZAR value per 1 BTC)

⚠️ IMPORTANT: All amounts are already in Rands (ZAR)
   - From/To amounts show quantity × ZAR value
   - Price shows the ZAR value per single unit
   - No currency conversion needed

Verification:
To × Price/Unit = From? 
5 BTC × R50,000/BTC = R250,000 ✓ CONSISTENT
```

### Why Show All Four?

**To verify they match each other!** If they don't, the system flags it.

- **Different** doesn't mean **wrong**
- **Multiple amounts** = **Better verification**
- **Math must check out** or it's flagged

---

## How to Read a Red Flag

### Example: Your First Red Flag

```
FLAGGED TRANSACTION #1
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚨 SEVERITY: CRITICAL
CODE: NEGATIVE_AMOUNT
MESSAGE: Transaction has negative source amount: -5

Transaction Details:
┌──────────────────────────┐
│ Type:        BUY         │
│ From:        BTC -5.0    │ ← The Problem (negative!)
│ To:          ZAR 250,000 │
│ Price/Unit:  R50,000     │
│ Date:        2024-01-15  │
└──────────────────────────┘

WHAT TO DO:
1. Open your CSV file
2. Find 2024-01-15 row
3. Check the amount: is it "-5"?
4. Change to "5" (remove minus)
5. Save and re-upload
```

---

## The Risk Score Explained

### How It's Calculated

```
Each problem adds points:
🚨 CRITICAL flag    = 25 points
⚠️ HIGH flag        = 15 points
⚡ MEDIUM flag      = 7 points
ℹ️ LOW flag         = 2 points

Example:
2 CRITICAL (2×25) + 1 HIGH (1×15) = 65/100
→ Risk Level: 🚨 VERY HIGH (75-100 is max severity)
```

### What Your Score Means

| Score | Level | Meaning |
|-------|-------|---------|
| 0 | ✅ MINIMAL | No problems |
| 1-24 | ℹ️ LOW | Minor issues |
| 25-49 | ⚡ MEDIUM | Some issues |
| 50-74 | ⚠️ HIGH | Significant issues |
| 75-100 | 🚨 VERY HIGH | Critical issues |

---

## Quick Fix Guide

### CRITICAL Issues (🚨 MUST FIX)

#### Problem: "Transaction has negative source amount: -5"
```
Fix: Remove the minus sign
FROM: -5
TO: 5
```

#### Problem: "Transaction has incomplete or invalid data"
```
Fix: Fill in missing values
BEFORE: 2024-01-15,BUY,BTC,1,,50000
AFTER:  2024-01-15,BUY,BTC,1,250000,50000
```

#### Problem: "Transaction causes negative balance for BTC: -5"
```
Fix: Either add missing purchases OR reduce sell amount
BEFORE:
  2024-01-01 BUY 5 BTC
  2024-01-15 SELL 10 BTC ← Can't sell 10 when you only have 5!
  
AFTER (Option 1 - Add missing purchase):
  2024-01-01 BUY 5 BTC
  2024-01-10 BUY 5 BTC ← Now you have 10 total
  2024-01-15 SELL 10 BTC ← Now it works!

AFTER (Option 2 - Fix the amount):
  2024-01-01 BUY 5 BTC
  2024-01-15 SELL 5 BTC ← Changed from 10 to 5
```

### HIGH Issues (⚠️ SHOULD FIX)

#### Problem: "Potential duplicate transaction detected"
```
Fix: Delete the duplicate row from your CSV
Your CSV has two identical rows on the same date.
Keep one, delete one.
```

#### Problem: "Large transaction detected: R1,250,000"
```
This is OK! You don't need to fix it.
Just make sure you have receipts/documentation.
```

### MEDIUM Issues (⚡ REVIEW)

#### Problem: "Potential wash trading detected"
```
This might be OK. 
If you're a legitimate day trader: Keep your trading records.
If you just made a quick profit: Document why you bought and sold same day.
SARS wants to see business purpose, not tax avoidance.
```

#### Problem: "Transaction fee exceeds 50% of transaction value"
```
Probably a data error:
Check if you meant: R1,500 (not R150,000)
Or: R15,000 (not R150,000)
Fix if it's an error, document if it's real.
```

---

## The Complete Workflow

### Step 1: Upload File
```
Click "Upload/Calculate" → Select your CSV → System analyzes
```

### Step 2: Review Red Flags
```
Red Flag panel appears showing:
- Summary of issues
- Total risk score
- Which flags are CRITICAL vs HIGH vs MEDIUM
```

### Step 3: Click "Show Details"
```
Expands to show:
- Each flagged transaction
- Exactly what's wrong
- The specific values that triggered the flag
```

### Step 4: Fix or Document
```
For CRITICAL flags:
  - Make corrections to CSV
  - Re-upload file
  
For HIGH/MEDIUM flags:
  - Gather supporting documentation
  - Keep receipts ready for SARS
  
For LOW flags:
  - Document explanations
  - Continue with filing
```

### Step 5: Monitor Your Score
```
✅ 0 score = Ready to file
🟡 1-24 score = Minor issues, probably OK
🟡 25-49 score = Some issues, address if possible
🔴 50-74 score = Significant, get professional help
🔴 75-100 score = Critical, must fix before filing
```

---

## Common Questions

### Q: "Are my amounts wrong if they show inconsistently?"
**A:** No, they show different aspects:
- **From**: What you sent (currency + amount)
- **To**: What you got (currency + amount)
- **Price**: Exchange rate per unit

These are all part of ONE transaction. All being shown = Better verification.

### Q: "Should I fix ALL red flags before filing?"
**A:** It depends on severity:
- 🚨 CRITICAL: YES - Must fix
- ⚠️ HIGH: YES - Should fix
- ⚡ MEDIUM: NO - Just document
- ℹ️ LOW: NO - For info only

### Q: "What if my risk score is high (>50)?"
**A:** Get professional help. Consult a:
- Crypto tax specialist
- South African tax accountant
- SARS tax advisor

They can help decide if flags are real issues or legitimate patterns.

### Q: "Can I ignore a LARGE_TRANSACTION flag?"
**A:** YES - it's informational only.
But keep documentation ready (receipts, exchange statements) because SARS scrutinizes large transactions.

---

## Documentation You Need

Based on your red flags, keep ready:

| Flag Type | Documentation Needed |
|-----------|----------------------|
| Negative Balance | Bank/exchange transaction history |
| Large Transaction | Receipt, invoice, exchange statement |
| Wash Trading | Trading records, business purpose statement |
| Excessive Fee | Exchange fee schedule, justified rate |
| Duplicate | Original exchange data proving it's unique |

---

## When You're Done

### All Red Flags Cleared?
```
✅ Green status
✅ Risk score = 0
✅ No flags showing
→ Ready to file with SARS
```

### Can't Clear All Flags?
```
🟡 Some MEDIUM/LOW flags remain
🟡 Risk score = 20-40
→ Document explanations, file with SARS
→ SARS may ask questions, be ready to explain
```

### Won't Clear Critical Flags?
```
🔴 CRITICAL flags remain
🔴 Risk score = 50+
→ DO NOT FILE
→ Get professional help first
→ Fix data or consult tax advisor
```

---

## Get More Help

For detailed explanations, read:
- `RED-FLAG-DETECTION-GUIDE.md` - Complete rule explanations
- `RED-FLAG-AMOUNTS-EXPLAINED.md` - How amounts work
- `RED-FLAG-DETECTION-RULES.md` - Rule matrix and scenarios
- `RED-FLAG-VISUAL-SCENARIOS.md` - Example transactions

---

## Summary

**The system detects 7 types of problems** using severity levels.

**You need to fix CRITICAL issues** before filing.

**HIGH issues should be fixed**, but might be legitimate (large transactions are OK if documented).

**MEDIUM/LOW issues are informational** - document and continue.

**All amounts shown are CONSISTENT** - they're different views of the same transaction.

**Your risk score combines all issues** into one 0-100 number.

**Higher score = More serious problems = Get professional help**

---

## Ready to Start?

1. ✅ Upload your CSV file
2. ✅ Look at the Red Flag summary
3. ✅ Click "Show Details" 
4. ✅ For each CRITICAL flag: Fix it
5. ✅ Re-upload if you made changes
6. ✅ When score is green (0) or low (1-24): Ready to file

**Questions? Check the detailed guides above.** 📚

