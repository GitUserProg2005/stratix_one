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
        ]);

        try {
            $aiResponse = $gigachat->sendRequest($validated['text']);

            return response()->json([
                'ai_response' => $aiResponse,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to get AI response',
            ], 500);
        }
    }
}
