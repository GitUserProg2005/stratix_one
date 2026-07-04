<?php

namespace App\Http\Controllers;

use App\Models\Rate;
use Illuminate\Support\Facades\Storage;

class RateController extends Controller
{
    public function index()
    {
        $rates = Rate::orderBy('price')
            ->get()
            ->map(fn (Rate $rate) => $this->formatRate($rate));

        return response()->json([
            'result' => 'ok',
            'rates' => $rates,
        ]);
    }

    private function formatRate(Rate $rate): array
    {
        $picture = $rate->picture;
        $pictureUrl = $picture
            ? (filter_var($picture, FILTER_VALIDATE_URL) ? $picture : Storage::disk('s3')->url($picture))
            : null;

        return [
            'id' => $rate->id,
            'title' => $rate->title,
            'picture' => $pictureUrl,
            'features' => $rate->features,
            'access_nodes' => $rate->access_nodes,
            'price' => $rate->price,
        ];
    }
}
