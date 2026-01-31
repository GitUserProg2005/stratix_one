<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\Release;
use App\MoonShine\Resources\UserResource; // <-- импорт ресурса
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\HasMany;

/**
 * @extends ModelResource<Release>
 */
class ReleaseResource extends ModelResource
{
    protected string $model = Release::class;

    protected string $title = 'Releases';

    /**
     * Поля для списка
     *
     * @return array
     */
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Title', 'title')->sortable(),
            BelongsTo::make('Artist', 'artist', resource: UserResource::class)->sortable(),
            Text::make('Type', 'type'),
        ];
    }

    /**
     * Поля для формы создания/редактирования
     *
     * @return array
     */
    protected function formFields(): array
    {
        return [
            Box::make([
                Text::make('Title', 'title')->required(),
                Select::make('Type', 'type')
                    ->options([
                        'album' => 'Album',
                        'single' => 'Single',
                        'ep' => 'EP',
                    ])
                    ->required(),
                BelongsTo::make('Artist', 'artist', 
                    resource: UserResource::class)
            ]),
            // HasMany::make('Tracks', 'tracks'),
        ];
    }

    /**
     * Поля для отображения деталей
     *
     * @return array
     */
    protected function detailFields(): array
    {
        return [
            ID::make(),
            Text::make('Title', 'title'),
            Text::make('Type', 'type'),
            BelongsTo::make('Artist', 'artist', UserResource::class),
            // HasMany::make('Tracks', 'tracks'),
        ];
    }

    /**
     * Правила валидации
     *
     * @param Release $item
     * @return array
     */
    protected function rules(mixed $item): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:album,single,ep'],
            'artist_id' => ['required', 'exists:users,id'],
        ];
    }
}
