<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistTracksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'preview_url' => $this->preview_url,
            'artist_name' => $this->release->artist->name ?? null,
            'file' => $this->file_url,
            'release_created_at' => optional($this->release->created_at)?->toDateString(),
        ];
    }
}
