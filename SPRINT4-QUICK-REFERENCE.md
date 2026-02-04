# Sprint 4 - Quick Reference Guide

## 🚀 Getting Started

### Start the Application
```bash
# Terminal 1 - Backend
cd backend
php -S localhost:8000 -t public

# Terminal 2 - Frontend
cd frontend
npm start
```

### Access Points
- **Upload Page**: http://localhost:3000/process
- **Dashboard**: http://localhost:3000/dashboard
- **API Endpoint**: http://localhost:8000/transactions.php

---

## 📊 Dashboard Features

### Summary Cards
- **Total Capital Gains**: All positive gains from disposals
- **Total Capital Losses**: All losses from disposals
- **Net Capital Gain**: Gains minus losses
- **Taxable Capital Gain**: Net gain × 40% (SARS requirement)
- **Tax Year Selector**: Filter all data by tax year

### Transaction Table
- **Click any row** to expand FIFO details
- **Sort columns** by clicking headers
- **Expand All** button for full visibility
- See complete lot consumption per disposal

### Filters
- **Asset**: Filter by cryptocurrency (BTC, ETH, etc.)
- **Type**: Filter by BUY, SELL, or TRADE
- **Tax Year**: Filter by specific tax year
- **Clear All**: Remove all filters instantly

### Charts
1. **Gains/Losses by Asset**: Bar chart showing performance per coin
2. **Gains/Losses by Tax Year**: Annual performance breakdown
3. **Overall Distribution**: Donut chart of gains vs losses
4. **Transaction Types**: Distribution of BUY/SELL/TRADE

### Export Options
- **PDF Audit Report**: SARS-ready comprehensive report
- **Excel Export**: CSV file with all transaction data

---

## 🧮 SARS Tax Calculations

### Capital Gains Tax (CGT) Formula
```
Net Capital Gain = Total Gains - Total Losses
Taxable Amount = Net Capital Gain × 40%
(After R40,000 annual exclusion, if applicable)
```

### Tax Year
- **Starts**: March 1
- **Ends**: February 28/29 (following year)
- **Example**: 2024/2025 = March 1, 2024 to Feb 29, 2025

### FIFO Method
- **First-In-First-Out**: Earliest purchases consumed first
- **Traceability**: Every disposal links back to specific purchases
- **Partial Lots**: Can consume part of a lot

---

## 🔍 FIFO Example

### Scenario
```
Jan 1: BUY 1 BTC @ R100,000
Feb 1: BUY 0.5 BTC @ R55,000 (R110,000 per BTC)
Mar 1: SELL 1.2 BTC @ R150,000
```

### FIFO Calculation
**Lots Consumed**:
1. First lot: 1.0 BTC @ R100,000 = R100,000 cost base
2. Second lot: 0.2 BTC @ R110,000 = R22,000 cost base

**Total Cost Base**: R122,000
**Proceeds**: R150,000 × 1.2 = R180,000
**Capital Gain**: R180,000 - R122,000 = R58,000
**Taxable (40%)**: R23,200

---

## 📄 Export Formats

### PDF Report Includes
1. Executive Summary (all tax metrics)
2. SARS Compliance Information
3. Complete Transaction Listing
4. FIFO Lot Breakdown per Disposal
5. Professional Disclaimer

### Excel/CSV Includes
- Summary section
- Transaction details with all columns
- FIFO lot consumption (nested)
- SARS compliance info
- Easy to filter and sort in Excel

---

## 🧪 Testing

### Run QA Tests
```bash
cd backend
./vendor/bin/phpunit tests/Sprint4QATest.php --testdox
```

### Run Performance Tests
```bash
cd backend
./vendor/bin/phpunit tests/PerformanceTest.php --testdox
```

### Test Cases Covered
- ✅ Summary accuracy
- ✅ FIFO traceability
- ✅ Tax year allocation
- ✅ Edge cases (zero, dust, large amounts)
- ✅ Multiple currencies
- ✅ Taxable amount calculation

---

## 🎯 Key Shortcuts

| Action | How To |
|--------|--------|
| View FIFO details | Click transaction row |
| Sort table | Click column header |
| Filter by asset | Use filter dropdown |
| Clear filters | Click "Clear All Filters" |
| Export PDF | Click "Download PDF Report" |
| Export Excel | Click "Download Excel File" |
| Change tax year | Use dropdown in summary section |
| Expand all rows | Click "Expand All" button |

---

## ⚠️ Important Notes

### For SARS Submission
1. ✅ Use this tool to calculate net capital gain
2. ✅ Apply R40,000 annual exclusion manually
3. ✅ Consult with tax professional before submitting
4. ✅ Keep all reports for audit purposes

### Inclusion Rates
- **Individuals**: 40%
- **Companies**: 80%
- **Trusts**: 80%

**This tool assumes 40% (individuals)**. Companies/trusts need different calculations.

### Annual Exclusion
- **Amount**: R40,000 per year per individual
- **Not Applied Automatically**: User or accountant must apply
- **Carry Forward**: Losses can be carried forward indefinitely

---

## 🐛 Troubleshooting

### Dashboard Shows "No Data"
- Upload a transaction file first at `/process`
- Check backend is running on port 8000

### Filters Not Working
- Clear browser cache
- Refresh page
- Check console for errors

### PDF/Excel Not Downloading
- Allow popups for the site (PDF)
- Check browser download settings (Excel)
- Ensure transactions are loaded

### Charts Not Displaying
- Ensure `recharts` is installed: `npm install`
- Check browser console for errors
- Verify transactions have disposal data

---

## 📞 Support

### Documentation
- `SPRINT4-COMPLETE.md` - Full implementation details
- `README.md` - Project overview
- `QUICKSTART.md` - Setup instructions

### Code Locations
- **Frontend Components**: `frontend/src/components/`
- **Services**: `frontend/src/services/`
- **Backend**: `backend/src/`
- **Tests**: `backend/tests/`

---

## ✅ Quick Validation Checklist

Before submitting to SARS:
- [ ] All transactions uploaded and processed
- [ ] Summary values look correct
- [ ] FIFO details are traceable
- [ ] Tax year is correct
- [ ] PDF report generated successfully
- [ ] Excel export downloaded
- [ ] Consulted with tax professional
- [ ] Applied R40,000 exclusion (if eligible)

---

**Last Updated**: February 4, 2026
**Version**: Sprint 4 Complete
