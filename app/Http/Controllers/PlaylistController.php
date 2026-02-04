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

        return Inertia::render('Playlist/Playlist', [
            'playlist' => new PlaylistResource($playlist),
        ]);
    }

    public function updatePlaylist(Request $request, int $playlistId)
    {
        $playlist = Playlist::where('owner_id', auth()->id())
            ->findOrFail($playlistId);

        $data = $request->validate([
            'title' => 'required|string|max:20',
        ]);

        $playlist->update(['title' => $data['title']]);

        return response()->json([
            'success' => true,
            'playlist' => new PlaylistResource($playlist->fresh('tracks.release')),
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

    /**
     * Форматирует длительность из секунд в 0:00 или H:MM:SS
     */
    private function formatDuration($totalSeconds): string
    {
        if (!$totalSeconds) {
            return '0:00';
        }

        $hours = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);
        $seconds = (int) round($totalSeconds % 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
