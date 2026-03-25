<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use App\Services\HRMISService;           // ← add this

class GoogleAuthController extends Controller
{
    public function popupLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $googleData = $this->resolveGoogleUserFromAccessToken($validated['access_token']);

        if (!$googleData) {
            return response()->json([
                'message' => 'Google sign-in failed. Please try again.',
            ], 401);
        }

        if (($googleData['aud'] ?? null) !== config('services.google.client_id')) {
            return response()->json([
                'message' => 'Invalid Google client configuration.',
            ], 422);
        }

        if (($googleData['email_verified'] ?? 'false') !== 'true') {
            return response()->json([
                'message' => 'Your Google email is not verified.',
            ], 422);
        }

        $email = $googleData['email'] ?? null;
        $googleId = $googleData['sub'] ?? ($googleData['user_id'] ?? null);

        if (!$email || !$googleId) {
            return response()->json([
                'message' => 'Unable to read Google account details.',
            ], 422);
        }

        $user = $this->findOrCreateUserByGoogle($googleId, $email);

        $this->syncHrmisDataForUser($email, $user);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'redirect' => route('dashboard-analytics'),
        ]);
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();
        $email = $googleUser->getEmail();

        // ── Find or create local user ────────────────────────────────────
        $user = $this->findOrCreateUserByGoogle($googleUser->getId(), $email);

        $this->syncHrmisDataForUser($email, $user);

        Auth::login($user);
        //dd($hrmisService->getAllDepartments());

        return redirect('/dashboard');
    }

    private function findOrCreateUserByGoogle(string $googleId, string $email): User
    {
        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            $user->update([
                'email' => $email,
            ]);
        } else {
            $user = User::create([
                'email'     => $email,
                'google_id' => $googleId,
                'password'  => Hash::make(uniqid()),
            ]);
        }

        return $user;
    }

    private function syncHrmisDataForUser(string $email, User $user): void
    {
        $response = Http::withToken(config('services.hrmis_api.token'))
            ->post(config('services.hrmis_api.url'), [
                'email' => $email,
            ]);

        $apiData = $response->json();
        $hrmisData = $apiData['data'] ?? null;

        $hrmisService = app(HRMISService::class);

        if (!empty($hrmisData)) {
            $hrmisService->syncEmployee($hrmisData, $user->user_id);
        }

        $hrmisService->syncAllDepartments();
    }

    private function resolveGoogleUserFromAccessToken(string $accessToken): ?array
    {
        $tokenInfo = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'access_token' => $accessToken,
        ]);

        if (!$tokenInfo->successful()) {
            return null;
        }

        $tokenData = $tokenInfo->json();

        // tokeninfo for access token may omit email on some clients; fallback to userinfo.
        if (empty($tokenData['email']) || empty($tokenData['sub'])) {
            $userInfo = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($userInfo->successful()) {
                $userData = $userInfo->json();
                $tokenData['email'] = $tokenData['email'] ?? ($userData['email'] ?? null);
                $tokenData['sub'] = $tokenData['sub'] ?? ($userData['sub'] ?? null);
                $tokenData['email_verified'] = $tokenData['email_verified'] ?? (($userData['email_verified'] ?? false) ? 'true' : 'false');
            }
        }

        return $tokenData;
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth-login-basic');
    }
}