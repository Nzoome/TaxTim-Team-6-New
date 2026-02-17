# Red Flag System - Detection Rules Matrix

## Quick Reference: How Each Rule Works

### Rule #1: INCOMPLETE DATA
```
┌─────────────────────────────────────────────┐
│ Severity: 🚨 CRITICAL                       │
├─────────────────────────────────────────────┤
│ Detects: Missing required transaction data   │
├─────────────────────────────────────────────┤
│ Examples:                                    │
│ ❌ No transaction date                       │
│ ❌ Currency = "UNKNOWN"                      │
│ ❌ Amount = 0 (when shouldn't be)            │
│ ❌ Price = 0 or missing                      │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ SARS requires complete audit trail.          │
│ Incomplete records = Audit failure           │
└─────────────────────────────────────────────┘
```

### Rule #2: NEGATIVE AMOUNT
```
┌─────────────────────────────────────────────┐
│ Severity: 🚨 CRITICAL                       │
├─────────────────────────────────────────────┤
│ Detects: Mathematically invalid values       │
├─────────────────────────────────────────────┤
│ Examples:                                    │
│ ❌ from_amount = -5 BTC (can't buy negative) │
│ ❌ to_amount = -250000 ZAR                   │
│ ❌ price = -50000 (negative price!)          │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ Data entry error or system malfunction       │
│ Must be corrected before filing              │
└─────────────────────────────────────────────┘
```

### Rule #3: DUPLICATE TRANSACTION
```
┌─────────────────────────────────────────────┐
│ Severity: ⚠️ HIGH                           │
├─────────────────────────────────────────────┤
│ Detects: Identical transactions appearing   │
│          multiple times                     │
├─────────────────────────────────────────────┤
│ Match Criteria (ALL must match):             │
│ • Date and Time (same second)                │
│ • Transaction Type (BUY, SELL, etc)          │
│ • From Currency & Amount                     │
│ • To Currency & Amount                       │
│ • Price per unit                             │
├─────────────────────────────────────────────┤
│ Example:                                     │
│ Transaction 1: 2024-01-15 09:00 | BUY...    │
│ Transaction 2: 2024-01-15 09:00 | BUY...    │
│ → FLAGGED: Likely duplicate entry            │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ Duplicates inflate gain/loss calculations    │
│ Creates false audit trail                    │
└─────────────────────────────────────────────┘
```

### Rule #4: LARGE TRANSACTION
```
┌─────────────────────────────────────────────┐
│ Severity: ⚠️ HIGH                           │
├─────────────────────────────────────────────┤
│ Detects: Single transaction > R1,000,000     │
├─────────────────────────────────────────────┤
│ Calculation:                                 │
│ For BUY:   to_amount × price                 │
│ For SELL:  from_amount × price               │
│ For TRADE: MAX(both) × price                 │
├─────────────────────────────────────────────┤
│ Example:                                     │
│ BUY 50 BTC @ R50,000 per BTC                │
│ = 50 × 50,000 = R2,500,000                   │
│ → FLAGGED: Exceeds R1M threshold             │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ SARS & FIC automatically scrutinize           │
│ large transactions                           │
│ Must have documentation ready                │
└─────────────────────────────────────────────┘
```

### Rule #5: WASH TRADING
```
┌─────────────────────────────────────────────┐
│ Severity: ⚡ MEDIUM                         │
├─────────────────────────────────────────────┤
│ Detects: Same-day buy & sell of same asset   │
├─────────────────────────────────────────────┤
│ Requirements:                                │
│ • Both BUY and SELL within 24 hours          │
│ • Same cryptocurrency asset                  │
│ • On the same calendar date                  │
├─────────────────────────────────────────────┤
│ Example Pattern:                             │
│ 2024-01-15 09:00 | BUY  5 BTC @ R50,000      │
│ 2024-01-15 17:00 | SELL 5 BTC @ R52,000      │
│         ↑ Same day, same asset               │
│ → FLAGGED: Wash trading pattern              │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ SARS views as market manipulation            │
│ Pattern suggests tax avoidance                │
│ Must explain business purpose                │
└─────────────────────────────────────────────┘
```

