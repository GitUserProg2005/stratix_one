<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\N8N\NodeController;
use App\Http\Controllers\N8N\RunController;
use App\Http\Controllers\N8N\WorkflowController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WorkflowCatalogController;
use App\Services\Prometheus\Metrics;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Prometheus\RenderTextFormat;

// Тест WebSocket — счётчик (без БД, кэш + ShouldBroadcastNow)
Route::get('/counter', [CounterController::class, 'index'])->name('counter');
Route::post('/counter/increment', [CounterController::class, 'increment'])->name('counter.increment');

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

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/{dashboard}', [DashboardController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.show');

Route::get('/dashboard/{dashboard}/widgets', [DashboardController::class, 'widgets'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.widgets');

Route::post('/dashboard', [DashboardController::class, 'createDashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.create');

Route::post('/dashboard/{dashboard}/widgets', [DashboardController::class, 'createWidget'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.widgets.create');

Route::post('/dashboard/widgets/{widget}/position', [DashboardController::class, 'updateWidgetPosition'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.widgets.position');

Route::get('/get-metrics/{workflow}', [DashboardController::class, 'getMetrics'])
    ->middleware(['auth', 'verified'])
    ->name('get.metrics');

// Публичный просмотр профиля по id (нужен для Avatar в Sidebar и др.)
Route::get('/profile/{user}', [ProfileController::class, 'profile'])->name('user.profile')->middleware('auth');
Route::get('/profile/{user}/workflow-graph', [ProfileController::class, 'workflowGraph'])->name('profile.workflow-graph')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/media', [ProfileController::class, 'updateMedia'])->name('profile.media');
    Route::post('/profile/background', [ProfileController::class, 'updateBackground'])->name('profile.background');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/ai-chat/rooms', [AiChatController::class, 'getRooms'])->name('ai-chat.rooms');
    Route::post('/ai-chat/rooms', [AiChatController::class, 'createRoom'])->name('ai-chat.rooms.create');
    Route::delete('/ai-chat/rooms/{room}', [AiChatController::class, 'deleteRoom'])->name('ai-chat.rooms.delete');
    Route::get('/ai-chat/rooms/{room}/messages', [AiChatController::class, 'getMessages'])->name('ai-chat.messages');
    Route::post('/ai-chat/rooms/{room}/process-message', [AiChatController::class, 'processMessage'])->name('ai-chat.process-message');
    Route::get('/ai-chat/rooms/{room}/context', [AiChatController::class, 'getContext'])->name('ai-chat.context');
    Route::post('/ai-chat/context', [AiChatController::class, 'createContext'])->name('ai-chat.context.create');

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
    Route::get('/get-rates', [RateController::class, 'index'])->name('get.rates');
    Route::get('/show-node/{nodeId}', [NodeController::class, 'showNode'])->name('show.node');
    Route::post('/create-node', [NodeController::class, 'createNode'])->name('create.node');
    Route::post('/update-node-position/{nodeId}', [NodeController::class, 'updateNodePosition'])->name('update.node.position');
    Route::put('/update-node/{nodeId}', [NodeController::class, 'updateNode'])->name('update.node');
    Route::delete('/delete-node/{nodeId}', [NodeController::class, 'deleteNode'])->name('delete.node');

    Route::get('/get-edges/{workflowId}', [NodeController::class, 'getEdges'])->name('get.edges');
    Route::get('/get-runs/{workflowId}', [RunController::class, 'getRuns'])->name('get.runs');
    Route::post('/create-edge', [NodeController::class, 'createEdge'])->name('create.edge');
    Route::delete('/delete-edge/{edgeId}', [NodeController::class, 'deleteEdge'])->name('delete.edge');
    Route::post('/transform-edge/{edgeId}', [NodeController::class, 'updateEdgeTransform'])
        ->name('edge.transform.update');

    Route::get('/get-node-schemas', [NodeController::class, 'getNodeSchemas'])->name('get.node.schemas');
    Route::get('/webhook-token/{nodeId}', [WebhookController::class, 'tokenByNode'])->name('webhook.token');

    Route::get('/store', [WorkflowCatalogController::class, 'index'])->name('catalog.index');
    Route::get('/store-categories', [WorkflowCatalogController::class, 'categories'])->name('catalog.categories');
    Route::get('/store/publish-status/{workflowId}', [WorkflowCatalogController::class, 'publishStatus'])->name('catalog.publish-status');
    Route::post('/store/deploy', [WorkflowCatalogController::class, 'deploy'])->name('catalog.deploy');
    Route::get('/store/{catalogWorkflow}', [WorkflowCatalogController::class, 'detail'])->name('catalog.detail');
    Route::post('/store/{catalogWorkflow}/install', [WorkflowCatalogController::class, 'install'])->name('catalog.install');
});

// Prometheus metrics (без web middleware — без сессий/CSRF для scrape).
Route::get('/metrics', function (Metrics $metrics) {
    return response($metrics->render(), 200)
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
})->withoutMiddleware(['web']);

Route::post('/webhooks/{token}', [WebhookController::class, 'trigger'])
    ->withoutMiddleware(['web']);


require __DIR__.'/auth.php';
