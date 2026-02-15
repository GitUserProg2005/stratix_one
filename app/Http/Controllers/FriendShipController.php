<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Friendship;


class FriendShipController extends Controller
{
    public function friends()
    {
        $userId = auth()->id();

        $friends = User::where(function ($q) use ($userId) {
            $q->whereHas('sentFriendRequests', function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->where('status', 'accepted');
            });
        })->orWhere(function ($q) use ($userId) {
            $q->whereHas('receivedFriendRequests', function ($q) use ($userId) {
                $q->where('receiver_id', $userId)
                  ->where('status', 'accepted');
            });
        })->get();
        
        return response()->json($friends);
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
