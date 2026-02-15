<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendShipController extends Controller
{
    public function friends()
    {
        $userId = auth()->id();

        $friends = User::whereHas('receivedFriendRequests', function ($q) use ($userId) {
            $q->where('sender_id', $userId)->where('status', 'accepted');
        })
        ->orWhereHas('sentFriendRequests', function ($q) use ($userId) {
            $q->where('receiver_id', $userId)->where('status', 'accepted');
        })
        ->get();

        return response()->json($friends->map(function (User $friend) use ($userId) {
            $chat = Chat::whereHas('users', fn ($q) => $q->where('user_id', $userId))
                ->whereHas('users', fn ($q) => $q->where('user_id', $friend->id))
                ->with('streak')
                ->first();
            $streak = $chat?->streak ? [
                'active' => $chat->streak->active,
                'days' => $chat->streak->days,
            ] : null;
            return array_merge($friend->toArray(), ['streak' => $streak]);
        })->all());
    }

    /** Список входящих заявок в друзья (pending) */
    public function pendingRequests()
    {
        $requests = Friendship::with('sender')
            ->where('receiver_id', auth()->id())
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($requests);
    }

    public function sendRequest(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'Cannot add yourself'], 403);
        }

        $exists = Friendship::where(function ($q) use ($user) {
            $q->where('sender_id', auth()->id())
              ->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $q->where('sender_id', $user->id)
              ->where('receiver_id', auth()->id());
        })->exists();

        if ($exists) {
            return response()->json(['error' => 'Request already exists'], 409);
        }

        Friendship::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $user->id,
            'status' => 'pending'
        ]);

        return response()->json(['success' => true]);
    }

    public function accept(Friendship $friendship)
    {
        if ($friendship->receiver_id !== auth()->id()) {
            abort(403);
        }

        $friendship->update([
            'status' => 'accepted'
        ]);

        return response()->json(['success' => true]);
    }

    public function reject(Friendship $friendship)
    {
        if ($friendship->receiver_id !== auth()->id()) {
            abort(403);
        }

        $friendship->update([
            'status' => 'rejected'
        ]);

        return response()->json(['success' => true]);
    }
}