### Rule #6: NEGATIVE BALANCE
```
┌─────────────────────────────────────────────┐
│ Severity: 🚨 CRITICAL                       │
├─────────────────────────────────────────────┤
│ Detects: Selling more than you own           │
│ Method: FIFO (First-In-First-Out)            │
├─────────────────────────────────────────────┤
│ How it works:                                │
│ 1. Track purchases in order                  │
│ 2. For each SELL, consume from oldest lot    │
│ 3. Flag if trying to sell more than balance  │
├─────────────────────────────────────────────┤
│ Example Scenario:                            │
│ 2024-01-01 | BUY 5 BTC                       │
│ 2024-01-15 | SELL 10 BTC ← Can't sell 10!    │
│ Available: 5 BTC                             │
│ Trying to sell: 10 BTC                       │
│ Deficit: -5 BTC                              │
│ → FLAGGED: Negative Balance for BTC          │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ Indicates missing buy transactions           │
│ Or incorrect amounts in your data            │
│ Must verify historical holdings              │
└─────────────────────────────────────────────┘
```

### Rule #7: EXCESSIVE FEE
```
┌─────────────────────────────────────────────┐
│ Severity: ⚡ MEDIUM                         │
├─────────────────────────────────────────────┤
│ Detects: Fees > 50% of transaction value     │
├─────────────────────────────────────────────┤
│ Calculation:                                 │
│ Transaction Value = to_amount × price        │
│ Fee % = fee / transaction_value              │
│ Threshold = 50%                              │
├─────────────────────────────────────────────┤
│ Example:                                     │
│ Buy 1 BTC @ R50,000 per unit                │
│ Transaction Value = R50,000                  │
│ Fee Charged = R30,000                        │
│ Fee % = 30,000 / 50,000 = 60%                │
│ → FLAGGED: Exceeds 50% threshold             │
├─────────────────────────────────────────────┤
│ Why It Matters:                              │
│ Indicates suspicious trading practices       │
│ Or data entry error (decimal point?)         │
│ SARS may question legitimacy                 │
└─────────────────────────────────────────────┘
```

---

## Risk Score Calculation

### How Points Are Awarded:
```
Critical Flag  →  25 points each
High Flag      →  15 points each
Medium Flag    →   7 points each
Low Flag       →   2 points each

Total capped at 100
```

### Example Score Breakdown:
```
Your Flags:
• 2 Critical Issues        = 2 × 25 = 50 points
• 1 High Issue            = 1 × 15 = 15 points
• 2 Medium Issues         = 2 × 7  = 14 points
• 0 Low Issues            = 0 × 2  = 0 points
                            ──────────────────
TOTAL SCORE                = 79 points

Risk Level = 🚨 VERY HIGH (75-100)
Meaning: Immediate attention required
```

---

## Risk Levels at a Glance

```
╔════════════════════════════════════════════════╗
║ Score 75-100  │ 🚨 VERY HIGH                   ║
║ ─────────────────────────────────────────────  ║
║ Status: Critical Issues Found                  ║
║ Action: Fix immediately before filing          ║
║ Recommended: Consult tax professional          ║
╠════════════════════════════════════════════════╣
║ Score 50-74   │ ⚠️ HIGH                        ║
║ ─────────────────────────────────────────────  ║
║ Status: Significant Issues Found               ║
║ Action: Review and correct major items         ║
║ Recommended: Get professional advice           ║
╠════════════════════════════════════════════════╣
║ Score 25-49   │ ⚡ MEDIUM                      ║
║ ─────────────────────────────────────────────  ║
║ Status: Some Issues Detected                   ║
║ Action: Address issues, explain patterns       ║
║ Recommended: Have documentation ready          ║
╠════════════════════════════════════════════════╣
║ Score 1-24    │ ℹ️ LOW                         ║
║ ─────────────────────────────────────────────  ║
║ Status: Minor Issues Detected                  ║
║ Action: Review for context/legitimacy          ║
║ Recommended: Document explanations             ║
╠════════════════════════════════════════════════╣
║ Score 0       │ ✅ MINIMAL                     ║
║ ─────────────────────────────────────────────  ║
║ Status: No Significant Issues                  ║
║ Action: Ready for SARS filing                  ║
║ Recommended: Keep records organized            ║
╚════════════════════════════════════════════════╝
```

---

## Transaction Amount Display Explained

When you see a flagged transaction, here's what each field means:

```
FLAGGED TRANSACTION DETAILS:
┌────────────────────────────────────────────────┐
│ Type:              BUY                          │
│ From:              BTC 5.0                      │
│ To:                ZAR 250000                   │
│ Price per Unit:    R50000                       │
│ Date:              2024-01-15 09:30:00          │
└────────────────────────────────────────────────┘

INTERPRETATION:
• You performed a BUY transaction
• You used: 5.0 BTC (what you sent)
• You received: R250,000 (what you got)
• Exchange rate: R50,000 per 1 BTC
• When: January 15, 2024 at 9:30 AM

VERIFICATION:
5 BTC × R50,000/BTC = R250,000 ✓ (Math checks out)
```

