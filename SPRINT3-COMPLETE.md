# Sprint 3 – SARS Tax Year Compliance (COMPLETE)

## Sprint Goal: SARS Tax Year Compliance ✅

All FIFO disposal results are now correctly allocated to the South African tax year (1 March – end February), with capital gains calculated according to SARS CGT rules.

---

## What Was Implemented

### 1. TaxYearResolver Service ✅
**File:** `backend/src/Services/TaxYearResolver.php`

A dedicated service that handles SARS tax year resolution:
- **Tax year boundaries:** 1 March to end of February
- **Leap year handling:** Correctly identifies Feb 28 vs Feb 29
- **Tax year labels:** Generates format "YYYY/YYYY+1" (e.g., "2024/2025")
- **Century rules:** Handles leap year exceptions (divisible by 100 but not 400)

**Key Methods:**
- `getTaxYearStartYear(DateTime $date): int` - Returns the starting year of the tax year
- `resolveTaxYearLabel(DateTime $date): string` - Returns formatted label (e.g., "2024/2025")
- `getTaxYearEndDate(int $startYear): DateTime` - Returns end date with leap year logic

---

### 2. Tax Year Allocation to Disposals ✅
**File:** `backend/src/Services/FIFOEngine.php` (modified)

Every SELL and TRADE disposal is now tagged with its correct SARS tax year:
- `taxYear` field added to all disposal breakdowns
- Each disposal appears in exactly one tax year bucket
- Tax year determined by transaction date

**Example Breakdown:**
```php
[
    'date' => '2024-06-15 10:30:00',
    'type' => 'SELL',
    'currency' => 'BTC',
    'amount' => 0.5,
    'proceeds' => 25000.0,
    'costBase' => 10000.0,
    'capitalGain' => 15000.0,
    'taxYear' => '2024/2025',  // ← NEW
    // ... other fields
]
```

---

### 3. Balance Snapshots at Tax Year Boundaries ✅
**File:** `backend/src/Services/FIFOEngine.php` (modified)

FIFO state is preserved across tax year boundaries:
- Automatic snapshots when transactions cross tax years
- Remaining FIFO lots captured at end of each tax year
- Ensures continuity and traceability of cost base

**API Method:**
```php
$engine = new FIFOEngine();
$engine->processTransactions($transactions);
$snapshots = $engine->getTaxYearSnapshots();
// Returns: ['2023/2024' => [...], '2024/2025' => [...]]
```

**Optional:** Snapshots can be disabled:
```php
$engine->processTransactions($transactions, ['snapshotTaxYearBoundaries' => false]);
```

---

### 4. Per-Coin Tax Year Gain Calculations ✅
**File:** `backend/src/Services/FIFOEngine.php` (modified)

Capital gains are aggregated per coin per tax year:
- Disposals grouped by tax year and cryptocurrency
- Transaction-level breakdowns preserved for audit trail
- Handles both gains and losses (negative gains)

**API Method:**
```php
$allocations = $engine->allocateDisposalsByTaxYear();
// Returns structure:
// [
//     '2024/2025' => [
//         'BTC' => [array of disposal breakdowns],
//         'ETH' => [array of disposal breakdowns]
//     ]
// ]
```

---

### 5. Annual CGT Exclusion (R40,000) ✅
**File:** `backend/src/Services/FIFOEngine.php` (modified)

SARS annual capital gains exclusion correctly applied:
- Default: R40,000 per tax year (configurable)
- Applied once per tax year after aggregating all gains
- Not applied to losses (losses remain negative)
- Only reduces positive net capital gains

**Rules:**
- If net gain > R40,000: net gain - R40,000
- If net gain ≤ R40,000: R0 (fully excluded)
- If net loss: exclusion = R0 (no effect on losses)

---

### 6. Inclusion Rate (40%) ✅
**File:** `backend/src/Services/FIFOEngine.php` (modified)

SARS inclusion rate applied to produce taxable capital gains:
- Default: 40% (configurable)
- Applied after the annual exclusion
- Converts capital gain into taxable capital gain

**Formula:**
```
Taxable Capital Gain = (Net Gain - Exclusion) × Inclusion Rate
```

**API Method:**
```php
$report = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);
// Returns comprehensive tax report structure
```

---

## Tax Report Structure

The `calculateGainsPerCoinPerTaxYear()` method returns:

```php
[
    '2024/2025' => [
        'coins' => [
            'BTC' => [
                'grossGain' => 80000.0,
                'breakdowns' => [/* array of disposal transactions */]
            ],
            'ETH' => [
                'grossGain' => 15000.0,
                'breakdowns' => [/* array of disposal transactions */]
            ]
        ],
        'netGrossGain' => 95000.0,              // Sum across all coins
        'annualExclusionApplied' => 40000.0,    // R40,000 exclusion
        'netAfterExclusion' => 55000.0,         // 95000 - 40000
        'taxableAfterInclusion' => 22000.0      // 55000 × 0.4
    ],
    '2025/2026' => [
        // ... next tax year
    ]
]
```

---

## Test Coverage

### New Test Files Created

**1. TaxYearResolverTest.php** (9 tests, 34 assertions)
- Tax year boundary logic (March 1 and Feb 28/29)
- Leap year detection (including century rules)
- Tax year label generation
- Edge cases and date transitions

