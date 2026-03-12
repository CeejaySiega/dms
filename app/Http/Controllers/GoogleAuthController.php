<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use App\Services\HRMISService;           // ← add this

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();
        $email      = $googleUser->getEmail();

        // ── Call HRMIS API to get this employee's data ──────────────────
        $response = Http::withToken(config('services.hrmis_api.token'))
            ->post(config('services.hrmis_api.url'), [
                'email' => $email,
            ]);

        $apiData   = $response->json();
        $hrmisData = $apiData['data'] ?? null;

        // ── Find or create local user ────────────────────────────────────
        $user         = $this->findOrCreateUser($googleUser);
        $hrmisService = app(HRMISService::class);

        // ── Sync THIS employee's dept/campus (existing logic) ────────────
        if (!empty($hrmisData)) {
            $hrmisService->syncEmployee($hrmisData, $user->user_id);
        }

        // ── Sync ALL departments from HRMIS (cached 24hrs) ──────────────
        // This populates the local departments table for routing/forwarding.
        // Won't hit the API again until the cache expires.
        $hrmisService->syncAllDepartments();

        Auth::login($user);
        //dd($hrmisService->getAllDepartments());

        return redirect('/dashboard');
    }

    private function findOrCreateUser($googleUser)
    {
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            $user->update([
                'email' => $googleUser->getEmail(),
            ]);
        } else {
            $user = User::create([
                'email'     => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password'  => Hash::make(uniqid()),
            ]);
        }

        return $user;
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth-login-basic');
    }
}