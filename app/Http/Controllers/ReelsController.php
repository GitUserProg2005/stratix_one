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
            ->orderBy('id')
            ->get()
            ->map(fn (Snippet $s) => [
                'id' => $s->id,
                'audio_url' => $s->audio_url,
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
}
