<?php

namespace App\Services\N8N;

use Illuminate\Support\Facades\Http;


class CallbackRequest
{
    public function send(
        string $url,
        string $secret,
        int $workflowId,
        string $runId,
        array $data,
    ): array {
        $url = trim($url);

        if ($url === '' || $secret === '') {
            throw new \RuntimeException('callback_url and signing_secret are required');
        }

        $this->guardUrl($url);

        // Собираем тело callback
        $payload = [
            'run_id' => $runId,
            'workflow_id' => $workflowId,
            'status' => 'completed',
            'data' => $data,
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

        // Отправляем POST с подписью
        $response = Http::timeout(15)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Signature' => $signature,
                'X-Timestamp' => $timestamp,
                'X-Run-Id' => $runId,
                'X-Idempotency-Key' => $runId,
            ])
            ->withBody($body, 'application/json')
            ->post($url);

        if ($response->failed()) {
            throw new \RuntimeException('Callback failed: HTTP '.$response->status());
        }

        return [
            'status_code' => $response->status(),
            'body' => $response->body(),
        ];
    }

    private function guardUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('Callback URL must use http or https');
        }

        if (app()->environment('production') && $scheme !== 'https') {
            throw new \RuntimeException('Callback URL must use https');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            throw new \RuntimeException('Callback URL host is not allowed');
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local')) {
            throw new \RuntimeException('Callback URL host is not allowed');
        }

        // Блокируем приватные IP после DNS-резолва (anti-SSRF)
        $ips = [];

        foreach (dns_get_record($host, DNS_A + DNS_AAAA) ?: [] as $record) {
            if (! empty($record['ip'])) {
                $ips[] = $record['ip'];
            }

            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            $resolved = gethostbyname($host);

            if ($resolved === $host) {
                throw new \RuntimeException('Callback URL host could not be resolved');
            }

            $ips[] = $resolved;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new \RuntimeException('Callback URL resolves to a private or reserved IP');
            }
        }
    }
}
