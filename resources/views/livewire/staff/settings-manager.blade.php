<div class="space-y-5">
    @if($flashMessage)
        <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 text-emerald-800 dark:text-emerald-200 px-4 py-3 text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif
    @if($errorMessage)
        <div class="rounded-2xl bg-red-50 dark:bg-red-950/60 border border-red-200 text-red-700 dark:text-red-300 px-4 py-3 text-sm font-medium">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Settings Tabs --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-700 pb-3">
        @php
            $tabs = [
                'loans_penalties' => __('messages.loan_limits') . ' & ' . __('messages.penalties'),
                'reminders' => __('messages.reminder_schedule'),
                'mpesa' => 'Vodacom Lipa Namba',
                'twilio' => __('messages.twilio_title'),
                'organization' => 'Branches, Depts & Titles',
                'roles' => 'Roles & Permissions',
                'staff' => __('messages.staff_users'),
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <button type="button" wire:click="$set('activeTab', '{{ $key }}')"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $activeTab === $key ? 'bg-[#B3261E] text-white shadow-sm' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if(!in_array($activeTab, ['staff', 'organization', 'roles'], true))
        <form wire:submit="saveSettings" class="space-y-5">
            @if($activeTab === 'loans_penalties')
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                    <h2 class="font-bold text-base">{{ __('messages.loan_limits') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Min Loan Principal (TZS)</label>
                            <input type="number" wire:model="minLoanAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Max Loan Principal (TZS)</label>
                            <input type="number" wire:model="maxLoanAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Max Term Weeks</label>
                            <input type="number" wire:model="maxWeeks" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Load / Interest Rate (%)</label>
                            <input type="number" wire:model="loadRatePercent" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                    <h2 class="font-bold text-base">{{ __('messages.penalty_settings') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Penalty Amount Per Member (TZS)</label>
                            <input type="number" wire:model="penaltyAmount" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">{{ __('messages.cutoff_time') }}</label>
                            <input type="text" wire:model="cutoffTime" placeholder="10:30" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Penalty Application Rule</label>
                        <select wire:model="penaltyRule" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            <option value="one_time_per_missed_due_date">One-time per missed due date</option>
                            <option value="per_day_late">Per day late</option>
                            <option value="escalating">Escalating (+25% per consecutive missed week)</option>
                        </select>
                    </div>
                </div>
            @endif

            @if($activeTab === 'reminders')
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                    <h2 class="font-bold text-base">{{ __('messages.reminder_schedule') }}</h2>
                    @foreach($availableOffsets as $offset)
                        @php
                            $label = $offset === 0 ? 'On due date (0 days)' : ($offset === 1 ? '1 day before' : "{$offset} days before");
                            $enabled = in_array($offset, $selectedOffsets, true);
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 py-2 border-b border-slate-100 dark:border-slate-700/60 last:border-0">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input type="checkbox" wire:click="toggleOffset({{ $offset }})" @checked($enabled) class="rounded text-[#B3261E]">
                                <span>{{ $label }}</span>
                            </label>
                            <div class="flex items-center gap-4 text-xs">
                                <label class="flex items-center gap-1.5">
                                    <input type="checkbox" wire:model="smsMap.{{ $offset }}" @disabled(!$enabled)>
                                    <span>SMS</span>
                                </label>
                                <label class="flex items-center gap-1.5">
                                    <input type="checkbox" wire:model="emailMap.{{ $offset }}" @disabled(!$enabled)>
                                    <span>Email</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($activeTab === 'mpesa')
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                    <h2 class="font-bold text-base">{{ __('messages.voda_lipa_title') }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">{{ __('messages.voda_lipa_number') }}</label>
                            <input type="text" wire:model="vodaLipaNamba" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">{{ __('messages.voda_business_name') }}</label>
                            <input type="text" wire:model="vodaBusinessName" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold pt-1">
                        <input type="checkbox" wire:model="mpesaAutoReconcile" class="rounded text-[#B3261E]">
                        <span>Auto-reconcile incoming Vodacom Lipa Namba callbacks to member loans</span>
                    </label>
                </div>
            @endif

            @if($activeTab === 'twilio')
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-base">{{ __('messages.twilio_title') }}</h2>
                        <label class="flex items-center gap-2 text-xs font-semibold">
                            <input type="checkbox" wire:model="twilioEnabled" class="rounded text-[#B3261E]">
                            <span>{{ __('messages.twilio_enable') }}</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">{{ __('messages.twilio_sid') }}</label>
                            <input type="text" wire:model="twilioAccountSid" placeholder="ACxxxxxxxxxxxxxxxx"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">
                                {{ __('messages.twilio_token') }}
                                @if($hasSavedTwilioToken)
                                    <span class="text-emerald-600 font-normal">(Saved)</span>
                                @endif
                            </label>
                            <input type="password" wire:model="twilioAuthToken" placeholder="{{ $hasSavedTwilioToken ? 'Leave blank to keep current' : 'Enter Auth Token' }}"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">{{ __('messages.twilio_from') }}</label>
                            <input type="text" wire:model="twilioFromNumber" placeholder="+15005550006"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm">
                        </div>
                    </div>
                </div>
            @endif

            @can('settings.update')
            <button type="submit" class="w-full rounded-2xl bg-[#B3261E] hover:bg-[#8C1D18] text-white font-bold py-3 text-sm shadow-sm">
                {{ __('messages.save_settings') }}
            </button>
            @endcan
        </form>
    @endif

    {{-- Tab: Organization CRUD (Branches, Departments, Job Titles) --}}
    @if($activeTab === 'organization')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            {{-- Branches CRUD --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                <h2 class="font-bold text-base">Branches ({{ $branches->count() }})</h2>
                <form wire:submit="saveBranch" class="space-y-2">
                    <input type="text" wire:model="branchName" placeholder="Branch Name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" wire:model="branchCode" placeholder="Code (HQ-01)" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                        <input type="text" wire:model="branchLocation" placeholder="Location" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-[#B3261E] text-white py-2 text-xs font-bold">
                        {{ $editBranchId ? 'Update Branch' : '+ Add Branch' }}
                    </button>
                </form>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                    @foreach($branches as $b)
                        <div class="py-2 flex justify-between items-center gap-2">
                            <div>
                                <div class="font-bold">{{ $b->name }} @if($b->code) ({{ $b->code }}) @endif</div>
                                <div class="text-slate-400">{{ $b->location }}</div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="editBranch({{ $b->id }})" class="text-blue-500 font-semibold">Edit</button>
                                <button type="button" wire:click="deleteBranch({{ $b->id }})" class="text-red-500 font-semibold">Delete</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Departments CRUD --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                <h2 class="font-bold text-base">Departments ({{ $departments->count() }})</h2>
                <form wire:submit="saveDepartment" class="space-y-2">
                    <input type="text" wire:model="departmentName" placeholder="Department Name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    <button type="submit" class="w-full rounded-xl bg-[#B3261E] text-white py-2 text-xs font-bold">
                        {{ $editDepartmentId ? 'Update Department' : '+ Add Department' }}
                    </button>
                </form>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                    @foreach($departments as $d)
                        <div class="py-2 flex justify-between items-center">
                            <span class="font-bold">{{ $d->name }}</span>
                            <div class="flex gap-2">
                                <button type="button" wire:click="editDepartment({{ $d->id }})" class="text-blue-500 font-semibold">Edit</button>
                                <button type="button" wire:click="deleteDepartment({{ $d->id }})" class="text-red-500 font-semibold">Delete</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Job Titles CRUD --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
                <h2 class="font-bold text-base">Job Titles ({{ $jobTitles->count() }})</h2>
                <form wire:submit="saveJobTitle" class="space-y-2">
                    <input type="text" wire:model="jobTitleName" placeholder="Job Title Name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    <button type="submit" class="w-full rounded-xl bg-[#B3261E] text-white py-2 text-xs font-bold">
                        {{ $editJobTitleId ? 'Update Job Title' : '+ Add Job Title' }}
                    </button>
                </form>
                <div class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                    @foreach($jobTitles as $jt)
                        <div class="py-2 flex justify-between items-center">
                            <span class="font-bold">{{ $jt->name }}</span>
                            <div class="flex gap-2">
                                <button type="button" wire:click="editJobTitle({{ $jt->id }})" class="text-blue-500 font-semibold">Edit</button>
                                <button type="button" wire:click="deleteJobTitle({{ $jt->id }})" class="text-red-500 font-semibold">Delete</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tab: Roles & Permissions CRUD --}}
    @if($activeTab === 'roles')
        @can('roles.view')
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-base">Roles &amp; Permissions ({{ $roles->count() }})</h2>
                    <p class="text-xs text-slate-500">System roles (Admin, Loan Officer, Cashier, Director, Member) are protected and cannot be deleted.</p>
                </div>
                @can('roles.create')
                <button type="button" wire:click="openCreateRoleModal"
                        class="rounded-xl border border-[#B3261E] text-[#B3261E] dark:text-[#F2B8B5] px-3.5 py-2 text-xs font-bold">
                    + Create Role
                </button>
                @endcan
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                @foreach($roles as $role)
                    @php $protected = in_array($role->name, \App\Livewire\Staff\SettingsManager::PROTECTED_ROLES, true); @endphp
                    <div class="py-3 flex flex-wrap justify-between items-start gap-3">
                        <div class="space-y-1 flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <strong class="text-sm">{{ $role->name }}</strong>
                                @if($protected)
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-[10px] font-bold uppercase">Protected System Role</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @forelse($role->permissions as $perm)
                                    <span class="px-2 py-0.5 rounded-md bg-[#FFDAD6]/50 dark:bg-[#8C1D18]/40 text-[10px] font-semibold">{{ $perm->name }}</span>
                                @empty
                                    <span class="text-slate-400">No permissions assigned</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @can('roles.edit')
                            <button type="button" wire:click="openEditRoleModal({{ $role->id }})"
                                    class="rounded-lg bg-slate-100 dark:bg-slate-700 px-3 py-1.5 text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">
                                Edit Permissions
                            </button>
                            @endcan
                            @if(!$protected)
                                @can('roles.delete')
                                <button type="button" wire:click="deleteRole({{ $role->id }})"
                                        class="rounded-lg bg-red-50 dark:bg-red-950/60 px-3 py-1.5 text-xs font-semibold text-red-600">
                                    Delete
                                </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endcan
    @endif

    @if($showRoleModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-2xl bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">{{ $editRoleId ? 'Edit Role & Permissions' : 'Create Custom Role' }}</h3>
            <form wire:submit="saveRole" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold mb-1">Role Name</label>
                    <input type="text" wire:model="roleName" @disabled($isProtectedRole)
                           placeholder="e.g. Auditor"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-sm disabled:opacity-60">
                    @error('roleName') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-2">Assign Permissions by Module</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-72 overflow-y-auto pr-1">
                        @foreach($groupedPermissions as $moduleName => $perms)
                            @php
                                $allChecked = count(array_intersect($perms, $rolePermissions)) === count($perms);
                            @endphp
                            <div class="rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-1.5">
                                    <span class="text-xs font-bold text-[#B3261E] dark:text-[#F2B8B5]">{{ $moduleName }}</span>
                                    <button type="button" wire:click="togglePermissionGroup({{ Js::from($perms) }})"
                                            class="text-[10px] font-semibold text-slate-500 hover:underline">
                                        {{ $allChecked ? 'Unselect all' : 'Select all' }}
                                    </button>
                                </div>
                                <div class="space-y-1">
                                    @foreach($perms as $permName)
                                        <label class="flex items-center gap-2 text-xs">
                                            <input type="checkbox" wire:model="rolePermissions" value="{{ $permName }}" class="rounded text-[#B3261E]">
                                            <span>{{ $permName }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showRoleModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Tab: Staff Users --}}
    @if($activeTab === 'staff')
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-base">{{ __('messages.staff_users') }} ({{ $staffUsers->count() }})</h2>
                    <p class="text-xs text-slate-500">Staff accounts require 2FA enrollment on login</p>
                </div>
                @can('staff.create')
                <button type="button" wire:click="openCreateStaffModal"
                        class="rounded-xl border border-[#B3261E] text-[#B3261E] dark:text-[#F2B8B5] px-3.5 py-2 text-xs font-bold">
                    + {{ __('messages.create_staff_user') }}
                </button>
                @endcan
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                @foreach($staffUsers as $su)
                    @php $sp = $su->staffProfile; @endphp
                    <div class="py-3 flex flex-wrap justify-between items-center gap-2">
                        <div>
                            <div>
                                <strong>{{ $su->name }}</strong>
                                @if($sp) <span class="font-mono text-[11px] text-slate-400">({{ $sp->staff_code }})</span> @endif
                                — <span class="text-[#B3261E] dark:text-[#F2B8B5] font-semibold">{{ $su->getRoleNames()->implode(', ') ?: 'Staff' }}</span>
                            </div>
                            <div class="text-slate-400 mt-0.5">
                                {{ $su->email }}
                                @if($sp?->phone) • {{ $sp->phone }} @endif
                                @if($sp?->jobTitle) • {{ $sp->jobTitle->name }} @endif
                                @if($sp?->department) • {{ $sp->department->name }} @endif
                                @if($sp?->branch) • {{ $sp->branch->name }} @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($sp)
                                <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] {{ $sp->status === 'active' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $sp->status }}
                                </span>
                            @endif
                            <span class="px-2 py-0.5 rounded font-bold {{ $su->hasTwoFactorEnabled() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $su->hasTwoFactorEnabled() ? '2FA Active' : '2FA Pending' }}
                            </span>
                            @can('staff.edit')
                            <button type="button" wire:click="openEditStaffModal({{ $su->id }})"
                                    class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 px-2.5 py-1 text-xs font-semibold text-[#B3261E] dark:text-[#F2B8B5]">
                                <x-icon name="edit" class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </button>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($showCreateStaffModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-2xl bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">{{ __('messages.create_staff_user') }}</h3>
            <form wire:submit="createStaffUser" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.full_name') }}</label>
                        <input type="text" wire:model="newStaffName" placeholder="{{ __('messages.full_name') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Staff Code</label>
                        <input type="text" wire:model="newStaffCode" placeholder="NODA-STF-003" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.email') }}</label>
                        <input type="email" wire:model="newStaffEmail" placeholder="{{ __('messages.email') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.phone') }}</label>
                        <div class="flex gap-1.5">
                            <x-country-code-select :countries="$countries" wire:model="newStaffPhoneCode" widthClass="w-28" />
                            <input type="text" wire:model="newStaffPhone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.password') }}</label>
                        <input type="password" wire:model="newStaffPassword" placeholder="••••••••" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">NIDA / National ID</label>
                        <input type="text" wire:model="newStaffNationalId" placeholder="NIDA / National ID" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Role</label>
                        <select wire:model="newStaffRole" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            @foreach($assignableRoles as $ar)
                                <option value="{{ $ar->name }}">{{ $ar->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Branch</label>
                        <select wire:model="newStaffBranchId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Department</label>
                        <select wire:model="newStaffDepartmentId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Select Department --</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Job Title</label>
                        <select wire:model="newStaffJobTitleId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Select Job Title --</option>
                            @foreach($jobTitles as $jt)
                                <option value="{{ $jt->id }}">{{ $jt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Hire Date</label>
                        <input type="date" wire:model="newStaffHireDate" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Employment Status</label>
                        <select wire:model="newStaffStatus" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Residential Address</label>
                    <input type="text" wire:model="newStaffAddress" placeholder="Residential Address" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Emergency Contact Name</label>
                        <input type="text" wire:model="newStaffEmergencyName" placeholder="Emergency Contact Name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Emergency Contact Phone</label>
                        <div class="flex gap-1.5">
                            <x-country-code-select :countries="$countries" wire:model="newStaffEmergencyPhoneCode" widthClass="w-28" />
                            <input type="text" wire:model="newStaffEmergencyPhone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                        </div>
                        @error('newStaffEmergencyPhone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                @error('newStaffEmail') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                @error('newStaffCode') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="$set('showCreateStaffModal', false)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.create_staff_user') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if($editStaffUserId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-2xl bg-white dark:bg-slate-800 rounded-3xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-bold">Edit Staff User</h3>
            <form wire:submit="updateStaffUser" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.full_name') }}</label>
                        <input type="text" wire:model="editStaffName" placeholder="{{ __('messages.full_name') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Staff Code</label>
                        <input type="text" wire:model="editStaffCode" placeholder="Staff Code" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.email') }}</label>
                        <input type="email" wire:model="editStaffEmail" placeholder="{{ __('messages.email') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">{{ __('messages.phone') }}</label>
                        <div class="flex gap-1.5">
                            <x-country-code-select :countries="$countries" wire:model="editStaffPhoneCode" widthClass="w-28" />
                            <input type="text" wire:model="editStaffPhone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">New Password (optional)</label>
                        <input type="password" wire:model="editStaffPassword" placeholder="Leave blank to keep current" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">NIDA / National ID</label>
                        <input type="text" wire:model="editStaffNationalId" placeholder="NIDA / National ID" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Role</label>
                        <select wire:model="editStaffRole" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            @foreach($assignableRoles as $ar)
                                <option value="{{ $ar->name }}">{{ $ar->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Branch</label>
                        <select wire:model="editStaffBranchId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Department</label>
                        <select wire:model="editStaffDepartmentId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Select Department --</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Job Title</label>
                        <select wire:model="editStaffJobTitleId" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="">-- Select Job Title --</option>
                            @foreach($jobTitles as $jt)
                                <option value="{{ $jt->id }}">{{ $jt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Hire Date</label>
                        <input type="date" wire:model="editStaffHireDate" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Employment Status</label>
                        <select wire:model="editStaffStatus" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1">Residential Address</label>
                    <input type="text" wire:model="editStaffAddress" placeholder="Residential Address" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Emergency Contact Name</label>
                        <input type="text" wire:model="editStaffEmergencyName" placeholder="Emergency Contact Name" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Emergency Contact Phone</label>
                        <div class="flex gap-1.5">
                            <x-country-code-select :countries="$countries" wire:model="editStaffEmergencyPhoneCode" widthClass="w-28" />
                            <input type="text" wire:model="editStaffEmergencyPhone" placeholder="712100001" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 bg-transparent px-3 py-2 text-xs">
                        </div>
                        @error('editStaffEmergencyPhone') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                @error('editStaffEmail') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                @error('editStaffCode') <div class="text-xs text-red-600">{{ $message }}</div> @enderror
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" wire:click="$set('editStaffUserId', null)" class="px-4 py-2 text-xs font-semibold">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="rounded-xl bg-[#B3261E] text-white px-4 py-2 text-xs font-bold">{{ __('messages.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
