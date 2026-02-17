# FIFO Layering Example - Multiple Purchase Dates

This document demonstrates how the improved FIFO system now displays different purchase dates when selling cryptocurrency.

## Example Scenario

### Purchases (Building up inventory):
1. **Jan 1, 2025**: Bought 0.5 BTC at R30,000/BTC = R15,000 total
2. **Feb 10, 2025**: Bought 0.7 BTC at R40,000/BTC = R28,000 total  
3. **Mar 20, 2025**: Bought 1.0 BTC at R50,000/BTC = R50,000 total

**Total Inventory**: 2.2 BTC with total cost of R93,000

---

### Sale Transaction (FIFO Deployment):
**Today (Feb 17, 2026)**: Sell 1.5 BTC at R60,000/BTC = R90,000 proceeds

---

## How FIFO Breaks Down the Sale

The calculator will "peel" through your purchase history layers (oldest first):

### FIFO Lots Consumed Display:

| Purchase Date | Amount | Cost/Unit | Cost Base | Held (Days) | Term |
|--------------|--------|-----------|-----------|-------------|------|
| Jan 1, 2025  | 0.5 BTC | R30,000 | R15,000 | 413 days | **LT** (Long-term) |
| Feb 10, 2025 | 0.7 BTC | R40,000 | R28,000 | 373 days | **LT** (Long-term) |
| Mar 20, 2025 | 0.3 BTC | R50,000 | R15,000 | 335 days | Short-term |
| **Total** | **1.5 BTC** | - | **R58,000** | - | - |

### Capital Gains Calculation:
- **Proceeds**: R90,000 (1.5 BTC × R60,000)
- **Cost Base**: R58,000 (from FIFO lots above)
- **Capital Gain**: R32,000

---

## Key Improvements Implemented

### 1. **Multiple Purchase Dates Displayed**
   - Each lot shows its original purchase date
   - Easy to see which "bucket" of coins is being used

### 2. **Professional Styling**
   - Clean, minimalist design
   - Reduced color usage for professional appearance
   - Clear typography and spacing

### 3. **Holding Period Indicators**
   - Days held displayed for each lot
   - "LT" badge for long-term holdings (365+ days)
   - Helps identify tax treatment implications

### 4. **Clear Cost Basis Breakdown**
   - Shows amount consumed from each lot
   - Individual cost per unit for each purchase
   - Total cost base calculated and highlighted

### 5. **Consistent Across All Outputs**
   - Web interface (TransactionTableEnhanced)
   - PDF reports
   - Excel/CSV exports

---

## Testing the Feature

To test this improvement with your own data:

1. Upload a CSV/XLSX file with multiple BUY transactions
2. Add a SELL transaction that spans multiple lots
3. Expand the transaction details in the results table
4. Look for the "📦 FIFO Lots Consumed" section
5. Verify that multiple purchase dates are shown with their amounts

---

## Technical Details

### Backend Changes:
- **File**: `backend/src/Services/FIFOEngine.php`
- **Method**: `formatConsumptionRecords()`
- Added `purchaseDate` field mapping
- Added `ageInDays` calculation
- Added `wallet` information for each lot

### Frontend Changes:
- **File**: `frontend/src/components/TransactionTableEnhanced.js`
- Enhanced FIFO lots display with better structure
- Added holding period indicators
- Improved introductory text

### Styling Changes:
- **File**: `frontend/src/components/TransactionTableEnhanced.css`
- Professional color scheme (grays instead of bright colors)
- Better typography and spacing
- Long-term badge styling
- Cleaner table borders and backgrounds

### Export Updates:
- **PDF Generator**: Enhanced with lot counts and total row
- **Excel Generator**: Added term classification and totals

---

## Benefits

1. **Tax Compliance**: Clear audit trail showing which coins were sold
2. **Transparency**: Users can verify FIFO calculations
3. **Holding Period Tracking**: Easy to identify long-term vs short-term gains
4. **Professional Presentation**: Suitable for submitting to SARS or tax professionals
5. **Educational**: Helps users understand how FIFO works

---

## Next Steps

The system is now ready to handle complex scenarios with multiple purchases and partial sales. The FIFO layering is displayed clearly and professionally across all interfaces.
