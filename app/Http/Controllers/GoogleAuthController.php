<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;

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
        $email = $googleUser->getEmail();
        
    
        $response = Http::withToken(config('services.hrmis_api.token'))
            ->post(config('services.hrmis_api.url'), [
                'email' => $email,
            ]);
        
        $apiData = $response->json();
        $hrmisData = $apiData['data'] ?? null;
        //dd($hrmisData);
        $user = $this->findOrCreateUser($googleUser);
        
        if (!empty($hrmisData)) {
            $hrmisService = app(\App\Services\HRMISService::class);
            $hrmisService->syncEmployee($hrmisData, $user->user_id);
        }
        
            Auth::login($user);

             return redirect('/dashboard');
    }

  
    private function findOrCreateUser($googleUser)
    {
     
        $user = User::where('google_id', $googleUser->getId())->first();    

        if ($user) {
            // Update avatar if it changed
            $user->update([
                'email' => $googleUser->getEmail(),
                
            ]);
        } else {
            
            $user = User::create([
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                
                'password' => Hash::make(uniqid()),
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
