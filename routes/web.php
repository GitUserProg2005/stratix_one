<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GamesController;
use App\Http\Controllers\N8N\NodeController;
use App\Http\Controllers\N8N\WorkflowController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\VehicleController;
use App\Services\Prometheus\Metrics;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Prometheus\RenderTextFormat;

// Тест WebSocket — счётчик (без БД, кэш + ShouldBroadcastNow)
Route::get('/counter', [CounterController::class, 'index'])->name('counter');
Route::post('/counter/increment', [CounterController::class, 'increment'])->name('counter.increment');
Route::get('/map', [MapController::class, 'index'])->name('map')->middleware('auth');
Route::get('/search', [MapController::class, 'search'])->name('map.search')->middleware('auth');

// Главная: гостям — лендинг Welcome, авторизованным — дашборд Index
Route::get('/', function () {
    if (auth()->check()) {
        return Inertia::render('Index');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

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

// Явный URL лендинга (те же props, что и у гостя на /)
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
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/vehicles/get', [VehicleController::class, 'getVehicles'])->name('vehicles.get');

    Route::post('/create-order', [OrderController::class, 'create'])->name('order.create');
    Route::post('/order-accept', [OrderController::class, 'accept'])->name('order.accept');
    Route::post('/order-arrived', [OrderController::class, 'arrived'])->name('order.arrived');
    Route::post('/order-in-way', [OrderController::class, 'inWay'])->name('order.in_way');
    Route::post('/order-complete', [OrderController::class, 'complete'])->name('order.complete');
    Route::post('/order-cancel', [OrderController::class, 'cancel'])->name('order.cancel');
    Route::post('/order/get', [OrderController::class, 'getOrder'])->name('get.order');
    Route::post('/driver/update-position', [DriverController::class, 'updatePosition'])->name('update.position');
    Route::get('/tournaments/active', [TournamentController::class, 'active'])->name('tournaments.active');
    Route::post('/tournaments/{tournament}/join', [TournamentController::class, 'join'])->name('tournaments.join');
    Route::post('/tournaments/{tournament}/update-position', [TournamentController::class, 'updatePosition'])->name('tournaments.update-position');
    Route::get('/tournaments/{tournament}/state', [TournamentController::class, 'state'])->name('tournaments.state');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat/prompt-response', [AiChatController::class, 'promptResponse'])->name('ai-chat.prompt-response');

    Route::get('/workflows', function () {
        return Inertia::render('N8N/Workflows');
    })->middleware('verified')->name('workflows.index');

    Route::post('/run-workflow/{workflowId}', [WorkflowController::class, 'runWorkflow'])->name('run.workflow');
    Route::get('/get-workflows', [WorkflowController::class, 'getWorkflows'])->name('get.workflows');
    Route::get('/show-workflow/{workflowId}', [WorkflowController::class, 'showWorkflow'])->name('show.workflow');
    Route::post('/create-workflow', [WorkflowController::class, 'createWorkflow'])->name('create.workflow');
    Route::post('/update-workflow/{workflowId}', [WorkflowController::class, 'updateWorkflow'])->name('update.workflow');
    Route::delete('/delete-workflow/{workflowId}', [WorkflowController::class, 'deleteWorkflow'])->name('delete.workflow');

    Route::get('/get-nodes/{workflowId}', [NodeController::class, 'getNodes'])->name('get.nodes');
    Route::get('/get-node-types', [NodeController::class, 'getNodeTypes'])->name('get.node.types');
    Route::get('/show-node/{nodeId}', [NodeController::class, 'showNode'])->name('show.node');
    Route::post('/create-node', [NodeController::class, 'createNode'])->name('create.node');
    Route::post('/update-node-position/{nodeId}', [NodeController::class, 'updateNodePosition'])->name('update.node.position');
    Route::put('/update-node/{nodeId}', [NodeController::class, 'updateNode'])->name('update.node');
    Route::delete('/delete-node/{nodeId}', [NodeController::class, 'deleteNode'])->name('delete.node');

    Route::get('/get-edges/{workflowId}', [NodeController::class, 'getEdges'])->name('get.edges');
    Route::post('/create-edge', [NodeController::class, 'createEdge'])->name('create.edge');
    Route::delete('/delete-edge/{edgeId}', [NodeController::class, 'deleteEdge'])->name('delete.edge');

    Route::get('/games/puzzle', [GamesController::class, 'index'])->name('puzzle.index');

    # Route::get('map-index', [])
});

// Prometheus metrics (без web middleware — без сессий/CSRF для scrape).
Route::get('/metrics', function (Metrics $metrics) {
    return response($metrics->render(), 200)
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
})->withoutMiddleware(['web']);

require __DIR__.'/auth.php';
