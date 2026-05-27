<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Year extends Model
{
    use HasFactory;

    protected $fillable = ['year'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    public function months(): HasMany
    {
        return $this->hasMany(Month::class);
    }
}
