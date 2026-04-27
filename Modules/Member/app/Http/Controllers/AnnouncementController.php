<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Models\AnnouncementComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AnnouncementController extends Controller
{
    use RespondsWithJson;
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $announcements = Announcement::with('author', 'comments.author')
                ->where('published_at', '<=', now())
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'title'        => $a->title,
                    'body'         => $a->body,
                    'is_pinned'    => $a->is_pinned,
                    'published_at' => $a->published_at?->format('M d, Y'),
                    'author'       => $a->author->name,
                    'comments'     => $a->comments->map(fn($c) => [
                        'id'         => $c->id,
                        'body'       => $c->body,
                        'author'     => $c->author->name,
                        'is_mine'    => $c->user_id === Auth::id(),
                        'created_at' => $c->created_at->format('M d, Y h:i A'),
                    ]),
                ]);

            return $this->respond('Member/Announcements/Index', [
                'announcements' => $announcements,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load announcements.');
        }
    }

    public function comment(Request $request, Announcement $announcement): JsonResponse|RedirectResponse
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

    public function deleteComment(AnnouncementComment $comment): JsonResponse|RedirectResponse
    {
        try {
            // Members can only delete their own comments
            if ($comment->user_id !== Auth::id()) {
                abort(403);
            }

            $comment->delete();
            return $this->respondSuccess('Comment deleted.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete comment.');
        }
    }
}
