<div class="space-y-4">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    @can('groups.create')
    <div class="flex justify-end">
        <button wire:click="openCreateGroupModal"
                class="rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold px-5 py-2.5 text-sm shadow-sm transition">
            + {{ __('messages.register_group') }}
        </button>
    </div>
    @endcan

    <div class="space-y-4">
        @foreach($groupData as $item)
            @php
                $group = $item['group'];
                $isExpanded = in_array($group->id, $expandedGroups, true);
            @endphp
            <div id="group-{{ $group->id }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm scroll-mt-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-start gap-4">
                        <div>
                            <h2 class="text-lg font-bold">{{ $group->name }}</h2>
                            <p class="text-xs {{ $item['overdue_count'] > 0 ? 'text-red-500 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                                {{ $item['members']->count() }} {{ __('messages.members') }} • {{ $item['overdue_count'] }} {{ __('messages.overdue') }}
                            </p>
                        </div>
                        <div class="rounded-xl px-3 py-1.5 {{ $group->loanOfficer ? 'bg-slate-100 dark:bg-slate-700/70 text-slate-700 dark:text-slate-200' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300' }}">
                            <div class="text-[11px] font-semibold">
                                {{ __('messages.loan_officer') }}: {{ $group->loanOfficer?->name ?? __('messages.unassigned') }}
                            </div>
                            @if($group->loanOfficer?->staffProfile?->phone)
                                <div class="text-[11px] text-slate-500 dark:text-slate-300 mt-0.5 flex items-center gap-1">
                                    <x-icon name="phone" class="w-3 h-3" />
                                    <a href="tel:{{ $group->loanOfficer->staffProfile->phone }}" class="hover:underline">{{ $group->loanOfficer->staffProfile->phone }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @can('groups.assign_officer')
                        <button wire:click="openAssignOfficer({{ $group->id }})"
                                class="rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">
                            {{ __('messages.assign_officer') }}
                        </button>
                        @endcan
                        @can('members.add')
                        <button wire:click="openAddMember({{ $group->id }})"
                                class="rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">
                            + {{ __('messages.add_member') }}
                        </button>
                        @endcan
                        <button wire:click="toggleGroup({{ $group->id }})"
                                class="rounded-xl bg-slate-100 dark:bg-slate-700 p-2">
                            @if($isExpanded)
                                <x-icon name="chevron-up" class="w-4 h-4" />
                            @else
                                <x-icon name="chevron-down" class="w-4 h-4" />
                            @endif
                        </button>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs">
                    <div>
                        <div class="text-slate-400">{{ __('messages.disbursed') }}</div>
                        <div class="font-bold">TZS {{ number_format($item['disbursed'], 0) }}</div>
                    </div>
                    <div>
                        <div class="text-slate-400">{{ __('messages.outstanding') }}</div>
                        <div class="font-bold">TZS {{ number_format($item['outstanding'], 0) }}</div>
                    </div>
                    <div>
                        <div class="text-slate-400">{{ __('messages.penalties') }}</div>
                        <div class="font-bold text-[#B3261E] dark:text-[#F2B8B5]">TZS {{ number_format($group->penalty_balance, 0) }}</div>
                    </div>
                </div>

                @if($isExpanded)
                    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700/70">
                        @foreach($item['members'] as $mRow)
                            @php
                                $m = $mRow['member'];
                                $metrics = $mRow['metrics'];
                            @endphp
                            <div class="py-3 flex items-center justify-between gap-3">
                                <a href="{{ route('staff.member.show', $m->id) }}" class="flex-1 min-w-0 hover:underline flex items-center gap-3">
                                    @if($m->photo_path)
                                        <img src="{{ Storage::disk('public')->url($m->photo_path) }}" alt="{{ $m->full_name }}" class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shrink-0">
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-sm">{{ $m->full_name }}</span>
                                            @if($metrics && $metrics['is_overdue'])
                                                <span class="px-2 py-0.5 rounded-md bg-red-100 text-red-700 text-[10px] font-bold">{{ __('messages.overdue') }}</span>
                                            @elseif($mRow['active_loan'])
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 text-[10px] font-bold">{{ __('messages.active') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $m->phone }}
                                            @if($m->email) • {{ $m->email }} @endif
                                            @if($m->gender) • {{ __('messages.' . $m->gender) }} @endif
                                            @if($m->age !== null) • {{ $m->age }} {{ __('messages.years') }} @endif
                                            @if($m->id_number) • {{ $m->id_type ?? 'ID' }}: {{ $m->id_number }} @endif
                                            @if($m->referees->isNotEmpty()) • {{ __('messages.referee') }}: {{ $m->referees->first()->full_name }} ({{ $m->referees->first()->phone }}) @endif
                                            @if($m->collaterals->isNotEmpty()) • {{ __('messages.collaterals') }}: {{ $m->collaterals->first()->item_name }}@if($m->collaterals->count() > 1) (+{{ $m->collaterals->count() - 1 }})@endif @endif
                                            @if($metrics)
                                                • Bal: TZS {{ number_format($metrics['total_remaining_balance'], 0) }}
                                                @if($metrics['next_payment_due_date']) • Due: {{ $metrics['next_payment_due_date']->format('d M Y') }} @endif
                                            @endif
                                        </div>
                                    </div>
                                </a>
                                @can('members.edit')
                                <button wire:click="openEditMember({{ $m->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 px-3 py-1.5 text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">
                                    <x-icon name="edit" class="w-3.5 h-3.5" />
                                    <span>{{ __('messages.edit_member') }}</span>
                                </button>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Register Group Modal --}}
    @if($showCreateGroupModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">{{ __('messages.register_group') }}</h3>
            @if($errorMessage)
                <div class="text-xs text-red-600 font-medium">{{ $errorMessage }}</div>
            @endif
            <form wire:submit="createGroup" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.group_name') }}</label>
                    <input type="text" wire:model="newGroupName" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('newGroupName') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                @if($canAssignOfficer)
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.loan_officer') }}</label>
                    <select wire:model="newGroupLoanOfficerId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        <option value="">-- {{ __('messages.select_loan_officer') }} --</option>
                        @foreach($loanOfficers as $officer)
                            <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif($isCurrentUserLoanOfficer)
                <div class="rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs">
                    <span class="font-semibold">{{ __('messages.loan_officer') }}:</span> {{ auth()->user()->name }} ({{ __('messages.auto_assigned_to_you') }})
                </div>
                @endif
                <div class="space-y-2">
                    @foreach($newMemberRows as $idx => $row)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 p-3 space-y-2 border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between items-center text-xs font-bold">
                                <span>Member #{{ $idx + 1 }}</span>
                                @if(count($newMemberRows) > 1)
                                    <button type="button" wire:click="removeMemberRow({{ $idx }})" class="text-red-500">Remove</button>
                                @endif
                            </div>
                            <input type="text" wire:model="newMemberRows.{{ $idx }}.full_name" placeholder="{{ __('messages.full_name') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            <div class="flex gap-1.5">
                                <x-country-code-select :countries="$countries" wire:model="newMemberRows.{{ $idx }}.phone_code" widthClass="w-28" />
                                <input type="text" wire:model="newMemberRows.{{ $idx }}.phone" placeholder="712100001" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <input type="email" wire:model="newMemberRows.{{ $idx }}.email" placeholder="{{ __('messages.email_optional') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <select wire:model="newMemberRows.{{ $idx }}.gender" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs">
                                    <option value="">-- {{ __('messages.select_gender') }} --</option>
                                    @foreach($genders as $g)
                                        <option value="{{ $g }}">{{ __('messages.' . $g) }}</option>
                                    @endforeach
                                </select>
                                <input type="date" wire:model="newMemberRows.{{ $idx }}.date_of_birth" max="{{ now()->subDay()->format('Y-m-d') }}" title="{{ __('messages.date_of_birth') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <select wire:model="newMemberRows.{{ $idx }}.id_type" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs">
                                    <option value="">-- {{ __('messages.select_id_type') }} --</option>
                                    @foreach($idTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                <input type="text" wire:model="newMemberRows.{{ $idx }}.id_number" placeholder="{{ __('messages.id_number') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.profile_image') }}</label>
                                    <input type="file" wire:model="newMemberRows.{{ $idx }}.photo" accept="image/*" class="w-full text-[11px] file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-200 dark:file:bg-slate-700">
                                    @error("newMemberRows.{$idx}.photo") <div class="text-[11px] text-red-600 mt-0.5">{{ $message }}</div> @enderror
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-500 dark:text-slate-400 mb-1">{{ __('messages.id_image') }}</label>
                                    <input type="file" wire:model="newMemberRows.{{ $idx }}.id_image" accept="image/*" class="w-full text-[11px] file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-slate-200 dark:file:bg-slate-700">
                                    @error("newMemberRows.{$idx}.id_image") <div class="text-[11px] text-red-600 mt-0.5">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ __('messages.referees') }} (min 1, max 2)</span>
                                    @if(count($row['referees'] ?? []) < 2)
                                        <button type="button" wire:click="addNewMemberReferee({{ $idx }})" class="text-[11px] font-semibold text-[#B3261E] dark:text-[#F2B8B5]">+ {{ __('messages.add_referee') }}</button>
                                    @endif
                                </div>
                                @foreach(($row['referees'] ?? []) as $rIdx => $ref)
                                    <div class="rounded-lg bg-white dark:bg-slate-800 p-2 border border-slate-200 dark:border-slate-700 space-y-1.5">
                                        <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500">
                                            <span>{{ __('messages.referee') }} #{{ $rIdx + 1 }}</span>
                                            @if(count($row['referees'] ?? []) > 1)
                                                <button type="button" wire:click="removeNewMemberReferee({{ $idx }}, {{ $rIdx }})" class="text-red-500">Remove</button>
                                            @endif
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            <input type="text" wire:model="newMemberRows.{{ $idx }}.referees.{{ $rIdx }}.full_name" placeholder="{{ __('messages.referee_name') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1 text-xs">
                                            <input type="text" wire:model="newMemberRows.{{ $idx }}.referees.{{ $rIdx }}.relationship" placeholder="{{ __('messages.relationship') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1 text-xs">
                                        </div>
                                        <div class="flex gap-1.5">
                                            <x-country-code-select :countries="$countries" wire:model="newMemberRows.{{ $idx }}.referees.{{ $rIdx }}.phone_code" widthClass="w-28" />
                                            <input type="text" wire:model="newMemberRows.{{ $idx }}.referees.{{ $rIdx }}.phone" placeholder="712100099" class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1 text-xs">
                                        </div>
                                        @error("newMemberRows.{$idx}.referees.{$rIdx}.full_name") <div class="text-[10px] text-red-600">{{ $message }}</div> @enderror
                                        @error("newMemberRows.{$idx}.referees.{$rIdx}.phone") <div class="text-[10px] text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                @endforeach
                            </div>
                            <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ __('messages.collaterals') }}</span>
                                    <button type="button" wire:click="addNewMemberCollateral({{ $idx }})" class="text-[11px] font-semibold text-[#B3261E] dark:text-[#F2B8B5]">+ {{ __('messages.add_collateral') }}</button>
                                </div>
                                @foreach(($row['collaterals'] ?? []) as $cIdx => $col)
                                    <div class="rounded-lg bg-white dark:bg-slate-800 p-2 border border-slate-200 dark:border-slate-700 space-y-1.5">
                                        <div class="flex justify-between items-center text-[10px] font-semibold text-slate-500">
                                            <span>{{ __('messages.collaterals') }} #{{ $cIdx + 1 }}</span>
                                            <button type="button" wire:click="removeNewMemberCollateral({{ $idx }}, {{ $cIdx }})" class="text-red-500">Remove</button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            <input type="text" wire:model="newMemberRows.{{ $idx }}.collaterals.{{ $cIdx }}.item_name" placeholder="{{ __('messages.collateral_item') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1 text-xs">
                                            <input type="number" step="0.01" wire:model="newMemberRows.{{ $idx }}.collaterals.{{ $cIdx }}.estimated_value" placeholder="{{ __('messages.estimated_value') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1 text-xs">
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            <input type="text" wire:model="newMemberRows.{{ $idx }}.collaterals.{{ $cIdx }}.serial_number" placeholder="{{ __('messages.serial_number') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1 text-xs">
                                            <input type="file" wire:model="newMemberRows.{{ $idx }}.collaterals.{{ $cIdx }}.image" accept="image/*" class="w-full text-[11px] file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-slate-200 dark:file:bg-slate-700">
                                        </div>
                                        @error("newMemberRows.{$idx}.collaterals.{$cIdx}.item_name") <div class="text-[10px] text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="addMemberRow" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 py-2 text-xs font-semibold">
                    + {{ __('messages.add_member') }} Row
                </button>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showCreateGroupModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Add Member Modal --}}
    @if($addMemberGroupId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">{{ __('messages.add_member') }}</h3>
            <form wire:submit="saveAddMember" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.full_name') }}</label>
                    <input type="text" wire:model="addMemberName" placeholder="{{ __('messages.full_name') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.phone') }}</label>
                    <div class="flex gap-2">
                        <x-country-code-select :countries="$countries" wire:model="addMemberPhoneCode" widthClass="w-32" />
                        <input type="text" wire:model="addMemberPhone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    @error('addMemberPhone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.email_optional') }}</label>
                    <input type="email" wire:model="addMemberEmail" placeholder="{{ __('messages.email_optional') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.gender') }}</label>
                        <select wire:model="addMemberGender" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            <option value="">-- {{ __('messages.select_gender') }} --</option>
                            @foreach($genders as $g)
                                <option value="{{ $g }}">{{ __('messages.' . $g) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.date_of_birth') }}</label>
                        <input type="date" wire:model="addMemberDob" max="{{ now()->subDay()->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('addMemberDob') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.id_type') }}</label>
                        <select wire:model="addMemberIdType" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            <option value="">-- {{ __('messages.select_id_type') }} --</option>
                            @foreach($idTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.id_number') }}</label>
                        <input type="text" wire:model="addMemberIdNumber" placeholder="{{ __('messages.id_number') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('addMemberIdNumber') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.profile_image') }}</label>
                        <input type="file" wire:model="addMemberPhoto" accept="image/*" class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-700">
                        @error('addMemberPhoto') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.id_image') }}</label>
                        <input type="file" wire:model="addMemberIdImage" accept="image/*" class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-700">
                        @error('addMemberIdImage') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold">{{ __('messages.referees') }} (min 1, max 2)</span>
                        @if(count($addMemberReferees) < 2)
                            <button type="button" wire:click="addAddMemberReferee" class="text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">+ {{ __('messages.add_referee') }}</button>
                        @endif
                    </div>
                    @error('addMemberReferees') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                    @foreach($addMemberReferees as $rIdx => $ref)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 p-3 border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex justify-between items-center text-[11px] font-semibold text-slate-500">
                                <span>{{ __('messages.referee') }} #{{ $rIdx + 1 }}</span>
                                @if(count($addMemberReferees) > 1)
                                    <button type="button" wire:click="removeAddMemberReferee({{ $rIdx }})" class="text-red-500">Remove</button>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <input type="text" wire:model="addMemberReferees.{{ $rIdx }}.full_name" placeholder="{{ __('messages.referee_name') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                                <input type="text" wire:model="addMemberReferees.{{ $rIdx }}.relationship" placeholder="{{ __('messages.relationship') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <div class="flex gap-2">
                                <x-country-code-select :countries="$countries" wire:model="addMemberReferees.{{ $rIdx }}.phone_code" widthClass="w-28" />
                                <input type="text" wire:model="addMemberReferees.{{ $rIdx }}.phone" placeholder="712100099" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            @error("addMemberReferees.{$rIdx}.full_name") <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                            @error("addMemberReferees.{$rIdx}.phone") <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold">{{ __('messages.collaterals') }}</span>
                        <button type="button" wire:click="addAddMemberCollateral" class="text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">+ {{ __('messages.add_collateral') }}</button>
                    </div>
                    @foreach($addMemberCollaterals as $cIdx => $col)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 p-3 border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex justify-between items-center text-[11px] font-semibold text-slate-500">
                                <span>{{ __('messages.collaterals') }} #{{ $cIdx + 1 }}</span>
                                <button type="button" wire:click="removeAddMemberCollateral({{ $cIdx }})" class="text-red-500">Remove</button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <input type="text" wire:model="addMemberCollaterals.{{ $cIdx }}.item_name" placeholder="{{ __('messages.collateral_item') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                                <input type="number" step="0.01" wire:model="addMemberCollaterals.{{ $cIdx }}.estimated_value" placeholder="{{ __('messages.estimated_value') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <input type="text" wire:model="addMemberCollaterals.{{ $cIdx }}.serial_number" placeholder="{{ __('messages.serial_number') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            <input type="file" wire:model="addMemberCollaterals.{{ $cIdx }}.image" accept="image/*" class="w-full text-xs file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-700">
                            @error("addMemberCollaterals.{$cIdx}.item_name") <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('addMemberGroupId', null)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.add_member') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Edit Member Modal --}}
    @if($editMemberId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">{{ __('messages.edit_member') }}</h3>
            <form wire:submit="saveEditMember" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.full_name') }}</label>
                    <input type="text" wire:model="editMemberName" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.phone') }}</label>
                    <div class="flex gap-2">
                        <x-country-code-select :countries="$countries" wire:model="editMemberPhoneCode" widthClass="w-32" />
                        <input type="text" wire:model="editMemberPhone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    @error('editMemberPhone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.email_optional') }}</label>
                    <input type="email" wire:model="editMemberEmail" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.gender') }}</label>
                        <select wire:model="editMemberGender" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            <option value="">-- {{ __('messages.select_gender') }} --</option>
                            @foreach($genders as $g)
                                <option value="{{ $g }}">{{ __('messages.' . $g) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.date_of_birth') }}</label>
                        <input type="date" wire:model="editMemberDob" max="{{ now()->subDay()->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('editMemberDob') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.id_type') }}</label>
                        <select wire:model="editMemberIdType" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            <option value="">-- {{ __('messages.select_id_type') }} --</option>
                            @foreach($idTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.id_number') }}</label>
                        <input type="text" wire:model="editMemberIdNumber" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('editMemberIdNumber') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.profile_image') }}</label>
                        @if($editMemberExistingPhoto)
                            <div class="mb-2">
                                <a href="{{ Storage::disk('public')->url($editMemberExistingPhoto) }}" target="_blank" class="inline-block">
                                    <img src="{{ Storage::disk('public')->url($editMemberExistingPhoto) }}" alt="Member Profile" class="h-20 w-20 rounded-full border border-slate-200 dark:border-slate-700 object-cover">
                                </a>
                            </div>
                        @endif
                        <input type="file" wire:model="editMemberPhoto" accept="image/*" class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-700">
                        @error('editMemberPhoto') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.id_image') }}</label>
                        @if($editMemberExistingIdImage)
                            <div class="mb-2">
                                <a href="{{ Storage::disk('public')->url($editMemberExistingIdImage) }}" target="_blank" class="inline-block">
                                    <img src="{{ Storage::disk('public')->url($editMemberExistingIdImage) }}" alt="Member ID" class="h-20 rounded-lg border border-slate-200 dark:border-slate-700 object-cover">
                                </a>
                            </div>
                        @endif
                        <input type="file" wire:model="editMemberIdImage" accept="image/*" class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-700">
                        @error('editMemberIdImage') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold">{{ __('messages.referees') }} (min 1, max 2)</span>
                        @if(count($editMemberReferees) < 2)
                            <button type="button" wire:click="addEditMemberReferee" class="text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">+ {{ __('messages.add_referee') }}</button>
                        @endif
                    </div>
                    @error('editMemberReferees') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                    @foreach($editMemberReferees as $rIdx => $ref)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 p-3 border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex justify-between items-center text-[11px] font-semibold text-slate-500">
                                <span>{{ __('messages.referee') }} #{{ $rIdx + 1 }}</span>
                                @if(count($editMemberReferees) > 1)
                                    <button type="button" wire:click="removeEditMemberReferee({{ $rIdx }})" class="text-red-500">Remove</button>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <input type="text" wire:model="editMemberReferees.{{ $rIdx }}.full_name" placeholder="{{ __('messages.referee_name') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                                <input type="text" wire:model="editMemberReferees.{{ $rIdx }}.relationship" placeholder="{{ __('messages.relationship') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <div class="flex gap-2">
                                <x-country-code-select :countries="$countries" wire:model="editMemberReferees.{{ $rIdx }}.phone_code" widthClass="w-28" />
                                <input type="text" wire:model="editMemberReferees.{{ $rIdx }}.phone" placeholder="712100099" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            @error("editMemberReferees.{$rIdx}.full_name") <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                            @error("editMemberReferees.{$rIdx}.phone") <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold">{{ __('messages.collaterals') }}</span>
                        <button type="button" wire:click="addEditMemberCollateral" class="text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">+ {{ __('messages.add_collateral') }}</button>
                    </div>
                    @foreach($editMemberCollaterals as $cIdx => $col)
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900 p-3 border border-slate-200 dark:border-slate-700 space-y-2">
                            <div class="flex justify-between items-center text-[11px] font-semibold text-slate-500">
                                <span>{{ __('messages.collaterals') }} #{{ $cIdx + 1 }}</span>
                                <button type="button" wire:click="removeEditMemberCollateral({{ $cIdx }})" class="text-red-500">Remove</button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <input type="text" wire:model="editMemberCollaterals.{{ $cIdx }}.item_name" placeholder="{{ __('messages.collateral_item') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                                <input type="number" step="0.01" wire:model="editMemberCollaterals.{{ $cIdx }}.estimated_value" placeholder="{{ __('messages.estimated_value') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            </div>
                            <input type="text" wire:model="editMemberCollaterals.{{ $cIdx }}.serial_number" placeholder="{{ __('messages.serial_number') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-1.5 text-xs">
                            @if(! empty($col['existing_image']))
                                <a href="{{ Storage::disk('public')->url($col['existing_image']) }}" target="_blank" class="inline-block">
                                    <img src="{{ Storage::disk('public')->url($col['existing_image']) }}" alt="Collateral" class="h-14 rounded-lg border border-slate-200 dark:border-slate-700 object-cover">
                                </a>
                            @endif
                            <input type="file" wire:model="editMemberCollaterals.{{ $cIdx }}.image" accept="image/*" class="w-full text-xs file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-100 dark:file:bg-slate-700">
                            @error("editMemberCollaterals.{$cIdx}.item_name") <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('editMemberId', null)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Assign Loan Officer Modal --}}
    @if($assignOfficerGroupId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ __('messages.assign_officer') }}</h3>
            @if($errorMessage)
                <div class="text-xs text-red-600 font-medium">{{ $errorMessage }}</div>
            @endif
            <form wire:submit="saveAssignOfficer" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.loan_officer') }}</label>
                    <select wire:model="assignOfficerUserId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        <option value="">-- {{ __('messages.unassigned') }} --</option>
                        @foreach($loanOfficers as $officer)
                            <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('assignOfficerGroupId', null)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
