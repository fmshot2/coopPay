<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Auto generate slug from name
    protected static function booted(): void
    {
        static::creating(function ($loanType) {
            $loanType->slug = Str::slug($loanType->name);
        });
    }

    public function loanPlans()
    {
        return $this->hasMany(LoanPlan::class);
    }
}
