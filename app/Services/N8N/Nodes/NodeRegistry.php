<?php

namespace App\Services\N8N\Nodes;

use App\Enums\NodeType;
use App\Services\N8N\BaseNode;
use ReflectionMethod;


class NodeRegistry {
    public function all(): array {
        return [
            NodeType::WEBHOOK_TRIGGER->value => \App\Services\N8N\Handles\WebhookTrigger::class,
            NodeType::AI_REQUEST->value => \App\Services\N8N\Handles\AiRequest::class,
            NodeType::AI_AGENT_REQUEST->value => \App\Services\N8N\Handles\AiAgentRequest::class,
            NodeType::EMAIL_REPORT->value => \App\Services\N8N\Handles\EmailReport::class,
            NodeType::OSRM->value => \App\Services\N8N\Handles\OSRM::class,
            NodeType::COLLECT_METRICS->value => \App\Services\N8N\Handles\CollectMetrics::class,
            NodeType::UPDATE_METRIC->value => \App\Services\N8N\Handles\UpdateMetric::class,
            NodeType::CONDITION->value => \App\Services\N8N\Handles\Condition::class,
            NodeType::LOG->value => \App\Services\N8N\Handles\LogNode::class,
            NodeType::SCHEDULE->value => \App\Services\N8N\Handles\Schedule::class,
            NodeType::PAGE_LOADER->value => \App\Services\N8N\Handles\PageLoader::class,
            NodeType::GO_WHISPER->value => \App\Services\N8N\Handles\GoWhisper::class,
            NodeType::MISTRAL_TEXT->value => \App\Services\N8N\Handles\MistralText::class,
            NodeType::MISTRAL_PICTURE->value => \App\Services\N8N\Handles\MistralPicture::class,
            NodeType::MISTRAL_OCR->value => \App\Services\N8N\Handles\MistralOcr::class,
            NodeType::POINT_IN_POLYGON->value => \App\Services\N8N\Handles\PointInPolygon::class,
            NodeType::HTTP_CALLBACK->value => \App\Services\N8N\Handles\HttpCallback::class,
            NodeType::TASK_TRIGGER->value => \App\Services\N8N\Handles\TaskTrigger::class,
            NodeType::TO_STRING->value => \App\Services\N8N\Handles\ToString::class,
        ];
    }

    public function resolve(string $type) {
        // 
    }

    public function schemas(): array {
        $result = [];

        foreach ($this->all() as $type => $class) {
            $inputSchemasByMode = is_callable([$class, 'inputSchemasByMode'])
                ? $class::inputSchemasByMode()
                : [];

            $dynamicInputKey = is_subclass_of($class, BaseNode::class)
                ? $class::dynamicInputConfigKey()
                : null;
            $dynamicOutputKey = is_subclass_of($class, BaseNode::class)
                ? $class::dynamicOutputConfigKey()
                : null;

            $result[$type] = [
                'inputSchema' => ! empty($inputSchemasByMode)
                    ? ($inputSchemasByMode['route'] ?? reset($inputSchemasByMode))
                    : (is_callable([$class, 'inputSchema'])
                        ? $class::inputSchema()
                        : BaseNode::inputSchema()),
                'inputSchemaModes' => ! empty($inputSchemasByMode) ? $inputSchemasByMode : null,
                'outputSchema' => is_callable([$class, 'outputSchema'])
                    ? $class::outputSchema()
                    : BaseNode::outputSchema(),
                'dynamic_input' => is_subclass_of($class, BaseNode::class) && (
                    $dynamicInputKey !== null
                    || $this->usesDynamicSchema($class, 'dynamicInputSchema')
                ),
                'dynamic_input_key' => $dynamicInputKey,
                'dynamic_output' => is_subclass_of($class, BaseNode::class) && (
                    $dynamicOutputKey !== null
                    || $this->usesDynamicSchema($class, 'dynamicOutputSchema')
                ),
                'dynamic_output_key' => $dynamicOutputKey,
            ];
        }

        if (!$result) {
            return [];
        }

        return $result;
    }

    private function usesDynamicSchema(string $class, string $method): bool
    {
        if (! is_subclass_of($class, BaseNode::class) || ! method_exists($class, $method)) {
            return false;
        }

        return (new ReflectionMethod($class, $method))
            ->getDeclaringClass()
            ->getName() !== BaseNode::class;
    }
}
