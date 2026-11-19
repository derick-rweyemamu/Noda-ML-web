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
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('categoryFilter', 'all')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold {{ $categoryFilter === 'all' ? 'bg-[#B3261E] text-white' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700' }}">
                All
            </button>
            @foreach($categories as $key => $label)
                <button wire:click="$set('categoryFilter', '{{ $key }}')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold {{ $categoryFilter === $key ? 'bg-[#B3261E] text-white' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <div class="inline-flex rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-0.5 text-xs font-bold">
                <button type="button" wire:click="$set('viewLocale', 'en')"
                        class="px-3 py-1 rounded-lg {{ $viewLocale === 'en' ? 'bg-[#B3261E] text-white' : 'text-slate-600 dark:text-slate-300' }}">
                    EN
                </button>
                <button type="button" wire:click="$set('viewLocale', 'sw')"
                        class="px-3 py-1 rounded-lg {{ $viewLocale === 'sw' ? 'bg-[#B3261E] text-white' : 'text-slate-600 dark:text-slate-300' }}">
                    SW
                </button>
            </div>

            @can('templates.manage')
            <button wire:click="openCreateModal"
                    class="rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold px-5 py-2.5 text-sm shadow-sm transition">
                + {{ __('messages.create_template') }}
            </button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($templates as $tpl)
            @php $localized = $tpl->forLocale($viewLocale); @endphp
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm flex flex-col justify-between gap-4">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold">{{ $tpl->name }}</h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                    {{ $categories[$tpl->category] ?? ucfirst($tpl->category) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tpl->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' }}">
                                    {{ $tpl->is_active ? 'Active' : 'Paused' }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 uppercase">
                                    {{ $viewLocale }}
                                </span>
                            </div>
                        </div>

                        @can('templates.manage')
                        <div class="flex items-center gap-1.5">
                            <button wire:click="toggleActive({{ $tpl->id }})"
                                    class="rounded-xl border border-slate-300 dark:border-slate-600 px-2.5 py-1 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700">
                                {{ $tpl->is_active ? 'Pause' : 'Activate' }}
                            </button>
                            <button wire:click="openEditModal({{ $tpl->id }})"
                                    class="inline-flex items-center gap-1 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 px-3 py-1 text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">
                                <x-icon name="edit" class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </button>
                            @if(! in_array($tpl->key, $coreKeys, true))
                                <button wire:click="deleteTemplate({{ $tpl->id }})"
                                        class="rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 px-2.5 py-1 text-xs font-semibold hover:bg-red-50 dark:hover:bg-red-950/40">
                                    Delete
                                </button>
                            @endif
                        </div>
                        @endcan
                    </div>

                    <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 space-y-1.5 text-xs">
                        <div class="font-bold text-slate-600 dark:text-slate-300 uppercase text-[10px]">{{ __('messages.email_template') }}</div>
                        <div><span class="text-slate-400">Subject:</span> <strong>{{ $localized['email_subject'] ?: '-' }}</strong></div>
                        <div class="text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ $localized['email_body'] ?: '-' }}</div>
                    </div>

                    <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 space-y-1 text-xs">
                        <div class="font-bold text-slate-600 dark:text-slate-300 uppercase text-[10px]">{{ __('messages.sms_template') }}</div>
                        <div class="text-slate-600 dark:text-slate-300">{{ $localized['sms_body'] ?: '-' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Create / Edit Template Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-4xl bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[92vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-bold">{{ $editTemplateId ? __('messages.edit_template') : __('messages.create_template') }}</h3>
                <div class="flex items-center gap-3">
                    <div class="inline-flex rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 p-0.5 text-xs font-bold">
                        <button type="button" wire:click="$set('editorLocale', 'en')"
                                class="px-3 py-1 rounded-lg {{ $editorLocale === 'en' ? 'bg-[#B3261E] text-white' : 'text-slate-600 dark:text-slate-300' }}">
                            English (EN)
                        </button>
                        <button type="button" wire:click="$set('editorLocale', 'sw')"
                                class="px-3 py-1 rounded-lg {{ $editorLocale === 'sw' ? 'bg-[#B3261E] text-white' : 'text-slate-600 dark:text-slate-300' }}">
                            Kiswahili (SW)
                        </button>
                    </div>
                    <button type="button" wire:click="$set('showModal', false)" class="text-xs font-semibold text-slate-500 hover:text-slate-800">{{ __('messages.cancel') }}</button>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-700 p-3 text-xs">
                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ __('messages.available_variables') }}:</span>
                <div class="flex flex-wrap gap-1.5 mt-1.5">
                    @foreach($placeholders as $ph)
                        <code class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[#B3261E] dark:text-[#F2B8B5] font-mono text-[11px]">{{ $ph }}</code>
                    @endforeach
                </div>
            </div>

            <form wire:submit="saveTemplate" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.template_name') }}</label>
                        <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('name') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.category') }}</label>
                        <select wire:model="category" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($editorLocale === 'en')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                        <div class="font-bold text-sm">{{ __('messages.email_template') }} (EN)</div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Email Subject (EN)</label>
                            <input type="text" wire:model.live.debounce.250ms="emailSubject" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Email Body (EN)</label>
                            <textarea rows="6" wire:model.live.debounce.250ms="emailBody" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs space-y-1">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Live Email Preview (EN)</div>
                            <div class="font-semibold">{{ $previewEmailSubject ?: '(No subject)' }}</div>
                            <div class="text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ $previewEmailBody ?: '(No body)' }}</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="font-bold text-sm">{{ __('messages.sms_template') }} (EN)</div>
                            @php $smsLen = mb_strlen($smsBody); @endphp
                            <span class="text-[11px] text-slate-500">{{ $smsLen }} chars • {{ max(1, (int) ceil($smsLen / 160)) }} SMS</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">SMS Message (EN)</label>
                            <textarea rows="6" wire:model.live.debounce.250ms="smsBody" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs space-y-1">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Live SMS Preview (EN)</div>
                            <div class="text-slate-600 dark:text-slate-300">{{ $previewSmsBody ?: '(No SMS message)' }}</div>
                        </div>
                    </div>
                </div>
                @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                        <div class="font-bold text-sm">{{ __('messages.email_template') }} (SW)</div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Kichwa cha Barua Pepe (SW)</label>
                            <input type="text" wire:model.live.debounce.250ms="emailSubjectSw" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Ujumbe wa Barua Pepe (SW)</label>
                            <textarea rows="6" wire:model.live.debounce.250ms="emailBodySw" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs space-y-1">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Hakikisho la Barua Pepe (SW)</div>
                            <div class="font-semibold">{{ $previewEmailSubject ?: '(Hakuna kichwa)' }}</div>
                            <div class="text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ $previewEmailBody ?: '(Hakuna ujumbe)' }}</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="font-bold text-sm">{{ __('messages.sms_template') }} (SW)</div>
                            @php $smsSwLen = mb_strlen($smsBodySw); @endphp
                            <span class="text-[11px] text-slate-500">{{ $smsSwLen }} chars • {{ max(1, (int) ceil($smsSwLen / 160)) }} SMS</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Ujumbe wa SMS (SW)</label>
                            <textarea rows="6" wire:model.live.debounce.250ms="smsBodySw" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs space-y-1">
                            <div class="text-[10px] font-bold uppercase text-slate-400">Hakikisho la SMS (SW)</div>
                            <div class="text-slate-600 dark:text-slate-300">{{ $previewSmsBody ?: '(Hakuna SMS)' }}</div>
                        </div>
                    </div>
                </div>
                @endif

                <label class="inline-flex items-center gap-2 text-xs font-semibold">
                    <input type="checkbox" wire:model="isActive" class="rounded border-slate-300 text-[#B3261E] focus:ring-[#B3261E]">
                    <span>Active template</span>
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-5 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
