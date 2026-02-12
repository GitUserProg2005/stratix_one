<?php

namespace App\Http\Controllers;

use App\Models\Snippet;

use Illuminate\Http\Request;
use Inertia\Inertia;


class ReelsController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'snippetId' => 'nullable|exists:snippets,id',
            'otherSnippetIds' => 'nullable|array',
            'otherSnippetIds.*' => 'integer|exists:snippets,id',
        ]);

        $query = Snippet::query()
        ->with('track:id,title,preview')
        ->whereNotNull('audio')
        ->withCount('likedBy')
        ->withExists([
            'likedBy as is_liked' => fn ($q) =>
                $q->where('user_id', auth()->id())
        ]); 

        if (!empty($data['snippetId']) || !empty($data['otherSnippetIds'])) {

            $ids = collect($data['otherSnippetIds'] ?? [])
                ->when(
                    !empty($data['snippetId']),
                    fn ($c) => $c->push($data['snippetId'])
                )
                ->unique()
                ->values();
    
            $query->whereIn('id', $ids);
        }

        $snippets = $query
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

    public function search(Request $request)
    {
        $data = $request->validate([
            'search' => 'required|string|max:50',
        ]);

        $snippets = Snippet::search($data['search'])
            ->query(fn ($q) =>
                $q->with([
                    'track:id,title,preview',
                    'track.tags:id,name',
                ])
                ->whereNotNull('audio')
            )
            ->take(20)
            ->get()
            ->map(fn (Snippet $s) => [
                'id' => $s->id,
                'track' => $s->track ? [
                    'id' => $s->track->id,
                    'title' => $s->track->title,
                    'preview_url' => $s->track->preview_url,
                    'tags' => $s->track->tags->map(fn ($t) => [
                        'id' => $t->id,
                        'name' => $t->name,
                    ]),
                ] : null,
            ]);

        return response()->json([
            'success' => true,
            'snippets' => $snippets,
        ]);
    }

    public function stop(Request $request, int $snippetId) {
        $data = $request->validate([
            'reason' => 'required|string|in:back,ended',
            'listen_time' => 'required|numeric|min:0',
            'duration' => 'required|numeric|min:0',
        ]);

        $request->user()->listens()->create([
            'snippet_id' => $snippetId,
            'reason' => $data['reason'],
            'listen_time' => (float) $data['listen_time'],
            'duration' => (float) $data['duration'],
        ]);

        return response()->json(['success' => true]);
    }
}
