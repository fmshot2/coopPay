<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LoanPlan extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'loan_amount',
        'interest_rate',
        'repayment_per_month',
        'total_months',
        'months_remaining',
        'amount_remaining',
        'start_date',
        'next_due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loan_amount'          => 'decimal:2',
            'interest_rate'        => 'decimal:2',
            'repayment_per_month'  => 'decimal:2',
            'amount_remaining'     => 'decimal:2',
            'start_date'           => 'date',
            'next_due_date'        => 'date',
        ];
    }

    // ---- Activity Log ----

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()  // only logs fields that actually changed
            ->setDescriptionForEvent(fn(string $eventName) => "Loan plan {$eventName}");
    }

    // ---- Relationships ----

    public function loanType()
    {
        return $this->belongsTo(LoanType::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function monthlyDeductions()
    {
        return $this->hasMany(\Modules\Payment\Models\MonthlyDeduction::class);
    }

    public function extraPayments()
    {
        return $this->hasMany(\Modules\Contribution\Models\ExtraPayment::class);
    }
}
