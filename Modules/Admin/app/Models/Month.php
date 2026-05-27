<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Month extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'year_id',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'year_id' => 'integer',
        ];
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(Year::class);
    }
}
