@props(['title' => null])
@php
    $user = auth()->user();
    $menu = \App\Support\Navigation::for($user);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | ' : '' }}{{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen" x-data="{ sidebar: false }">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:bg-white focus:px-3 focus:py-2">Skip to main content</a>

    {{-- Sidebar --}}
    <aside class="no-print fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-900 text-brand-100 transition-transform lg:translate-x-0"
        :class="sidebar ? 'translate-x-0' : '-translate-x-full'" aria-label="Main menu">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 border-b border-brand-800 px-5 py-4">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
            <span class="leading-tight">
                <span class="block text-[15px] font-semibold text-white">{{ $systemName }}</span>
                <span class="block text-[12px] text-brand-300">{{ $countryName }}</span>
            </span>
        </a>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @foreach ($menu as $section)
                <p class="mb-1 mt-4 px-2 text-[11px] font-semibold uppercase tracking-wider text-brand-400 first:mt-0">{{ $section['heading'] }}</p>
                <ul>
                    @foreach ($section['items'] as $item)
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif
                                class="flex items-center gap-3 border-l-2 px-2.5 py-2 text-sm transition-colors {{ $isActive ? 'border-accent-500 bg-brand-800 font-medium text-white' : 'border-transparent text-brand-100 hover:bg-brand-800/60 hover:text-white' }}">
                                <x-icon :name="$item['icon']" class="h-[18px] w-[18px] shrink-0" />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="border-t border-brand-800 px-5 py-3 text-[12px] text-brand-300">
            {{ $issuingAuthority }}
        </div>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-brand-800 px-3 py-2">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 px-2.5 py-2 text-sm text-brand-100 transition-colors hover:bg-brand-800/60 hover:text-white">
                <x-icon name="logout" class="h-[18px] w-[18px] shrink-0" />
                Sign out
            </button>
        </form>
    </aside>
    <div class="fixed inset-0 z-30 bg-ink/40 lg:hidden" x-show="sidebar" x-cloak @click="sidebar = false"></div>

    <div class="lg:pl-64">
        {{-- Top bar --}}
        <header class="no-print sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-line bg-white px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button type="button" class="btn-secondary btn-sm lg:hidden" @click="sidebar = true" aria-label="Open menu">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>
                <div class="hidden sm:block">
                    <p class="text-sm font-medium text-ink">{{ $user->facility?->name ?? ($user->isRole(\App\Enums\RoleName::Patient) ? 'Patient portal' : 'National administration') }}</p>
                    <p class="text-[12px] text-muted">{{ now()->format('l, j F Y') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Notification bell --}}
                <div class="relative" x-data="notificationBell('{{ route('notifications.unread') }}', {{ config('health_passport.notification_poll_seconds') }})" @click.outside="open = false">
                    <button type="button" class="relative flex h-10 w-10 items-center justify-center border border-line text-ink hover:border-brand-600" @click="open = !open" aria-label="Notifications">
                        <x-icon name="bell" class="h-5 w-5" />
                        <span x-show="count > 0" x-cloak x-text="count > 9 ? '9+' : count"
                            class="absolute -right-1.5 -top-1.5 min-w-[20px] bg-red-700 px-1 text-center text-[11px] font-semibold leading-5 text-white"></span>
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-80 border border-line bg-white shadow-lg">
                        <div class="flex items-center justify-between border-b border-line px-4 py-2.5">
                            <p class="text-sm font-semibold">Notifications</p>
                            <a href="{{ route('notifications.index') }}" class="link text-[13px]">View all</a>
                        </div>
                        <template x-if="items.length === 0">
                            <p class="px-4 py-6 text-center text-sm text-muted">You have no unread notifications.</p>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url" class="block border-b border-line px-4 py-3 last:border-b-0 hover:bg-brand-50">
                                <p class="text-sm font-medium text-ink" x-text="item.title"></p>
                                <p class="mt-0.5 line-clamp-2 text-[13px] text-muted" x-text="item.message"></p>
                                <p class="mt-1 text-[12px] text-muted" x-text="item.created"></p>
                            </a>
                        </template>
                        <button type="button" x-show="canAskPermission" x-cloak @click="askPermission()" class="w-full border-t border-line px-4 py-2.5 text-left text-[13px] text-brand-700 hover:bg-brand-50">
                            Turn on desktop alerts
                        </button>
                    </div>
                </div>

                {{-- Account menu --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="flex items-center gap-2.5 border border-line py-1 pl-1 pr-2.5 hover:border-brand-600" @click="open = !open" aria-label="Account menu">
                        <span class="flex h-8 w-8 items-center justify-center bg-brand-700 text-[13px] font-semibold text-white">{{ $user->initials() }}</span>
                        <span class="hidden text-left leading-tight md:block">
                            <span class="block text-sm font-medium">{{ $user->name }}</span>
                            <span class="block text-[12px] text-muted">{{ $user->roleLabel() }}</span>
                        </span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-muted" />
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 mt-2 w-52 border border-line bg-white shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-brand-50"><x-icon name="user" class="h-4 w-4" /> My profile</a>
                        <a href="{{ route('password.change') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-brand-50"><x-icon name="key" class="h-4 w-4" /> Change password</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-red-700 hover:bg-red-50"><x-icon name="logout" class="h-4 w-4" /> Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main id="main" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>
</html>
