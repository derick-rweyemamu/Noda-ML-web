<div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gradient-to-b from-[#FFDAD6]/40 via-slate-50 to-slate-50 dark:from-[#8C1D18]/20 dark:via-slate-900 dark:to-slate-900">
    <div class="w-full max-w-md space-y-6">
        <div class="text-center">
            <img src="{{ asset('images/logo.png') }}" alt="NODA ML Logo" class="w-20 h-20 mx-auto rounded-3xl shadow-md object-cover">
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight">{{ __('messages.app_name') }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('messages.tagline') }}</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-lg border border-slate-200/80 dark:border-slate-700 p-6 space-y-5">
            <div>
                <h2 class="text-xl font-bold">{{ __('messages.welcome_back') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('messages.sign_in_subtitle') }}</p>
            </div>

            <form wire:submit="login" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.email') }}</label>
                    <input type="email" wire:model="email" placeholder="staff@nodaml.co.tz"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3.5 py-2.5 text-sm focus:border-[#B3261E] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.password') }}</label>
                    <input type="password" wire:model="password" placeholder="••••••••"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3.5 py-2.5 text-sm focus:border-[#B3261E] focus:outline-none">
                </div>

                @if($errorMessage)
                    <div class="rounded-xl bg-red-50 dark:bg-red-950/70 text-red-700 dark:text-red-300 px-3 py-2 text-xs font-medium">
                        {{ $errorMessage }}
                    </div>
                @endif

                <button type="submit"
                        class="w-full rounded-xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold py-2.5 text-sm transition shadow-sm">
                    {{ __('messages.login') }} →
                </button>
            </form>
        </div>
    </div>
</div>
