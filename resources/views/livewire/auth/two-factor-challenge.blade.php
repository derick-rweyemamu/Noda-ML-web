<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-3xl shadow-lg border border-slate-200 dark:border-slate-700 p-6 space-y-5">
        <div class="text-center">
            <img src="{{ asset('images/logo.png') }}" alt="NODA ML" class="w-14 h-14 mx-auto rounded-2xl object-cover">
            <h1 class="mt-3 text-xl font-bold">{{ __('messages.two_factor_title') }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
        </div>

        @if($showRecoveryCodes)
            <div class="space-y-4">
                <div class="rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 p-4">
                    <h2 class="font-bold text-sm text-amber-900 dark:text-amber-200">{{ __('messages.recovery_codes_title') }}</h2>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">{{ __('messages.recovery_codes_desc') }}</p>
                    <div class="grid grid-cols-2 gap-2 mt-3 font-mono text-xs">
                        @foreach($generatedRecoveryCodes as $rc)
                            <div class="bg-white dark:bg-slate-900 rounded-lg px-2.5 py-1.5 text-center border border-amber-200 dark:border-amber-700">{{ $rc }}</div>
                        @endforeach
                    </div>
                </div>
                <button wire:click="continueToDashboard" class="w-full rounded-xl bg-[#B3261E] text-white font-bold py-2.5 text-sm">
                    {{ __('messages.verify_continue') }} →
                </button>
            </div>
        @elseif(! $user->hasTwoFactorEnabled() && $showSetupQr)
            <div class="space-y-4"
                 x-data="{
                     digits: ['', '', '', '', '', ''],
                     init() { this.$nextTick(() => this.$refs['s0']?.focus()); },
                     handleInput(idx, e) {
                         const val = (e.target.value || '').replace(/\D/g, '').slice(-1);
                         this.digits[idx] = val;
                         e.target.value = val;
                         if (val && idx < 5) this.$refs['s' + (idx + 1)]?.focus();
                         this.submitIfComplete();
                     },
                     handleKeydown(idx, e) {
                         if (e.key === 'Backspace' && !this.digits[idx] && idx > 0) {
                             this.$refs['s' + (idx - 1)]?.focus();
                         }
                     },
                     handlePaste(e) {
                         const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                         if (pasted.length === 0) return;
                         e.preventDefault();
                         for (let i = 0; i < 6; i++) this.digits[i] = pasted[i] || '';
                         this.$refs['s' + Math.min(pasted.length, 5)]?.focus();
                         this.submitIfComplete();
                     },
                     submitIfComplete() {
                         const joined = this.digits.join('');
                         if (/^\d{6}$/.test(joined)) {
                             $wire.set('code', joined).then(() => $wire.confirmSetup());
                         }
                     }
                 }">
                <p class="text-xs text-slate-600 dark:text-slate-300">{{ __('messages.two_factor_setup_desc') }}</p>
                <div class="flex flex-col items-center bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($otpAuthUri) }}"
                         alt="2FA QR Code" class="w-40 h-40 rounded-xl bg-white p-2">
                    <div class="mt-3 text-xs text-slate-500">{{ __('messages.secret_key') }}:</div>
                    <code class="mt-1 font-mono text-sm font-bold tracking-widest text-[#B3261E] dark:text-[#F2B8B5]">{{ $setupSecret }}</code>
                </div>
                <form wire:submit="confirmSetup" class="space-y-3">
                    <div class="grid grid-cols-6 gap-2" @paste="handlePaste($event)">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" maxlength="1"
                                   x-ref="s{{ $i }}"
                                   :value="digits[{{ $i }}]"
                                   @input="handleInput({{ $i }}, $event)"
                                   @keydown="handleKeydown({{ $i }}, $event)"
                                   class="h-12 text-center font-mono text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-transparent focus:border-[#B3261E] focus:outline-none">
                        @endfor
                    </div>
                    @if($errorMessage)
                        <div class="text-xs text-red-600 font-medium text-center">{{ $errorMessage }}</div>
                    @endif
                    <button type="submit" class="w-full rounded-xl bg-[#B3261E] text-white font-bold py-2.5 text-sm">
                        {{ __('messages.two_factor_setup_title') }}
                    </button>
                </form>
                <div class="text-center">
                    <button type="button" wire:click="closeSetup" class="text-xs font-semibold text-slate-500 hover:underline">
                        ← Back
                    </button>
                </div>
            </div>
        @else
            <div class="space-y-4"
                 x-data="{
                     useRecovery: false,
                     digits: ['', '', '', '', '', ''],
                     init() { this.$nextTick(() => this.$refs['v0']?.focus()); },
                     handleInput(idx, e) {
                         const val = (e.target.value || '').replace(/\D/g, '').slice(-1);
                         this.digits[idx] = val;
                         e.target.value = val;
                         if (val && idx < 5) this.$refs['v' + (idx + 1)]?.focus();
                         this.submitIfComplete();
                     },
                     handleKeydown(idx, e) {
                         if (e.key === 'Backspace' && !this.digits[idx] && idx > 0) {
                             this.$refs['v' + (idx - 1)]?.focus();
                         }
                     },
                     handlePaste(e) {
                         const pasted = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                         if (pasted.length === 0) return;
                         e.preventDefault();
                         for (let i = 0; i < 6; i++) this.digits[i] = pasted[i] || '';
                         this.$refs['v' + Math.min(pasted.length, 5)]?.focus();
                         this.submitIfComplete();
                     },
                     submitIfComplete() {
                         const joined = this.digits.join('');
                         if (/^\d{6}$/.test(joined)) {
                             $wire.set('code', joined).then(() => $wire.verify());
                         }
                     }
                 }">
                <p class="text-xs text-slate-600 dark:text-slate-300 text-center">{{ __('messages.two_factor_verify_desc') }}</p>

                <form wire:submit="verify" class="space-y-3">
                    <div x-show="!useRecovery" class="grid grid-cols-6 gap-2" @paste="handlePaste($event)">
                        @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" maxlength="1"
                                   x-ref="v{{ $i }}"
                                   :value="digits[{{ $i }}]"
                                   @input="handleInput({{ $i }}, $event)"
                                   @keydown="handleKeydown({{ $i }}, $event)"
                                   class="h-12 text-center font-mono text-xl font-bold rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-transparent focus:border-[#B3261E] focus:outline-none">
                        @endfor
                    </div>

                    <div x-show="useRecovery" style="display: none;">
                        <input type="text" wire:model="code" placeholder="XXXX-XXXX"
                               class="w-full text-center font-mono text-base tracking-widest rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2.5">
                    </div>

                    @if($errorMessage)
                        <div class="text-xs text-red-600 font-medium text-center">{{ $errorMessage }}</div>
                    @endif
                    @if($statusMessage)
                        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 p-3 text-xs break-all">
                            {{ $statusMessage }}
                        </div>
                    @endif
                    <button type="submit" class="w-full rounded-xl bg-[#B3261E] text-white font-bold py-2.5 text-sm">
                        {{ __('messages.verify_continue') }}
                    </button>
                </form>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 text-center space-y-2">
                    @if(! $user->hasTwoFactorEnabled())
                        <button type="button" wire:click="openSetup" class="text-xs font-bold text-[#B3261E] dark:text-[#F2B8B5] hover:underline">
                            Set up 2FA
                        </button>
                    @else
                        <button type="button" @click="useRecovery = !useRecovery" class="block w-full text-xs font-semibold text-slate-500 hover:underline">
                            <span x-show="!useRecovery">Use a one-time recovery code instead</span>
                            <span x-show="useRecovery" style="display: none;">Use 6-digit authenticator code</span>
                        </button>
                        <button type="button" wire:click="sendRecoveryEmail" class="text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5] hover:underline">
                            {{ __('messages.lost_device') }}
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
