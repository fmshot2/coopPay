<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MonthlyDeduction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'loan_plan_id',
        'month',
        'expected_amount',
        'status',
        'confirmed_at',
        'approved_by',
        'approved_at',
        'member_note',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'confirmed_at'    => 'datetime',
            'approved_at'     => 'datetime',
        ];
    }

    // ---- Activity Log ----

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Monthly deduction {$eventName}");
    }

    // ---- Relationships ----

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function loanPlan()
    {
        return $this->belongsTo(\Modules\Loan\Models\LoanPlan::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
