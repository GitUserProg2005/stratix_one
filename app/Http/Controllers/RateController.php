<?php

namespace App\Http\Controllers;

use App\Models\Rate;

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
        return [
            'id' => $rate->id,
            'title' => $rate->title,
            'picture' => $rate->pictureUrl(),
            'features' => $rate->features,
            'access_nodes' => $rate->access_nodes,
            'price' => $rate->price,
        ];
    }
}
