<?php

namespace Modules\Member\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MessageController extends Controller
{
    use RespondsWithJson;
    /**
     * Display member's messages.
     */
    public function index(): Response|JsonResponse|RedirectResponse
    {
        try {
            $messages = Message::with(['sender', 'receiver'])
                ->where('receiver_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            $unreadCount = Message::where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->count();

            return $this->respond('Member/Messages/Index', [
                'messages' => $messages,
                'unreadCount' => $unreadCount,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load messages.');
        }
    }

    /**
     * Show the form for creating a new message.
     */
    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Member/Messages/Create');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load message creation form.');
        }
    }

    /**
     * Display a specific message.
     */
    public function show(Message $message): Response|JsonResponse|RedirectResponse
    {
        try {
            // Ensure the message belongs to the member
            if ($message->receiver_id !== auth()->id() && $message->sender_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this message.');
            }

            // Mark as read if the member is the receiver
            if ($message->receiver_id === auth()->id()) {
                $message->markAsRead();
            }

            $message->load(['sender', 'receiver']);

            return $this->respond('Member/Messages/Show', [
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load message.');
        }
    }

    /**
     * Send a message to admin.
     */
    public function send(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'subject' => 'nullable|string|max:255',
                'body' => 'required|string|max:5000',
            ]);

            // Get admin user (first admin found)
            $admin = User::role('admin')->first();

            if (!$admin) {
                return $this->respondSingleError('No admin found to send message to.');
            }

            $message = Message::create([
                'sender_id' => auth()->id(),
                'receiver_id' => $admin->id,
                'subject' => $request->subject,
                'body' => $request->body,
            ]);

            return $this->respondSuccess('Message sent to admin successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to send message.');
        }
    }

    /**
     * Reply to a message.
     */
    public function reply(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        try {
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

            return $this->respondSuccess('Reply sent successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to send reply.');
        }
    }

    /**
     * Delete a message.
     */
    public function destroy(Message $message): JsonResponse|RedirectResponse
    {
        try {
            // Ensure the message belongs to the member
            if ($message->receiver_id !== auth()->id() && $message->sender_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this message.');
            }

            $message->delete();

            return $this->respondSuccess('Message deleted successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete message.');
        }
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Message $message): JsonResponse|RedirectResponse
    {
        try {
            // Ensure the message belongs to the member
            if ($message->receiver_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this message.');
            }

            $message->markAsRead();

            return $this->respondSuccess('Message marked as read.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to mark message as read.');
        }
    }

    /**
     * Get unread message count.
     */
    public function unreadCount(): JsonResponse|RedirectResponse
    {
        try {
            $count = Message::where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->count();

            return $this->respondSuccess('Unread count retrieved.', ['count' => $count]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load unread count.');
        }
    }
}
