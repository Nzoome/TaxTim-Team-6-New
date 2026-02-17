# 🚩 Red Flag System - Quick Reference

## Quick Start

### For Developers
```bash
# Run test suite
cd backend
php tests/RedFlagDetectionTest.php

# Test with sample data
Upload: test-suspicious-transactions.csv
```

### For Users
1. Upload transaction file
2. Check for red flag banner
3. Review flagged transactions
4. Take corrective action
5. Re-upload if needed

---

## Detection Rules Summary

| Rule | Severity | Trigger | Points |
|------|----------|---------|--------|
| Missing Data | 🚨 CRITICAL | Empty or invalid fields | 25 |
| Negative Amounts | 🚨 CRITICAL | Amount < 0 | 25 |
| Negative Balance | 🚨 CRITICAL | Sell > Holdings | 25 |
| Duplicate Transaction | ⚠️ HIGH | Identical entries | 15 |
| Large Transaction | ⚠️ HIGH | > R1,000,000 | 15 |
| Wash Trading | ⚡ MEDIUM | Same-day buy/sell | 7 |
| Excessive Fee | ⚡ MEDIUM | Fee > 50% value | 7 |

---

## Risk Score Guide

```
75-100: 🔴 VERY HIGH - Immediate action required
50-74:  🟠 HIGH - Review and corrections recommended
25-49:  🟡 MEDIUM - Some issues detected
1-24:   🔵 LOW - Minor issues detected
0:      🟢 MINIMAL - No issues
```

---

## Code Examples

### Backend Usage
```php
use CryptoTax\Services\SuspiciousTransactionDetector;

$detector = new SuspiciousTransactionDetector();
$results = $detector->analyzeTransactions($transactions, $balances);

// Access results
$flags = $results['red_flags'];
$summary = $results['summary'];
$riskScore = $summary['audit_risk_score'];
$hasCritical = $results['has_critical_issues'];
```

### Frontend Usage
```javascript
import SuspiciousTransactionSummary from './components/SuspiciousTransactionSummary';

<SuspiciousTransactionSummary
  redFlags={data.red_flags}
  summary={data.red_flag_summary}
  auditRiskLevel={data.audit_risk_level}
  hasCriticalIssues={data.has_critical_issues}
/>
```

---

## API Response Structure

```json
{
  "red_flags": [
    {
      "severity": "CRITICAL",
      "code": "NEGATIVE_AMOUNT",
      "message": "Description",
      "line_number": 4,
      "transaction": { ... },
      "metadata": { ... }
    }
  ],
  "red_flag_summary": {
    "total_flags": 5,
    "critical_count": 2,
    "high_count": 1,
    "medium_count": 2,
    "low_count": 0,
    "audit_risk_score": 65
  },
  "has_critical_issues": true,
  "audit_risk_level": "HIGH"
}
```

---

## File Locations

### Backend
- Service: `backend/src/Services/SuspiciousTransactionDetector.php`
- Tests: `backend/tests/RedFlagDetectionTest.php`
- Integration: `backend/src/Services/FileProcessor.php`
- API: `backend/public/transactions.php`

### Frontend
- Component: `frontend/src/components/SuspiciousTransactionSummary.js`
- Styles: `frontend/src/components/SuspiciousTransactionSummary.css`
- Integration: `frontend/src/components/Dashboard.js`

### Test Data
- Sample CSV: `test-suspicious-transactions.csv`

---

## Common Issues & Solutions

### Issue: No red flags showing
**Solution:** Check API response includes `red_flags` field

### Issue: False positives
**Solution:** Adjust detection thresholds in `SuspiciousTransactionDetector.php`

### Issue: Missing severity
**Solution:** Ensure all transactions have required fields

---

## Testing Checklist

- [ ] Missing data detection
- [ ] Negative amount detection
- [ ] Duplicate transaction detection
- [ ] Large transaction detection
- [ ] Wash trading detection
- [ ] Excessive fee detection
- [ ] Negative balance detection
- [ ] Risk score calculation
- [ ] Clean data validation
- [ ] Report export

**Expected:** 10/10 tests passing ✅

---

## Configuration

```php
// Adjust in SuspiciousTransactionDetector.php

const LARGE_TRANSACTION_THRESHOLD = 1000000;  // R1M
const WASH_TRADE_WINDOW = 86400;              // 24 hours
const EXCESSIVE_FEE_THRESHOLD = 0.5;          // 50%
```

---

## Key Methods

```php
// Detection
analyzeTransactions(array $transactions, array $balances): array

// Filtering
getRedFlagsBySeverity(string $severity): array

// Export
exportReport(): string
```

---

## Integration Steps

1. ✅ Create `SuspiciousTransactionDetector.php`
2. ✅ Integrate with `FileProcessor.php`
3. ✅ Update `transactions.php` API
4. ✅ Create `SuspiciousTransactionSummary.js`
5. ✅ Add to `Dashboard.js`
6. ✅ Create test suite
7. ✅ Add test data

---

## Status: ✅ FULLY IMPLEMENTED

All detection rules operational and tested.
Ready for production use.

---

For detailed documentation, see [RED-FLAG-SYSTEM.md](RED-FLAG-SYSTEM.md)
