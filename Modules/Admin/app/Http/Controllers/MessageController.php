<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Mail\MessageReply;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class MessageController extends Controller
{
    use RespondsWithJson;

    /**
     * Display admin inbox with all messages.
     */
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $messages = Message::with(['sender', 'receiver'])
                ->where('receiver_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $unreadCount = Message::where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->count();

            return $this->respond('Admin/Messages/Index', [
                'messages' => $messages,
                'unreadCount' => $unreadCount,
                'filters' => $request->only(['per_page']),
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
            $members = User::role('member')->select('id', 'name', 'email')->get();

            return $this->respond('Admin/Messages/Create', [
                'members' => $members,
            ]);
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
            // Ensure the message belongs to the admin
            if ($message->receiver_id !== auth()->id()) {
                abort(403, 'Unauthorized access to this message.');
            }

            // Mark as read
            $message->markAsRead();

            $message->load(['sender', 'receiver']);

            return $this->respond('Admin/Messages/Show', [
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load message.');
        }
    }

    /**
     * Reply to a message in-app.
     */
    public function reply(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        try {
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

            return $this->respondSuccess('Reply sent successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to send reply.');
        }
    }

    /**
     * Reply to a message via email.
     */
    public function replyViaEmail(Request $request, Message $message): JsonResponse|RedirectResponse
    {
        try {
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

            return $this->respondSuccess('Reply sent via email and saved in-app.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to send email reply.');
        }
    }

    /**
     * Send a new message to a member.
     */
    public function send(Request $request): JsonResponse|RedirectResponse
    {
        try {
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

            return $this->respondSuccess('Message sent successfully.');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to send message.');
        }
    }

    /**
     * Delete a message.
     */
    public function destroy(Message $message): JsonResponse|RedirectResponse
    {
        try {
            // Ensure the message belongs to the admin
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
            // Ensure the message belongs to the admin
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
