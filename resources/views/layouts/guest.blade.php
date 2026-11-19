<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: localStorage.getItem('noda_dark') === null ? true : localStorage.getItem('noda_dark') === 'true' }" :class="{ 'dark': dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.app_name') }} — {{ __('messages.tagline') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 antialiased transition-colors">
    <div class="fixed top-4 right-4 z-50 flex items-center gap-2">
        <div class="flex rounded-xl bg-white/90 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 p-1 shadow-sm text-xs font-semibold">
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
                class="rounded-xl bg-white/90 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs font-semibold shadow-sm flex items-center gap-1.5">
            <span x-show="!dark" class="flex items-center gap-1.5"><x-icon name="moon" class="w-4 h-4" /> {{ __('messages.theme_dark') }}</span>
            <span x-show="dark" class="flex items-center gap-1.5"><x-icon name="sun" class="w-4 h-4" /> {{ __('messages.theme_light') }}</span>
        </button>
    </div>

    {{ $slot }}
    @livewireScripts
</body>
</html>
