# FIFO Multiple Purchase Dates - Quick Start Guide

## How to See the FIFO Breakdown

### Step 1: Upload Your Transaction File
1. Open the web interface (http://localhost:3000)
2. Click "Upload CSV/XLSX"
3. Select your transaction file

### Step 2: View Transaction Details
1. Find a SELL or TRADE transaction in the results
2. Click the **▶** (expand) button on the left
3. Scroll down to the **"📦 FIFO Lots Consumed"** section

### Step 3: Understand the Breakdown
Each row shows:
- **Purchase Date**: When you originally bought these coins
- **Amount**: How much of this purchase was used
- **Cost/Unit**: The price you paid per coin
- **Cost Base**: Total cost for this portion (Amount × Cost/Unit)
- **Held (Days)**: How many days you held these coins
  - **LT badge** = Long-term (365+ days)

## What You'll See

### Example Display:
```
📦 FIFO Lots Consumed (3 lots)
This sale consumed 3 lots from your purchase history (oldest first):

Purchase Date      Amount      Cost/Unit    Cost Base    Held
─────────────────────────────────────────────────────────────
Jan 1, 2025       0.5 BTC     R30,000.00   R15,000.00   412 LT
Feb 10, 2025      0.7 BTC     R40,000.00   R28,000.00   371 LT
Mar 20, 2025      0.3 BTC     R50,000.00   R15,000.00   334
─────────────────────────────────────────────────────────────
Total Cost Base:                            R58,000.00
```

## Key Points to Remember

### 1. FIFO = First In, First Out
- Your oldest purchases are used first
- Like a queue at a store - first person in is first out

### 2. Long-Term Badge (LT)
- Appears when coins held for 365+ days
- May qualify for favorable tax treatment
- Important for tax planning

### 3. Multiple Lots
- One sale can use multiple purchases
- Each purchase has its own date and cost
- Total cost base = sum of all lots used

### 4. Partial Lots
- A lot can be partially consumed
- Example: Bought 1.0 BTC, only 0.3 BTC used
- Remaining 0.7 BTC stays in queue for next sale

## Exporting the Data

### PDF Report
1. Click "Download PDF Report"
2. FIFO breakdown included for each sale
3. Professional format for SARS submission

### Excel/CSV Export
1. Click "Download CSV/Excel"
2. Each lot gets its own row
3. Includes "Term" column (Long-term/Short-term)
4. Easy to import into accounting software

## Common Questions

### Q: Why do I see multiple dates for one sale?
**A**: Your sale uses coins from different purchases. FIFO consumes oldest first, so if you bought coins on 3 different days, all 3 dates appear.

### Q: What does the LT badge mean?
**A**: Long-Term holding (365+ days). These coins were held for more than a year before selling.

### Q: Can I change the order?
**A**: No. FIFO (First-In-First-Out) is the required method for South African tax purposes. The order is automatic based on purchase dates.

### Q: What if I don't see the breakdown?
**A**: The breakdown only appears for SELL and TRADE transactions. BUY transactions don't consume lots, they create them.

### Q: How is the cost base calculated?
**A**: For each lot: `Amount Used × Original Cost Per Unit`
Total: Sum of all lots consumed

## Testing the Feature

Use the provided test file: `test_fifo_layering.csv`

This file contains:
- 3 BUY transactions (building inventory)
- 1 SELL transaction (consuming inventory)
- Perfect example of multiple purchase dates

## Troubleshooting

### Issue: No FIFO breakdown showing
**Check**:
- Is it a SELL or TRADE transaction? (BUY doesn't show breakdown)
- Did you expand the row? (Click the ▶ button)
- Does the transaction have enough history?

### Issue: Dates seem wrong
**Check**:
- Verify your CSV file dates are in correct format
- Check transaction order in the table
- Ensure dates are chronological

### Issue: Cost base doesn't match my records
**Check**:
- Verify all purchase transactions are uploaded
- Check if fees are included correctly
- Compare individual lot costs in breakdown

## Benefits

### For You:
✅ Understand which coins were sold
✅ See your holding periods
✅ Plan future sales for tax efficiency
✅ Have proof for SARS audits

### For Your Tax Professional:
✅ Complete audit trail
✅ Clear cost basis calculation
✅ Professional documentation
✅ SARS-compliant methodology

## Need Help?

1. Check the full documentation: `FIFO-IMPLEMENTATION-SUMMARY.md`
2. View examples: `FIFO-LAYERING-EXAMPLE.md`
3. See visual guide: `FIFO-VISUAL-COMPARISON.md`
4. Run the test: `php backend/tests/TestFIFOLayering.php`

---

**Remember**: The FIFO breakdown shows the "layers" of your purchases that make up each sale. This transparency is crucial for accurate tax reporting and audit defense.
