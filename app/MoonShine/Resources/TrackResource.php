<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Track;
use App\MoonShine\Resources\ReleaseResource;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\File;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;

class TrackResource extends ModelResource
{
    protected string $model = Track::class;

    protected string $title = 'Tracks';

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Title', 'title')->sortable(),
            BelongsTo::make('Release', 'release', ReleaseResource::class),
        ];
    }

    protected function formFields(): iterable
    {
        return [
            Box::make([
                Text::make('Title', 'title')->required(),
                BelongsTo::make('Release', 'release', resource: ReleaseResource::class)
                    ->searchable()
                    ->required(),

                BelongsToMany::make('Теги', 'tags', fn ($item) => "$item->name")
                    ->searchable()->creatable(),

                // Превью на S3
                File::make('Preview', 'preview')
                    ->disk('s3')
                    ->dir('tracks_previews') // второй параметр — директория
                    ,

                // Аудиофайл
                File::make('File', 'file')
                    ->disk('s3') 
                    ->dir('tracks_files')
            ]),
        ];
    }

    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Title', 'title'),
            BelongsTo::make('Release', 'release', ReleaseResource::class),
            File::make('Preview', 'preview')->disk('s3', 'tracks_previews'),
            File::make('File', 'file')->disk('public', 'tracks_files'),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            
        ];
    }
}
