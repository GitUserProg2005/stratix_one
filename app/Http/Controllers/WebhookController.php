<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Workflow;
use App\Models\Webhook;

use App\Services\N8N\Runner;


class WebhookController extends Controller
{
    public function __invoke(string $token, Request $request) {
        $webhook = Webhook::where('token', $token)
            ->firstOrFail();
        
        $workflow = Workflow::with('nodes', 'nodes.edges')
            ->findOrFail($webhook->workflow_id);

        $edges = $workflow->nodes->flatMap(fn ($node) => $node->edges ?? []);

        $runner = new Runner(
            workflowId: $workflow->id,
            nodes: $workflow->nodes,
            edges: $edges,
            context: $request->all()
        );

        $runner->startFromNode($webhook->node_id);

        return response()->json([
            'result' => 'ok',
        ]);
    }
}
