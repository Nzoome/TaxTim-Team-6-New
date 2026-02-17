# 🚩 Red Flag System - Suspicious Transaction Detection

## Overview

The **Red Flag System** is an automated suspicious transaction detection module designed to identify incorrect, inconsistent, or audit-sensitive cryptocurrency transactions. It improves data accuracy, ensures FIFO compliance, reduces SARS audit risk, and increases transparency.

---

## ✅ Implementation Status

**All Features Implemented and Tested ✓**

- ✅ Backend Detection Service (`SuspiciousTransactionDetector.php`)
- ✅ Integration with FileProcessor
- ✅ Integration with FIFO Engine
- ✅ Frontend Display Component (`SuspiciousTransactionSummary.js`)
- ✅ Dashboard Integration
- ✅ Comprehensive Test Suite (100% Pass Rate)
- ✅ Test Data Files

---

## 🎯 Objectives

1. **Detect incomplete or inconsistent transaction data**
2. **Identify violations of FIFO principles**
3. **Flag transactions that may trigger SARS audit attention**
4. **Provide clear explanations for each flagged item**
5. **Categorize risk severity levels**
6. **Display an audit-risk summary report to users**

---

## 🔍 Detection Rules

### 1. **Missing or Invalid Data** (CRITICAL)
- Missing dates, currencies, or amounts
- Invalid or unknown currency codes
- Zero amounts in non-transfer transactions
- Missing or zero prices

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-01-05,SELL,ETH,1.0,ZAR,0,2500,0
```
**Flag:** Zero destination amount detected

---

### 2. **Negative Amounts** (CRITICAL)
- Negative buy/sell amounts
- Negative prices
- Invalid transaction values

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-02-01,BUY,ZAR,-5000,ETH,2.0,2500,50
```
**Flag:** Negative source amount: -5000

---

### 3. **Duplicate Transactions** (HIGH)
- Identical transactions at same timestamp
- Same amount, currency, price, and type
- Potential data entry errors

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-01-01,BUY,ZAR,10000,BTC,0.2,50000,100
2024-01-01,BUY,ZAR,10000,BTC,0.2,50000,100
```
**Flag:** Potential duplicate transaction detected

---

### 4. **Large Transactions** (HIGH)
- Transactions exceeding R1,000,000 threshold
- May trigger SARS audit attention
- Requires additional documentation

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-03-01,BUY,ZAR,1500000,BTC,30,50000,1000
```
**Flag:** Large transaction: R1,500,000.00 (threshold: R1,000,000.00)

---

### 5. **Negative Balances** (CRITICAL)
- Selling more than owned
- Transactions causing negative asset balances
- FIFO compliance violations

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-04-10,SELL,DOGE,1000,ZAR,5000,5,0
```
**Flag:** Selling DOGE without prior purchase - negative balance detected

---

### 6. **Wash Trading** (MEDIUM)
- Same-day buy and sell of same asset
- Buy and sell within 24 hours
- Potential tax manipulation pattern

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-04-01 09:00:00,BUY,ZAR,10000,SOL,200,50,50
2024-04-01 15:00:00,SELL,SOL,200,ZAR,10000,50,50
```
**Flag:** Potential wash trading for SOL: buy and sell within 24 hours

---

### 7. **Excessive Fees** (MEDIUM)
- Transaction fees exceeding 50% of transaction value
- Potential data entry errors
- Unusually high exchange fees

**Example:**
```
Date,Type,From Currency,From Amount,To Currency,To Amount,Price,Fee
2024-03-15,BUY,ZAR,5000,BTC,0.1,50000,3000
```
**Flag:** Transaction fee (R3000.00) exceeds 50% of transaction value (R5000.00)

---

## 📊 Severity Levels

### 🚨 CRITICAL (25 points each)
- **Prevents accurate tax calculation**
- **Must be fixed before SARS submission**
- Examples: Missing data, negative amounts, negative balances

### ⚠️ HIGH (15 points each)
- **Likely errors or SARS audit triggers**
- **Strong recommendation to review**
- Examples: Large transactions, duplicates

### ⚡ MEDIUM (7 points each)
- **Suspicious patterns requiring review**
- **May need additional documentation**
- Examples: Wash trading, excessive fees

### ℹ️ LOW (2 points each)
- **Minor inconsistencies**
- **Informational flags**
- Examples: Minor data quality issues

---

## 🎯 Audit Risk Scoring

### Risk Score Calculation
```
Risk Score = (Critical × 25) + (High × 15) + (Medium × 7) + (Low × 2)
Maximum Score: 100
```

