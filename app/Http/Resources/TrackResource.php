<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Track;


class TrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'preview_url' => $this->preview_url,
            'file' => $this->file_url,
            'lyrics' => $this->lyrics,

            'tags' => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ]),

            'release' => [
                'id' => $this->release->id,
                'title' => $this->release->title,
                'type' => $this->release->type,
                'created_at' => optional($this->release->created_at)?->toDateString(),
            ],

            'artist' => [
                'id' => $this->release->artist->id,
                'name' => $this->release->artist->name,
                'tracks_count' => $this->release->artist->tracks_count,

                // здесь оставляем только необходимые данные для списка треков артиста
                'tracks' => $this->release->artist->tracks->map(fn ($track) => [
                    'id' => $track->id,
                    'title' => $track->title,
                    'preview_url' => $track->preview_url,
                    // НЕ вставляем сюда 'artist' повторно
                ]),
            ],
        ];
    }
}
