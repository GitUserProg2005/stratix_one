<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

use App\Services\Prometheus\Metrics;
use App\Services\SubscriptionPayment;

use App\Models\Rate;
use App\Models\Purchace;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function rates(Request $request)
    {
        $rates = Rate::orderBy('price')->get()->map(function (Rate $rate) {
            $picture = $rate->picture;
            $pictureUrl = $picture
                ? (filter_var($picture, FILTER_VALIDATE_URL) ? $picture : Storage::disk('s3')->url($picture))
                : null;
            return [
                'id' => $rate->id,
                'title' => $rate->title,
                'picture' => $pictureUrl,
                'features' => $rate->features,
                'price' => $rate->price,
            ];
        });
        return Inertia::render('Payment/Rates', [
            'rates' => $rates,
        ]);
    }

    public function handlePurchase(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'result' => 'error',
                'context' => 'Пользователь не авторизован'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'rate_id' => 'required|exists:rates,id',
            'rate_title' => 'required|string|max:255',
            'rate_price' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => 'error',
                'context' => 'Неверные данные',
                'errors' => $validator->errors()
            ], 422);
        }

        $rateId = $request->input('rate_id');
        $productName = $request->input('rate_title');
        $price = (int) $request->input('rate_price');

        $rate = Rate::find($rateId);
        if (!$rate) {
            return response()->json([
                'result' => 'error',
                'context' => 'Тариф не найден'
            ], 404);
        }

        $paymentLink = SubscriptionPayment::createPaymentLink(
            $productName,
            $price,
            [
                'user_id' => $user->id,
                'rate' => [
                    'rate_id' => $rateId,
                    'title' => $productName,
                ]
            ]
        );

        if ($paymentLink) {
            $request->session()->put('pending_rate_id', $rateId);
            return response()->json(['payment_url' => $paymentLink]);
        } else {
            return response()->json(['error' => 'Ошибка при создании платежа. Проверьте SHOP_ID и SK_YK в .env и логи.'], 500);
        }
    }

    public function callbackPayment(Request $request, Metrics $metrics)
    {
        if ($request->isMethod('post')) {
            $object = $request->input('object', []);
            $event = $request->input('event');

            Log::info('POST callback - Webhook received', [
                'event' => $event,
                'object' => $object,
            ]);

            $paymentId = $object['id'] ?? $request->input('object.id') ?? $request->input('payment_id');

            if (!$paymentId) {
                Log::error('POST callback - Payment ID not found', ['request_data' => $request->all()]);
                return response()->json(['message' => 'Payment ID not found'], 400);
            }

            $purchace = Purchace::where('payment_id', $paymentId)->first();

            if (!$purchace) {
                $metadata = $object['metadata'] ?? [];
                $purchaseIdFromMetadata = $metadata['purchase_id'] ?? null;

                if ($purchaseIdFromMetadata) {
                    $purchace = Purchace::find($purchaseIdFromMetadata);
                    if ($purchace && $paymentId) {
                        $purchace->update(['payment_id' => $paymentId]);
                    }
                }
            }

            if (!$purchace) {
                Log::error('POST callback - Purchase not found', ['payment_id' => $paymentId]);
                return response()->json(['message' => 'Purchase not found'], 404);
            }

            $user = User::find($purchace->user_id);

            if (!$user) {
                Log::error('POST callback - User not found', ['purchase_id' => $purchace->id]);
                return response()->json(['message' => 'Пользователь не найден'], 404);
            }

            $status = $object['status'] ?? $request->input('object.status') ?? $request->input('status');

            if ($status === 'succeeded' || $event === 'payment.succeeded') {
                try {
                    DB::beginTransaction();

                    $purchace->is_paid = true;
                    $purchace->save();

                    $metadata = $object['metadata'] ?? [];
                    $rateId = $metadata['rate_id'] ?? null;

                    if ($rateId) {
                        $user->rate_id = $rateId;
                        $user->save();
                    } else {
                        $rateIdFromSession = $request->session()->get('pending_rate_id');
                        if ($rateIdFromSession) {
                            $user->rate_id = $rateIdFromSession;
                            $user->save();
                        }
                    }

                    DB::commit();

                    $metrics->counter('payment_callback_total', 'Total number of successful payment callbacks')->inc();

                    Log::info('POST callback - Payment processed successfully', [
                        'purchase_id' => $purchace->id,
                        'user_id' => $user->id,
                        'rate_id' => $user->rate_id,
                    ]);

                    return response()->json(['message' => 'Payment processed successfully'], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('POST callback - Error processing payment', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json(['message' => 'Error processing payment'], 500);
                }
            }

            return response()->json(['message' => 'Payment status: ' . ($status ?? 'unknown')], 200);
        }

        $paymentId = $request->input('payment_id') ?? $request->input('orderId');

        if (!$paymentId) {
            $user = auth()->user();
            if ($user) {
                $purchace = Purchace::where('user_id', $user->id)->latest()->first();
                if ($purchace && $purchace->payment_id) {
                    $paymentId = $purchace->payment_id;
                }
            }
        }

        if (!$paymentId) {
            return redirect()->route('rates')->with('error', 'Платеж не найден');
        }

        $purchace = Purchace::where('payment_id', $paymentId)->first();

        if (!$purchace) {
            return redirect()->route('rates')->with('error', 'Платеж не найден');
        }

        if (!$purchace->is_paid) {
            $paymentData = SubscriptionPayment::checkPaymentStatus($paymentId);

            if ($paymentData && isset($paymentData['status']) && $paymentData['status'] === 'succeeded') {
                try {
                    DB::beginTransaction();

                    $purchace->is_paid = true;
                    $purchace->save();

                    $user = User::find($purchace->user_id);
                    if ($user) {
                        $metadata = $paymentData['metadata'] ?? [];
                        $rateId = $metadata['rate_id'] ?? $request->session()->get('pending_rate_id');

                        if ($rateId) {
                            $user->rate_id = $rateId;
                            $user->save();
                        }
                    }

                    DB::commit();

                    $metrics->counter('payment_callback_total', 'Total number of successful payment callbacks')->inc();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('GET callback - Error processing payment', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('payment.status', ['paymentId' => $paymentId]);
    }

    public function paymentStatus(Request $request, string $paymentId)
    {
        $purchace = Purchace::where('payment_id', $paymentId)->firstOrFail();
        if (auth()->id() && (int) auth()->id() !== (int) $purchace->user_id) {
            return redirect()->route('rates')->with('error', 'Доступ запрещён');
        }
        $user = User::find($purchace->user_id);

        if (!$user) {
            return redirect()->route('rates')->with('error', 'Пользователь не найден');
        }

        $rate = $user->rate_id ? Rate::find($user->rate_id) : null;

        return Inertia::render('Payment/PaymentStatus', [
            'user' => $user,
            'purchace' => $purchace,
            'rate' => $rate,
        ]);
    }
}
