<div class="space-y-4">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 text-sm font-medium">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[260px]">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="{{ __('messages.search_contacts') }}"
                   class="w-full sm:w-72 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm">
            <select wire:model.live="groupFilter"
                    class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-sm">
                <option value="">{{ __('messages.all_groups') }}</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('notifications.dispatch')
            <button wire:click="openMessageModal"
                    class="rounded-2xl border border-[#B3261E] text-[#B3261E] dark:text-[#F2B8B5] hover:bg-red-50 dark:hover:bg-red-950/30 font-bold px-4 py-2.5 text-sm transition inline-flex items-center gap-1.5">
                <x-icon name="send" class="w-4 h-4" />
                <span>{{ count($selectedContactIds) > 0 ? 'Message Selected (' . count($selectedContactIds) . ')' : 'Message Contacts' }}</span>
            </button>
            @endcan

            @can('members.add')
            <button wire:click="openCreateModal"
                    class="rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold px-5 py-2.5 text-sm shadow-sm transition">
                + {{ __('messages.add_contact') }}
            </button>
            @endcan
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/60 text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        @can('notifications.dispatch')
                        <th class="px-4 py-3 w-10"></th>
                        @endcan
                        <th class="px-4 py-3">{{ __('messages.full_name') }}</th>
                        <th class="px-4 py-3">{{ __('messages.phone') }}</th>
                        <th class="px-4 py-3">{{ __('messages.email') }}</th>
                        <th class="px-4 py-3">{{ __('messages.select_group') }}</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                    @forelse($contacts as $contact)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                            @can('notifications.dispatch')
                            <td class="px-4 py-3">
                                <input type="checkbox" wire:model.live="selectedContactIds" value="{{ $contact->id }}"
                                       class="rounded border-slate-300 text-[#B3261E] focus:ring-[#B3261E]">
                            </td>
                            @endcan
                            <td class="px-4 py-3 font-semibold">
                                <a href="{{ route('staff.member.show', $contact->id) }}" class="hover:underline text-slate-900 dark:text-slate-100">
                                    {{ $contact->full_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                <a href="tel:{{ $contact->phone }}" class="hover:underline">{{ $contact->phone }}</a>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                @if($contact->email)
                                    <a href="mailto:{{ $contact->email }}" class="hover:underline">{{ $contact->email }}</a>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-700 font-medium">
                                    {{ $contact->group?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @can('notifications.dispatch')
                                    <button wire:click="openMessageModal({{ $contact->id }})"
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 px-2.5 py-1.5 text-xs font-semibold">
                                        <x-icon name="send" class="w-3.5 h-3.5" />
                                        <span>Message</span>
                                    </button>
                                    @endcan
                                    @can('members.edit')
                                    <button wire:click="openEditModal({{ $contact->id }})"
                                            class="inline-flex items-center gap-1 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 px-3 py-1.5 text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">
                                        <x-icon name="edit" class="w-3.5 h-3.5" />
                                        <span>Edit</span>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                No contacts found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit Contact Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4">
            <h3 class="text-lg font-bold">{{ $editMemberId ? __('messages.edit_contact') : __('messages.add_contact') }}</h3>
            <form wire:submit="saveContact" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.select_group') }}</label>
                    <select wire:model="groupId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    @error('groupId') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.full_name') }}</label>
                    <input type="text" wire:model="fullName" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('fullName') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.phone') }}</label>
                    <div class="flex gap-2">
                        <x-country-code-select :countries="$countries" wire:model="phoneCode" widthClass="w-32" />
                        <input type="text" wire:model="phone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    @error('phone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.email_optional') }}</label>
                    <input type="email" wire:model="email" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('email') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Send Message / Marketing Modal --}}
    @if($showMessageModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-2xl bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-lg font-bold">Send Message to Contacts</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Recipients: <strong>{{ count($selectedContactIds) }}</strong> contact(s)</p>
                </div>
                <button type="button" wire:click="$set('showMessageModal', false)" class="text-xs font-semibold text-slate-500">{{ __('messages.cancel') }}</button>
            </div>

            <form wire:submit="sendMessages" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.templates') }}</label>
                        <select wire:model.live="selectedTemplateId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Custom Message --</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Channel</label>
                        <select wire:model.live="messageChannel" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="sms">SMS Only</option>
                            <option value="email">Email Only</option>
                            <option value="both">Both SMS & Email</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.language') }}</label>
                        <select wire:model.live="messageLocale" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="en">English (EN)</option>
                            <option value="sw">Kiswahili (SW)</option>
                        </select>
                    </div>
                </div>

                @if(in_array($messageChannel, ['sms', 'both'], true))
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.sms_template') }}</label>
                    <textarea rows="3" wire:model="messageSmsBody" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm"></textarea>
                    @error('messageSmsBody') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                @endif

                @if(in_array($messageChannel, ['email', 'both'], true))
                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Email Subject</label>
                        <input type="text" wire:model="messageEmailSubject" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('messageEmailSubject') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Email Body</label>
                        <textarea rows="5" wire:model="messageEmailBody" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm"></textarea>
                        @error('messageEmailBody') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                @endif

                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                    Placeholders: <code>{name}</code>, <code>{group}</code>, <code>{company}</code>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showMessageModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-5 py-2 text-xs font-bold">Send Message</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
