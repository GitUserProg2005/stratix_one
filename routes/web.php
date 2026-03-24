<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\AiChatController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Тест WebSocket — счётчик (без БД, кэш + ShouldBroadcastNow)
Route::get('/counter', [CounterController::class, 'index'])->name('counter');
Route::post('/counter/increment', [CounterController::class, 'increment'])->name('counter.increment');

// Главная — редирект на дашборд для авторизованных, welcome для гостей
Route::get('/', function () {
    return Inertia::render('Index');
})->name('home');

Route::get('/welcome', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// UI kit / components preview (public)
Route::get('/uiux', function () {
    return Inertia::render('UIUX');
})->name('uiux');

// Subscription / Payment (YooKassa)
Route::get('/rates', [PaymentController::class, 'rates'])->name('rates');
Route::post('/payment/purchase', [PaymentController::class, 'handlePurchase'])->name('payment.purchase')->middleware('auth');
Route::get('/payment/callback', [PaymentController::class, 'callbackPayment'])->name('callback.payment');
Route::post('/payment/callback', [PaymentController::class, 'callbackPayment'])->name('callback.payment.post');
Route::get('/payment/status/{paymentId}', [PaymentController::class, 'paymentStatus'])->name('payment.status')->middleware('auth');

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Публичный просмотр профиля по id (нужен для Avatar в Sidebar и др.)
Route::get('/profile/{user}', [ProfileController::class, 'profile'])->name('user.profile')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat/prompt-response', [AiChatController::class, 'promptResponse'])->name('ai-chat.prompt-response');
});

require __DIR__.'/auth.php';
