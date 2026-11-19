<div class="space-y-4">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            @foreach(['all' => 'All', 'active' => __('messages.active'), 'overdue' => __('messages.overdue'), 'completed' => __('messages.completed')] as $key => $label)
                <button wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold {{ $statusFilter === $key ? 'bg-[#B3261E] text-white' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @can('loans.create')
        <button wire:click="openLoanModal"
                class="rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold px-5 py-2.5 text-sm shadow-sm transition">
            + {{ __('messages.request_loan') }}
        </button>
        @endcan
    </div>

    {{-- Grouped Loan Listing --}}
    <div class="space-y-4">
        @foreach($loansByGroup as $gRow)
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h2 class="text-lg font-bold">{{ $gRow['group']->name }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $gRow['loans']->count() }} loan(s) • {{ __('messages.outstanding') }}: TZS {{ number_format($gRow['total_remaining'], 0) }}
                        </p>
                    </div>
                    @if($gRow['has_unpaid'])
                        @can('payments.record')
                        <button wire:click="openGroupPayment({{ $gRow['group']->id }})"
                                class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">
                            {{ __('messages.record_payment') }}
                        </button>
                        @endcan
                    @endif
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-700/70">
                    @foreach($gRow['loans'] as $lRow)
                        @php
                            $l = $lRow['loan'];
                            $m = $lRow['metrics'];
                        @endphp
                        <div class="py-3 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-sm">{{ $l->member->full_name }} (Loan #{{ $l->id }})</span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $l->status === 'overdue' ? 'bg-red-100 text-red-700' : ($l->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') }}">
                                    {{ strtoupper($l->status) }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-300">
                                {{ __('messages.principal') }}: TZS {{ number_format($l->principal, 0) }} •
                                {{ __('messages.weekly_installment') }}: TZS {{ number_format($l->weekly_amount, 2) }} •
                                {{ __('messages.remaining_balance') }}: TZS {{ number_format($m['total_remaining_balance'], 2) }}
                            </div>
                            @if($m['next_payment_due_date'])
                                <div class="text-xs {{ $m['is_overdue'] ? 'text-red-500 font-semibold' : 'text-slate-400' }}">
                                    {{ __('messages.next_due_date') }}: {{ $m['next_payment_due_date']->format('d M Y') }} •
                                    {{ __('messages.amount_due_week') }}: TZS {{ number_format($m['amount_due_this_week'], 2) }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Request Loan Modal --}}
    @if($showLoanModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">{{ __('messages.request_loan') }}</h3>
            <form wire:submit="createLoan" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.search_group') }}</label>
                    <input type="text" wire:model.live="loanGroupSearch" placeholder="{{ __('messages.search_group') }}"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.select_group') }}</label>
                    <select wire:model.live="loanGroupId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($filteredLoanGroups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.select_member') }}</label>
                    <select wire:model="loanMemberId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($loanGroupMembers as $m)
                            <option value="{{ $m->id }}">{{ $m->full_name }} ({{ $m->phone }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.principal') }}</label>
                        <input type="number" wire:model.live="loanPrincipal" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.term_weeks') }}</label>
                        <input type="number" wire:model.live="loanWeeks" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.rest_days') }}</label>
                        <input type="number" wire:model.live="loanRestDays" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                </div>
                @error('loanPrincipal') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                @error('loanWeeks') <div class="text-xs text-red-600">{{ $message }}</div> @enderror

                @if(count($previewSchedule) > 0)
                    <div class="rounded-2xl bg-[#FFDAD6]/40 dark:bg-[#8C1D18]/20 p-3 text-xs space-y-1">
                        <div class="font-bold">Live Preview ({{ round($settings->load_rate * 100) }}% load) • 1st Return: {{ now()->addDays(max(1, $loanRestDays))->format('d M Y') }}</div>
                        @foreach(array_slice($previewSchedule, 0, 5) as $inst)
                            <div class="flex justify-between">
                                <span>Wk {{ $inst['week_number'] }} ({{ $inst['due_date']->format('d M Y') }})</span>
                                <span class="font-semibold">TZS {{ number_format($inst['amount'], 2) }}</span>
                            </div>
                        @endforeach
                        @if(count($previewSchedule) > 5)
                            @php $last = end($previewSchedule); @endphp
                            <div class="flex justify-between border-t border-slate-300/50 pt-1">
                                <span>Wk {{ $last['week_number'] }} ({{ $last['due_date']->format('d M Y') }})</span>
                                <span class="font-semibold">TZS {{ number_format($last['amount'], 2) }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showLoanModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">Approve & Activate</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Record Payment Modal (Locked to Selected Group + Searchable Member Dropdown) --}}
    @if($paymentGroupId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-data="{ openMemberList: false }">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('messages.record_payment') }}</h3>
            <div class="rounded-xl bg-[#FFDAD6]/50 dark:bg-[#8C1D18]/30 px-3 py-2 text-xs font-bold">
                {{ __('messages.select_group') }}: {{ $paymentGroupName }}
            </div>

            <form wire:submit="savePayment" class="space-y-3">
                <div class="relative">
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.select_member') }}</label>
                    <button type="button" @click="openMemberList = !openMemberList"
                            class="w-full flex items-center justify-between rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm text-left">
                        <span>{{ $paymentMembers->firstWhere('id', $paymentMemberId)?->full_name ?? 'Select member...' }}</span>
                        <span>▼</span>
                    </button>

                    <div x-show="openMemberList" @click.away="openMemberList = false"
                         class="absolute z-20 mt-1 w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl p-2 space-y-1 max-h-56 overflow-y-auto">
                        <input type="text" wire:model.live="paymentMemberSearch" placeholder="{{ __('messages.search_member') }}"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                        @foreach($paymentMembers as $pm)
                            <button type="button" wire:click="selectPaymentMember({{ $pm->id }})" @click="openMemberList = false"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs hover:bg-slate-100 dark:hover:bg-slate-800 {{ $paymentMemberId === $pm->id ? 'bg-[#FFDAD6]/60 font-bold' : '' }}">
                                {{ $pm->full_name }} ({{ $pm->phone }})
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.amount') }}</label>
                    <input type="number" step="0.01" wire:model="paymentAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.week_number') }}</label>
                    <input type="number" wire:model="paymentWeek" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('paymentGroupId', null)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_payment') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
