<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(): Response
    {
        $activities = Activity::with('causer')
            ->latest()
            ->take(100)
            ->get()
            ->map(fn($a) => [
                'id'          => $a->id,
                'description' => $a->description,
                'causer'      => $a->causer?->name ?? 'System',
                'causer_id'   => $a->causer?->member_id ?? '—',
                'subject'     => $a->subject_type ? class_basename($a->subject_type) : '—',
                'event'       => $a->event,
                'properties'  => $a->properties,
                'created_at'  => $a->created_at->format('M d, Y h:i A'),
            ]);

        return Inertia::render('Admin/ActivityLog/Index', [
            'activities' => $activities,
        ]);
    }
}
