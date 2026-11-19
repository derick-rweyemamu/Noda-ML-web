<div class="space-y-5">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    @php $sp = $user->staffProfile; @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        {{-- Left Column: Profile Details --}}
        <div class="lg:col-span-7 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-base">My Staff Profile</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Role: <strong>{{ $user->getRoleNames()->implode(', ') ?: 'Staff' }}</strong>
                    @if($sp) • Code: <strong class="font-mono">{{ $sp->staff_code }}</strong> @endif
                </p>
            </div>
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $user->hasTwoFactorEnabled() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $user->hasTwoFactorEnabled() ? '2FA Active' : '2FA Not Set' }}
            </span>
        </div>

        @if($sp)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 rounded-xl bg-slate-50 dark:bg-slate-900/60 p-3 text-xs">
                <div><span class="text-slate-400 block">Branch</span><strong>{{ $sp->branch?->name ?? '-' }}</strong></div>
                <div><span class="text-slate-400 block">Department</span><strong>{{ $sp->department?->name ?? '-' }}</strong></div>
                <div><span class="text-slate-400 block">Job Title</span><strong>{{ $sp->jobTitle?->name ?? '-' }}</strong></div>
                <div><span class="text-slate-400 block">Status</span><strong class="uppercase">{{ $sp->status }}</strong></div>
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.full_name') }}</label>
                    <input type="text" wire:model="name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('name') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.email') }}</label>
                    <input type="email" wire:model="email" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    @error('email') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.phone') }}</label>
                    <div class="flex gap-1.5">
                        <x-country-code-select :countries="$countries" wire:model="phoneCode" widthClass="w-28" />
                        <input type="text" wire:model="phone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    @error('phone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">NIDA / National ID</label>
                    <input type="text" wire:model="national_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Residential Address</label>
                    <input type="text" wire:model="address" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">{{ __('messages.language') }}</label>
                    <select wire:model="locale" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                        <option value="en">English (EN)</option>
                        <option value="sw">Kiswahili (SW)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Emergency Contact Name</label>
                    <input type="text" wire:model="emergency_contact_name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Emergency Contact Phone</label>
                    <div class="flex gap-1.5">
                        <x-country-code-select :countries="$countries" wire:model="emergencyContactPhoneCode" widthClass="w-28" />
                        <input type="text" wire:model="emergency_contact_phone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                    </div>
                    @error('emergency_contact_phone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
            <button type="submit" class="rounded-xl bg-[#B3261E] hover:bg-[#8C1D18] text-white px-5 py-2.5 text-xs font-bold">
                {{ __('messages.save_changes') }}
            </button>
        </form>
    </div>

        {{-- Right Column: Change Password & Two-Factor Security --}}
        <div class="lg:col-span-5 space-y-5">
            {{-- Change Password --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
                <h2 class="font-bold text-base">Change Password</h2>
                @if($passwordMessage)
                    <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 px-3 py-2 text-xs font-medium">
                        {{ $passwordMessage }}
                    </div>
                @endif
                <form wire:submit="updatePassword" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Current Password</label>
                        <input type="password" wire:model="current_password" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        @error('current_password') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">New Password</label>
                            <input type="password" wire:model="new_password" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                            @error('new_password') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Confirm New Password</label>
                            <input type="password" wire:model="new_password_confirmation" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button type="submit" class="rounded-xl border border-[#B3261E] text-[#B3261E] dark:text-[#F2B8B5] px-5 py-2.5 text-xs font-bold">
                        Update Password
                    </button>
                </form>
            </div>

            {{-- Two-Factor Security --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-base">{{ __('messages.two_factor_title') }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Re-enroll or change your authenticator device</p>
                </div>
                <button type="button" wire:click="resetTwoFactor" class="rounded-xl bg-slate-100 dark:bg-slate-700 px-4 py-2 text-xs font-bold hover:bg-slate-200">
                    {{ $user->hasTwoFactorEnabled() ? 'Re-enroll 2FA' : 'Set up 2FA' }}
                </button>
            </div>
        </div>
    </div>
</div>
