<?php

namespace App\Services\N8N\Handles;

use App\Mail\EmailReport as EmailReportMailable;
use Illuminate\Support\Facades\Mail;
use App\Services\N8N\BaseNode;

class EmailReport extends BaseNode
{
    public function handle(): string
    {
        $email = $this->getConfig('email') ?? null;

        if (!$email) {
            throw new \RuntimeException('Email not set in node config');
        }

        Mail::to($email)->queue(new EmailReportMailable($this->input));

        return 'Email успешно отправлен на '.$email;
    }
}
