<?php

namespace App\Http\Controllers;

use App\Services\AI\Gigachat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class AiChatController extends Controller
{
    public function index()
    {
        return Inertia::render('AI/Chat');
    }

    public function promptResponse(Request $request, Gigachat $gigachat)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
            'json_mode' => ['nullable', 'boolean'],
        ]);

        try {
            $jsonMode = (bool) ($validated['json_mode'] ?? false);
            $aiResponse = $gigachat->sendRequest($validated['text'], $jsonMode);

            return response()->json([
                'ai_response' => $aiResponse,
                'json_mode' => $jsonMode,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to get AI response',
            ], 500);
        }
    }
}
