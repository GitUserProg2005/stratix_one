<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Resources\TrackResource;
use App\Http\Resources\ArtistTracksResource;

use Inertia\Inertia;

use App\Models\Tag;
use App\Models\Track;
use App\Models\Release;
use App\Models\UserListen;
use App\Services\Home\HotRecommendation;
use App\Services\Home\RecentViewedTracks;
use App\Services\Home\TracksByTag;
use Illuminate\Support\Facades\Auth;


class TrackController extends Controller
{
    public function index()
    {
        $tracks = [
            'recommended' => TrackResource::collection(collect()),
            'recently' => TrackResource::collection(collect()),
            'by_tag' => [],
        ];

        $groupedByTag = (new TracksByTag(10, 10))->getGrouped();
        if (!empty($groupedByTag)) {
            $tracks['tag_names'] = Tag::whereIn('id', array_keys($groupedByTag))->pluck('name', 'id')->toArray();
            $allTrackIds = collect($groupedByTag)->flatten()->unique()->values()->all();
            $tracksById = Track::whereIn('id', $allTrackIds)
                ->with('release.artist')
                ->get()
                ->keyBy('id');
            foreach ($groupedByTag as $tagId => $trackIds) {
                $ordered = collect($trackIds)->map(fn ($id) => $tracksById->get($id))->filter()->values();
                $tracks['by_tag'][$tagId] = TrackResource::collection($ordered);
            }
        } else {
            $tracks['tag_names'] = [];
        }

        if (Auth::check()) {
            $recommendedIds = (new HotRecommendation(Auth::id(), 'track', 10))->getHotRecommendation();
            if (!empty($recommendedIds)) {
                $recommendedTracks = Track::whereIn('id', $recommendedIds)
                    ->with('release.artist')
                    ->get();
                $ordered = collect($recommendedIds)->map(function ($id) use ($recommendedTracks) {
                    return $recommendedTracks->firstWhere('id', $id);
                })->filter()->values();

                $tracks['recommended'] = TrackResource::collection($ordered);
            }

            $recentlyIds = (new RecentViewedTracks(Auth::id(), 9))->getTrackIds();
            if (!empty($recentlyIds)) {
                $recentlyTracks = Track::whereIn('id', $recentlyIds)
                    ->with('release.artist')
                    ->get();
                $ordered = collect($recentlyIds)->map(function ($id) use ($recentlyTracks) {
                    return $recentlyTracks->firstWhere('id', $id);
                })->filter()->values();

                $tracks['recently'] = TrackResource::collection($ordered);
            }
        }

        return Inertia::render('Index', [
            'tracks' => $tracks,
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

    /**
     * Сохранить факт остановки прослушивания трека (Back / окончание трека).
     */
    public function stop(Request $request, int $trackId)
    {
        $data = $request->validate([
            'reason' => 'required|string|in:back,ended',
            'listen_time' => 'required|numeric|min:0',
            'duration' => 'required|numeric|min:0',
        ]);

        Track::findOrFail($trackId);

        $request->user()->listens()->create([
            'track_id' => $trackId,
            'reason' => $data['reason'],
            'listen_time' => (float) $data['listen_time'],
            'duration' => (float) $data['duration'],
        ]);

        return response()->json(['success' => true]);
    }
}
