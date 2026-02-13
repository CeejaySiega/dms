<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Recipient;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
    $verticalMenuData = json_decode($verticalMenuJson);

    $inboxCount = 0;

    if (Auth::check()) {
      $inboxCount = Recipient::where('user_id', Auth::id())
        ->where(function ($query) {
          $query->whereNull('action')->orWhere('action', 'pending');
        })
        ->count();
      if ($inboxCount > 0) {
        $this->applyBadgeToAllMenu($verticalMenuData->menu, ['danger', (string) $inboxCount]);
      }
    }

    // Debug: Check if badge was applied
    // dd($verticalMenuData->menu);

    // Share all menuData to all the views
    $this->app->make('view')->share('menuData', [$verticalMenuData]);
    $this->app->make('view')->share('inboxCount', $inboxCount);
  }

  /**
   * Recursively apply badge to a menu item by slug.
   */
  private function applyBadgeBySlug(array $menuItems, string $slug, array $badge): void
  {
    foreach ($menuItems as $item) {
      if (isset($item->slug) && $item->slug === $slug) {
        $item->badge = $badge;
      }

      if (isset($item->slug) && is_array($item->slug) && in_array($slug, $item->slug, true)) {
        $item->badge = $badge;
      }

      if (isset($item->submenu) && is_array($item->submenu)) {
        $this->applyBadgeBySlug($item->submenu, $slug, $badge);
      }
    }
  }

  /**
   * Apply badge to all menu items (excluding headers).
   */
  private function applyBadgeToAllMenu($menuItems, array $badge): void
  {
    foreach ($menuItems as $item) {
      // Skip menu headers
      if (isset($item->menuHeader)) {
        continue;
      }

      // Apply badge to all items that don't already have one
      if (!isset($item->badge)) {
        $item->badge = $badge;
      }

      // Recursively apply to submenus
      if (isset($item->submenu) && is_array($item->submenu)) {
        $this->applyBadgeToAllMenu($item->submenu, $badge);
      }
    }
  }
}
