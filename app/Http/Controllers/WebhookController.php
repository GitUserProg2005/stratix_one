<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Workflow;
use App\Models\Webhook;
use App\Models\User;

use App\Services\N8N\CheckRate;
use App\Services\N8N\IncomingDataNormalizer;
use App\Services\N8N\Runner;


class WebhookController extends Controller
{
    public function trigger(string $token, Request $request, IncomingDataNormalizer $normalizer, CheckRate $checkRate)
    {
        // 1. Находим webhook по URL-токену
        $webhook = Webhook::where('token', $token)->firstOrFail();

        // 2. Берём владельца webhook
        $owner = User::query()->whereKey($webhook->user_id)->firstOrFail();

        // 3. Проверяем API-ключ из заголовка против ключа владельца
        if (! $owner->verifyApiKey($request->header('X-Api-Key'))) {
            return response()->json([
                'result' => 'error',
                'message' => 'Invalid API key',
            ], 401);
        }

        // 4. Проверяем тариф владельца по нодам workflow
        $workflow = Workflow::with('nodes', 'nodes.edges')
            ->findOrFail($webhook->workflow_id);

        if (! $checkRate->checkRate($owner->rate_id, $workflow->nodes)) {
            return response()->json([
                'result' => 'error',
                'message' => 'У владельца webhook нет доступа к нодам этого workflow',
            ], 403);
        }

        // 5. Нормализуем вход по схеме webhook-ноды
        $edges = $workflow->nodes->flatMap(fn ($node) => $node->edges ?? []);

        $webhookNode = $workflow->nodes->firstWhere('id', $webhook->node_id);
        $nodeConfig = is_string($webhookNode?->config)
            ? json_decode($webhookNode->config, true)
            : (array) ($webhookNode?->config ?? []);
        $requestSchema = $nodeConfig['request'] ?? null;

        try {
            $context = $normalizer->normalizeRequest($request, $requestSchema);
        } catch (\RuntimeException $e) {
            return response()->json([
                'result' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        // 6. Запускаем runner с webhook-ноды
        $runner = new Runner(
            workflowId: $workflow->id,
            nodes: $workflow->nodes,
            edges: $edges,
            context: $context,
        );

        $runId = $runner->startFromNode($webhook->node_id);

        return response()->json([
            'result' => 'accepted',
            'run_id' => $runId,
        ], 202);
    }

    public function tokenByNode(int $nodeId)
    {
        $webhook = Webhook::query()
            ->where('node_id', $nodeId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json([
            'result' => 'ok',
            'token' => $webhook->token,
        ]);
    }
}
