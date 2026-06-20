<?php

namespace App\Services\N8N;

use App\Events\WorkflowStep;

class BroadcastChunks
{
    public static function send(
        int $workflowId,
        string $result,
        int $currentNodeId,
        ?int $nextNodeId,
        int $chunkSize = 1000,
    ): void {
        if (trim($result) === '') {
            broadcast(new WorkflowStep($workflowId, '', $currentNodeId, $nextNodeId));

            return;
        }

        $result = mb_convert_encoding($result, 'UTF-8', 'UTF-8');

        $length = mb_strlen($result, 'UTF-8');
        $pos = 0;

        while ($pos < $length) {
            $chunk = mb_substr($result, $pos, $chunkSize, 'UTF-8');
            $pos += mb_strlen($chunk, 'UTF-8');

            $chunk = preg_replace('/[\x00-\x1F\x7F]/u', '', $chunk);
            $safeChunkJson = json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

            if ($safeChunkJson === false) {
                \Log::error('Ошибка JSON encode чанка', ['error' => json_last_error_msg()]);

                continue;
            }

            $safeChunk = json_decode($safeChunkJson);

            broadcast(new WorkflowStep(
                workflowId: $workflowId,
                result: $safeChunk,
                currentNodeId: $currentNodeId,
                nextProcessingNodeId: $nextNodeId
            ));
        }
    }
}
