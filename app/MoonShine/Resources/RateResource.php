<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\NodeType;
use App\Models\Rate;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsToMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<Rate>
 */
class RateResource extends ModelResource
{
    protected string $model = Rate::class;

    protected string $title = 'Rates';

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'title'),
            Image::make('Изображение', 'picture')
                ->disk('s3'),
            Number::make('Цена', 'price'),
            BelongsToMany::make('Типы нод', 'nodeTypes', 'name')
                ->onlyCount(),
        ];
    }

    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function formFields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'title')->required(),
                Image::make('Изображение', 'picture')
                    ->disk('s3'),
                Number::make('Цена', 'price')->required()->min(0),
                Json::make('Особенности', 'features')
                    ->fields([
                        Text::make('Особенность', 'name'),
                        Text::make('Расшифровка', 'description'),
                    ]),
                BelongsToMany::make(
                    'Типы нод',
                    'nodeTypes',
                    fn (NodeType $item): string => "{$item->name} ({$item->type})",
                    NodeTypeResource::class,
                )
                    ->horizontalMode()
                    ->withCheckAll()
                    ->columnLabel('Нода')
                    ->valuesQuery(static fn ($query) => $query->orderBy('name')),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'title'),
            Image::make('Изображение', 'picture')
                ->disk('s3'),
            Number::make('Цена', 'price'),
            Json::make('Особенности', 'features')
                ->fields([
                    Text::make('Особенность'),
                    Text::make('Расшифровка'),
                ]),
            BelongsToMany::make(
                'Типы нод',
                'nodeTypes',
                fn (NodeType $item): string => "{$item->name} ({$item->type})",
            )
                ->inLine(', '),
        ];
    }

    /**
     * @param Rate $item
     *
     * @return array<string, string[]|string>
     */
    protected function rules(mixed $item): array
    {
        return [];
    }
}
