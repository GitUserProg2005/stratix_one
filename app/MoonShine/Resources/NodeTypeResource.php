<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\NodeType;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<NodeType>
 */
class NodeTypeResource extends ModelResource
{
    protected string $model = NodeType::class;

    protected string $title = 'NodeTypes';

    protected string $column = 'name';

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name'),
            Text::make('Тип', 'type'),
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
                Text::make('Название', 'name')->required(),
                Text::make('Тип', 'type')->required(),
                Text::make('Описание', 'description'),
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
            Text::make('Название', 'name'),
            Text::make('Тип', 'type'),
            Text::make('Описание', 'description'),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [];
    }
}
