@php
use Illuminate\Support\Facades\Route;
@endphp

<ul class="menu-sub">
    @foreach ($menu as $submenu)
        @if (!isset($submenu->visible) || $submenu->visible)

            @php
                $activeClass = null;
                $currentRouteName = Route::currentRouteName();

                if (isset($submenu->slug)) {
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
                }

                // Resolve sub-item badge
                // Supports two formats:
                //   Indexed  : ['danger', '5']  ← set by MenuServiceProvider at runtime
                //   Key-based: { "color": "danger", "key": "incomingBadgeCount" } ← from JSON
                $subBadge = null;
                if (isset($submenu->badge)) {
                    $subBadgeArr = (array) $submenu->badge;

                    if (array_key_exists(0, $subBadgeArr) && array_key_exists(1, $subBadgeArr)) {
                        // Indexed format set by MenuServiceProvider
                        $subBadgeColor = (string) $subBadgeArr[0];
                        $subBadgeCount = (int) $subBadgeArr[1];
                    } else {
                        // Key-based format from JSON
                        $subBadgeColor = $subBadgeArr['color'] ?? 'primary';
                        $subBadgeKey   = $subBadgeArr['key']   ?? null;
                        $subBadgeCount = $subBadgeKey ? (int) ($$subBadgeKey ?? 0) : 0;
                    }

                    if ($subBadgeCount > 0) {
                        $subBadge = [
                            'color' => $subBadgeColor,
                            'count' => $subBadgeCount > 99 ? '99+' : $subBadgeCount,
                        ];
                    }
                }
            @endphp

            <li class="menu-item {{ $activeClass }}">
                <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0);' }}"
                   class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                   @if (isset($submenu->target)) target="_blank" @endif>

                    @isset($submenu->icon)
                        <i class="{{ $submenu->icon }}"></i>
                    @endisset

                    <div data-i18n="{{ $submenu->name }}">{{ __($submenu->name) }}</div>

                    @if ($subBadge)
                        <div class="badge text-bg-{{ $subBadge['color'] }} rounded-pill ms-auto">
                            {{ $subBadge['count'] }}
                        </div>
                    @endif
                </a>

                @isset($submenu->submenu)
                    @include('layouts.sections.menu.submenu', [
                        'menu'               => $submenu->submenu,
                        'incomingBadgeCount' => $incomingBadgeCount ?? 0,
                        'receivedBadgeCount' => $receivedBadgeCount ?? 0,
                        'sentBadgeCount'     => $sentBadgeCount     ?? 0,
                    ])
                @endisset
            </li>

        @endif
    @endforeach
</ul>