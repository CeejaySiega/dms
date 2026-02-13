<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load global helpers
        $this->loadHelpers();
        
        // Register custom route model bindings with encrypted ID support
        $this->registerEncryptedRouteBindings();
    }
    
    /**
     * Register route model bindings that support encrypted IDs
     */
    private function registerEncryptedRouteBindings(): void
    {
        // Document model binding
        \Illuminate\Support\Facades\Route::bind('document', function ($value) {
            $id = decryptId($value);
            return \App\Models\Document::findOrFail($id);
        });
        
        // User model binding
        \Illuminate\Support\Facades\Route::bind('user', function ($value) {
            $id = decryptId($value);
            return \App\Models\User::findOrFail($id);
        });
        
        // Group model binding
        \Illuminate\Support\Facades\Route::bind('group', function ($value) {
            $id = decryptId($value);
            return \App\Models\Group::findOrFail($id);
        });
        
        // Recipient model binding
        \Illuminate\Support\Facades\Route::bind('recipient', function ($value) {
            $id = decryptId($value);
            return \App\Models\Recipient::findOrFail($id);
        });
        
        // ReceivedDocument model binding
        \Illuminate\Support\Facades\Route::bind('receivedDocument', function ($value) {
            $id = decryptId($value);
            return \App\Models\ReceivedDocument::findOrFail($id);
        });
    }

    /**
     * Load helper files
     */
    private function loadHelpers(): void
    {
        $helperPath = app_path('Helpers/helpers.php');
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }
}