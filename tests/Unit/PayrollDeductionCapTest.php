<?php

namespace Tests\Unit;

use App\Services\PayrollDeductionService;
use PHPUnit\Framework\TestCase;

/**
 * Client test C-36: a zero-earnings payroll showed PHP 32,375.00 deductions
 * against PHP 0.00 gross, while net was silently held at zero.
 */
class PayrollDeductionCapTest extends TestCase
{
    public function test_zero_earnings_have_no_deductions(): void
    {
        $breakdown = [
            'sss_deduction' => 675.0,
            'phic_deduction' => 450.0,
            'hdmf_deduction' => 200.0,
            'tax_withholding' => 1350.0,
            'absence_deduction' => 29700.0,
        ];

        $this->assertSame([
            'sss_deduction' => 0.0,
            'phic_deduction' => 0.0,
            'hdmf_deduction' => 0.0,
            'tax_withholding' => 0.0,
            'absence_deduction' => 0.0,
        ], PayrollDeductionService::capToGross($breakdown, 0.0));
    }

    public function test_deductions_never_exceed_gross_earnings(): void
    {
        $breakdown = [
            'absence_deduction' => 29700.0,
            'sss_deduction' => 675.0,
            'phic_deduction' => 450.0,
            'hdmf_deduction' => 200.0,
            'tax_withholding' => 1350.0,
        ];

        $capped = PayrollDeductionService::capToGross($breakdown, 1500.0);

        $this->assertSame(1500.0, round(array_sum($capped), 2));
        $this->assertLessThanOrEqual(1500.0, $capped['absence_deduction']);
        $this->assertGreaterThanOrEqual(0.0, min($capped));
    }
}
