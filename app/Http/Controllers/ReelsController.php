<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use Inertia\Inertia;


class ReelsController extends Controller
{
    public function index()
    {
        $snippets = Snippet::with('track:id,title,preview')
            ->whereNotNull('audio')
            ->withCount('likedBy')
            ->withExists([
                'likedBy as is_liked' => fn($q) => $q->where('user_id', auth()->id())
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (Snippet $s) => [
                'id' => $s->id,
                'audio_url' => $s->audio_url,
                'is_liked' => $s->is_liked,
                'likes_count' => $s->liked_by_count,
                'track' => $s->track ? [
                    'id' => $s->track->id,
                    'title' => $s->track->title,
                    'preview_url' => $s->track->preview_url,
                ] : null,
            ]);

        return Inertia::render('Reels/Reels', [
            'snippets' => $snippets,
        ]);
    }

    public function likeToggle(Snippet $snippet)
    {
        $user = auth()->user();

        if ($snippet->isLikedBy($user)) {
            $snippet->likedBy()->detach($user->id);
        } else {
            $snippet->likedBy()->attach($user->id);
        }

        return response()->json([
            'liked' => $snippet->isLikedBy($user),
            'likes_count' => $snippet->likesCount(),
        ]);
    }
}
