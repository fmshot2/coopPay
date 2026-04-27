<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Models\AnnouncementComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $query = Announcement::with('author', 'comments.author')
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at');

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            }

            // Apply pinned filter
            if ($request->filled('pinned')) {
                $query->where('is_pinned', $request->input('pinned') === 'yes');
            }

            // Apply date filters
            if ($request->filled('date_from')) {
                $query->whereDate('published_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('published_at', '<=', $request->input('date_to'));
            }

            $perPage = $request->input('per_page', 10);
            $announcements = $query->paginate($perPage)->through(fn($a) => $this->formatAnnouncement($a));

            return $this->respond('Admin/Announcements/Index', [
                'announcements' => $announcements,
                'filters' => $request->only(['search', 'pinned', 'date_from', 'date_to', 'per_page']),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load announcements.');
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title'        => ['required', 'string', 'max:255'],
                'body'         => ['required', 'string'],
                'is_pinned'    => ['boolean'],
                'published_at' => ['nullable', 'date'],
            ]);

            Announcement::create([
                'user_id'      => Auth::id(),
                'title'        => $request->title,
                'body'         => $request->body,
                'is_pinned'    => $request->is_pinned ?? false,
                'published_at' => $request->published_at ?? now(),
            ]);

            return $this->respondSuccess('Announcement posted successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create announcement.');
        }
    }

    public function update(Request $request, Announcement $announcement)
    {
        try {
            $request->validate([
                'title'        => ['required', 'string', 'max:255'],
                'body'         => ['required', 'string'],
                'is_pinned'    => ['boolean'],
                'published_at' => ['nullable', 'date'],
            ]);

            $announcement->update([
                'title'        => $request->title,
                'body'         => $request->body,
                'is_pinned'    => $request->is_pinned ?? false,
                'published_at' => $request->published_at,
            ]);

            return $this->respondSuccess('Announcement updated successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update announcement.');
        }
    }

    public function destroy(Announcement $announcement)
    {
        try {
            $announcement->delete();
            return $this->respondSuccess('Announcement deleted.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete announcement.');
        }
    }

    public function comment(Request $request, Announcement $announcement)
    {
        try {
            $request->validate([
                'body' => ['required', 'string', 'max:1000'],
            ]);

            AnnouncementComment::create([
                'announcement_id' => $announcement->id,
                'user_id'         => Auth::id(),
                'body'            => $request->body,
            ]);

            return $this->respondSuccess('Comment posted.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to post comment.');
        }
    }

    public function deleteComment(AnnouncementComment $comment)
    {
        try {
            $comment->delete();
            return $this->respondSuccess('Comment deleted.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete comment.');
        }
    }

    private function formatAnnouncement(Announcement $a): array
    {
        return [
            'id'           => $a->id,
            'title'        => $a->title,
            'body'         => $a->body,
            'is_pinned'    => $a->is_pinned,
            'published_at' => $a->published_at?->format('M d, Y h:i A'),
            'author'       => $a->author->name,
            'comments'     => $a->comments->map(fn($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'author'     => $c->author->name,
                'created_at' => $c->created_at->format('M d, Y h:i A'),
            ]),
        ];
    }
}
