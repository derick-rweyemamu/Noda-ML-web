<div class="space-y-4">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    @can('notifications.dispatch')
    <button wire:click="dispatchDue"
            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold py-3 text-sm shadow-sm">
        <x-icon name="send" class="w-4 h-4" />
        <span>{{ __('messages.dispatch_reminders') }}</span>
    </button>
    @endcan

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
        <h2 class="font-bold text-base">{{ __('messages.notification_log') }} ({{ $reminders->count() }})</h2>
        @foreach($reminders as $rem)
            <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 space-y-1 text-xs">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="font-bold">{{ $rem->member?->full_name }}</span>
                        <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-bold uppercase">{{ $rem->channel }}</span>
                        @if($rem->is_penalty_notice)
                            <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 font-bold">PENALTY</span>
                        @endif
                    </div>
                    <span class="px-2 py-0.5 rounded font-bold uppercase {{ $rem->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($rem->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ $rem->status }}
                    </span>
                </div>
                <div class="text-slate-600 dark:text-slate-300">{{ $rem->message }}</div>
                <div class="text-slate-400">
                    Scheduled: {{ $rem->scheduled_date->format('d M Y, H:i') }}
                    @if($rem->sent_at) • Sent: {{ $rem->sent_at->format('d M Y, H:i') }} @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
