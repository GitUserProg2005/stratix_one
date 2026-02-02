<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Playlist;


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

    public function showPlaylist(int $playlistId) {
        $playlist = Playlist::with('tracks')
            ->withCount('tracks')
            ->where('owner_id', auth()->id())
            ->findOrFail($playlistId);

        $totalSeconds = $playlist->tracks->sum('duration');
        $playlist->duration = $this->formatDuration($totalSeconds);
        
        return Inertia::render('Playlist/Playlist', [
            'playlist' => $playlist
        ]);
    }

    public function getPlaylists() {
        $playlists = Playlist::with('tracks')
            ->where('owner_id', auth()->id())->get()
            ->map(function ($playlist) {
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

    private function formatDuration($totalSeconds) {
        if (!$totalSeconds) return null;

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = round($totalSeconds % 60);

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }
}
