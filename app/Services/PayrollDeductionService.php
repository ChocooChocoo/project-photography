<?php

namespace App\Services;

/**
 * Keeps payroll deductions honest: an employee cannot owe more in deductions
 * than the gross pay earned in the period. Absence is reduced first because it
 * is pay for time not worked; fixed deductions then use what remains.
 */
class PayrollDeductionService
{
    /**
     * @param  array<string, float|int>  $breakdown
     * @return array<string, float>
     */
    public static function capToGross(array $breakdown, float $grossAmount): array
    {
        $gross = max(0.0, round($grossAmount, 2));
        $remaining = $gross;
        $capped = [];

        if (array_key_exists('absence_deduction', $breakdown)) {
            $absence = min(max(0.0, round((float) $breakdown['absence_deduction'], 2)), $remaining);
            $capped['absence_deduction'] = round($absence, 2);
            $remaining = round($remaining - $absence, 2);
        }

        foreach ($breakdown as $key => $amount) {
            if ($key === 'absence_deduction') {
                continue;
            }

            $value = min(max(0.0, round((float) $amount, 2)), $remaining);
            $capped[$key] = round($value, 2);
            $remaining = round($remaining - $value, 2);
        }

        // Keep the caller's key order for stable display and stored snapshots.
        return array_replace(array_fill_keys(array_keys($breakdown), 0.0), $capped);
    }
}
