<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Recipient;
use App\Models\Document;           // ← add for sent count
use App\Models\ReceivedDocument;   // ← add for received count

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

    $inboxCount    = 0;
    $receivedCount = 0;
    $sentCount     = 0;

    if (Auth::check()) {
      $userId = Auth::id();
      $userRole = strtolower((string) optional(Auth::user()->employee)->role);

      // Filter menu items based on roles property
      $verticalMenuData->menu = $this->filterMenuByRoles($verticalMenuData->menu, $userRole);

      // Incoming — your existing logic, untouched
      $inboxCount = Recipient::where('user_id', $userId)
        ->where(function ($query) {
          $query->whereNull('action')->orWhere('action', 'pending');
        })
        ->count();

      // Received — documents already received by this user
      // ⚠️ Adjust column name to match your ReceivedDocument table
      $receivedCount = ReceivedDocument::where('user_id', $userId)
        ->count();

      // Sent — documents created/sent by this user
      // ⚠️ Adjust column name: may be 'sender_id', 'user_id', 'created_by', etc.
      $sentCount = Document::where('created_by', $userId)
        ->count();

      // Parent "Mail" badge = pending inbox count only
      if ($inboxCount > 0) {
        // Badge the parent "Mail" item (its slug is an array)
        $this->applyBadgeBySlug(
          $verticalMenuData->menu,
          'documents.incoming',
          ['danger', $this->formatCount($inboxCount)],
          parentOnly: true
        );

        // Badge the "Incoming" child item
        $this->applyBadgeBySlug(
          $verticalMenuData->menu,
          'documents.incoming',
          ['danger', $this->formatCount($inboxCount)]
        );
      }
    }

    // Share to all views — same as your original
    $this->app->make('view')->share('menuData',      [$verticalMenuData]);
    $this->app->make('view')->share('inboxCount',    $inboxCount);
    $this->app->make('view')->share('receivedCount', $receivedCount);
    $this->app->make('view')->share('sentCount',     $sentCount);
  }

  /**
   * Filter menu items based on roles property.
   * If a menu item has a 'roles' property, only show it if current role is in that array.
   */
  private function filterMenuByRoles(array $menuItems, string $currentRole): array
  {
    $filtered = [];

    foreach ($menuItems as $item) {
      // Skip menu headers
      if (isset($item->menuHeader)) {
        $filtered[] = $item;
        continue;
      }

      // If item has roles property, check if current role is allowed
      if (isset($item->roles) && is_array($item->roles) && count($item->roles) > 0) {
        $allowedRoles = array_map('strtolower', $item->roles);
        if (!in_array($currentRole, $allowedRoles, true)) {
          continue; // Skip this item, user doesn't have permission
        }
      }

      // Recursively filter submenu items
      if (isset($item->submenu) && is_array($item->submenu)) {
        $item->submenu = $this->filterMenuByRoles($item->submenu, $currentRole);
      }

      $filtered[] = $item;
    }

    return $filtered;
  }

  /**
   * Remove menu items recursively by slug.
   */
  private function removeMenuBySlug(array $menuItems, array $blockedSlugs): array
  {
    $filtered = [];

    foreach ($menuItems as $item) {
      if (isset($item->slug) && is_string($item->slug) && in_array($item->slug, $blockedSlugs, true)) {
        continue;
      }

      if (isset($item->submenu) && is_array($item->submenu)) {
        $item->submenu = $this->removeMenuBySlug($item->submenu, $blockedSlugs);
      }

      $filtered[] = $item;
    }

    return $filtered;
  }

  /**
   * Format count: show "99+" if over 99.
   */
  private function formatCount(int $count): string
  {
    return $count > 99 ? '99+' : (string) $count;
  }

  /**
   * Recursively apply badge to a menu item by slug.
   *
   * @param bool $parentOnly  true  → only match parent items (slug is an array)
   *                          false → only match child items  (slug is a string)
   */
  private function applyBadgeBySlug(array $menuItems, string $slug, array $badge, bool $parentOnly = false): void
  {
    foreach ($menuItems as $item) {
      // Exact string slug → child/leaf item
      if (!$parentOnly && isset($item->slug) && is_string($item->slug) && $item->slug === $slug) {
        $item->badge = $badge;
      }

      // Array slug → parent item (e.g. Mail has ["documents.incoming", ...])
      if ($parentOnly && isset($item->slug) && is_array($item->slug) && in_array($slug, $item->slug, true)) {
        $item->badge = $badge;
      }

      // Recurse into submenu (only when looking for child items)
      if (!$parentOnly && isset($item->submenu) && is_array($item->submenu)) {
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
      if (isset($item->menuHeader)) {
        continue;
      }

      if (!isset($item->badge)) {
        $item->badge = $badge;
      }

      if (isset($item->submenu) && is_array($item->submenu)) {
        $this->applyBadgeToAllMenu($item->submenu, $badge);
      }
    }
  }
}