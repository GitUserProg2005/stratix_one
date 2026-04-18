<?php

namespace App\Services\N8N\Handles;

use App\Mail\EmailReport as EmailReportMailable;
use Illuminate\Support\Facades\Mail;
use App\Services\N8N\BaseNode;

class EmailReport extends BaseNode
{
    public static function inputSchema(): array {
        // return [
        //     'type' => 'group',
        //     'name' => 'root',
        //     'fields' => [
        //         [
        //             'type' => 'field',
        //             'key' => 'report',
        //             'data_type' => 'string'
        //         ]
        //     ]
        // ];

        return self::field('report', 'string', true);
    }

    public static function outputSchema(): array {
        // return [
        //     'type' => 'group',
        //     'name' => 'root',
        //     'fields' => [
        //         [
        //             'type' => 'field',
        //             'key' => 'message',
        //             'data_type' => 'string'
        //         ]
        //     ]
        // ];

        return self::field('message', 'string', true);
    }
    
    public function handle(): array
    {
        $email = $this->getConfig('email') ?? null;

        // Берем данные по ключу и input
        $report = $this->input('report');

        if (!$email) {
            $this->broadcastError($this->workflowId, $this->node->id, 'Email not set in node config');
            // throw new \RuntimeException('Email not set in node config');
        }
        
        // Оправка report-а по email через очередь
        // Mail::to($email)->queue(new EmailReportMailable($report));

        Mail::to($email)->send(new EmailReportMailable($report));

        return $this->success(null, ['message' => 'Email успешно отправлен на '.$email]);
    }
}
