<?php

namespace Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnnouncementComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'body',
    ];

    // ---- Relationships ----

    public function announcement()
    {
        return $this->belongsTo(\Modules\Announcement\Models\Announcement::class);
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
