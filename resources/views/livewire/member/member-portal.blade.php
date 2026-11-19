<div class="space-y-5">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            @if($member->photo_path)
                <a href="{{ Storage::disk('public')->url($member->photo_path) }}" target="_blank" class="shrink-0">
                    <img src="{{ Storage::disk('public')->url($member->photo_path) }}" alt="{{ $member->full_name }}" class="w-14 h-14 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                </a>
            @endif
            <div>
                <h1 class="text-xl font-bold">{{ $member->full_name }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $member->group?->name }} • {{ $member->phone }}
                    @if($member->gender) • {{ __('messages.' . $member->gender) }} @endif
                    @if($member->age !== null) • {{ $member->age }} {{ __('messages.years') }} @endif
                    @if($member->id_number) • {{ $member->id_type ?? 'ID' }}: {{ $member->id_number }} @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($member->id_image_path)
                <a href="{{ Storage::disk('public')->url($member->id_image_path) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700">
                    <img src="{{ Storage::disk('public')->url($member->id_image_path) }}" alt="{{ $member->full_name }} ID" class="w-6 h-4 rounded object-cover">
                    <span>{{ __('messages.id_image') }}</span>
                </a>
            @endif
            @if($isStaff)
                <a href="{{ route('staff.groups', ['group' => $member->group_id]) }}#group-{{ $member->group_id }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-600 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                    <span>&larr;</span>
                    <span>{{ __('messages.back_to_groups') }}</span>
                </a>
            @else
                <span class="px-3 py-1 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-700">
                    {{ __('messages.read_only') }}
                </span>
            @endif
        </div>
    </div>

    @forelse($activeLoans as $row)
        @php
            $loan = $row['loan'];
            $m = $row['metrics'];
        @endphp
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-bold text-base">Loan #{{ $loan->id }} • {{ $loan->term_weeks }} Weeks</h2>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $m['is_overdue'] ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                        {{ $m['is_overdue'] ? __('messages.overdue') : __('messages.active') }}
                    </span>
                    @if($isStaff && $m['is_overdue'])
                        @can('payments.record')
                        <button type="button" wire:click="openRecordPayment({{ $loan->id }})"
                                class="rounded-xl bg-[#B3261E] hover:bg-[#8C1D18] text-white px-3 py-1.5 text-xs font-bold transition">
                            + {{ __('messages.record_payment') }}
                        </button>
                        @endcan
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div><span class="text-slate-400 block">{{ __('messages.principal') }}</span><strong>TZS {{ number_format($loan->principal, 0) }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.total_repayable') }}</span><strong>TZS {{ number_format($loan->total_repayable, 0) }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.weekly_installment') }}</span><strong>TZS {{ number_format($loan->weekly_amount, 2) }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.amount_paid') }}</span><strong>TZS {{ number_format($m['amount_paid'], 2) }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.remaining_balance') }}</span><strong class="text-[#B3261E] dark:text-[#F2B8B5]">TZS {{ number_format($m['total_remaining_balance'], 2) }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.next_due_date') }}</span><strong>{{ $m['next_payment_due_date']?->format('d M Y') ?? 'Settled' }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.amount_due_week') }}</span><strong>TZS {{ number_format($m['amount_due_this_week'], 2) }}</strong></div>
                <div><span class="text-slate-400 block">{{ __('messages.weeks_remaining') }}</span><strong>{{ $m['weeks_remaining'] }} / {{ $loan->term_weeks }}</strong></div>
            </div>

            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                <div class="h-full {{ $m['is_overdue'] ? 'bg-red-500' : 'bg-[#B3261E]' }}" style="width: {{ round($m['progress'] * 100) }}%"></div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 text-sm text-slate-500">No active loans.</div>
    @endforelse

    <div class="bg-emerald-50/80 dark:bg-emerald-950/30 rounded-2xl border border-emerald-200 dark:border-emerald-800 p-5 space-y-2 text-xs">
        <h3 class="font-bold text-sm text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
            <x-icon name="phone" class="w-4 h-4" />
            <span>{{ __('messages.voda_lipa_title') }}</span>
        </h3>
        <div>{{ __('messages.voda_lipa_number') }}: <strong class="text-base font-mono">{{ $settings->voda_lipa_namba }}</strong></div>
        <div>{{ __('messages.voda_business_name') }}: <strong>{{ $settings->voda_business_name }}</strong></div>
        <div class="text-slate-600 dark:text-slate-300">{{ __('messages.voda_lipa_steps') }}</div>
    </div>

    <div class="bg-amber-50/70 dark:bg-amber-950/30 rounded-2xl border border-amber-200 dark:border-amber-800 p-5 space-y-2 text-xs">
        <h3 class="font-bold text-sm">Penalty Summary (Shared Group Liability)</h3>
        <div>Your Attributable Penalties: <strong>TZS {{ number_format($member->penalty_balance, 0) }}</strong></div>
        <div>Total Group Penalties: <strong>TZS {{ number_format($member->group?->penalty_balance ?? 0, 0) }}</strong></div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-2 text-xs">
        <h3 class="font-bold text-sm">{{ __('messages.referees') }}</h3>
        @forelse($member->referees as $ref)
            <div class="flex flex-wrap items-center justify-between gap-2 py-1.5 border-b last:border-b-0 border-slate-100 dark:border-slate-700/70">
                <div>
                    <span class="font-semibold text-sm">{{ $ref->full_name }}</span>
                    @if($ref->relationship)
                        <span class="text-slate-500 dark:text-slate-400">({{ $ref->relationship }})</span>
                    @endif
                </div>
                <a href="tel:{{ $ref->phone }}" class="font-mono text-[#B3261E] dark:text-[#F2B8B5] hover:underline">{{ $ref->phone }}</a>
            </div>
        @empty
            <div class="text-slate-500 dark:text-slate-400">No referees recorded.</div>
        @endforelse
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-2 text-xs">
        <h3 class="font-bold text-sm">{{ __('messages.collaterals') }}</h3>
        @forelse($member->collaterals as $col)
            <div class="flex flex-wrap items-center justify-between gap-3 py-2 border-b last:border-b-0 border-slate-100 dark:border-slate-700/70">
                <div class="flex items-center gap-3">
                    @if($col->image_path)
                        <a href="{{ Storage::disk('public')->url($col->image_path) }}" target="_blank" class="shrink-0">
                            <img src="{{ Storage::disk('public')->url($col->image_path) }}" alt="{{ $col->item_name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 dark:border-slate-700">
                        </a>
                    @endif
                    <div>
                        <div class="font-semibold text-sm">{{ $col->item_name }}</div>
                        @if($col->serial_number)
                            <div class="text-slate-500 dark:text-slate-400">{{ __('messages.serial_number') }}: {{ $col->serial_number }}</div>
                        @endif
                    </div>
                </div>
                @if($col->estimated_value !== null)
                    <span class="font-bold text-[#B3261E] dark:text-[#F2B8B5]">TZS {{ number_format($col->estimated_value, 0) }}</span>
                @endif
            </div>
        @empty
            <div class="text-slate-500 dark:text-slate-400">No collateral recorded.</div>
        @endforelse
    </div>

    {{-- Record Payment Modal --}}
    @if($paymentLoanId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('messages.record_payment') }} — Loan #{{ $paymentLoanId }}</h3>
            <form wire:submit="savePayment" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.amount_tzs') }}</label>
                    <input type="number" step="0.01" wire:model="paymentAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('paymentAmount') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.week_number') }}</label>
                    <input type="number" min="1" wire:model="paymentWeek" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('paymentWeek') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="$set('paymentLoanId', null)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
