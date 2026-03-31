<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;


class MapController extends Controller
{
    private const PUBLIC_NOMINATIM = 'https://nominatim.openstreetmap.org';

    public function index()
    {
        return Inertia::render('Map/Index');
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $limit = min(max((int) $request->input('limit', 8), 1), 20);
        $params = [
            'q' => $q,
            'format' => 'json',
            'limit' => $limit,
            'addressdetails' => 1,
        ];
        $headers = [
            'User-Agent' => config('services.nominatim.user_agent', 'DriveeApp/1.0'),
        ];

        $baseUrl = config('services.nominatim.url', self::PUBLIC_NOMINATIM);
        $url = rtrim($baseUrl, '/') . '/search';
        $response = $this->requestNominatim($url, $params, $headers);

        if ($response === null && $baseUrl !== self::PUBLIC_NOMINATIM) {
            Log::warning('Nominatim instance is unavailable, fallback to public API', ['url' => $url]);
            $response = $this->requestNominatim(
                self::PUBLIC_NOMINATIM . '/search',
                $params,
                $headers
            );
        }

        if ($response === null || ! $response->successful()) {
            return response()->json([]);
        }

        $data = $response->json();
        return response()->json(is_array($data) ? $data : []);
    }

    private function requestNominatim(string $url, array $params, array $headers): ?\Illuminate\Http\Client\Response
    {
        try {
            return Http::withHeaders($headers)
                ->timeout(10)
                ->connectTimeout(3)
                ->get($url, $params);
        } catch (\Throwable $e) {
            Log::debug('Nominatim request failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
