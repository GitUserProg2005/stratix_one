<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // первый трек для preview
        $firstTrack = $this->tracks->first();

        // суммарная длительность всех треков
        $totalSeconds = $this->tracks->sum('duration');

        // уникальные имена артистов треков плейлиста
        $artistsNames = $this->tracks
            ->map(fn ($track) => $track->release?->artist?->name)
            ->filter()
            ->unique()
            ->values();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'preview_url' => $firstTrack?->preview_url ?? null,
            'tracks_count' => $this->tracks->count(),
            'duration' => $this->formatDuration($totalSeconds),
            'artists_names' => $this->formatArtistsNames($artistsNames),
            'tracks' => $this->tracks->map(function ($track) {
                return [
                    'id' => $track->id,
                    'title' => $track->title,
                    'preview_url' => $track->preview_url,
                    'duration' => $track->duration,
                    'artist' => $track->release?->artist?->name,
                    'release_created_at' => $track->release?->created_at?->toDateString(), // безопасно
                ];
            }),
        ];
    }

    /**
     * Строка имён артистов: "Артист 1, Артист 2" или "Артист 1, Артист 2 и другие"
     */
    private function formatArtistsNames(\Illuminate\Support\Collection $names): string
    {
        if ($names->isEmpty()) {
            return '';
        }
        if ($names->count() <= 2) {
            return $names->implode(', ');
        }
        return $names->take(3)->implode(', ') . ' и другие';
    }

    /**
     * Форматирует длительность из секунд в 0:00 или H:MM:SS
     */
    private function formatDuration($totalSeconds) {
        if (!$totalSeconds) return '0:00';

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
