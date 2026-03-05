<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rate;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Json;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\ComponentContract;

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
            ])
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
        ];
    }

    /**
     * @param Rate $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    protected function rules(mixed $item): array
    {
        return [];
    }
}
