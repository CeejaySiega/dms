@php
use Illuminate\Support\Facades\Route;
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{url('/dashboard')}}" class="app-brand-link">
            <span class="app-brand-logo demo">@include('_partials.macros')</span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">{{config('variables.templateName')}}</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="icon-base bx bx-chevron-left icon-sm d-flex align-items-center justify-content-center"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>
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
        $activeClass = null;
        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName === $menu->slug) {
            $activeClass = 'active';
        } elseif (isset($menu->submenu)) {
            if (gettype($menu->slug) === 'array') {
                foreach($menu->slug as $slug){
                    if (str_contains($currentRouteName,$slug) && strpos($currentRouteName,$slug) === 0) {
                        $activeClass = 'active open';
                    }
                }
            } else {
                if (str_contains($currentRouteName,$menu->slug) && strpos($currentRouteName,$menu->slug) === 0) {
                    $activeClass = 'active open';
                }
            }
        }
        @endphp

        <li class="menu-item {{ $activeClass }}">
            <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" 
               class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
               @if (isset($menu->target)) target="_blank" @endif>
                @php
                $badge = null;
                if (isset($menu->badge) && is_array($menu->badge) && count($menu->badge) >= 2) {
                    $badge = $menu->badge;
                } elseif (isset($inboxCount) && $inboxCount > 0 && isset($menu->slug) && is_array($menu->slug) && in_array('documents.incoming', $menu->slug, true)) {
                    $badge = ['danger', (string) $inboxCount];
                }elseif (isset($inboxCount) && $inboxCount > 0 && isset($menu->slug) && is_array($menu->slug) && in_array('documents.receive', $menu->slug, true)) {
                    $badge = ['danger', (string) $inboxCount];
                }
                @endphp
                @isset($menu->icon)
                <span class="menu-icon-wrapper">
                    <i class="{{ $menu->icon }}"></i>
                    @if ($badge)
                    <span class="menu-icon-badge bg-{{ $badge[0] }}">{{ $badge[1] }}</span>
                    @endif
                </span>
                @endisset
                <div>{{ __($menu->name) }}</div>
                @if ($badge && !isset($menu->icon))
                <div class="badge rounded-pill bg-{{ $badge[0] }} text-uppercase ms-auto">{{ $badge[1] }}</div>
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
