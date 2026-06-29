<?php

namespace Modules\Admin\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Division\Models\Division;

class ImportConflict extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'max_loan_amount',
        'monthly_savings_target',
        'division_id',
        'importable_type',
        'importable_id',
        'source',
        'conflicting_user_ids',
        'status',
        'resolution_notes',
    ];

    protected $casts = [
        'conflicting_user_ids'   => 'array',
        'max_loan_amount'        => 'decimal:2',
        'monthly_savings_target' => 'decimal:2',
    ];

    public function importable()
    {
        return $this->morphTo();
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function conflictingUsers()
    {
        return User::whereIn('id', $this->conflicting_user_ids ?? [])->get();
    }
}