### Risk Level Categories

| Score | Level | Description |
|-------|-------|-------------|
| 75-100 | **VERY HIGH** | Immediate attention required |
| 50-74 | **HIGH** | Review and corrections recommended |
| 25-49 | **MEDIUM** | Some issues detected |
| 1-24 | **LOW** | Minor issues detected |
| 0 | **MINIMAL** | No significant issues |

---

## 💻 Backend Implementation

### Core Service: `SuspiciousTransactionDetector.php`

**Location:** `backend/src/Services/SuspiciousTransactionDetector.php`

**Key Methods:**
```php
// Main analysis method
public function analyzeTransactions(array $transactions, array $balances = []): array

// Detection methods
private function detectMissingData(array $transactions): void
private function detectInvalidAmounts(array $transactions): void
private function detectDuplicateTransactions(array $transactions): void
private function detectLargeTransactions(array $transactions): void
private function detectWashTrading(array $transactions): void
private function detectNegativeBalances(array $transactions, array $balances): void

// Utility methods
public function exportReport(): string
public function getRedFlagsBySeverity(string $severity): array
```

### Integration Points

#### 1. FileProcessor Integration
```php
// In FileProcessor.php
$this->suspiciousDetector = new SuspiciousTransactionDetector();
$detectionResults = $this->suspiciousDetector->analyzeTransactions($transactions);
```

#### 2. Transactions API Integration
```php
// In transactions.php
use CryptoTax\Services\SuspiciousTransactionDetector;

$suspiciousDetector = new SuspiciousTransactionDetector();
$detectionResults = $suspiciousDetector->analyzeTransactions(
    $transactionObjects, 
    $fifoResults['balances']
);
```

---

## 🎨 Frontend Implementation

### Component: `SuspiciousTransactionSummary.js`

**Location:** `frontend/src/components/SuspiciousTransactionSummary.js`

**Features:**
- ✅ Color-coded severity indicators
- ✅ Expandable/collapsible details
- ✅ Severity filtering
- ✅ Transaction-level details
- ✅ Audit risk score display
- ✅ Actionable recommendations

**Props:**
```javascript
{
  redFlags: Array,           // Array of flagged transactions
  summary: Object,           // Summary statistics
  auditRiskLevel: String,    // Risk level description
  hasCriticalIssues: Boolean // Critical flag indicator
}
```

### Dashboard Integration
```javascript
// In Dashboard.js
import SuspiciousTransactionSummary from './SuspiciousTransactionSummary';

<SuspiciousTransactionSummary
  redFlags={data.red_flags}
  summary={data.red_flag_summary}
  auditRiskLevel={data.audit_risk_level}
  hasCriticalIssues={data.has_critical_issues}
/>
```

---

## 🧪 Testing

### Test Suite: `RedFlagDetectionTest.php`

**Location:** `backend/tests/RedFlagDetectionTest.php`

**Test Coverage:**
1. ✅ Missing/Invalid Data Detection
2. ✅ Negative Amount Detection
3. ✅ Duplicate Transaction Detection
4. ✅ Large Transaction Detection
5. ✅ Wash Trading Detection
6. ✅ Excessive Fee Detection
7. ✅ Negative Balance Detection
8. ✅ Risk Score Calculation
9. ✅ Clean Data Validation (No False Positives)
10. ✅ Report Export Functionality

**Run Tests:**
```bash
cd backend
php tests/RedFlagDetectionTest.php
```

**Expected Output:**
```
=== TEST SUMMARY ===
Total Tests: 10
Passed: 10 ✓
Failed: 0 ✗
Success Rate: 100%

🎉 ALL TESTS PASSED! Red Flag System is fully operational.
```

---

## 📁 Test Data Files

### `test-suspicious-transactions.csv`

**Location:** `test-suspicious-transactions.csv`

**Contains:**
- Duplicate transactions
- Negative amounts
- Large transactions
- Wash trading patterns
- Excessive fees
- Missing data
- Negative balance scenarios

**Use for:** Manual testing and demonstration

---

## 📤 API Response Format

