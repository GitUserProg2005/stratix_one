<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Playlist;

use App\Http\Resources\PlaylistResource;

use Inertia\Inertia;


class PlaylistController extends Controller
{
    public function createPlaylist(Request $request) {
        $data = $request->validate([
            'title' => 'required|string|max:20'
        ]);

        $playlist = Playlist::create([
            'title' => $data['title'],
            'owner_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'playlist' => $playlist
        ]);
    }

    public function showPlaylist(int $playlistId)
    {
        $playlist = Playlist::with('tracks.release')
            ->where('owner_id', auth()->id())
            ->findOrFail($playlistId);

        // Возвращаем через ресурс, можно для Inertia сразу
        return Inertia::render('Playlist/Playlist', [
            'playlist' => new PlaylistResource($playlist),
        ]);
    }

    public function addTrackToPlaylist(Request $request, int $playlistId)
    {
        // Валидация входных данных
        $data = $request->validate([
            'trackId' => 'required|exists:tracks,id',
        ]);

        // Находим плейлист
        $playlist = Playlist::find($playlistId);

        if (!$playlist) {
            return response()->json([
                'success' => false,
                'message' => 'Плейлист не найден',
            ], 404);
        }

        // Добавляем трек в pivot table
        // Предположим, что у Playlist есть отношение tracks() через belongsToMany
        if ($playlist->tracks()->where('track_id', $data['trackId'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Трек уже в плейлисте',
            ]);
        }

        $playlist->tracks()->attach($data['trackId']);

        return response()->json([
            'success' => true,
            'message' => 'Трек успешно добавлен в плейлист',
        ]);
    }

    public function getPlaylists()
    {
        // Берём все плейлисты пользователя с треками и их релизами
        $playlists = Playlist::with('tracks.release')
            ->where('owner_id', auth()->id())
            ->get();

        // Возвращаем через ресурс
        return response()->json([
            'success' => true,
            'playlists' => PlaylistResource::collection($playlists),
        ]);
}

    public function getPlaylistsWithoutTrack(Request $request) {
        $data = $request->validate([
            'track_id' => 'required|exists:tracks,id'
        ]);

        $trackId = $data['track_id'];

        $playlists = Playlist::with('tracks')
            ->where('owner_id', auth()->id())
            ->whereDoesntHave('tracks', function ($q) use ($trackId) {
                $q->where('tracks.id', $trackId);
            })->get()->map(function ($playlist) {
                $playlist->tracks_count = $playlist->tracks->count();

                $totalSeconds = $playlist->tracks->sum('duration');
                $playlist->duration = $this->formatDuration($totalSeconds);

                return $playlist;
            });
        
        return response()->json([
            'success' => true,
            'playlists' => $playlists
        ]); 
    }
}
