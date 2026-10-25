<?php

namespace App\Rules;

use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * One person cannot file two pending or approved leave requests for the same
 * days, or two overtime requests for the same hours. A double click on Submit
 * used to create both, and HR and payroll then saw duplicates.
 *
 * Use NoOverlappingTimeOff::leave() on start_date and ::overtime() on start_time.
 * Pass the id of the request being edited so it does not clash with itself.
 */
class NoOverlappingTimeOff implements DataAwareRule, ValidationRule
{
    private array $data = [];

    private function __construct(private string $kind, private ?int $exceptId) {}

    public static function leave(mixed $exceptId = null): self
    {
        return new self('leave', is_numeric($exceptId) ? (int) $exceptId : null);
    }

    public static function overtime(mixed $exceptId = null): self
    {
        return new self('overtime', is_numeric($exceptId) ? (int) $exceptId : null);
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userId = auth()->id();
        if (! $userId) {
            return;
        }

        if ($this->kind === 'leave') {
            $start = $this->data['start_date'] ?? null;
            $end = $this->data['end_date'] ?? $start;
            if (! $start || ! $end) {
                return;
            }
            $clash = LeaveRequestModel::where('user_id', $userId)
                ->whereIn('status', ['pending', 'approved'])
                ->when($this->exceptId, fn ($query) => $query->whereKeyNot($this->exceptId))
                ->whereDate('start_date', '<=', $end)
                ->whereDate('end_date', '>=', $start)
                ->exists();
            if ($clash) {
                $fail('You already have a pending or approved leave request for these dates.');
            }

            return;
        }

        $date = $this->data['overtime_date'] ?? null;
        $startTime = $this->data['start_time'] ?? null;
        $endTime = $this->data['end_time'] ?? null;
        if (! $date || ! $startTime || ! $endTime) {
            return;
        }
        $clash = OvertimeRequestModel::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($this->exceptId, fn ($query) => $query->whereKeyNot($this->exceptId))
            ->whereDate('overtime_date', $date)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
        if ($clash) {
            $fail('You already have a pending or approved overtime request for these hours.');
        }
    }
}
