<?php

namespace App\Models\StudioHR;

use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores generated payroll records for employee salary processing.
 */
class GeneratedPayrollModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_generated_payrolls';

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
        'payroll_reference',
        'user_id',
        'studio_id',
        'payroll_setting_id',
        'generated_by',
        'employee_type',
        'payroll_basis',
        'employee_role',
        'period_start',
        'period_end',
        'attendance_days_present',
        'attendance_days_absent',
        'attendance_minutes_late',
        'attendance_minutes_undertime',
        'booking_count',
        'attendance_amount',
        'booking_amount',
        'gross_amount',
        'total_deductions',
        'net_amount',
        'deduction_breakdown',
        'computation_summary',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'attendance_amount' => 'decimal:2',
        'booking_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'deduction_breakdown' => 'array',
        'computation_summary' => 'array',
        'status' => 'string',
        'reviewed_at' => 'datetime',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the employee for this generated payroll.
     */
    public function employee()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the studio for this generated payroll.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
    }

    /**
     * Get the payroll setting used during generation.
     */
    public function payrollSetting()
    {
        return $this->belongsTo(EmployeePayrollModel::class, 'payroll_setting_id');
    }

    /**
     * Get the HR user who generated this payroll.
     */
    public function generator()
    {
        return $this->belongsTo(UserModel::class, 'generated_by');
    }

    /**
     * Get the finance user who reviewed this generated payroll.
     */
    public function reviewer()
    {
        return $this->belongsTo(UserModel::class, 'reviewed_by');
    }
}
