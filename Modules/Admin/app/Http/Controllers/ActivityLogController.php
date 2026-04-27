<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\ResponseHelper;

class ActivityLogController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $query = Activity::with('causer')->latest();

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('member_id', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('event')) {
                $query->where('event', $request->input('event'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $activities = $query->paginate(15)
                ->through(fn($a) => [
                    'id'          => $a->id,
                    'description' => $a->description,
                    'causer'      => $a->causer?->name ?? 'System',
                    'causer_id'   => $a->causer?->member_id ?? '—',
                    'subject'     => $a->subject_type ? class_basename($a->subject_type) : '—',
                    'subject_id'  => $a->subject_id,
                    'event'       => $a->event,
                    'properties'  => [
                        'attributes' => $a->properties['attributes'] ?? [],
                        'old'        => $a->properties['old'] ?? [],
                    ],
                    'created_at'  => $a->created_at->format('M d, Y h:i A'),
                ]);

            return $this->respond('Admin/ActivityLog/Index', [
                'activities' => $activities,
                'filters'    => $request->only(['search', 'event', 'date_from', 'date_to']),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load activity log.');
        }
    }
}
