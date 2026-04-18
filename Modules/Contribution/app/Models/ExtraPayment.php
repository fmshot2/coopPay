<?php

namespace Modules\Contribution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExtraPayment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'loan_plan_id',
        'type',
        'amount',
        'narration',
        'screenshot_path',
        'status',
        'approved_by',
        'approved_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    // ---- Activity Log ----

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Extra payment {$eventName}");
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
