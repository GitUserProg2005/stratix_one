<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\IndexController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ReelsController;

// Reels
Route::get('/reels', [ReelsController::class, 'index'])->name('reels.index');
Route::get('/get-more-reels', [ReelsController::class, 'getReels'])->name('reels.get');
Route::get('/snippets/search', [ReelsController::class, 'search'])
    ->name('snippets.search');
Route::post('/snippet/{snippetId}/stop', [ReelsController::class, 'stop'])
    ->name('snippets.stop')->middleware('auth');

Route::middleware(['auth'])->group(function() {
    Route::post('/snippets/{snippet}/like', [ReelsController::class, 'likeToggle'])
         ->name('snippets.like');
    Route::get('/snippets/{snippet}/comments', [ReelsController::class, 'getComments'])
         ->name('snippets.comments');
    Route::post('/snippets/{snippet}/comments', [ReelsController::class, 'createComment'])
         ->name('snippets.comments.create');
});

// Tracks
Route::get('/', [TrackController::class, 'index'])->name('tracks.index');
Route::get('/track/{trackId}', [TrackController::class, 'show'])->name('tracks.show');
Route::get('/track-search', [TrackController::class, 'search'])->name('tracks.search');
Route::post('/track/{trackId}/stop', [TrackController::class, 'stop'])->name('tracks.stop')->middleware('auth');

// Playlists
Route::prefix('playlists')->middleware('auth')->group(function () {
    // Получение всех плейлистов пользователя
    Route::get('/my-playlists', [PlaylistController::class, 'getPlaylists'])
        ->name('get.playlists');

    // Создание нового плейлиста
    Route::post('/create', [PlaylistController::class, 'createPlaylist'])
        ->name('create.playlists');

    // Просмотр конкретного плейлиста
    Route::get('/my-playlists/{playlistId}', [PlaylistController::class, 'showPlaylist'])
        ->name('playlist.show');

    // Обновление плейлиста (название и т.д.)
    Route::patch('/my-playlists/{playlistId}', [PlaylistController::class, 'updatePlaylist'])
        ->name('playlist.update');

    // Удаление плейлиста
    Route::delete('/my-playlists/{playlistId}', [PlaylistController::class, 'destroyPlaylist'])
        ->name('playlist.destroy');

    // Добавление трека в плейлист
    Route::post('/add-track-to-playlist/{playlistId}', [PlaylistController::class, 'addTrackToPlaylist'])
        ->name('playlist.add.track');

    // Удаление трека из плейлиста
    Route::delete('/my-playlists/{playlistId}/tracks/{trackId}', [PlaylistController::class, 'removeTrackFromPlaylist'])
        ->name('playlist.remove.track');

    // Новый метод: получение плейлистов без конкретного трека
    Route::get('/without-track', [PlaylistController::class, 'getPlaylistsWithoutTrack'])
        ->name('get.playlists.without.track');
});

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