---

## Common Scenarios

### Scenario 1: Missing Data
```
Your CSV:
Date,Type,From,FromAmt,To,ToAmt,Price
,BUY,BTC,1.0,ZAR,50000,50000

Missing: Date ← Empty field
Detection: INCOMPLETE_DATA (CRITICAL)
Fix: Add date: 2024-01-15,BUY,BTC,1.0,ZAR,50000,50000
```

### Scenario 2: Negative Amount  
```
Your CSV:
2024-01-15,BUY,BTC,-5,ZAR,250000,50000

Problem: from_amount = -5 (negative purchase!)
Detection: NEGATIVE_AMOUNT (CRITICAL)
Fix: Remove minus: 2024-01-15,BUY,BTC,5,ZAR,250000,50000
```

### Scenario 3: Duplicate Entry
```
Your CSV:
2024-01-15,BUY,BTC,1.0,ZAR,50000,50000
2024-01-15,BUY,BTC,1.0,ZAR,50000,50000

Problem: Exact same row twice (likely import error)
Detection: DUPLICATE_TRANSACTION (HIGH)
Fix: Delete one duplicate row
```

### Scenario 4: Insufficient Balance
```
Your CSV:
2024-01-01,BUY,BTC,5.0,ZAR,250000,50000
2024-01-15,SELL,BTC,10.0,ZAR,500000,50000

Problem: Only bought 5 BTC, trying to sell 10
Detection: NEGATIVE_BALANCE for BTC (CRITICAL)
Fix: Either:
  - Find your missing purchase (did you buy before 2024-01-01?)
  - Correct the sell amount to 5.0 BTC
  - Verify your historical holdings
```

### Scenario 5: Large Trade
```
Your CSV:
2024-01-15,BUY,BTC,50.0,ZAR,2500000,50000

Calculation: 50 BTC × R50,000 = R2,500,000
Threshold: R1,000,000
Status: Exceeds by R1,500,000
Detection: LARGE_TRANSACTION (HIGH)
Action: This is informational. Keep receipts/exchange statements.
```

### Scenario 6: Same-Day Trading
```
Your CSV:
2024-01-15 09:00,BUY,BTC,5.0,ZAR,250000,50000
2024-01-15 17:00,SELL,BTC,5.0,ZAR,260000,52000

Problem: Bought and sold same asset same day
Detection: WASH_TRADING (MEDIUM)
Note: Wash trading can be legitimate business activity.
Action: Document business purpose if questioned by SARS.
```

---

## Amount Consistency Check

The system ensures all displayed amounts are mathematically consistent:

```
Given:
- From Amount: 5 BTC
- To Amount: 250,000 ZAR  
- Price per Unit: R50,000/BTC

Verification:
5 BTC × R50,000/BTC = 250,000 ZAR ✓

If this doesn't match, the transaction is flagged for
INCOMPLETE_DATA or data inconsistency.
```

---

## When to Worry (And When Not To)

| Flag Type | Severity | Action |
|-----------|----------|--------|
| Missing Date | CRITICAL | Must fix - cannot process |
| Negative Amount | CRITICAL | Must fix - mathematically invalid |
| Zero Price | CRITICAL | Must fix - cannot calculate gains |
| Duplicate Entry | HIGH | Should fix - inflates records |
| Large Trade | HIGH | Don't fix - but prepare documentation |
| Wash Trading | MEDIUM | Review - may require explanation |
| Excessive Fee | MEDIUM | Check - may be data entry error |
| Negative Balance | CRITICAL | Must investigate - balance issue |

---

## Next Steps After Seeing Flags

1. **Read each flag carefully** - Understand WHY it was flagged
2. **Categorize by type:**
   - Data errors (fix immediately)
   - Pattern issues (gather documentation)
   - Large transactions (prepare evidence)
3. **Fix critical errors** - Re-upload if changed
4. **Document pattern trades** - Explain business purpose
5. **Consult professional** - If audit risk > 50

---

## Technical Details

For implementation details, see:
- `RED-FLAG-SYSTEM.md` - Algorithm documentation
- `SuspiciousTransactionDetector.php` - Backend source code
- `SuspiciousTransactionSummary.js` - Frontend display code

