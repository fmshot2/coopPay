<?php

namespace Modules\Loan\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Admin\Models\Month;
use Modules\Admin\Models\Year;

// use Modules\Loan\Database\Factories\MonthlyRepaymentFactory;

class MonthlyRepayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'loan_plan_id',
        'year_id',
        'month_id',
        'amount_due',
        'amount_paid',
        'screenshot_path',
        'narration',
        'status',
        'approved_by',
        'approved_at',
        'admin_note',
    ];

    protected $table = 'monthly_repayments';

    public $timestamps = true;

    // protected static function newFactory(): MonthlyRepaymentFactory
    // {
    //     // return MonthlyRepaymentFactory::new();
    // }

    // relationships
    public function loanPlan()
    {
        return $this->belongsTo(LoanPlan::class);
    }

    public function year()
    {
        return $this->belongsTo(Year::class);
    }

    public function month()
    {
        return $this->belongsTo(Month::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
