<div class="space-y-5">
    @if($flashMessage)
        <div
            class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div
            class="rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-200 text-red-700 dark:text-red-300 px-4 py-3 text-sm font-medium">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @can('penalties.sweep')
        <button wire:click="runSweep"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold py-3 text-sm shadow-sm">
            <x-icon name="play" class="w-4 h-4" />
            <span>{{ __('messages.run_penalty_sweep') }}</span>
        </button>
        @endcan
        @can('penalties.apply')
        <button wire:click="openPenaltyModal"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-[#B3261E] text-[#B3261E] dark:text-[#F2B8B5] font-bold py-3 text-sm">
            <x-icon name="gavel" class="w-4 h-4" />
            <span>{{ __('messages.manual_penalty') }}</span>
        </button>
        @endcan
        @can('mpesa.simulate')
        <button wire:click="openMpesaModal"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 text-sm shadow-sm">
            <x-icon name="phone" class="w-4 h-4" />
            <span>{{ __('messages.simulate_mpesa') }}</span>
        </button>
        @endcan
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
        <h2 class="font-bold text-base">{{ __('messages.mpesa_transactions') }} ({{ $mpesaTransactions->count() }})</h2>
        @forelse($mpesaTransactions as $tx)
            <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 flex justify-between items-center text-xs">
                <div>
                    <div class="font-bold">{{ $tx->receipt_number }} • {{ $tx->msisdn }}</div>
                    <div class="text-slate-500">
                        Lipa Namba: {{ $tx->lipa_namba }}
                        @if($tx->member) • {{ $tx->member->full_name }} (Loan #{{ $tx->loan_id }}) @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-bold">TZS {{ number_format($tx->amount, 0) }}</div>
                    <span
                        class="px-2 py-0.5 rounded font-bold uppercase text-[10px] {{ $tx->status === 'reconciled' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $tx->status }}
                    </span>
                </div>
            </div>
        @empty
            <p class="text-xs text-slate-400">No Vodacom Lipa Namba transactions yet.</p>
        @endforelse
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
        <h2 class="font-bold text-base">{{ __('messages.penalties') }} ({{ $penalties->count() }})</h2>
        @foreach($penalties as $pen)
            <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 flex justify-between items-center text-xs">
                <div>
                    <div class="font-bold">{{ $pen->group?->name }} • {{ $pen->member?->full_name }}</div>
                    <div class="text-slate-500">{{ $pen->reason }}</div>
                    <div class="text-slate-400">{{ $pen->applied_at->format('d M Y, H:i') }}</div>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 font-bold">TZS
                    {{ number_format($pen->total_penalty, 0) }}</span>
            </div>
        @endforeach
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
        <h2 class="font-bold text-base">{{ __('messages.audit_log') }}</h2>
        @foreach($auditLogs as $log)
            <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs">
                <div class="flex justify-between font-bold">
                    <span>{{ $log->action }} → {{ $log->target }}</span>
                    <span class="text-slate-400">{{ $log->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="text-slate-600 dark:text-slate-300 mt-0.5">{{ $log->details }}</div>
                <div class="text-slate-400 mt-0.5">Actor: {{ $log->actor_name }} ({{ $log->actor_role }})</div>
            </div>
        @endforeach
    </div>

    @if($showMpesaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
                <h3 class="text-lg font-bold">{{ __('messages.simulate_mpesa') }}</h3>
                <form wire:submit="simulateMpesa" class="space-y-3">
                    <input type="text" wire:model="mpesaReceipt" placeholder="Receipt Number (e.g. VODA123456)"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    <input type="text" wire:model="mpesaPhone" placeholder="Payer Phone (+255712100001)"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    <input type="number" wire:model="mpesaAmount" placeholder="Amount (TZS)"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('showMpesaModal', false)"
                            class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 text-white px-4 py-2 text-xs font-bold">Send
                            Callback</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showPenaltyModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
                <h3 class="text-lg font-bold">{{ __('messages.apply_to_group') }}</h3>
                @if($errorMessage)
                    <div class="rounded-xl bg-red-50 dark:bg-red-950/60 text-red-700 dark:text-red-300 px-3 py-2 text-xs font-medium">
                        {{ $errorMessage }}
                    </div>
                @endif
                <form wire:submit="applyManualPenalty" class="space-y-3">
                    <input type="text" wire:model.live="groupSearch" placeholder="{{ __('messages.search_group') }}"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    <select wire:model.live="penaltyGroupId"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($filteredGroups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="penaltyMemberId"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($groupMembers as $gm)
                            <option value="{{ $gm->id }}">{{ $gm->full_name }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model="penaltyReason" placeholder="{{ __('messages.reason') }}"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('showPenaltyModal', false)"
                            class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                        <button type="submit"
                            class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.apply_to_group') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>