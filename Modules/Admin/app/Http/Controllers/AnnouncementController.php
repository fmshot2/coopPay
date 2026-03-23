<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Models\AnnouncementComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        $announcements = Announcement::with('author', 'comments.author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn($a) => $this->formatAnnouncement($a));

        return Inertia::render('Admin/Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request)
    {
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

        return back()->with('success', 'Announcement posted successfully.');
    }

    public function update(Request $request, Announcement $announcement)
    {
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

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    public function comment(Request $request, Announcement $announcement)
    {
        $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        AnnouncementComment::create([
            'announcement_id' => $announcement->id,
            'user_id'         => Auth::id(),
            'body'            => $request->body,
        ]);

        return back()->with('success', 'Comment posted.');
    }

    public function deleteComment(AnnouncementComment $comment)
    {
        $comment->delete();
        return back()->with('success', 'Comment deleted.');
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
