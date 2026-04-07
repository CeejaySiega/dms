@php
use Illuminate\Support\Facades\Route;

$currentRole = strtolower((string) optional(auth()->user()->employee)->role);
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ url('/dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">@include('_partials.macros')</span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('variables.templateName') }}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large">
            <i class="icon-base bx bx-chevron-left"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @foreach ($menuData[0]->menu as $menu)

            @if (isset($menu->menuHeader))
                @if (!isset($menu->visible) || $menu->visible)
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
                    </li>
                @endif
            @else
                @if (!isset($menu->visible) || $menu->visible)

                    @php
                        $shouldHideForRole = isset($menu->slug)
                            && is_string($menu->slug)
                            && $menu->slug === 'users.index'
                            && !in_array($currentRole, ['admin', 'superadmin']);
                    @endphp

                    @if($shouldHideForRole)
                        @continue
                    @endif

                    @php
                        $activeClass = null;
                        $currentRouteName = Route::currentRouteName();

                        if ($currentRouteName === $menu->slug) {
                            $activeClass = 'active';
                        } elseif (isset($menu->submenu)) {
                            if (gettype($menu->slug) === 'array') {
                                foreach ($menu->slug as $slug) {
                                    if (str_contains($currentRouteName, $slug) && strpos($currentRouteName, $slug) === 0) {
                                        $activeClass = 'active open';
                                    }
                                }
                            } else {
                                if (str_contains($currentRouteName, $menu->slug) && strpos($currentRouteName, $menu->slug) === 0) {
                                    $activeClass = 'active open';
                                }
                            }
                        }

                        // Resolve badge
                        $badge = null;
                        if (isset($menu->badge) && is_array($menu->badge) && count($menu->badge) >= 2) {
                            $badge = $menu->badge;
                        } elseif (
                            isset($inboxCount) && $inboxCount > 0 &&
                            isset($menu->slug) && is_array($menu->slug) &&
                            (in_array('documents.incoming', $menu->slug, true) || in_array('documents.receive', $menu->slug, true))
                        ) {
                            $badge = ['danger', (string) $inboxCount];
                        }
                    @endphp

                    <li class="menu-item {{ $activeClass }}">
                        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
                           class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                           @if (isset($menu->target)) target="_blank" @endif>

                            @isset($menu->icon)
                                <i class="{{ $menu->icon }}"></i>
                            @endisset

                            <div data-i18n="{{ $menu->name }}">{{ __($menu->name) }}</div>

                            @if ($badge)
                                <div class="badge text-bg-{{ $badge[0] }} rounded-pill ms-auto">{{ $badge[1] }}</div>
                            @endif
                        </a>

                        @isset($menu->submenu)
                            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
                        @endisset
                    </li>

                @endif
            @endif

        @endforeach
    </ul>
</aside>