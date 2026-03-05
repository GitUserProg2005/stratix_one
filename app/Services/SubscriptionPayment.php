<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Purchace;

class SubscriptionPayment
{
    public static function createPaymentLink(string $productName, int $price, array $data): string
    {
        $createdPurchase = Purchace::create([
            'user_id' => $data['user_id'],
            'payment_link' => null,
            'payment_id' => null,
        ]);

        $paymentData = [
            'amount' => [
                'value' => number_format($price, 2, '.', ''),
                'currency' => 'RUB'
            ],
            'capture' => true,
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => route('callback.payment'),
            ],
            'description' => $productName ?? 'Подписка',
            'metadata' => [
                'rate_title' => $data['rate']['title'] ?? null,
                'rate_id' => $data['rate']['rate_id'] ?? null,
                'purchase_id' => $createdPurchase->id
            ],
        ];

        try {
            $response = Http::withBasicAuth(env('SHOP_ID'), env('SK_YK'))
                ->withHeaders(['Idempotence-Key' => uniqid()])
                ->post('https://api.yookassa.ru/v3/payments', $paymentData);

            if ($response->successful()) {
                $payment = $response->json();
                $url = $payment['confirmation']['confirmation_url'] ?? '';
                $paymentId = $payment['id'] ?? '';

                $createdPurchase->update([
                    'payment_link' => $url,
                    'payment_id' => $paymentId
                ]);

                return $url;
            } else {
                Log::error('Ошибка в payment-response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'purchase_id' => $createdPurchase->id
                ]);
                return '';
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при оплате', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_id' => $createdPurchase->id
            ]);
            return '';
        }
    }

    public static function checkPaymentStatus(string $paymentId): ?array
    {
        try {
            $response = Http::withBasicAuth(env('SHOP_ID'), env('SK_YK'))
                ->get("https://api.yookassa.ru/v3/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Ошибка при проверке статуса платежа', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payment_id' => $paymentId
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при проверке статуса платежа', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $paymentId
            ]);
            return null;
        }
    }
}
