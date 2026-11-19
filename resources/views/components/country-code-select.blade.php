@props([
    'countries',
    'widthClass' => 'w-32',
])

@php
    $items = $countries->map(fn ($c) => [
        'iso2' => strtolower($c->iso2),
        'code' => $c->phone_code,
        'name' => $c->name,
        'flagUrl' => $c->flag_url,
    ])->values();
@endphp

<div x-data="{
        open: false,
        search: '',
        selected: '+255',
        items: {{ Js::from($items) }},
        get current() {
            return this.items.find(i => i.code === this.selected) || this.items[0];
        },
        get filtered() {
            if (!this.search.trim()) return this.items;
            const q = this.search.toLowerCase();
            return this.items.filter(i =>
                i.name.toLowerCase().includes(q) ||
                i.iso2.includes(q) ||
                i.code.includes(q)
            );
        }
    }"
    x-modelable="selected"
    {{ $attributes->merge(['class' => 'relative ' . $widthClass]) }}>
    <button type="button" @click="open = !open"
            class="w-full h-full flex items-center justify-between gap-1.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-2.5 py-2 text-xs">
        <span class="flex items-center gap-1.5 min-w-0">
            <img :src="current?.flagUrl" :alt="current?.iso2" class="w-5 h-3.5 rounded-[2px] object-cover shrink-0">
            <span class="font-semibold truncate" x-text="current?.code"></span>
        </span>
        <x-icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400 shrink-0" />
    </button>

    <div x-show="open" @click.away="open = false" x-transition
         class="absolute left-0 z-50 mt-1 w-64 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl p-2 space-y-1.5"
         style="display: none;">
        <input type="text" x-model="search" placeholder="Search country or +code..."
               class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-2.5 py-1.5 text-xs focus:outline-none focus:border-[#B3261E]">
        <div class="max-h-48 overflow-y-auto space-y-0.5">
            <template x-for="item in filtered" :key="item.iso2">
                <button type="button"
                        @click="selected = item.code; open = false; search = '';"
                        class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs text-left hover:bg-slate-100 dark:hover:bg-slate-800"
                        :class="selected === item.code ? 'bg-[#FFDAD6]/60 dark:bg-[#8C1D18]/40 font-bold' : ''">
                    <img :src="item.flagUrl" :alt="item.iso2" class="w-5 h-3.5 rounded-[2px] object-cover shrink-0">
                    <span class="flex-1 truncate" x-text="item.name"></span>
                    <span class="text-slate-400 font-mono" x-text="item.code"></span>
                </button>
            </template>
        </div>
    </div>
</div>
