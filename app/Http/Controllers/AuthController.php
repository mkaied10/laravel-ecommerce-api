<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (!Auth::attempt($validated)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.invalid_credentials')],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return ResponseHelper::success(__('auth.login_success'), [
            'token' => $token,
            'user' => $user->only('name', 'email'),
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('profiles', 'public');
        }

        $user = User::create([
            'name' => $data['name'], 
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'image' => $data['image'] ?? null,
        ]);

        $user->sendEmailVerificationNotification();

        return ResponseHelper::success(__('auth.register_success'), [
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user->only('name', 'email'),
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return ResponseHelper::success(__('auth.logout_success'), [], 200);
        } catch (\Exception $e) {
            return ResponseHelper::error(__('auth.error'), 500, $e->getMessage());
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $imageUrl = $googleUser->getAvatar();
            $imageName = null;
            if ($imageUrl) {
                $imageContent = file_get_contents($imageUrl);
                $imageName = 'profiles/' . Str::random(10) . '.jpg';
                Storage::disk('public')->put($imageName, $imageContent);
            }

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(), 
                    'password' => bcrypt(Str::random(24)),
                    'email_verified_at' => now(),
                    'image' => $imageName,
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return ResponseHelper::success(__('auth.login_success'), [
                'token' => $token,
                'user' => $user->only('name', 'email'),
            ], 200);
        } catch (\Exception $e) {
            return ResponseHelper::error(__('auth.error'), 500, $e->getMessage());
        }
    }

    public function show(Request $request)
    {
        return ResponseHelper::success(__('auth.user_details'), [
            'user' => new UserResource($request->user()),
        ], 200);
    }
}