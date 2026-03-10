<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserModel;
use App\Models\StudioOwner\StudiosModel;

class EmployeePayrollModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_employee_payroll';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'studio_id',
        'created_by',
        'payroll_basis',
        'daily_rate',
        'monthly_salary',
        'hourly_rate',
        'per_booking_rate',
        'booking_commission_percentage',
        'sss_deduction',
        'phic_deduction',
        'hdmf_deduction',
        'tax_withholding',
        'sss_loan_deduction',
        'hdmf_loan_deduction',
        'other_deductions',
        'is_taxable',
        'tax_type',
        'tax_percentage',
        'tax_code',
        'subject_to_vat',
        'vat_percentage',
        'vat_type',
        'absence_deduction_per_day',
        'undertime_deduction_per_hour',
        'late_grace_period_minutes',
        'late_deduction_per_minute',
        'absent_deduction_method',
        'absent_fixed_deduction',
        'absent_percentage_deduction',
        'paid_holidays',
        'payment_schedule',
        'payday_1',
        'payday_2',
        'payday_weekly',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'payment_method',
        'is_active',
        'effective_date',
        'expiry_date',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'daily_rate'                    => 'decimal:2',
        'monthly_salary'                => 'decimal:2',
        'hourly_rate'                   => 'decimal:2',
        'per_booking_rate'              => 'decimal:2',
        'booking_commission_percentage' => 'decimal:2',
        'sss_deduction'                 => 'decimal:2',
        'phic_deduction'                => 'decimal:2',
        'hdmf_deduction'                => 'decimal:2',
        'tax_withholding'               => 'decimal:2',
        'sss_loan_deduction'            => 'decimal:2',
        'hdmf_loan_deduction'           => 'decimal:2',
        'other_deductions'              => 'decimal:2',
        'is_taxable'                    => 'boolean',
        'tax_percentage'                => 'decimal:2',
        'subject_to_vat'                => 'boolean',
        'vat_percentage'                => 'decimal:2',
        'absence_deduction_per_day'     => 'decimal:2',
        'undertime_deduction_per_hour'  => 'decimal:2',
        'late_grace_period_minutes'     => 'integer',
        'late_deduction_per_minute'     => 'decimal:2',
        'absent_fixed_deduction'        => 'decimal:2',
        'absent_percentage_deduction'   => 'decimal:2',
        'paid_holidays'                 => 'boolean',
        'payday_1'                      => 'integer',
        'payday_2'                      => 'integer',
        'is_active'                     => 'boolean',
        'effective_date'                => 'date',
        'expiry_date'                   => 'date',
        'created_at'                    => 'datetime',
        'updated_at'                    => 'datetime',
        'deleted_at'                    => 'datetime',
    ];

    /**
     * Get the employee associated with this payroll setting.
     */
    public function employee()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio associated with this payroll setting.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the creator (studio owner) of this payroll setting.
     */
    public function creator()
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }

    /**
     * Check if this is for a photographer (booking-based).
     */
    public function isPhotographerPayroll(): bool
    {
        return $this->payroll_basis === 'booking_and_attendance';
    }

    /**
     * Check if this is for a regular employee (attendance-only).
     */
    public function isEmployeePayroll(): bool
    {
        return $this->payroll_basis === 'attendance_only';
    }

    /**
     * Calculate daily rate based on settings.
     */
    public function calculateDailyRate(): float
    {
        if ($this->daily_rate > 0) {
            return (float) $this->daily_rate;
        }
        
        if ($this->monthly_salary > 0) {
            // Assuming 22 working days per month average
            return (float) ($this->monthly_salary / 22);
        }
        
        if ($this->hourly_rate > 0) {
            // Assuming 8 hours per day
            return (float) ($this->hourly_rate * 8);
        }
        
        return 0.00;
    }

    /**
     * Calculate hourly rate.
     */
    public function calculateHourlyRate(): float
    {
        if ($this->hourly_rate > 0) {
            return (float) $this->hourly_rate;
        }
        
        $dailyRate = $this->calculateDailyRate();
        if ($dailyRate > 0) {
            // Assuming 8 hours per day
            return $dailyRate / 8;
        }
        
        return 0.00;
    }

    /**
     * Calculate total deductions.
     */
    public function getTotalDeductionsAttribute(): float
    {
        $total = (float) $this->sss_deduction +
                (float) $this->phic_deduction +
                (float) $this->hdmf_deduction +
                (float) $this->tax_withholding +
                (float) $this->sss_loan_deduction +
                (float) $this->hdmf_loan_deduction +
                (float) $this->other_deductions;
        
        return round($total, 2);
    }

    /**
     * Calculate monthly net salary (without attendance/booking variables).
     */
    public function getBaseMonthlyNetAttribute(): float
    {
        $monthlyBase = (float) $this->monthly_salary;
        
        // If no monthly salary, calculate from daily rate
        if ($monthlyBase <= 0) {
            $monthlyBase = $this->calculateDailyRate() * 22; // 22 working days
        }
        
        $totalAllowances = $this->total_allowances;
        $totalDeductions = $this->total_deductions;
        
        return round($monthlyBase + $totalAllowances - $totalDeductions, 2);
    }

    /**
     * Get payment schedule display text.
     */
    public function getPaymentScheduleDisplayAttribute(): string
    {
        $schedules = [
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-Weekly',
            'semi_monthly' => 'Semi-Monthly',
            'monthly' => 'Monthly',
        ];
        
        $display = $schedules[$this->payment_schedule] ?? ucfirst($this->payment_schedule);
        
        // Add specific paydays
        if ($this->payment_schedule === 'semi_monthly' && $this->payday_1 && $this->payday_2) {
            $display .= " ({$this->payday_1}st and {$this->payday_2}th)";
        } elseif ($this->payment_schedule === 'monthly' && $this->payday_1) {
            $display .= " ({$this->payday_1}st)";
        } elseif ($this->payment_schedule === 'weekly' && $this->payday_weekly) {
            $display .= " (" . ucfirst($this->payday_weekly) . ")";
        }
        
        return $display;
    }

    /**
     * Get payroll basis display text.
     */
    public function getPayrollBasisDisplayAttribute(): string
    {
        $basis = [
            'attendance_only' => 'Attendance Only',
            'booking_and_attendance' => 'Booking + Attendance',
        ];
        
        return $basis[$this->payroll_basis] ?? ucfirst(str_replace('_', ' ', $this->payroll_basis));
    }

    /**
     * Scope to filter by studio.
     */
    public function scopeByStudio($query, $studioId)
    {
        return $query->where('studio_id', $studioId);
    }

    /**
     * Scope to filter by employee.
     */
    public function scopeByEmployee($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter active payroll settings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by payroll basis.
     */
    public function scopeByPayrollBasis($query, $basis)
    {
        return $query->where('payroll_basis', $basis);
    }
}