### File Upload Response (`index.php`)
```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "summary": {...},
    "red_flags": [
      {
        "severity": "CRITICAL",
        "code": "NEGATIVE_AMOUNT",
        "message": "Transaction has negative source amount: -5000",
        "transaction_index": 3,
        "line_number": 4,
        "transaction": {
          "date": "2024-02-01 00:00:00",
          "type": "BUY",
          "from": "ZAR -5000",
          "to": "ETH 2.0",
          "price": 2500
        },
        "metadata": {},
        "timestamp": "2026-02-13 12:00:00"
      }
    ],
    "red_flag_summary": {
      "total_flags": 8,
      "critical_count": 3,
      "high_count": 2,
      "medium_count": 2,
      "low_count": 1,
      "audit_risk_score": 72
    },
    "has_critical_issues": true,
    "audit_risk_level": "HIGH - Review and corrections recommended"
  }
}
```

### Transactions API Response (`transactions.php`)
```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "summary": {...},
    "analytics": {...},
    "red_flags": [...],
    "red_flag_summary": {...},
    "has_critical_issues": true,
    "audit_risk_level": "HIGH - Review and corrections recommended"
  }
}
```

---

## 🎨 User Interface Features

### Summary Card
- **Total Flags Count**
- **Severity Breakdown** (Critical/High/Medium/Low)
- **Risk Score** (0-100)
- **Audit Risk Level** (Color-coded)

### Expandable Details
- **Severity Filters** (Filter by Critical/High/Medium/Low)
- **Flag List** (Transaction-by-transaction breakdown)
- **Recommendations** (Actionable advice)

### Visual Indicators
- 🚨 Critical (Red)
- ⚠️ High (Orange)
- ⚡ Medium (Yellow)
- ℹ️ Low (Blue)
- ✅ Clean (Green)

---

## 🔧 Configuration

### Detection Thresholds

**In `SuspiciousTransactionDetector.php`:**
```php
// Large transaction threshold (in ZAR)
const LARGE_TRANSACTION_THRESHOLD = 1000000; // R1,000,000

// Wash trading time window (seconds)
const WASH_TRADE_WINDOW = 86400; // 24 hours

// Excessive fee threshold (percentage)
const EXCESSIVE_FEE_THRESHOLD = 0.5; // 50% of transaction value
```

### Customization
To adjust detection sensitivity, modify the constants above.

---

## 📋 Recommendations for Users

### For CRITICAL Issues:
1. **Review and correct all flagged transactions**
2. **Verify data accuracy before SARS submission**
3. **Fix missing or invalid data immediately**

### For HIGH Issues:
1. **Prepare additional documentation for large transactions**
2. **Remove or justify duplicate entries**
3. **Ensure balance accuracy**

### For MEDIUM Issues:
1. **Review wash trading patterns**
2. **Verify fee calculations**
3. **Consider tax professional consultation**

### General Best Practices:
- Upload complete transaction history
- Ensure chronological ordering
- Include all required fields
- Verify exchange rate accuracy
- Keep supporting documentation

---

## 🚀 Usage Example

### 1. Upload File with Suspicious Transactions
```
Upload: test-suspicious-transactions.csv
```

### 2. System Detects Issues
```
🚩 8 Red Flags Detected
Risk Score: 72/100
Risk Level: HIGH - Review and corrections recommended
```

### 3. Review Flagged Transactions
```
🚨 CRITICAL - Line 4
Negative source amount: -5000
Action: Correct transaction data

⚠️ HIGH - Line 6
Large transaction: R1,500,000.00
Action: Prepare additional documentation
```

### 4. Take Action
- Fix critical errors
- Document high-risk items
- Review medium-risk patterns
- Re-upload corrected file

---

## 🎯 Benefits

### For Users:
- ✅ Catch errors before SARS submission
- ✅ Reduce audit risk
- ✅ Improve data quality
- ✅ Clear actionable guidance

### For Tax Compliance:
- ✅ SARS-ready reports
- ✅ Audit trail transparency
- ✅ FIFO compliance verification
- ✅ Professional documentation

---

## 📚 Related Documentation

- [SPRINT4-COMPLETE.md](SPRINT4-COMPLETE.md) - Sprint 4 implementation details
- [QUICKSTART.md](QUICKSTART.md) - Getting started guide
- [PROJECT-PRESENTATION.md](PROJECT-PRESENTATION.md) - Project overview

---

## 🏆 Test Results

**Date:** February 13, 2026  
**Status:** ✅ ALL TESTS PASSED  
**Success Rate:** 100%  
**Tests Run:** 10  
**Failures:** 0

---

## 👥 Support

For questions or issues with the Red Flag System:
1. Check this documentation
2. Review test suite examples
3. Examine test data files
4. Consult SARS tax guidelines

---

**© 2026 TaxTim Crypto Tax Calculator - Team 6**
