<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Loan\Models\LoanType;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Loan extends Model
{
    use HasFactory;
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
     return LogOptions::defaults()
        ->logOnly([
            'amount',
            'interest_rate',
            'duration_months',
            'monthly_payment',
            'total_payment',
            'remaining_amount',
            'status',
            'start_date',
            'end_date',
        ])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}

    protected $fillable = [
        'user_id',
        'loan_type_id',
        'loan_application_id',
        'amount',
        'interest_rate',
        'duration_months',
        'monthly_payment',
        'total_payment',
        'purpose',
        'status',
        'start_date',
        'end_date',
        'remaining_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function getDescriptionForEvent(string $eventName): string
{
    return "Loan has been {$eventName}";
}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
