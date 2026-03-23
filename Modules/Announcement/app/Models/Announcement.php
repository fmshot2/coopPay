<?php

namespace Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Announcement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'is_pinned',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'    => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // ---- Activity Log ----

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Announcement {$eventName}");
    }

    // ---- Relationships ----

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(\Modules\Announcement\Models\AnnouncementComment::class);
    }
}
