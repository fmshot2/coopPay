<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\MessageReply;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MessageController extends Controller
{
    /**
     * Display admin inbox with all messages.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $messages = Message::with(['sender', 'receiver'])
            ->where('receiver_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $unreadCount = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['per_page']),
        ]);
    }

    /**
     * Show the form for creating a new message.
     */
    public function create()
    {
        $members = User::role('member')->select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Messages/Create', [
            'members' => $members,
        ]);
    }

    /**
     * Display a specific message.
     */
    public function show(Message $message)
    {
        // Ensure the message belongs to the admin
        if ($message->receiver_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        // Mark as read
        $message->markAsRead();

        $message->load(['sender', 'receiver']);

        return Inertia::render('Admin/Messages/Show', [
            'message' => $message,
        ]);
    }

    /**
     * Reply to a message in-app.
     */
    public function reply(Request $request, Message $message)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Ensure the message belongs to the admin
        if ($message->receiver_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        $reply = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $message->sender_id,
            'subject' => 'Re: ' . ($message->subject ?? 'No Subject'),
            'body' => $request->body,
        ]);

        return redirect()->route('admin.messages.show', $reply)
            ->with('success', 'Reply sent successfully.');
    }

    /**
     * Reply to a message via email.
     */
    public function replyViaEmail(Request $request, Message $message)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        // Ensure the message belongs to the admin
        if ($message->receiver_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        $sender = $message->sender;

        // Send email reply using Mailable
        Mail::to($sender->email)->send(new MessageReply(
            $message,
            $request->body,
            auth()->user()->name
        ));

        // Also save as in-app message
        $reply = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $message->sender_id,
            'subject' => 'Re: ' . ($message->subject ?? 'No Subject'),
            'body' => $request->body,
        ]);

        return redirect()->route('admin.messages.show', $reply)
            ->with('success', 'Reply sent via email and saved in-app.');
    }

    /**
     * Send a new message to a member.
     */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'subject' => $request->subject,
            'body' => $request->body,
        ]);

        return redirect()->route('admin.messages.show', $message)
            ->with('success', 'Message sent successfully.');
    }

    /**
     * Delete a message.
     */
    public function destroy(Message $message)
    {
        // Ensure the message belongs to the admin
        if ($message->receiver_id !== auth()->id() && $message->sender_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this message.');
        }

        $message->delete();

        return redirect()->route('admin.messages.index')
            ->with('success', 'Message deleted successfully.');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Message $message)
    {
        // Ensure the message belongs to the admin
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
