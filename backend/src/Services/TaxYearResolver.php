<?php

namespace CryptoTax\Services;

use DateTime;

/**
 * TaxYearResolver
 *
 * Resolves SARS tax years which run from 1 March -> end February
 * Example: 2024-03-01 -> 2025-02-28 belongs to tax year label "2024/2025"
 */
class TaxYearResolver
{
    /**
     * Resolve the tax year start (integer year) for a given date
     *
     * @param DateTime $date
     * @return int Start year (e.g., 2024 for tax year 2024/2025)
     */
    public function getTaxYearStartYear(DateTime $date): int
    {
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n'); // 1-12

        // If month is March (3) or later, tax year starts this calendar year
        if ($month >= 3) {
            return $year;
        }

        // Months January or February belong to previous tax year start
        return $year - 1;
    }

    /**
     * Return a label for the tax year for a given date
     * Format: "YYYY/YYYY+1" (e.g., "2024/2025")
     *
     * @param DateTime $date
     * @return string
     */
    public function resolveTaxYearLabel(DateTime $date): string
    {
        $start = $this->getTaxYearStartYear($date);
        $end = $start + 1;
        return sprintf('%04d/%04d', $start, $end);
    }

    /**
     * Given a tax year start integer, returns the DateTime for the tax year end
     * (end of February of end year)
     *
     * @param int $startYear
     * @return DateTime
     */
    public function getTaxYearEndDate(int $startYear): DateTime
    {
        $endYear = $startYear + 1;

        // Determine if endYear is leap year (Feb 29)
        $isLeap = ($endYear % 4 === 0 && ($endYear % 100 !== 0 || $endYear % 400 === 0));
        $day = $isLeap ? 29 : 28;

        return new DateTime(sprintf('%04d-02-%02d 23:59:59', $endYear, $day));
    }
}
