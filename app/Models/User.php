<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\CausesActivity;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, CausesActivity;

    protected $fillable = [
        'member_id',
        'name',
        'email',
        'phone',
        'password',
        'profile_photo',
        'is_active',
        'must_change_password',
        'division_id',
        'max_loan_amount',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    // ---- Relationships ----

    // public function loanPlan()
    // {
    //     return $this->hasOne(\Modules\Loan\Models\LoanPlan::class);
    // }

    public function loanPlan()
    {
        return $this->hasOne(\Modules\Loan\Models\LoanPlan::class)->latestOfMany();
    }

    public function loanPlans()
    {
        return $this->hasMany(\Modules\Loan\Models\LoanPlan::class);
    }

    public function activeLoanPlans()
    {
        return $this->hasMany(\Modules\Loan\Models\LoanPlan::class)->where('status', 'active');
    }

    public function monthlyDeductions()
    {
        return $this->hasMany(\Modules\Payment\Models\MonthlyDeduction::class);
    }

    public function extraPayments()
    {
        return $this->hasMany(\Modules\Contribution\Models\ExtraPayment::class);
    }

    public function announcements()
    {
        return $this->hasMany(\Modules\Announcement\Models\Announcement::class);
    }

    public function announcementComments()
    {
        return $this->hasMany(\Modules\Announcement\Models\AnnouncementComment::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id')->where('is_read', false);
    }

    public function division()
    {
        return $this->belongsTo(\Modules\Division\Models\Division::class);
    }
}
