<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          dark: localStorage.getItem('noda_dark') === null ? true : localStorage.getItem('noda_dark') === 'true',
          sidebarOpen: false,
          sidebarCollapsed: localStorage.getItem('noda_sidebar_collapsed') === 'true'
      }"
      :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.app_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 antialiased transition-colors">
    @php
        $nav = [
            ['route' => 'staff.groups', 'label' => __('messages.groups'), 'icon' => 'groups', 'perm' => 'groups.view'],
            ['route' => 'staff.contacts', 'label' => __('messages.contacts'), 'icon' => 'contacts', 'perm' => 'contacts.view'],
            ['route' => 'staff.loans', 'label' => __('messages.loans'), 'icon' => 'loans', 'perm' => 'loans.view'],
            ['route' => 'staff.payments', 'label' => __('messages.payments'), 'icon' => 'payments', 'perm' => 'payments.view'],
            ['route' => 'staff.notifications', 'label' => __('messages.notifications'), 'icon' => 'notifications', 'perm' => 'notifications.view'],
            ['route' => 'staff.templates', 'label' => __('messages.templates'), 'icon' => 'templates', 'perm' => 'templates.view'],
            ['route' => 'staff.reports', 'label' => __('messages.reports'), 'icon' => 'reports', 'perm' => 'reports.view'],
        ];
        $settingsSub = [
            ['tab' => 'loans_penalties', 'label' => __('messages.loan_limits') . ' & ' . __('messages.penalties')],
            ['tab' => 'reminders', 'label' => __('messages.reminder_schedule')],
            ['tab' => 'mpesa', 'label' => 'Vodacom Lipa Namba'],
            ['tab' => 'twilio', 'label' => __('messages.twilio_title')],
            ['tab' => 'organization', 'label' => 'Branches, Depts & Titles'],
            ['tab' => 'roles', 'label' => 'Roles & Permissions'],
            ['tab' => 'staff', 'label' => __('messages.staff_users')],
        ];
        $onSettings = request()->routeIs('staff.settings');
    @endphp

    <div class="min-h-screen flex">
        @if(auth()->user()->isStaff())
            {{-- Mobile Backdrop --}}
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 z-40 bg-black/50 lg:hidden" style="display: none;"></div>

            {{-- Responsive Sidebar (Fixed/Sticky to Viewport, Independent Scrollbar) --}}
            <aside :class="[
                       sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                       sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'
                   ]"
                   class="fixed inset-y-0 left-0 z-50 h-screen w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col transition-all duration-200 lg:sticky lg:top-0 shrink-0">
                <div class="p-4 flex items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-700 shrink-0">
                    <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'lg:justify-center lg:w-full' : ''">
                        <img src="{{ asset('images/logo.png') }}" alt="NODA ML Logo" class="w-10 h-10 rounded-xl object-cover shadow-sm shrink-0">
                        <div class="min-w-0" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                            <div class="font-extrabold text-base tracking-tight text-[#B3261E] dark:text-[#F2B8B5]">{{ __('messages.app_name') }}</div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ __('messages.staff_admin') }}</div>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 p-3 space-y-1.5 overflow-y-auto">
                    @foreach($nav as $item)
                        @can($item['perm'])
                        @php $active = request()->routeIs($item['route']); @endphp
                        <div x-data="{ showTip: false, tipTop: 0, tipLeft: 0 }"
                             @mouseenter="if (sidebarCollapsed) { const r = $el.getBoundingClientRect(); tipTop = r.top + r.height / 2; tipLeft = r.right + 12; showTip = true; }"
                             @mouseleave="showTip = false">
                            <a href="{{ route($item['route']) }}" @click="sidebarOpen = false"
                               :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ $active ? 'bg-[#B3261E] text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/70' }}">
                                <x-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : ''" class="truncate">{{ $item['label'] }}</span>
                            </a>

                            <div x-show="sidebarCollapsed && showTip"
                                 :style="`top: ${tipTop}px; left: ${tipLeft}px; transform: translateY(-50%);`"
                                 class="hidden lg:block fixed z-[60] pointer-events-none px-2.5 py-1.5 rounded-lg bg-slate-900 dark:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap shadow-lg"
                                 style="display: none;">
                                {{ $item['label'] }}
                            </div>
                        </div>
                        @endcan
                    @endforeach

                    {{-- Collapsible Settings Dropdown --}}
                    @can('settings.view')
                    <div x-data="{ settingsOpen: {{ $onSettings ? 'true' : 'false' }}, showTip: false, tipTop: 0, tipLeft: 0 }"
                         @mouseenter="if (sidebarCollapsed) { const r = $el.getBoundingClientRect(); tipTop = r.top + 18; tipLeft = r.right + 12; showTip = true; }"
                         @mouseleave="showTip = false">
                        <button type="button"
                                @click="if (sidebarCollapsed) { window.location.href = '{{ route('staff.settings') }}'; } else { settingsOpen = !settingsOpen; }"
                                :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'justify-between'"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ $onSettings ? 'bg-[#B3261E] text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/70' }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <x-icon name="settings" class="w-5 h-5 shrink-0" />
                                <span :class="sidebarCollapsed ? 'lg:hidden' : ''" class="truncate">{{ __('messages.settings') }}</span>
                            </span>
                            <span :class="sidebarCollapsed ? 'lg:hidden' : ''">
                                <x-icon name="chevron-down" class="w-4 h-4 transition-transform" ::class="settingsOpen ? 'rotate-180' : ''" />
                            </span>
                        </button>

                        <div x-show="settingsOpen && !sidebarCollapsed" class="mt-1 ml-4 pl-3 border-l border-slate-200 dark:border-slate-700 space-y-1">
                            @foreach($settingsSub as $sub)
                                @php $subActive = $onSettings && request()->query('tab', 'loans_penalties') === $sub['tab']; @endphp
                                <a href="{{ route('staff.settings', ['tab' => $sub['tab']]) }}" @click="sidebarOpen = false"
                                   class="block px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $subActive ? 'bg-[#FFDAD6] text-[#410002] dark:bg-[#8C1D18] dark:text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/60' }}">
                                    {{ $sub['label'] }}
                                </a>
                            @endforeach
                        </div>

                        <div x-show="sidebarCollapsed && showTip"
                             :style="`top: ${tipTop}px; left: ${tipLeft}px; transform: translateY(-50%);`"
                             class="hidden lg:block fixed z-[60] pointer-events-none px-2.5 py-1.5 rounded-lg bg-slate-900 dark:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap shadow-lg"
                             style="display: none;">
                            {{ __('messages.settings') }}
                        </div>
                    </div>
                    @endcan
                </nav>

                <div class="p-3 border-t border-slate-200 dark:border-slate-700 space-y-2 shrink-0" x-data="{ userMenuOpen: false }">
                    <div class="relative" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                        <div class="flex items-center justify-between gap-2 px-2 py-1.5 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700">
                            <div class="min-w-0 text-xs">
                                <div class="font-bold truncate">{{ auth()->user()->name }}</div>
                                <div class="text-slate-400 truncate">{{ auth()->user()->email }}</div>
                            </div>
                            <button type="button" @click="userMenuOpen = !userMenuOpen"
                                    title="User Menu"
                                    class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-300 shrink-0 transition">
                                <x-icon name="chevron-up" class="w-4 h-4 transition-transform" ::class="userMenuOpen ? 'rotate-180' : ''" />
                            </button>
                        </div>

                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false" x-transition
                             class="absolute bottom-full left-0 right-0 mb-2 z-50 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-lg p-1.5 space-y-1 text-xs"
                             style="display: none;">
                            <a href="{{ route('staff.profile') }}" @click="sidebarOpen = false; userMenuOpen = false"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                                <x-icon name="edit" class="w-4 h-4 text-[#B3261E] dark:text-[#F2B8B5]" />
                                <span>View Profile &amp; Password</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-semibold text-[#B3261E] dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/50">
                                    <x-icon name="logout" class="w-4 h-4" />
                                    <span>{{ __('messages.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" :class="sidebarCollapsed ? '' : 'hidden'">
                        @csrf
                        <button type="submit"
                                class="group relative w-full flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-950/60 text-[#B3261E] dark:text-red-300 p-2 text-xs font-bold hover:bg-red-100 transition">
                            <x-icon name="logout" class="w-4 h-4 shrink-0" />
                            <span x-show="sidebarCollapsed"
                                  class="hidden lg:block pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity absolute left-full ml-3 px-2.5 py-1.5 rounded-lg bg-slate-900 dark:bg-slate-700 text-white text-xs font-semibold whitespace-nowrap shadow-lg z-50">
                                {{ __('messages.logout') }}
                            </span>
                        </button>
                    </form>
                </div>
            </aside>
        @endif

        {{-- Main Content Column --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="sticky top-0 z-30 bg-white/95 dark:bg-slate-800/95 backdrop-blur border-b border-slate-200 dark:border-slate-700">
                <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        @if(auth()->user()->isStaff())
                            <button @click="sidebarOpen = !sidebarOpen"
                                    class="lg:hidden rounded-xl border border-slate-200 dark:border-slate-700 p-2">
                                <x-icon name="menu" class="w-5 h-5" />
                            </button>
                            <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('noda_sidebar_collapsed', sidebarCollapsed)"
                                    title="Toggle Sidebar"
                                    class="hidden lg:inline-flex rounded-xl border border-slate-200 dark:border-slate-700 p-2 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                <span x-show="!sidebarCollapsed"><x-icon name="chevron-left" class="w-4 h-4" /></span>
                                <span x-show="sidebarCollapsed"><x-icon name="chevron-right" class="w-4 h-4" /></span>
                            </button>
                        @else
                            <img src="{{ asset('images/logo.png') }}" alt="NODA ML" class="w-9 h-9 rounded-xl object-cover">
                        @endif
                        <div>
                            <div class="font-bold text-sm sm:text-base">
                                @if(!auth()->user()->isStaff())
                                    {{ __('messages.app_name') }} — {{ __('messages.member_portal') }}
                                @else
                                    {{ collect($nav)->first(fn ($i) => request()->routeIs($i['route']))['label'] ?? ($onSettings ? __('messages.settings') : __('messages.app_name')) }}
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ auth()->user()->name }} • {{ auth()->user()->isStaff() ? __('messages.staff_admin') : __('messages.read_only') }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex rounded-xl bg-slate-100 dark:bg-slate-700 p-1 text-xs font-semibold">
                            <a href="{{ route('locale.switch', 'en') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-[#B3261E] text-white' : 'text-slate-600 dark:text-slate-300' }}">
                                <img src="{{ asset('assets/images/flags/gb.png') }}" alt="EN" class="w-4 h-3 rounded-[2px] object-cover">
                                <span>EN</span>
                            </a>
                            <a href="{{ route('locale.switch', 'sw') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg {{ app()->getLocale() === 'sw' ? 'bg-[#B3261E] text-white' : 'text-slate-600 dark:text-slate-300' }}">
                                <img src="{{ asset('assets/images/flags/tz.png') }}" alt="SW" class="w-4 h-3 rounded-[2px] object-cover">
                                <span>SW</span>
                            </a>
                        </div>
                        <button @click="dark = !dark; localStorage.setItem('noda_dark', dark)"
                                class="rounded-xl border border-slate-200 dark:border-slate-700 p-2">
                            <span x-show="!dark"><x-icon name="moon" class="w-4 h-4" /></span>
                            <span x-show="dark"><x-icon name="sun" class="w-4 h-4" /></span>
                        </button>
                        @if(!auth()->user()->isStaff())
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-red-50 dark:bg-red-950/60 text-[#B3261E] dark:text-red-300 px-3 py-1.5 text-xs font-bold">
                                    {{ __('messages.logout') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </header>

            <main class="flex-1 w-full px-4 sm:px-6 py-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
