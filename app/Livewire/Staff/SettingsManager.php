<?php

namespace App\Livewire\Staff;

use App\Models\AdminSetting;
use App\Models\AppLockResetRequest;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PhoneHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts.app')]
class SettingsManager extends Component
{
    public const PROTECTED_ROLES = ['Super Admin', 'Admin', 'Loan Officer', 'Cashier', 'Director', 'Member'];

    #[Url(as: 'tab')]
    public string $activeTab = 'loans_penalties';

    public array $availableOffsets = [3, 1, 0];
    public array $selectedOffsets = [];
    public array $smsMap = [];
    public array $emailMap = [];
    public float $penaltyAmount = 10000;
    public string $penaltyRule = 'one_time_per_missed_due_date';
    public string $cutoffTime = '10:30';
    public float $minLoanAmount = 100000;
    public float $maxLoanAmount = 3000000;
    public int $maxWeeks = 26;
    public int $loadRatePercent = 17;
    public string $vodaLipaNamba = '55443322';
    public string $vodaBusinessName = 'NODA ML MICROFINANCE';
    public bool $mpesaAutoReconcile = true;
    public bool $twilioEnabled = false;
    public string $twilioAccountSid = '';
    public string $twilioAuthToken = '';
    public string $twilioFromNumber = '';
    public bool $hasSavedTwilioToken = false;

    // Organization CRUD: Branch, Department, Job Title
    public ?int $editBranchId = null;
    public string $branchName = '';
    public string $branchCode = '';
    public string $branchLocation = '';

    public ?int $editDepartmentId = null;
    public string $departmentName = '';

    public ?int $editJobTitleId = null;
    public string $jobTitleName = '';

    // Staff user creation modal
    public bool $showCreateStaffModal = false;
    public string $newStaffName = '';
    public string $newStaffEmail = '';
    public string $newStaffPassword = '';
    public string $newStaffRole = 'Loan Officer';
    public string $newStaffCode = '';
    public string $newStaffPhoneCode = '+255';
    public string $newStaffPhone = '';
    public string $newStaffNationalId = '';
    public ?int $newStaffBranchId = null;
    public ?int $newStaffDepartmentId = null;
    public ?int $newStaffJobTitleId = null;
    public string $newStaffHireDate = '';
    public string $newStaffStatus = 'active';
    public string $newStaffAddress = '';
    public string $newStaffEmergencyName = '';
    public string $newStaffEmergencyPhoneCode = '+255';
    public string $newStaffEmergencyPhone = '';

    // Staff user edit modal
    public ?int $editStaffUserId = null;
    public string $editStaffName = '';
    public string $editStaffEmail = '';
    public string $editStaffPassword = '';
    public string $editStaffRole = 'Loan Officer';
    public string $editStaffCode = '';
    public string $editStaffPhoneCode = '+255';
    public string $editStaffPhone = '';
    public string $editStaffNationalId = '';
    public ?int $editStaffBranchId = null;
    public ?int $editStaffDepartmentId = null;
    public ?int $editStaffJobTitleId = null;
    public string $editStaffHireDate = '';
    public string $editStaffStatus = 'active';
    public string $editStaffAddress = '';
    public string $editStaffEmergencyName = '';
    public string $editStaffEmergencyPhoneCode = '+255';
    public string $editStaffEmergencyPhone = '';

    // Roles & Permissions CRUD
    public bool $showRoleModal = false;
    public ?int $editRoleId = null;
    public string $roleName = '';
    public array $rolePermissions = [];
    public bool $isProtectedRole = false;

