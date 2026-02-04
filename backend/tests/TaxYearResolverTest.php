<?php

namespace CryptoTax\Tests;

use PHPUnit\Framework\TestCase;
use CryptoTax\Services\TaxYearResolver;
use DateTime;

/**
 * TaxYearResolver Test Suite
 * 
 * Tests SARS tax year resolution logic (1 March - end February)
 */
class TaxYearResolverTest extends TestCase
{
    private TaxYearResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TaxYearResolver();
    }

    /**
     * Test: Date in March belongs to current year's tax year
     */
    public function testMarchBelongsToCurrentYear(): void
    {
        $date = new DateTime('2024-03-01');
        $startYear = $this->resolver->getTaxYearStartYear($date);
        $label = $this->resolver->resolveTaxYearLabel($date);

        $this->assertEquals(2024, $startYear);
        $this->assertEquals('2024/2025', $label);
    }

    /**
     * Test: Date in February belongs to previous year's tax year
     */
    public function testFebruaryBelongsToPreviousYear(): void
    {
        $date = new DateTime('2024-02-29'); // Leap year
        $startYear = $this->resolver->getTaxYearStartYear($date);
        $label = $this->resolver->resolveTaxYearLabel($date);

        $this->assertEquals(2023, $startYear);
        $this->assertEquals('2023/2024', $label);
    }

    /**
     * Test: Date in January belongs to previous year's tax year
     */
    public function testJanuaryBelongsToPreviousYear(): void
    {
        $date = new DateTime('2024-01-15');
        $startYear = $this->resolver->getTaxYearStartYear($date);
        $label = $this->resolver->resolveTaxYearLabel($date);

        $this->assertEquals(2023, $startYear);
        $this->assertEquals('2023/2024', $label);
    }

    /**
     * Test: Mid-year dates belong to current year's tax year
     */
    public function testMidYearBelongsToCurrentYear(): void
    {
        $dates = [
            '2024-04-15',
            '2024-06-30',
            '2024-09-01',
            '2024-12-31'
        ];

        foreach ($dates as $dateStr) {
            $date = new DateTime($dateStr);
            $startYear = $this->resolver->getTaxYearStartYear($date);
            $label = $this->resolver->resolveTaxYearLabel($date);

            $this->assertEquals(2024, $startYear, "Failed for date: {$dateStr}");
            $this->assertEquals('2024/2025', $label, "Failed for date: {$dateStr}");
        }
    }

    /**
     * Test: Tax year boundary dates (exactly on Mar 1 and Feb 28/29)
     */
    public function testTaxYearBoundaryDates(): void
    {
        // Start of tax year
        $start = new DateTime('2024-03-01 00:00:00');
        $this->assertEquals(2024, $this->resolver->getTaxYearStartYear($start));
        $this->assertEquals('2024/2025', $this->resolver->resolveTaxYearLabel($start));

        // End of tax year (non-leap)
        $end = new DateTime('2024-02-28 23:59:59');
        $this->assertEquals(2023, $this->resolver->getTaxYearStartYear($end));
        $this->assertEquals('2023/2024', $this->resolver->resolveTaxYearLabel($end));

        // End of tax year (leap year)
        $endLeap = new DateTime('2025-02-28 23:59:59');
        $this->assertEquals(2024, $this->resolver->getTaxYearStartYear($endLeap));
        $this->assertEquals('2024/2025', $this->resolver->resolveTaxYearLabel($endLeap));
    }

    /**
     * Test: Get tax year end date for non-leap year
     */
    public function testGetTaxYearEndDateNonLeap(): void
    {
        // 2025 is not a leap year
        $endDate = $this->resolver->getTaxYearEndDate(2024);
        
        $this->assertEquals('2025-02-28', $endDate->format('Y-m-d'));
        $this->assertEquals('23:59:59', $endDate->format('H:i:s'));
    }

    /**
     * Test: Get tax year end date for leap year
     */
    public function testGetTaxYearEndDateLeapYear(): void
    {
        // 2028 is a leap year
        $endDate = $this->resolver->getTaxYearEndDate(2027);
        
        $this->assertEquals('2028-02-29', $endDate->format('Y-m-d'));
        $this->assertEquals('23:59:59', $endDate->format('H:i:s'));
    }

    /**
     * Test: Multiple consecutive tax years
     */
    public function testConsecutiveTaxYears(): void
    {
        $testCases = [
            ['2023-03-01', '2023/2024'],
            ['2023-12-31', '2023/2024'],
            ['2024-02-29', '2023/2024'], // Leap year end
            ['2024-03-01', '2024/2025'],
            ['2024-12-31', '2024/2025'],
            ['2025-02-28', '2024/2025'], // Non-leap year end
            ['2025-03-01', '2025/2026'],
        ];

        foreach ($testCases as [$dateStr, $expectedLabel]) {
            $date = new DateTime($dateStr);
            $label = $this->resolver->resolveTaxYearLabel($date);
            $this->assertEquals($expectedLabel, $label, "Failed for date: {$dateStr}");
        }
    }

    /**
     * Test: Leap year century rules (2000, 2100, 2400)
     */
    public function testLeapYearCenturyRules(): void
    {
        // 2000 is a leap year (divisible by 400)
        $endDate2000 = $this->resolver->getTaxYearEndDate(1999);
        $this->assertEquals('2000-02-29', $endDate2000->format('Y-m-d'));

        // 2100 is NOT a leap year (divisible by 100 but not by 400)
        $endDate2100 = $this->resolver->getTaxYearEndDate(2099);
        $this->assertEquals('2100-02-28', $endDate2100->format('Y-m-d'));

        // 2400 is a leap year (divisible by 400)
        $endDate2400 = $this->resolver->getTaxYearEndDate(2399);
        $this->assertEquals('2400-02-29', $endDate2400->format('Y-m-d'));
    }
}
