<?php

namespace App\Livewire\Staff;

use App\Models\Country;
use App\Models\Group;
use App\Models\Member;
use App\Models\MemberCollateral;
use App\Models\MemberReferee;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LoanCalculatorService;
use App\Services\PhoneHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class GroupsManager extends Component
{
    use WithFileUploads;

    public array $expandedGroups = [];

    // Register Group modal
    public bool $showCreateGroupModal = false;
    public string $newGroupName = '';
    public ?int $newGroupLoanOfficerId = null;
    public array $newMemberRows = [[
        'full_name' => '', 'phone_code' => '+255', 'phone' => '', 'email' => '',
        'gender' => '', 'date_of_birth' => '', 'id_type' => '', 'id_number' => '', 'id_image' => null, 'photo' => null,
        'referees' => [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']],
        'collaterals' => [],
    ]];

    // Assign Loan Officer modal
    public ?int $assignOfficerGroupId = null;
    public ?int $assignOfficerUserId = null;

    // Add Member modal
    public ?int $addMemberGroupId = null;
    public string $addMemberName = '';
    public string $addMemberPhoneCode = '+255';
    public string $addMemberPhone = '';
    public string $addMemberEmail = '';
    public string $addMemberGender = '';
    public string $addMemberDob = '';
    public string $addMemberIdType = '';
    public string $addMemberIdNumber = '';
    public $addMemberIdImage = null;
    public $addMemberPhoto = null;
    public array $addMemberReferees = [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']];
    public array $addMemberCollaterals = [];

    // Edit Member modal
    public ?int $editMemberId = null;
    public string $editMemberName = '';
    public string $editMemberPhoneCode = '+255';
    public string $editMemberPhone = '';
    public string $editMemberEmail = '';
    public string $editMemberGender = '';
    public string $editMemberDob = '';
    public string $editMemberIdType = '';
    public string $editMemberIdNumber = '';
    public $editMemberIdImage = null;
    public ?string $editMemberExistingIdImage = null;
    public $editMemberPhoto = null;
    public ?string $editMemberExistingPhoto = null;
    public array $editMemberReferees = [];
    public array $editMemberCollaterals = [];

    public string $flashMessage = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        $groupId = request()->integer('group');
        if ($groupId > 0) {
            $this->expandedGroups = [$groupId];
        }
    }

    public function toggleGroup(int $groupId): void
    {
        if (in_array($groupId, $this->expandedGroups, true)) {
            $this->expandedGroups = array_values(array_diff($this->expandedGroups, [$groupId]));
        } else {
            $this->expandedGroups[] = $groupId;
        }
    }

    public function addMemberRow(): void
    {
        $this->newMemberRows[] = [
            'full_name' => '', 'phone_code' => '+255', 'phone' => '', 'email' => '',
            'gender' => '', 'date_of_birth' => '', 'id_type' => '', 'id_number' => '', 'id_image' => null, 'photo' => null,
            'referees' => [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']],
            'collaterals' => [],
        ];
    }

    public function addNewMemberReferee(int $memberIdx): void
    {
        if (count($this->newMemberRows[$memberIdx]['referees'] ?? []) >= 2) {
            return;
        }
        $this->newMemberRows[$memberIdx]['referees'][] = ['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => ''];
    }

    public function removeNewMemberReferee(int $memberIdx, int $refIdx): void
    {
        if (count($this->newMemberRows[$memberIdx]['referees'] ?? []) > 1) {
            unset($this->newMemberRows[$memberIdx]['referees'][$refIdx]);
            $this->newMemberRows[$memberIdx]['referees'] = array_values($this->newMemberRows[$memberIdx]['referees']);
        }
    }

    public function addNewMemberCollateral(int $memberIdx): void
    {
        $this->newMemberRows[$memberIdx]['collaterals'][] = ['item_name' => '', 'estimated_value' => '', 'serial_number' => '', 'description' => '', 'image' => null];
    }

    public function removeNewMemberCollateral(int $memberIdx, int $colIdx): void
    {
        unset($this->newMemberRows[$memberIdx]['collaterals'][$colIdx]);
        $this->newMemberRows[$memberIdx]['collaterals'] = array_values($this->newMemberRows[$memberIdx]['collaterals']);
    }

    public function removeMemberRow(int $index): void
    {
        if (count($this->newMemberRows) > 1) {
            unset($this->newMemberRows[$index]);
            $this->newMemberRows = array_values($this->newMemberRows);
        }
    }

    public function openCreateGroupModal(): void
    {
        $user = Auth::user();
        abort_unless($user?->can('groups.create'), 403);
        $this->errorMessage = '';
        $this->newGroupName = '';
        $this->newGroupLoanOfficerId = $user?->hasRole('Loan Officer') ? $user->id : null;
        $this->newMemberRows = [[
            'full_name' => '', 'phone_code' => '+255', 'phone' => '', 'email' => '',
            'gender' => '', 'date_of_birth' => '', 'id_type' => '', 'id_number' => '', 'id_image' => null, 'photo' => null,
            'referees' => [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']],
            'collaterals' => [],
        ]];
        $this->showCreateGroupModal = true;
    }

    public function createGroup(): void
    {
        $user = Auth::user();
        abort_unless($user?->can('groups.create'), 403);
        $this->errorMessage = '';
        $this->newGroupName = trim($this->newGroupName);
        $allowedIdTypes = implode(',', Member::ID_TYPES);
        $allowedGenders = implode(',', Member::GENDERS);
        $this->validate([
            'newGroupName' => 'required|string|min:2|unique:groups,name',
            'newGroupLoanOfficerId' => 'nullable|integer|exists:users,id',
            'newMemberRows' => 'required|array|min:1',
            'newMemberRows.*.full_name' => 'required|string|min:2',
            'newMemberRows.*.phone' => 'required|string|min:6',
            'newMemberRows.*.email' => 'nullable|email',
            'newMemberRows.*.gender' => "nullable|in:{$allowedGenders}",
            'newMemberRows.*.date_of_birth' => 'nullable|date|before:today',
            'newMemberRows.*.id_type' => "nullable|in:{$allowedIdTypes}",
            'newMemberRows.*.id_number' => 'nullable|string|min:4|max:50',
            'newMemberRows.*.id_image' => 'nullable|image|max:4096',
            'newMemberRows.*.photo' => 'nullable|image|max:4096',
            'newMemberRows.*.referees' => 'required|array|min:1|max:2',
            'newMemberRows.*.referees.*.full_name' => 'required|string|min:2',
            'newMemberRows.*.referees.*.phone' => 'required|string|min:6',
            'newMemberRows.*.referees.*.relationship' => 'nullable|string|max:60',
            'newMemberRows.*.collaterals' => 'nullable|array',
            'newMemberRows.*.collaterals.*.item_name' => 'required|string|min:2|max:120',
            'newMemberRows.*.collaterals.*.estimated_value' => 'nullable|numeric|min:0',
            'newMemberRows.*.collaterals.*.serial_number' => 'nullable|string|max:80',
            'newMemberRows.*.collaterals.*.description' => 'nullable|string|max:500',
            'newMemberRows.*.collaterals.*.image' => 'nullable|image|max:4096',
        ]);

        if (Group::whereRaw('LOWER(name) = ?', [strtolower($this->newGroupName)])->exists()) {
            $this->addError('newGroupName', 'A group with this name already exists.');
            return;
        }

        $loanOfficerId = null;
        if (! $user->can('groups.assign_officer') && $user->hasRole('Loan Officer')) {
            $loanOfficerId = $user->id;
        } elseif ($this->newGroupLoanOfficerId) {
            if (! User::role('Loan Officer')->where('id', $this->newGroupLoanOfficerId)->exists()) {
                $this->errorMessage = 'Selected user must have the Loan Officer role.';
                return;
            }
            $loanOfficerId = $this->newGroupLoanOfficerId;
        } elseif ($user->hasRole('Loan Officer')) {
            $loanOfficerId = $user->id;
        }

        $phones = array_map(
            fn ($r) => PhoneHelper::formatE164($r['phone_code'] ?? '+255', $r['phone']),
            $this->newMemberRows
        );
        foreach ($phones as $p) {
            if (! PhoneHelper::isValidE164($p)) {
                $this->errorMessage = 'Please enter valid phone numbers for all members.';
                return;
            }
        }
        if (count(array_unique($phones)) !== count($phones)) {
            $this->errorMessage = 'Phone numbers in the new group must be unique.';
            return;
        }
        if (Member::whereIn('phone', $phones)->exists()) {
            $this->errorMessage = 'One or more phone numbers are already registered.';
            return;
        }

        $idNumbers = array_values(array_filter(array_map(
            fn ($r) => trim($r['id_number'] ?? ''),
            $this->newMemberRows
        )));
        if (count(array_unique($idNumbers)) !== count($idNumbers)) {
            $this->errorMessage = 'ID numbers in the new group must be unique.';
            return;
        }
        if (! empty($idNumbers) && Member::whereIn('id_number', $idNumbers)->exists()) {
            $this->errorMessage = 'One or more ID numbers are already registered.';
            return;
        }

        DB::transaction(function () use ($phones, $loanOfficerId) {
            $group = Group::create([
                'name' => trim($this->newGroupName),
                'loan_officer_id' => $loanOfficerId,
                'penalty_balance' => 0,
            ]);
            foreach ($this->newMemberRows as $idx => $row) {
                $idImagePath = ! empty($row['id_image'])
                    ? $row['id_image']->store('member-ids', 'public')
                    : null;
                $photoPath = ! empty($row['photo'])
                    ? $row['photo']->store('member-photos', 'public')
                    : null;
                $member = Member::create([
                    'group_id' => $group->id,
                    'full_name' => trim($row['full_name']),
                    'phone' => $phones[$idx],
                    'email' => trim($row['email'] ?? '') ?: null,
                    'gender' => trim($row['gender'] ?? '') ?: null,
                    'date_of_birth' => trim($row['date_of_birth'] ?? '') ?: null,
                    'id_type' => trim($row['id_type'] ?? '') ?: null,
                    'id_number' => trim($row['id_number'] ?? '') ?: null,
                    'id_image_path' => $idImagePath,
                    'photo_path' => $photoPath,
                ]);
                foreach ($row['referees'] as $ref) {
                    MemberReferee::create([
                        'member_id' => $member->id,
                        'full_name' => trim($ref['full_name']),
                        'phone' => PhoneHelper::formatE164($ref['phone_code'] ?? '+255', $ref['phone']),
                        'relationship' => trim($ref['relationship'] ?? '') ?: null,
                    ]);
                }
                foreach (($row['collaterals'] ?? []) as $col) {
                    $colImg = ! empty($col['image']) ? $col['image']->store('member-collaterals', 'public') : null;
                    MemberCollateral::create([
                        'member_id' => $member->id,
                        'item_name' => trim($col['item_name']),
                        'estimated_value' => ($col['estimated_value'] ?? '') !== '' ? (float) $col['estimated_value'] : null,
                        'serial_number' => trim($col['serial_number'] ?? '') ?: null,
                        'description' => trim($col['description'] ?? '') ?: null,
                        'image_path' => $colImg,
                    ]);
                }
            }
            $officerNote = $loanOfficerId ? ', officer #' . $loanOfficerId : '';
            AuditService::log(Auth::user(), 'CREATE_GROUP', $group->name, 'Registered group with ' . count($this->newMemberRows) . ' member(s)' . $officerNote);
        });

        $this->showCreateGroupModal = false;
        $this->newGroupName = '';
        $this->newGroupLoanOfficerId = null;
        $this->newMemberRows = [[
            'full_name' => '', 'phone_code' => '+255', 'phone' => '', 'email' => '',
            'gender' => '', 'date_of_birth' => '', 'id_type' => '', 'id_number' => '', 'id_image' => null, 'photo' => null,
            'referees' => [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']],
            'collaterals' => [],
        ]];
        $this->flashMessage = 'Group registered successfully.';
    }

    public function openAssignOfficer(int $groupId): void
    {
        abort_unless(Auth::user()?->can('groups.assign_officer'), 403);
        $group = Group::findOrFail($groupId);
        $this->assignOfficerGroupId = $group->id;
        $this->assignOfficerUserId = $group->loan_officer_id;
        $this->errorMessage = '';
    }

    public function saveAssignOfficer(): void
    {
        abort_unless(Auth::user()?->can('groups.assign_officer'), 403);
        $this->errorMessage = '';
        $this->validate([
            'assignOfficerUserId' => 'nullable|integer|exists:users,id',
        ]);

        if ($this->assignOfficerUserId && ! User::role('Loan Officer')->where('id', $this->assignOfficerUserId)->exists()) {
            $this->errorMessage = 'Selected user must have the Loan Officer role.';
            return;
        }

        $group = Group::findOrFail($this->assignOfficerGroupId);
        $group->update(['loan_officer_id' => $this->assignOfficerUserId ?: null]);
        $officerName = $group->fresh()->loanOfficer?->name ?? 'Unassigned';

        AuditService::log(Auth::user(), 'ASSIGN_GROUP_OFFICER', $group->name, "Assigned loan officer: {$officerName}");
        $this->assignOfficerGroupId = null;
        $this->assignOfficerUserId = null;
        $this->flashMessage = "{$group->name} assigned to {$officerName}.";
    }

    public function addAddMemberReferee(): void
    {
        if (count($this->addMemberReferees) >= 2) {
            return;
        }
        $this->addMemberReferees[] = ['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => ''];
    }

    public function removeAddMemberReferee(int $idx): void
    {
        if (count($this->addMemberReferees) > 1) {
            unset($this->addMemberReferees[$idx]);
            $this->addMemberReferees = array_values($this->addMemberReferees);
        }
    }

    public function addAddMemberCollateral(): void
    {
        $this->addMemberCollaterals[] = ['item_name' => '', 'estimated_value' => '', 'serial_number' => '', 'description' => '', 'image' => null];
    }

    public function removeAddMemberCollateral(int $idx): void
    {
        unset($this->addMemberCollaterals[$idx]);
        $this->addMemberCollaterals = array_values($this->addMemberCollaterals);
    }

    public function addEditMemberReferee(): void
    {
        if (count($this->editMemberReferees) >= 2) {
            return;
        }
        $this->editMemberReferees[] = ['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => ''];
    }

    public function removeEditMemberReferee(int $idx): void
    {
        if (count($this->editMemberReferees) > 1) {
            unset($this->editMemberReferees[$idx]);
            $this->editMemberReferees = array_values($this->editMemberReferees);
        }
    }

    public function addEditMemberCollateral(): void
    {
        $this->editMemberCollaterals[] = ['item_name' => '', 'estimated_value' => '', 'serial_number' => '', 'description' => '', 'image' => null, 'existing_image' => null];
    }

    public function removeEditMemberCollateral(int $idx): void
    {
        unset($this->editMemberCollaterals[$idx]);
        $this->editMemberCollaterals = array_values($this->editMemberCollaterals);
    }

    public function openAddMember(int $groupId): void
    {
        $this->addMemberGroupId = $groupId;
        $this->addMemberName = '';
        $this->addMemberPhoneCode = '+255';
        $this->addMemberPhone = '';
        $this->addMemberEmail = '';
        $this->addMemberGender = '';
        $this->addMemberDob = '';
        $this->addMemberIdType = '';
        $this->addMemberIdNumber = '';
        $this->addMemberIdImage = null;
        $this->addMemberPhoto = null;
        $this->addMemberReferees = [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']];
        $this->addMemberCollaterals = [];
        $this->errorMessage = '';
    }

    public function saveAddMember(): void
    {
        abort_unless(Auth::user()?->can('members.add'), 403);
        $this->addMemberPhone = PhoneHelper::formatE164($this->addMemberPhoneCode, $this->addMemberPhone);
        $this->addMemberIdNumber = trim($this->addMemberIdNumber);
        $allowedIdTypes = implode(',', Member::ID_TYPES);
        $allowedGenders = implode(',', Member::GENDERS);
        $this->validate([
            'addMemberName' => 'required|string|min:2',
            'addMemberPhone' => 'required|string|min:8|unique:members,phone',
            'addMemberEmail' => 'nullable|email',
            'addMemberGender' => "nullable|in:{$allowedGenders}",
            'addMemberDob' => 'nullable|date|before:today',
            'addMemberIdType' => "nullable|in:{$allowedIdTypes}",
            'addMemberIdNumber' => 'nullable|string|min:4|max:50|unique:members,id_number',
            'addMemberIdImage' => 'nullable|image|max:4096',
            'addMemberPhoto' => 'nullable|image|max:4096',
            'addMemberReferees' => 'required|array|min:1|max:2',
            'addMemberReferees.*.full_name' => 'required|string|min:2',
            'addMemberReferees.*.phone' => 'required|string|min:6',
            'addMemberReferees.*.relationship' => 'nullable|string|max:60',
            'addMemberCollaterals' => 'nullable|array',
            'addMemberCollaterals.*.item_name' => 'required|string|min:2|max:120',
            'addMemberCollaterals.*.estimated_value' => 'nullable|numeric|min:0',
            'addMemberCollaterals.*.serial_number' => 'nullable|string|max:80',
            'addMemberCollaterals.*.description' => 'nullable|string|max:500',
            'addMemberCollaterals.*.image' => 'nullable|image|max:4096',
        ]);

        $idImagePath = $this->addMemberIdImage
            ? $this->addMemberIdImage->store('member-ids', 'public')
            : null;
        $photoPath = $this->addMemberPhoto
            ? $this->addMemberPhoto->store('member-photos', 'public')
            : null;

        $group = Group::findOrFail($this->addMemberGroupId);
        $member = Member::create([
            'group_id' => $group->id,
            'full_name' => trim($this->addMemberName),
            'phone' => $this->addMemberPhone,
            'email' => trim($this->addMemberEmail) ?: null,
            'gender' => trim($this->addMemberGender) ?: null,
            'date_of_birth' => trim($this->addMemberDob) ?: null,
            'id_type' => trim($this->addMemberIdType) ?: null,
            'id_number' => $this->addMemberIdNumber ?: null,
            'id_image_path' => $idImagePath,
            'photo_path' => $photoPath,
        ]);

        foreach ($this->addMemberReferees as $ref) {
            MemberReferee::create([
                'member_id' => $member->id,
                'full_name' => trim($ref['full_name']),
                'phone' => PhoneHelper::formatE164($ref['phone_code'] ?? '+255', $ref['phone']),
                'relationship' => trim($ref['relationship'] ?? '') ?: null,
            ]);
        }

        foreach ($this->addMemberCollaterals as $col) {
            $colImg = ! empty($col['image']) ? $col['image']->store('member-collaterals', 'public') : null;
            MemberCollateral::create([
                'member_id' => $member->id,
                'item_name' => trim($col['item_name']),
                'estimated_value' => ($col['estimated_value'] ?? '') !== '' ? (float) $col['estimated_value'] : null,
                'serial_number' => trim($col['serial_number'] ?? '') ?: null,
                'description' => trim($col['description'] ?? '') ?: null,
                'image_path' => $colImg,
            ]);
        }

        AuditService::log(Auth::user(), 'ADD_MEMBER', $member->full_name, "Added to {$group->name} ({$member->phone})");
        $this->addMemberGroupId = null;
        $this->addMemberIdImage = null;
        $this->addMemberPhoto = null;
        $this->addMemberReferees = [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']];
        $this->addMemberCollaterals = [];
        $this->flashMessage = "{$member->full_name} added to {$group->name}.";
    }

    public function openEditMember(int $memberId): void
    {
        $member = Member::with(['referees', 'collaterals'])->findOrFail($memberId);
        [$code, $local] = PhoneHelper::splitE164($member->phone);
        $this->editMemberId = $member->id;
        $this->editMemberName = $member->full_name;
        $this->editMemberPhoneCode = $code;
        $this->editMemberPhone = $local;
        $this->editMemberEmail = $member->email ?? '';
        $this->editMemberGender = $member->gender ?? '';
        $this->editMemberDob = $member->date_of_birth?->format('Y-m-d') ?? '';
        $this->editMemberIdType = $member->id_type ?? '';
        $this->editMemberIdNumber = $member->id_number ?? '';
        $this->editMemberIdImage = null;
        $this->editMemberExistingIdImage = $member->id_image_path;
        $this->editMemberPhoto = null;
        $this->editMemberExistingPhoto = $member->photo_path;
        $this->editMemberReferees = $member->referees->map(function (MemberReferee $r) {
            [$rCode, $rLocal] = PhoneHelper::splitE164($r->phone);
            return [
                'full_name' => $r->full_name,
                'phone_code' => $rCode,
                'phone' => $rLocal,
                'relationship' => $r->relationship ?? '',
            ];
        })->values()->all();
        if (empty($this->editMemberReferees)) {
            $this->editMemberReferees = [['full_name' => '', 'phone_code' => '+255', 'phone' => '', 'relationship' => '']];
        }
        $this->editMemberCollaterals = $member->collaterals->map(function (MemberCollateral $c) {
            return [
                'item_name' => $c->item_name,
                'estimated_value' => $c->estimated_value !== null ? (string) $c->estimated_value : '',
                'serial_number' => $c->serial_number ?? '',
                'description' => $c->description ?? '',
                'image' => null,
                'existing_image' => $c->image_path,
            ];
        })->values()->all();
        $this->errorMessage = '';
    }

    public function saveEditMember(): void
    {
        abort_unless(Auth::user()?->can('members.edit'), 403);
        $fullPhone = PhoneHelper::formatE164($this->editMemberPhoneCode, $this->editMemberPhone);
        $this->editMemberPhone = $fullPhone;
        $this->editMemberIdNumber = trim($this->editMemberIdNumber);
        $allowedIdTypes = implode(',', Member::ID_TYPES);
        $allowedGenders = implode(',', Member::GENDERS);
        $this->validate([
            'editMemberName' => 'required|string|min:2',
            'editMemberPhone' => 'required|string|min:8|unique:members,phone,' . $this->editMemberId,
            'editMemberEmail' => 'nullable|email',
            'editMemberGender' => "nullable|in:{$allowedGenders}",
            'editMemberDob' => 'nullable|date|before:today',
            'editMemberIdType' => "nullable|in:{$allowedIdTypes}",
            'editMemberIdNumber' => 'nullable|string|min:4|max:50|unique:members,id_number,' . $this->editMemberId,
            'editMemberIdImage' => 'nullable|image|max:4096',
            'editMemberPhoto' => 'nullable|image|max:4096',
            'editMemberReferees' => 'required|array|min:1|max:2',
            'editMemberReferees.*.full_name' => 'required|string|min:2',
            'editMemberReferees.*.phone' => 'required|string|min:6',
            'editMemberReferees.*.relationship' => 'nullable|string|max:60',
            'editMemberCollaterals' => 'nullable|array',
            'editMemberCollaterals.*.item_name' => 'required|string|min:2|max:120',
            'editMemberCollaterals.*.estimated_value' => 'nullable|numeric|min:0',
            'editMemberCollaterals.*.serial_number' => 'nullable|string|max:80',
            'editMemberCollaterals.*.description' => 'nullable|string|max:500',
            'editMemberCollaterals.*.image' => 'nullable|image|max:4096',
        ]);

        $member = Member::findOrFail($this->editMemberId);
        $idImagePath = $member->id_image_path;
        if ($this->editMemberIdImage) {
            if ($idImagePath && Storage::disk('public')->exists($idImagePath)) {
                Storage::disk('public')->delete($idImagePath);
            }
            $idImagePath = $this->editMemberIdImage->store('member-ids', 'public');
        }
        $photoPath = $member->photo_path;
        if ($this->editMemberPhoto) {
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $this->editMemberPhoto->store('member-photos', 'public');
        }

        DB::transaction(function () use ($member, $fullPhone, $idImagePath, $photoPath) {
            $member->update([
                'full_name' => trim($this->editMemberName),
                'phone' => $fullPhone,
                'email' => trim($this->editMemberEmail) ?: null,
                'gender' => trim($this->editMemberGender) ?: null,
                'date_of_birth' => trim($this->editMemberDob) ?: null,
                'id_type' => trim($this->editMemberIdType) ?: null,
                'id_number' => $this->editMemberIdNumber ?: null,
                'id_image_path' => $idImagePath,
                'photo_path' => $photoPath,
            ]);

            $member->referees()->delete();
            foreach ($this->editMemberReferees as $ref) {
                MemberReferee::create([
                    'member_id' => $member->id,
                    'full_name' => trim($ref['full_name']),
                    'phone' => PhoneHelper::formatE164($ref['phone_code'] ?? '+255', $ref['phone']),
                    'relationship' => trim($ref['relationship'] ?? '') ?: null,
                ]);
            }

            $member->collaterals()->delete();
            foreach ($this->editMemberCollaterals as $col) {
                $colImg = $col['existing_image'] ?? null;
                if (! empty($col['image'])) {
                    if ($colImg && Storage::disk('public')->exists($colImg)) {
                        Storage::disk('public')->delete($colImg);
                    }
                    $colImg = $col['image']->store('member-collaterals', 'public');
                }
                MemberCollateral::create([
                    'member_id' => $member->id,
                    'item_name' => trim($col['item_name']),
                    'estimated_value' => ($col['estimated_value'] ?? '') !== '' ? (float) $col['estimated_value'] : null,
                    'serial_number' => trim($col['serial_number'] ?? '') ?: null,
                    'description' => trim($col['description'] ?? '') ?: null,
                    'image_path' => $colImg,
                ]);
            }
        });

        AuditService::log(Auth::user(), 'UPDATE_MEMBER', $member->full_name, "Updated phone={$member->phone}, email=" . ($member->email ?? '-'));
        $this->editMemberId = null;
        $this->editMemberIdImage = null;
        $this->editMemberExistingIdImage = null;
        $this->editMemberPhoto = null;
        $this->editMemberExistingPhoto = null;
        $this->editMemberReferees = [];
        $this->editMemberCollaterals = [];
        $this->flashMessage = "{$member->full_name} updated.";
    }

    public function render(LoanCalculatorService $calc)
    {
        $groups = Group::with(['loanOfficer.staffProfile', 'members.referees', 'members.collaterals', 'members.loans.payments', 'loans.payments'])->orderBy('id')->get();
        $groupData = $groups->map(function ($group) use ($calc) {
            $disbursed = $group->loans->sum('principal');
            $outstanding = $group->loans->where('status', '!=', 'completed')->sum(function ($loan) use ($calc) {
                return $calc->computeMetrics($loan)['remaining_loan_balance'];
            }) + $group->penalty_balance;

            $overdueCount = 0;
            $members = $group->members->map(function ($m) use ($calc, &$overdueCount) {
                $activeLoan = $m->loans->firstWhere('status', '!=', 'completed');
                $metrics = $activeLoan ? $calc->computeMetrics($activeLoan) : null;
                if ($metrics && $metrics['is_overdue']) {
                    $overdueCount++;
                }
                return [
                    'member' => $m,
                    'active_loan' => $activeLoan,
                    'metrics' => $metrics,
                ];
            });

            return [
                'group' => $group,
                'disbursed' => $disbursed,
                'outstanding' => $outstanding,
                'overdue_count' => $overdueCount,
                'members' => $members,
            ];
        });

        return view('livewire.staff.groups-manager', [
            'groupData' => $groupData,
            'countries' => Country::orderByRaw("CASE WHEN iso2 = 'TZ' THEN 0 ELSE 1 END, name ASC")->get(),
            'loanOfficers' => User::role('Loan Officer')->orderBy('name')->get(),
            'idTypes' => Member::ID_TYPES,
            'genders' => Member::GENDERS,
            'canAssignOfficer' => Auth::user()?->can('groups.assign_officer') ?? false,
            'isCurrentUserLoanOfficer' => Auth::user()?->hasRole('Loan Officer') ?? false,
        ]);
    }
}
