<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getConversations(Request $request)
    {
        $user = $request->user();

        // Get all unique conversations
        $conversations = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender', 'receiver', 'messageable'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($user) {
                return $message->sender_id === $user->id 
                    ? $message->receiver_id 
                    : $message->sender_id;
            })
            ->map(function ($messages) use ($user) {
                $otherUser = $messages->first()->sender_id === $user->id 
                    ? $messages->first()->receiver 
                    : $messages->first()->sender;
                
                $lastMessage = $messages->first();
                $unreadCount = $messages->where('receiver_id', $user->id)
                    ->where('is_read', false)
                    ->count();

                return [
                    'user' => $otherUser,
                    'last_message' => $lastMessage,
                    'unread_count' => $unreadCount,
                    'updated_at' => $lastMessage->created_at,
                ];
            })
            ->sortByDesc('updated_at')
            ->values();

        return response()->json($conversations);
    }

    public function getMessages(Request $request, User $otherUser)
    {
        $user = $request->user();

        $messages = Message::conversation($user->id, $otherUser->id)
            ->with(['sender', 'receiver', 'messageable'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        Message::conversation($user->id, $otherUser->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
            'messageable_type' => 'nullable|string',
            'messageable_id' => 'nullable|integer',
        ]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'messageable_type' => $validated['messageable_type'] ?? null,
            'messageable_id' => $validated['messageable_id'] ?? null,
            'message' => $validated['message'],
        ]);

        $message->load(['sender', 'receiver', 'messageable']);

        // Broadcast message via Pusher
        Broadcast::channel('user.' . $validated['receiver_id'], function () {
            return true;
        });

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    public function markAsRead(Request $request, Message $message)
    {
        if ($message->receiver_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->markAsRead();

        return response()->json($message);
    }
}

