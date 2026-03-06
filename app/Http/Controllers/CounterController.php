<?php

namespace App\Http\Controllers;

use App\Events\CounterUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CounterController extends Controller
{
    public function index()
    {
        try {
            $count = (int) Cache::get('ws_counter', 0);
        } catch (\Throwable $e) {
            report($e);
            $count = 0;
        }

        return Inertia::render('Counter', [
            'count' => $count,
        ]);
    }

    public function increment(Request $request)
    {
        $count = 0;
        try {
            $count = (int) Cache::get('ws_counter', 0);
            $count++;
            Cache::put('ws_counter', $count);
        } catch (\Throwable $e) {
            report($e);
            $count = max(1, $count);
        }

        try {
            event(new CounterUpdated($count));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['count' => $count]);
    }
}