**2. TaxYearIntegrationTest.php** (12 tests, 56 assertions)
- Disposal tagging with tax years
- Snapshot creation at boundaries
- Disposal allocation by tax year and coin
- Gross gain calculations per coin per year
- Annual CGT exclusion application
- Inclusion rate application
- Multi-coin scenarios
- Multi-year scenarios with lot carryover
- Edge cases (losses, zero gains, etc.)

**Total Test Suite:** 30 tests, 169 assertions ✅

All tests passing:
```
✔ 9 tests  - FIFOEngine (Sprint 2)
✔ 12 tests - TaxYearIntegration (Sprint 3)
✔ 9 tests  - TaxYearResolver (Sprint 3)
```

---

## Usage Examples

### Basic Tax Report Generation

```php
use CryptoTax\Services\FIFOEngine;

// 1. Process transactions (must be chronologically sorted)
$engine = new FIFOEngine();
$result = $engine->processTransactions($transactions);

// 2. Get tax year allocations
$disposalsByYear = $engine->allocateDisposalsByTaxYear();

// 3. Calculate final tax report with SARS rules
$taxReport = $engine->calculateGainsPerCoinPerTaxYear(40000.0, 0.4);

// 4. Access tax year snapshots
$snapshots = $engine->getTaxYearSnapshots();
```

### Custom Exclusion and Inclusion Rate

```php
// Example: R50,000 exclusion with 35% inclusion rate
$taxReport = $engine->calculateGainsPerCoinPerTaxYear(50000.0, 0.35);
```

### Accessing Specific Tax Year Data

```php
$report = $engine->calculateGainsPerCoinPerTaxYear();

foreach ($report as $taxYear => $data) {
    echo "Tax Year: {$taxYear}\n";
    echo "Net Gross Gain: R" . number_format($data['netGrossGain'], 2) . "\n";
    echo "After Exclusion: R" . number_format($data['netAfterExclusion'], 2) . "\n";
    echo "Taxable Gain: R" . number_format($data['taxableAfterInclusion'], 2) . "\n\n";
    
    foreach ($data['coins'] as $coin => $coinData) {
        echo "  {$coin}: R" . number_format($coinData['grossGain'], 2) . "\n";
    }
}
```

---

## Files Modified

| File | Changes |
|------|---------|
| `backend/src/Services/FIFOEngine.php` | Added tax year integration, snapshots, allocation methods, and CGT calculations |
| `backend/src/Services/TaxYearResolver.php` | **NEW** - SARS tax year resolution logic |
| `backend/tests/TaxYearResolverTest.php` | **NEW** - 9 tests for tax year resolver |
| `backend/tests/TaxYearIntegrationTest.php` | **NEW** - 12 tests for tax integration |

---

## Sprint 3 Deliverables Summary

✅ **FIFO results correctly allocated to SARS tax years**
- Every disposal tagged with tax year label
- Tax year determined by disposal date (1 Mar - end Feb)

✅ **Correct capital gains per coin per tax year**
- Aggregated per cryptocurrency per tax year
- Transaction-level breakdowns preserved

✅ **Correct taxable capital gains after exclusion and inclusion rate**
- R40,000 annual exclusion applied once per tax year
- 40% inclusion rate applied to remaining gain
- Loss handling (exclusion not applied to losses)

✅ **Base cost snapshots preserved across tax-year boundaries**
- FIFO lots carried forward correctly
- State captured at end of each tax year
- Traceability maintained

✅ **Comprehensive test coverage**
- 21 new tests covering all Sprint 3 functionality
- All 30 tests passing (Sprint 2 + Sprint 3)

---

## Explicitly Out of Scope (As Per Sprint 3 Requirements)

The following items were intentionally **not** implemented in Sprint 3:

❌ Charts and visualizations
❌ UI dashboards
❌ Reports generation
❌ Export functionality
❌ API endpoint modifications

These will be addressed in future sprints focusing on UI/UX and reporting.

---

## Technical Notes

### SARS Tax Year Rules Implemented
- Tax year runs from **1 March** to **end of February**
- Example: Tax year "2024/2025" = 1 Mar 2024 to 28 Feb 2025
- Leap years correctly handled (29 Feb when applicable)

### CGT Rules Applied
1. **Gross Capital Gain:** Proceeds - Cost Base (per disposal)
2. **Net Capital Gain:** Sum of all gains and losses (per tax year)
3. **Annual Exclusion:** R40,000 deducted from positive net gain only
4. **Taxable Capital Gain:** (Net Gain - Exclusion) × 40%

### Capital Losses
- Losses are preserved as negative gains
- Can offset gains within the same tax year
- Exclusion not applied to losses
- Inclusion rate applied to net result (even if negative)

---

## Next Steps (Post-Sprint 3)

While not part of Sprint 3, potential next steps include:

1. **API Integration:** Expose tax reports via REST endpoints
2. **Frontend Display:** Visualize tax year breakdowns in dashboard
3. **PDF Reports:** Generate SARS-compliant tax reports
4. **Multi-year Loss Carryover:** Track and apply losses across years
5. **Individual Tax Rate Integration:** Apply marginal tax rates to taxable gains

---

## Verification

Run the complete test suite:

```powershell
cd backend
.\vendor\bin\phpunit tests/ --testdox
```

Expected output:
```
OK (30 tests, 169 assertions)
```

---

**Sprint 3 Status:** ✅ **COMPLETE**

All requirements met, fully tested, and production-ready for tax-correct FIFO calculations with SARS compliance.
