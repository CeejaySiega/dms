@php
use Illuminate\Support\Facades\Route;
@endphp

<ul class="menu-sub">
    @foreach ($menu as $submenu)
        @php
            $activeClass = null;
            $currentRouteName = Route::currentRouteName();

            if ($currentRouteName === $submenu->slug) {
                $activeClass = 'active';
            } elseif (isset($submenu->submenu)) {
                if (gettype($submenu->slug) === 'array') {
                    foreach ($submenu->slug as $slug) {
                        if (str_contains($currentRouteName, $slug) && strpos($currentRouteName, $slug) === 0) {
                            $activeClass = 'active open';
                        }
                    }
                } else {
                    if (str_contains($currentRouteName, $submenu->slug) && strpos($currentRouteName, $submenu->slug) === 0) {
                        $activeClass = 'active open';
                    }
                }
            }

            $badge = null;
            if (isset($submenu->badge) && is_array($submenu->badge) && count($submenu->badge) >= 2) {
                $badge = $submenu->badge;
            } elseif (isset($inboxCount) && $inboxCount > 0 && isset($submenu->slug) && $submenu->slug === 'documents.incoming') {
                $badge = ['danger', (string) $inboxCount];
            }
        @endphp

        <li class="menu-item {{ $activeClass }}">
            <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
               class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
               @if (!empty($submenu->target)) target="_blank" @endif>

                @isset($submenu->icon)
                    <i class="{{ $submenu->icon }}"></i>
                @endisset

                <div data-i18n="{{ $submenu->name ?? '' }}">{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>

                @if ($badge)
                    <span class="badge bg-{{ $badge[0] }} ms-auto"
                          style="border-radius:50%;width:1.5rem;height:1.5rem;display:flex;align-items:center;justify-content:center;padding:0;font-size:0.7rem;">{{ $badge[1] }}</span>
                @endif
            </a>

            @isset($submenu->submenu)
                @include('layouts.sections.menu.submenu', ['menu' => $submenu->submenu])
            @endisset
        </li>
    @endforeach
</ul>
