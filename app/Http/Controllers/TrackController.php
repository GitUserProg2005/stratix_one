<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Resources\TrackResource;
use App\Http\Resources\ArtistTracksResource;

use Inertia\Inertia;

use App\Models\Track;
use App\Models\Release;


class TrackController extends Controller
{
    public function index() {
        $tracks = Track::with('release.artist')->get(); 

        return Inertia::render('Index', [
            'tracks' => TrackResource::collection($tracks)
        ]);
    }

    public function show(Request $request, int $trackId)
    {
        $data = $request->validate([
            'rightNow' => 'nullable|integer',
            'back' => ['nullable', 'string', function ($attr, $value, $fail) {
                // только относительный путь без протокола (защита от open redirect)
                if ($value !== null && $value !== '' && (!str_starts_with($value, '/') || str_contains($value, '//'))) {
                    $fail('Недопустимый URL возврата.');
                }
            }],
        ]);

        $backUrl = isset($data['back']) && $data['back'] !== '' ? $data['back'] : null;

        $track = Track::with([
            'tags',
            'release.artist' => function ($q) {
                $q->withCount('tracks')
                ->with(['tracks' => function ($q) {
                    $q->select('tracks.id','tracks.title','tracks.preview',
                    'tracks.duration', 'tracks.lyrics', 'tracks.file', 'tracks.release_id')
                        ->with('release.artist');
                }]);
            }
        ])->findOrFail($trackId);

        return Inertia::render('Player/Player', [
            'track' => new TrackResource($track),
            'artist_tracks' => TrackResource::collection(
                $track->release->artist->tracks
            ),
            'rightNow' => $data['rightNow'] ?? null,
            'backUrl' => $backUrl,
        ]);
    }

    public function search(Request $request) {
        $data = $request->validate([
            'search' => 'required|string|max:50',
        ]);

        $tracks = Track::search($data['search'])
            ->query(fn ($q) =>
                $q->with([
                    'tags',
                    'release:id,artist_id,created_at',
                    'release.artist:id,name'
                ])
            )
        ->get();

        return response()->json([
            'success' => true,
            'tracks' => TrackResource::collection($tracks)
        ]);
    }
}
