# FIFO Date Breakdown - Visual Comparison

## Before vs After

### ❌ BEFORE: Single Line Display
```
Date: 2026-02-17
Type: SELL
Amount: 1.5 BTC
Proceeds: R90,000.00
Cost Base: R58,000.00
Capital Gain: R32,000.00
```

**Problem**: No visibility into which purchases were used!

---

### ✅ AFTER: Layered Breakdown

```
Date: 2026-02-17
Type: SELL  
Amount: 1.5 BTC
Proceeds: R90,000.00
Cost Base: R58,000.00
Capital Gain: R32,000.00

📦 FIFO LOTS CONSUMED (3 lots):
This sale consumed 3 lots from your purchase history (oldest first):

╔══════════════════════════════════════════════════════════════╗
║ Purchase Date   │ Amount  │ Cost/Unit  │ Cost Base │ Held   ║
╠═════════════════╪═════════╪════════════╪═══════════╪════════╣
║ Jan 1, 2025     │ 0.5 BTC │ R30,000.00 │ R15,000   │ 412 LT ║
║ Feb 10, 2025    │ 0.7 BTC │ R40,000.00 │ R28,000   │ 371 LT ║
║ Mar 20, 2025    │ 0.3 BTC │ R50,000.00 │ R15,000   │ 334    ║
╠═════════════════╧═════════╧════════════╪═══════════╧════════╣
║ Total Cost Base:                       │ R58,000.00         ║
╚════════════════════════════════════════╧════════════════════╝

💡 FIFO Method: First-In-First-Out - oldest coins sold first
```

**Benefits**: Complete transparency of which coins were sold!

---

## Understanding the Breakdown

### The "Peeling Layers" Concept

Think of your crypto purchases as a stack of pancakes:

```
        NEWEST (Top of stack)
    ┌─────────────────────────┐
    │  Mar 20: 1.0 BTC        │ ← Still remains (0.7 BTC left)
    │  @ R50,000/BTC          │
    ├─────────────────────────┤
    │  Feb 10: 0.7 BTC        │ ← CONSUMED (fully used)
    │  @ R40,000/BTC          │
    ├─────────────────────────┤
    │  Jan 1: 0.5 BTC         │ ← CONSUMED (fully used)
    │  @ R30,000/BTC          │
    └─────────────────────────┘
        OLDEST (Bottom of stack)
```

When you sell 1.5 BTC, FIFO takes from the bottom (oldest) first:
1. **Jan 1 lot**: Take all 0.5 BTC (oldest)
2. **Feb 10 lot**: Take all 0.7 BTC (second oldest)
3. **Mar 20 lot**: Take 0.3 BTC (only partial needed)

Total: 0.5 + 0.7 + 0.3 = 1.5 BTC ✅

---

## Why Multiple Dates Matter for Taxes

### Example: Same Sale, Different Dates = Different Tax Treatment

#### Scenario A: All Long-Term (365+ days)
```
All lots held > 365 days = Long-term capital gains
Potentially lower tax rates in some jurisdictions
```

#### Scenario B: Mixed Term
```
Some lots < 365 days = Short-term capital gains
Some lots > 365 days = Long-term capital gains
Different tax treatment for each portion
```

### Our Display Shows Both:
- **Days Held**: Exact number (412, 371, 334)
- **LT Badge**: Visual indicator for long-term holdings
- **Purchase Date**: Proof for tax authorities

---

## Professional Styling Philosophy

### Old Style (Rejected):
```css
background: linear-gradient(135deg, #fff5f5 0%, #fffaf0 100%);
border-color: #fc8181; /* Bright pink/orange */
```
❌ Too colorful
❌ Looks unprofessional
❌ Distracting

### New Style (Implemented):
```css
background: #fafbfc; /* Subtle gray */
border: 1px solid #d1d5db; /* Neutral border */
```
✅ Professional
✅ Clean and minimal
✅ Suitable for tax reports
✅ Easy to read and print

---

## Real-World Use Cases

### Use Case 1: SARS Audit
**Question**: "How did you calculate R58,000 cost base?"

**Answer**: Show the FIFO breakdown table:
- Jan 1 purchase: 0.5 BTC × R30,000 = R15,000
- Feb 10 purchase: 0.7 BTC × R40,000 = R28,000
- Mar 20 purchase: 0.3 BTC × R50,000 = R15,000
- **Total**: R58,000 ✅

### Use Case 2: Tax Planning
**Question**: "Should I sell now or wait?"

**Answer**: Check the FIFO breakdown:
- Next lots to be sold are from Jan & Feb 2025
- Both are long-term (held > 365 days)
- Potentially favorable tax treatment

### Use Case 3: Record Keeping
**Question**: "Which specific coins did I sell?"

**Answer**: The breakdown shows:
- Exact purchase dates
- Exact amounts from each purchase
- Complete audit trail

---

## Technical Implementation Details

### Backend Changes
```php
// FIFOEngine.php - formatConsumptionRecords()
$formatted[] = [
    'amountConsumed' => $record['amountConsumed'],
    'costBase' => $record['costBase'],
    'costPerUnit' => $record['lot']->getCostPerUnit(),
    'purchaseDate' => $purchaseDate,      // ← Added
    'ageInDays' => $ageInDays,            // ← Added
    'wallet' => $record['lot']->getWallet() // ← Added
];
```

### Frontend Display
```javascript
// TransactionTableEnhanced.js
<td className="age-cell">
  {lot.ageInDays >= 365 ? (
    <span className="long-term">
      {lot.ageInDays} 
      <span className="term-badge">LT</span>
    </span>
  ) : (
    <span className="short-term">{lot.ageInDays}</span>
  )}
</td>
```

---

## Export Formats

All three export formats now show the detailed breakdown:

### 1. Web Interface
- Interactive table
- Expandable rows
- Color-coded long-term badges
- Tooltip information

### 2. PDF Export
- Professional layout
- Clear table formatting
- Suitable for printing
- Includes all lot details

### 3. Excel/CSV Export
- One row per lot
- Additional "Term" column
- Total row included
- Easy to import into accounting software

---

## Summary

The improvement transforms a simple transaction line into a **detailed audit trail** that:

✅ Shows exactly which coins were sold (by purchase date)
✅ Calculates holding periods for each lot
✅ Identifies long-term vs short-term holdings
✅ Provides complete cost basis breakdown
✅ Looks professional for tax submission
✅ Works consistently across all formats

This is exactly what the reviewer requested: **"peeling the layers"** of your transaction history to show the multiple purchase dates that make up each sale.