    public string $flashMessage = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        $s = AdminSetting::current();
        $this->selectedOffsets = $s->reminder_lead_days;
        $this->smsMap = $s->sms_enabled_by_offset;
        $this->emailMap = $s->email_enabled_by_offset;
        $this->penaltyAmount = (float) $s->penalty_amount_per_member;
        $this->penaltyRule = $s->penalty_rule;
        $this->cutoffTime = sprintf('%02d:%02d', $s->penalty_cutoff_hour, $s->penalty_cutoff_minute);
        $this->minLoanAmount = (float) $s->min_loan_amount;
        $this->maxLoanAmount = (float) $s->max_loan_amount;
        $this->maxWeeks = (int) $s->max_weeks;
        $this->loadRatePercent = (int) round($s->load_rate * 100);
        $this->vodaLipaNamba = (string) $s->voda_lipa_namba;
        $this->vodaBusinessName = (string) $s->voda_business_name;
        $this->mpesaAutoReconcile = (bool) $s->mpesa_auto_reconcile;
        $this->twilioEnabled = (bool) $s->twilio_enabled;
        $this->twilioAccountSid = (string) ($s->twilio_account_sid ?? '');
        $this->twilioFromNumber = (string) ($s->twilio_from_number ?? '');
        $this->hasSavedTwilioToken = ! empty($s->twilio_auth_token);
        $this->newStaffHireDate = now()->format('Y-m-d');
    }

    public function toggleOffset(int $offset): void
    {
        if (in_array($offset, $this->selectedOffsets, true)) {
            $this->selectedOffsets = array_values(array_diff($this->selectedOffsets, [$offset]));
        } else {
            $this->selectedOffsets[] = $offset;
            rsort($this->selectedOffsets);
        }
    }

    public function saveSettings(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $this->errorMessage = '';
        $parts = explode(':', trim($this->cutoffTime));
        $hour = isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : -1;
        $min = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : -1;

        if ($hour < 0 || $hour > 23 || $min < 0 || $min > 59) {
            $this->errorMessage = 'Cutoff time must be in 24h HH:mm format (e.g., 10:30).';
            return;
        }

        $this->validate([
            'penaltyAmount' => 'required|numeric|min:0',
            'minLoanAmount' => 'required|numeric|min:1000|lte:maxLoanAmount',
            'maxLoanAmount' => 'required|numeric|min:50000|max:10000000',
            'maxWeeks' => 'required|integer|min:1|max:52',
            'loadRatePercent' => 'required|integer|min:1|max:50',
        ]);

        $s = AdminSetting::current();
        $updateData = [
            'reminder_lead_days' => array_values($this->selectedOffsets),
            'sms_enabled_by_offset' => $this->smsMap,
            'email_enabled_by_offset' => $this->emailMap,
            'penalty_amount_per_member' => $this->penaltyAmount,
            'penalty_rule' => $this->penaltyRule,
            'penalty_cutoff_hour' => $hour,
            'penalty_cutoff_minute' => $min,
            'min_loan_amount' => $this->minLoanAmount,
            'max_loan_amount' => $this->maxLoanAmount,
            'max_weeks' => $this->maxWeeks,
            'load_rate' => $this->loadRatePercent / 100,
            'voda_lipa_namba' => trim($this->vodaLipaNamba) ?: '55443322',
            'voda_business_name' => trim($this->vodaBusinessName) ?: 'NODA ML MICROFINANCE',
            'mpesa_auto_reconcile' => $this->mpesaAutoReconcile,
            'twilio_enabled' => $this->twilioEnabled,
            'twilio_account_sid' => trim($this->twilioAccountSid) ?: null,
            'twilio_from_number' => trim($this->twilioFromNumber) ?: null,
        ];

        if (trim($this->twilioAuthToken) !== '') {
            $updateData['twilio_auth_token'] = trim($this->twilioAuthToken);
            $this->hasSavedTwilioToken = true;
            $this->twilioAuthToken = '';
        }

        $s->update($updateData);
        AuditService::log(Auth::user(), 'UPDATE_SETTINGS', 'AdminSettings', "Updated settings on tab {$this->activeTab}");
        $this->flashMessage = 'Admin settings saved & audit-logged.';
    }

    // --- Branch CRUD ---
    public function saveBranch(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $this->validate([
            'branchName' => 'required|string|min:2|unique:branches,name,' . ($this->editBranchId ?? 'NULL'),
            'branchCode' => 'nullable|string|max:20',
            'branchLocation' => 'nullable|string|max:100',
        ]);

        if ($this->editBranchId) {
            Branch::findOrFail($this->editBranchId)->update([
                'name' => trim($this->branchName),
                'code' => trim($this->branchCode) ?: null,
                'location' => trim($this->branchLocation) ?: null,
            ]);
            $this->flashMessage = 'Branch updated.';
        } else {
            Branch::create([
                'name' => trim($this->branchName),
                'code' => trim($this->branchCode) ?: null,
                'location' => trim($this->branchLocation) ?: null,
            ]);
            $this->flashMessage = 'Branch created.';
        }
        $this->reset(['editBranchId', 'branchName', 'branchCode', 'branchLocation']);
    }

    public function editBranch(int $id): void
    {
        $b = Branch::findOrFail($id);
        $this->editBranchId = $b->id;
        $this->branchName = $b->name;
        $this->branchCode = $b->code ?? '';
        $this->branchLocation = $b->location ?? '';
    }

    public function deleteBranch(int $id): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        Branch::findOrFail($id)->delete();
        $this->flashMessage = 'Branch deleted.';
    }

    // --- Department CRUD ---
    public function saveDepartment(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $this->validate([
            'departmentName' => 'required|string|min:2|unique:departments,name,' . ($this->editDepartmentId ?? 'NULL'),
        ]);

        if ($this->editDepartmentId) {
            Department::findOrFail($this->editDepartmentId)->update(['name' => trim($this->departmentName)]);
            $this->flashMessage = 'Department updated.';
        } else {
            Department::create(['name' => trim($this->departmentName)]);
            $this->flashMessage = 'Department created.';
        }
        $this->reset(['editDepartmentId', 'departmentName']);
    }

    public function editDepartment(int $id): void
    {
        $d = Department::findOrFail($id);
        $this->editDepartmentId = $d->id;
        $this->departmentName = $d->name;
    }

    public function deleteDepartment(int $id): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        Department::findOrFail($id)->delete();
        $this->flashMessage = 'Department deleted.';
    }

    // --- Job Title CRUD ---
    public function saveJobTitle(): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        $this->validate([
            'jobTitleName' => 'required|string|min:2|unique:job_titles,name,' . ($this->editJobTitleId ?? 'NULL'),
        ]);

        if ($this->editJobTitleId) {
            JobTitle::findOrFail($this->editJobTitleId)->update(['name' => trim($this->jobTitleName)]);
            $this->flashMessage = 'Job title updated.';
        } else {
            JobTitle::create(['name' => trim($this->jobTitleName)]);
            $this->flashMessage = 'Job title created.';
        }
        $this->reset(['editJobTitleId', 'jobTitleName']);
    }

    public function editJobTitle(int $id): void
    {
        $j = JobTitle::findOrFail($id);
        $this->editJobTitleId = $j->id;
        $this->jobTitleName = $j->name;
    }

    public function deleteJobTitle(int $id): void
    {
        abort_unless(Auth::user()?->can('settings.update'), 403);
        JobTitle::findOrFail($id)->delete();
        $this->flashMessage = 'Job title deleted.';
    }

    // --- Staff User Creation ---
    public function openCreateStaffModal(): void
    {
        $nextNum = StaffProfile::count() + 1;
        $this->newStaffCode = sprintf('NODA-STF-%03d', $nextNum);
        $this->newStaffBranchId = Branch::value('id');
        $this->newStaffDepartmentId = Department::value('id');
        $this->newStaffJobTitleId = JobTitle::value('id');
        $this->newStaffHireDate = now()->format('Y-m-d');
        $this->newStaffStatus = 'active';
        $this->showCreateStaffModal = true;
    }

    public function createStaffUser(): void
    {
        abort_unless(Auth::user()?->can('staff.create'), 403);
        $this->newStaffPhone = trim($this->newStaffPhone) !== ''
            ? PhoneHelper::formatE164($this->newStaffPhoneCode, $this->newStaffPhone)
            : '';
        $this->newStaffEmergencyPhone = trim($this->newStaffEmergencyPhone) !== ''
            ? PhoneHelper::formatE164($this->newStaffEmergencyPhoneCode, $this->newStaffEmergencyPhone)
            : '';
        $allowedRoles = Role::whereNotIn('name', ['Super Admin', 'Member'])->pluck('name')->implode(',');
        $this->validate([
            'newStaffName' => 'required|string|min:2',
            'newStaffEmail' => 'required|email|unique:users,email',
            'newStaffPassword' => 'required|string|min:6',
            'newStaffRole' => "required|in:{$allowedRoles}",
            'newStaffCode' => 'required|string|unique:staff_profiles,staff_code',
            'newStaffPhone' => 'nullable|string|min:8|unique:staff_profiles,phone',
            'newStaffEmergencyPhone' => 'nullable|string|min:8',
            'newStaffBranchId' => 'nullable|exists:branches,id',
            'newStaffDepartmentId' => 'nullable|exists:departments,id',
            'newStaffJobTitleId' => 'nullable|exists:job_titles,id',
            'newStaffStatus' => 'required|in:active,suspended,terminated',
        ]);

        DB::transaction(function () {
            $staff = User::create([
                'name' => trim($this->newStaffName),
                'email' => strtolower(trim($this->newStaffEmail)),
                'password' => Hash::make($this->newStaffPassword),
                'role' => 'staff',
                'locale' => 'en',
            ]);
            $staff->assignRole($this->newStaffRole);

            StaffProfile::create([
                'user_id' => $staff->id,
                'staff_code' => trim($this->newStaffCode),
                'phone' => trim($this->newStaffPhone) ?: null,
                'national_id' => trim($this->newStaffNationalId) ?: null,
                'branch_id' => $this->newStaffBranchId,
                'department_id' => $this->newStaffDepartmentId,
                'job_title_id' => $this->newStaffJobTitleId,
                'hire_date' => $this->newStaffHireDate ?: null,
                'status' => $this->newStaffStatus,
                'address' => trim($this->newStaffAddress) ?: null,
                'emergency_contact_name' => trim($this->newStaffEmergencyName) ?: null,
                'emergency_contact_phone' => trim($this->newStaffEmergencyPhone) ?: null,
            ]);

            AuditService::log(Auth::user(), 'CREATE_STAFF_USER', $staff->name, "Created {$this->newStaffRole} ({$this->newStaffCode})");
        });

        $this->showCreateStaffModal = false;
        $this->reset([
            'newStaffName', 'newStaffEmail', 'newStaffPassword', 'newStaffPhone',
            'newStaffNationalId', 'newStaffAddress', 'newStaffEmergencyName', 'newStaffEmergencyPhone',
        ]);
        $this->newStaffPhoneCode = '+255';
        $this->newStaffEmergencyPhoneCode = '+255';
        $this->flashMessage = 'Staff user & profile created.';
    }

    public function openEditStaffModal(int $userId): void
    {
        abort_unless(Auth::user()?->can('staff.edit'), 403);
        $user = User::with('staffProfile')->where('role', 'staff')->findOrFail($userId);
        abort_if($user->hasRole('Super Admin'), 403);

        $sp = $user->staffProfile;
        [$phoneCode, $phoneLocal] = PhoneHelper::splitE164($sp?->phone);
        $this->editStaffUserId = $user->id;
        $this->editStaffName = $user->name;
        $this->editStaffEmail = $user->email;
        $this->editStaffPassword = '';
        $this->editStaffRole = $user->getRoleNames()->first() ?: 'Loan Officer';
        $this->editStaffCode = $sp?->staff_code ?? sprintf('NODA-STF-%03d', $user->id);
        $this->editStaffPhoneCode = $phoneCode;
        $this->editStaffPhone = $phoneLocal;
        $this->editStaffNationalId = $sp?->national_id ?? '';
        $this->editStaffBranchId = $sp?->branch_id;
        $this->editStaffDepartmentId = $sp?->department_id;
        $this->editStaffJobTitleId = $sp?->job_title_id;
        $this->editStaffHireDate = $sp?->hire_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->editStaffStatus = $sp?->status ?? 'active';
        $this->editStaffAddress = $sp?->address ?? '';
        $this->editStaffEmergencyName = $sp?->emergency_contact_name ?? '';
        [$emergencyCode, $emergencyLocal] = PhoneHelper::splitE164($sp?->emergency_contact_phone);
        $this->editStaffEmergencyPhoneCode = $emergencyCode;
        $this->editStaffEmergencyPhone = $emergencyLocal;
    }

    public function updateStaffUser(): void
    {
        abort_unless(Auth::user()?->can('staff.edit'), 403);
        $user = User::with('staffProfile')->where('role', 'staff')->findOrFail($this->editStaffUserId);
        abort_if($user->hasRole('Super Admin'), 403);

        $profileId = $user->staffProfile?->id;
        $allowedRoles = Role::whereNotIn('name', ['Super Admin', 'Member'])->pluck('name')->implode(',');
        $this->editStaffPhone = trim($this->editStaffPhone) !== ''
            ? PhoneHelper::formatE164($this->editStaffPhoneCode, $this->editStaffPhone)
            : '';
        $this->editStaffEmergencyPhone = trim($this->editStaffEmergencyPhone) !== ''
            ? PhoneHelper::formatE164($this->editStaffEmergencyPhoneCode, $this->editStaffEmergencyPhone)
            : '';
        $this->validate([
            'editStaffName' => 'required|string|min:2',
            'editStaffEmail' => 'required|email|unique:users,email,' . $user->id,
            'editStaffPassword' => 'nullable|string|min:6',
            'editStaffRole' => "required|in:{$allowedRoles}",
            'editStaffCode' => 'required|string|unique:staff_profiles,staff_code,' . ($profileId ?? 'NULL'),
            'editStaffPhone' => 'nullable|string|min:8|unique:staff_profiles,phone,' . ($profileId ?? 'NULL'),
            'editStaffEmergencyPhone' => 'nullable|string|min:8',
            'editStaffBranchId' => 'nullable|exists:branches,id',
            'editStaffDepartmentId' => 'nullable|exists:departments,id',
            'editStaffJobTitleId' => 'nullable|exists:job_titles,id',
            'editStaffStatus' => 'required|in:active,suspended,terminated',
        ]);

        DB::transaction(function () use ($user) {
            $userData = [
                'name' => trim($this->editStaffName),
                'email' => strtolower(trim($this->editStaffEmail)),
            ];
            if (trim($this->editStaffPassword) !== '') {
                $userData['password'] = Hash::make($this->editStaffPassword);
            }
            $user->update($userData);
            $user->syncRoles([$this->editStaffRole]);

            StaffProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'staff_code' => trim($this->editStaffCode),
                    'phone' => trim($this->editStaffPhone) ?: null,
                    'national_id' => trim($this->editStaffNationalId) ?: null,
                    'branch_id' => $this->editStaffBranchId ?: null,
                    'department_id' => $this->editStaffDepartmentId ?: null,
                    'job_title_id' => $this->editStaffJobTitleId ?: null,
                    'hire_date' => $this->editStaffHireDate ?: null,
                    'status' => $this->editStaffStatus,
                    'address' => trim($this->editStaffAddress) ?: null,
                    'emergency_contact_name' => trim($this->editStaffEmergencyName) ?: null,
                    'emergency_contact_phone' => trim($this->editStaffEmergencyPhone) ?: null,
                ]
            );

            AuditService::log(Auth::user(), 'UPDATE_STAFF_USER', $user->name, "Updated {$this->editStaffRole} ({$this->editStaffCode}, status={$this->editStaffStatus})");
        });

        $this->editStaffUserId = null;
        $this->flashMessage = 'Staff user details updated.';
    }

    // --- Roles & Permissions CRUD ---
    public function openCreateRoleModal(): void
    {
        abort_unless(Auth::user()?->can('roles.create'), 403);
        $this->editRoleId = null;
        $this->roleName = '';
        $this->rolePermissions = [];
        $this->isProtectedRole = false;
        $this->showRoleModal = true;
    }

    public function openEditRoleModal(int $roleId): void
    {
        abort_unless(Auth::user()?->can('roles.edit'), 403);
        $role = Role::with('permissions')->findOrFail($roleId);
        abort_if($role->name === 'Super Admin', 403);

        $this->editRoleId = $role->id;
        $this->roleName = $role->name;
        $this->rolePermissions = $role->permissions->pluck('name')->toArray();
        $this->isProtectedRole = in_array($role->name, self::PROTECTED_ROLES, true);
        $this->showRoleModal = true;
    }

    public function saveRole(): void
    {
        abort_unless(Auth::user()?->can($this->editRoleId ? 'roles.edit' : 'roles.create'), 403);
        $cleanName = trim($this->roleName);
        if (strcasecmp($cleanName, 'Super Admin') === 0) {
            $this->addError('roleName', 'Super Admin role cannot be created or modified.');
            return;
        }

        if ($this->editRoleId) {
            $role = Role::findOrFail($this->editRoleId);
            abort_if($role->name === 'Super Admin', 403);

            if (! in_array($role->name, self::PROTECTED_ROLES, true)) {
                $this->validate(['roleName' => 'required|string|min:2|unique:roles,name,' . $role->id]);
                $role->update(['name' => $cleanName]);
            }
            $role->syncPermissions($this->rolePermissions);
            AuditService::log(Auth::user(), 'UPDATE_ROLE', $role->name, 'Updated permissions: ' . implode(', ', $this->rolePermissions));
            $this->flashMessage = "Role '{$role->name}' updated.";
        } else {
            $this->validate(['roleName' => 'required|string|min:2|unique:roles,name']);
            $role = Role::create(['name' => $cleanName]);
            $role->syncPermissions($this->rolePermissions);
            AuditService::log(Auth::user(), 'CREATE_ROLE', $role->name, 'Created role with permissions: ' . implode(', ', $this->rolePermissions));
            $this->flashMessage = "Role '{$role->name}' created.";
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->showRoleModal = false;
    }

    public function deleteRole(int $roleId): void
    {
        abort_unless(Auth::user()?->can('roles.delete'), 403);
        $role = Role::findOrFail($roleId);
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            $this->errorMessage = "System role '{$role->name}' is protected and cannot be deleted.";
            return;
        }

        $name = $role->name;
        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        AuditService::log(Auth::user(), 'DELETE_ROLE', $name, 'Deleted custom role');
        $this->flashMessage = "Role '{$name}' deleted.";
    }

    public function togglePermissionGroup(array $perms): void
    {
        $allSelected = count(array_intersect($perms, $this->rolePermissions)) === count($perms);
        if ($allSelected) {
            $this->rolePermissions = array_values(array_diff($this->rolePermissions, $perms));
        } else {
            $this->rolePermissions = array_values(array_unique(array_merge($this->rolePermissions, $perms)));
        }
    }

    public function approveAppLockReset(int $requestId): void
    {
        $this->authorize('settings.update');
        $req = AppLockResetRequest::with('user')->findOrFail($requestId);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $req->update([
            'status' => 'approved',
            'reset_code' => $code,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);
        AuditService::log(Auth::user(), 'APP_LOCK_RESET_APPROVED', "User #{$req->user_id}", "Approved {$req->lock_type} reset (code valid 15 min)");
        $this->flashMessage = "Approved reset for {$req->user?->name}. One-time code: {$code} (valid 15 mins).";
    }

    public function rejectAppLockReset(int $requestId): void
    {
        $this->authorize('settings.update');
        $req = AppLockResetRequest::with('user')->findOrFail($requestId);
        $req->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        AuditService::log(Auth::user(), 'APP_LOCK_RESET_REJECTED', "User #{$req->user_id}", "Rejected {$req->lock_type} reset request");
        $this->flashMessage = "Rejected reset request for {$req->user?->name}.";
    }

    public function render()
    {
        $allPerms = Permission::orderBy('name')->pluck('name');
        $moduleMap = [
            'Groups & Members' => ['groups.view', 'groups.create', 'groups.assign_officer', 'members.add', 'members.edit'],
            'Loans' => ['loans.view', 'loans.create'],
            'Payments & M-Pesa' => ['payments.view', 'payments.record', 'mpesa.simulate'],
            'Penalties' => ['penalties.sweep', 'penalties.apply'],
            'Notifications' => ['notifications.view', 'notifications.dispatch'],
            'Contacts & Templates' => ['contacts.view', 'templates.view', 'templates.manage'],
            'Reports & Finance' => ['reports.view', 'reports.manage'],
            'Settings' => ['settings.view', 'settings.update'],
            'Staff Users & Roles' => ['staff.create', 'staff.edit', 'roles.view', 'roles.create', 'roles.edit', 'roles.delete'],
            'Member Portal' => ['member.portal.view'],
        ];
        $groupedPermissions = [];
        foreach ($moduleMap as $module => $list) {
            $present = array_values(array_intersect($list, $allPerms->toArray()));
            if (! empty($present)) {
                $groupedPermissions[$module] = $present;
            }
        }

        return view('livewire.staff.settings-manager', [
            'staffUsers' => User::with(['staffProfile.branch', 'staffProfile.department', 'staffProfile.jobTitle'])
                ->where('role', 'staff')
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
                ->orderBy('id')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'jobTitles' => JobTitle::orderBy('name')->get(),
            'countries' => Country::orderByRaw("CASE WHEN iso2 = 'TZ' THEN 0 ELSE 1 END, name ASC")->get(),
            'roles' => Role::with('permissions')->where('name', '!=', 'Super Admin')->orderBy('id')->get(),
            'assignableRoles' => Role::whereNotIn('name', ['Super Admin', 'Member'])->orderBy('id')->get(),
            'groupedPermissions' => $groupedPermissions,
            'appLockResetRequests' => AppLockResetRequest::with(['user', 'approver'])->latest()->limit(30)->get(),
            'pendingAppLockResetsCount' => AppLockResetRequest::where('status', 'pending')->count(),
        ]);
    }
}
