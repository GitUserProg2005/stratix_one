<?php

namespace App\Services\N8N\Handles;

use App\Mail\EmailReport as EmailReportMailable;
use Illuminate\Support\Facades\Mail;

class EmailReport
{
    public static function handleEmailReport($node, string $result): string
    {
        $config = is_string($node->config)
            ? json_decode($node->config, true)
            : $node->config;
        $email = $config['email'] ?? null;

        if (! $email) {
            throw new \RuntimeException('Email not set in node config');
        }

        Mail::to($email)->queue(new EmailReportMailable($result));

        return 'Email успешно отправлен на '.$email;
    }
}
