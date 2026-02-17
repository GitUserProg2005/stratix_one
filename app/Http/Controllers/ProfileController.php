<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\User;
use App\Models\Friendship;


class ProfileController extends Controller
{
    public function profile(User $user)
    {
        $authId = auth()->id();
        $friendshipStatus = null;
        $friendshipId = null;

        if ($authId && $authId !== $user->id) {
            $friendship = Friendship::where(function ($q) use ($authId, $user) {
                $q->where('sender_id', $authId)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($authId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $authId);
            })->first();

            if ($friendship) {
                $friendshipStatus = $friendship->status;
                $friendshipId = $friendship->id;
                if ($friendshipStatus === 'pending') {
                    $friendshipStatus = $friendship->sender_id === $authId ? 'pending_sent' : 'pending_received';
                }
            }
        }

        $likedSnippets = $user->likedSnippets()
            ->with('track:id,title,preview')
            ->whereNotNull('audio')
            ->orderByDesc('liked_snippets.created_at')
            ->limit(24)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'track' => $s->track ? [
                    'id' => $s->track->id,
                    'title' => $s->track->title,
                    'preview_url' => $s->track->preview_url,
                ] : null,
            ]);

        return Inertia::render('Auth/Profile', [
            'user' => $user,
            'friendshipStatus' => $friendshipStatus,
            'friendshipId' => $friendshipId,
            'likedSnippets' => $likedSnippets,
        ]);
    }


    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
