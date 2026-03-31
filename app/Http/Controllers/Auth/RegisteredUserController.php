<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\User;
use App\Enums\UserRole;
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
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:passenger,driver',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];

        if ($request->input('role') === 'driver') {
            $rules['vehicle_picture'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120';
            $rules['vehicle_license_plate'] = 'nullable|string|max:20';
            $rules['vehicle_brand'] = 'nullable|string|max:100';
            $rules['vehicle_model'] = 'nullable|string|max:100';
            $rules['vehicle_color'] = 'nullable|string|max:50';
        }

        $request->validate($rules);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $this->uploadToS3($request->file('avatar'), 'wix_avatars/');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?: null,
            'password' => Hash::make($request->password),
            'avatar' => $avatarPath,
            'role' => $request->role === 'driver' ? UserRole::Driver : UserRole::Passenger,
        ]);

        if ($user->role === UserRole::Driver) {
            $vehiclePicturePath = null;
            if ($request->hasFile('vehicle_picture')) {
                $vehiclePicturePath = $this->uploadToS3($request->file('vehicle_picture'), 'vehicles/');
            }

            Vehicle::create([
                'driver_id' => $user->id,
                'picture' => $vehiclePicturePath,
                'brand' => $request->vehicle_brand ?: null,
                'model' => $request->vehicle_model ?: null,
                'color' => $request->vehicle_color ?: null,
                'license_plate' => $request->vehicle_license_plate ?: null,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('map', absolute: false));
    }

    private function uploadToS3($file, string $prefix): ?string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $extension;
        $path = $prefix . $fileName;

        try {
            $uploaded = Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');
            return $uploaded ? $path : null;
        } catch (\Exception) {
            return null;
        }
    }
}
