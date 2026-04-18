<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    /**
     * Display member's messages.
     */
    public function index()
    {
        $messages = Message::with(['sender', 'receiver'])
            ->where('receiver_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $unreadCount = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return Inertia::render('Member/Messages/Index', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Show the form for creating a new message.
     */
    public function create()
    {
        return Inertia::render('Member/Messages/Create');
    }

    /**
     * Display a specific message.
     */
    public function show(Message $message)
    {
        // Ensure the message belongs to the member
        if ($message->receiver_id !== auth()->id() && $message->sender_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        // Mark as read if the member is the receiver
        if ($message->receiver_id === auth()->id()) {
            $message->markAsRead();
        }

        $message->load(['sender', 'receiver']);

        return Inertia::render('Member/Messages/Show', [
            'message' => $message,
        ]);
    }

    /**
     * Send a message to admin.
     */
    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        // Get admin user (first admin found)
        $admin = User::role('admin')->first();

        if (!$admin) {
            return back()->with('error', 'No admin found to send message to.');
        }

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $admin->id,
            'subject' => $request->subject,
            'body' => $request->body,
        ]);

        return redirect()->route('member.messages.show', $message)
            ->with('success', 'Message sent to admin successfully.');
    }

    /**
     * Reply to a message.
     */
    public function reply(Request $request, Message $message)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Ensure the message belongs to the member
        if ($message->receiver_id !== auth()->id() && $message->sender_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        $reply = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $message->sender_id,
            'subject' => 'Re: ' . ($message->subject ?? 'No Subject'),
            'body' => $request->body,
        ]);

        return redirect()->route('member.messages.show', $reply)
            ->with('success', 'Reply sent successfully.');
    }

    /**
     * Delete a message.
     */
    public function destroy(Message $message)
    {
        // Ensure the message belongs to the member
        if ($message->receiver_id !== auth()->id() && $message->sender_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        $message->delete();

        return redirect()->route('member.messages.index')
            ->with('success', 'Message deleted successfully.');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Message $message)
    {
        // Ensure the message belongs to the member
        if ($message->receiver_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        $message->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Get unread message count.
     */
    public function unreadCount()
    {
        $count = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
