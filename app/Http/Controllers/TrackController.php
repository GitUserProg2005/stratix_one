<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Resources\TrackResource;
use App\Http\Resources\ArtistTracksResource;

use Inertia\Inertia;

use App\Models\User;
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
            'rightNow' => 'nullable|integer'
        ]);

        $track = Track::with([
            'tags',
            'release.artist' => function ($q) {
                $q->withCount('tracks')
                ->with(['tracks' => function ($q) {
                    $q->select('tracks.id','tracks.title','tracks.preview', 'tracks.file', 'tracks.release_id')
                        ->with('tags','release.artist');
                }]);
            }
        ])->findOrFail($trackId);

        return Inertia::render('Player/Player', [
            'track' => new TrackResource($track),
            'artist_tracks' => TrackResource::collection(
                $track->release->artist->tracks
            ),
            'rightNow' => $data['rightNow'] ?? null,
        ]);
    }
}
