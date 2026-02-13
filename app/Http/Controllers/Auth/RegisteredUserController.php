<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $avatarPath = null;
        
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid() . '.' . $extension;
            $avatarPath = 'wix_avatars/' . $fileName;
            
            try {
                // Загружаем файл в S3 (используем тот же формат, что и в других местах проекта)
                $uploaded = Storage::disk('s3')->put($avatarPath, file_get_contents($file->getRealPath()), 'public');
                
                if ($uploaded) {
                    // Проверяем, что файл действительно существует
                    $exists = Storage::disk('s3')->exists($avatarPath);
                    $url = Storage::disk('s3')->url($avatarPath);
                    
                    \Log::info('Avatar upload attempt', [
                        'path' => $avatarPath,
                        'uploaded' => $uploaded,
                        'exists' => $exists,
                        'url' => $url,
                    ]);
                } else {
                    \Log::error('Avatar upload failed', ['path' => $avatarPath]);
                    $avatarPath = null;
                }
            } catch (\Exception $e) {
                \Log::error('Avatar upload exception', [
                    'path' => $avatarPath,
                    'error' => $e->getMessage(),
                ]);
                $avatarPath = null;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
