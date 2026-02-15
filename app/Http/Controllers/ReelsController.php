<?php

namespace App\Http\Controllers;

use App\Models\Snippet;
use App\Models\Comment;

use App\Services\Home\HotRecommendation;

use Illuminate\Support\Facades\Auth;
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
            ->withCount(['likedBy', 'comments'])
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
            ->limit(2)
            ->get()
            ->map(fn (Snippet $s) => [
                'id' => $s->id,
                'audio_url' => $s->audio_url,
                'is_liked' => $s->is_liked,
                'likes_count' => $s->liked_by_count,
                'comments_count' => $s->comments_count,
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

    public function getReels(Request $request)
    {
        // $data = $request->validate([
        //     'lastSnippetId' => 'required|integer|exists:snippets,id',
        // ]);

        $hotRecommendation = new HotRecommendation(Auth::id(), 'snippet', 2);
        $recommendedIds = $hotRecommendation->getHotRecommendation();

        \Log::info('RECOMMENDED IDS: ',  [
            'ids' => $recommendedIds 
        ]);

        $snippets = Snippet::query()
            ->with('track:id,title,preview')
            ->whereNotNull('audio')
            ->withCount(['likedBy', 'comments'])
            ->withExists([
                'likedBy as is_liked' => fn ($q) =>
                    $q->where('user_id', auth()->id())
            ])
            ->whereIn('id', $recommendedIds)
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->map(fn (Snippet $s) => [
                'id' => $s->id,
                'audio_url' => $s->audio_url,
                'is_liked' => $s->is_liked,
                'likes_count' => $s->liked_by_count,
                'comments_count' => $s->comments_count,
                'track' => $s->track ? [
                    'id' => $s->track->id,
                    'title' => $s->track->title,
                    'preview_url' => $s->track->preview_url,
                ] : null,
            ]);

        return response()->json([
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

    public function getComments(Snippet $snippet)
    {
        $comments = $snippet->comments()
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar_url' => $comment->user->avatar_url ?? null,
                ],
            ]);

        return response()->json([
            'success' => true,
            'comments' => $comments,
            'comments_count' => $snippet->comments()->count(),
        ]);
    }

    public function createComment(Request $request, Snippet $snippet)
    {
        $data = $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $comment = $snippet->comments()->create([
            'user_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        $comment->load('user:id,name,avatar');

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at->diffForHumans(),
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'avatar_url' => $comment->user->avatar_url ?? null,
                ],
            ],
            'comments_count' => $snippet->comments()->count(),
        ]);
    }
}
