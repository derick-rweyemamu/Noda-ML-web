<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\CommunicationTemplate;
use App\Models\FinancialEntry;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MemberCollateral;
use App\Models\MemberReferee;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\Reminder;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LoanCalculatorService;
use App\Services\MpesaReconciliationService;
use App\Services\NotificationService;
use App\Services\PenaltyEngineService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NodaApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', strtolower(trim($data['email'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'member_id' => $user->member_id,
                'locale' => $user->locale,
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function dashboard(Request $request, LoanCalculatorService $calc): JsonResponse
    {
        $user = $request->user();
        $settings = AdminSetting::current();

        if ($user->isMember()) {
            $member = Member::with(['group.penalties', 'loans.payments', 'referees', 'collaterals'])->findOrFail($user->member_id);
            return response()->json([
                'role' => 'member',
                'member' => $this->serializeMember($member),
                'loans' => $member->loans->map(fn ($l) => [
                    'loan' => $l,
                    'metrics' => $calc->computeMetrics($l, now(), $settings),
                ]),
                'settings' => $settings,
            ]);
        }

        $groups = Group::with(['loanOfficer.staffProfile', 'members.referees', 'members.collaterals'])->orderBy('id')->get()->map(function (Group $g) {
            return [
                'id' => $g->id,
                'name' => $g->name,
                'loan_officer_id' => $g->loan_officer_id,
                'loan_officer_name' => $g->loanOfficer?->name,
                'loan_officer_phone' => $g->loanOfficer?->staffProfile?->phone,
                'penalty_balance' => $g->penalty_balance,
                'created_at' => $g->created_at,
                'members' => $g->members->map(fn (Member $m) => $this->serializeMember($m)),
            ];
        });

        $staffUsers = User::with(['roles', 'staffProfile'])
            ->where('role', 'staff')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))
            ->orderBy('id')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'roles' => $u->getRoleNames()->values(),
                'phone' => $u->staffProfile?->phone,
            ]);

        return response()->json([
            'role' => 'staff',
            'groups' => $groups,
            'members' => Member::with(['referees', 'collaterals'])->orderBy('id')->get()->map(fn (Member $m) => $this->serializeMember($m)),
            'loans' => Loan::with('payments')->get()->map(fn ($l) => [
                'loan' => $l,
                'metrics' => $calc->computeMetrics($l, now(), $settings),
            ]),
            'penalties' => Penalty::latest('applied_at')->get(),
            'reminders' => Reminder::latest('scheduled_date')->get(),
            'audit_logs' => AuditLog::latest()->take(50)->get(),
            'settings' => $settings,
            'staff_users' => $staffUsers,
            'communication_templates' => CommunicationTemplate::orderBy('id')->get(),
        ]);
    }

    private function serializeMember(Member $m): array
    {
        return [
            'id' => $m->id,
            'group_id' => $m->group_id,
            'full_name' => $m->full_name,
            'phone' => $m->phone,
            'email' => $m->email,
            'gender' => $m->gender,
            'date_of_birth' => $m->date_of_birth?->format('Y-m-d'),
            'age' => $m->age,
            'id_type' => $m->id_type,
            'id_number' => $m->id_number,
            'id_image_url' => $m->id_image_path ? Storage::disk('public')->url($m->id_image_path) : null,
            'photo_url' => $m->photo_path ? Storage::disk('public')->url($m->photo_path) : null,
            'penalty_balance' => $m->penalty_balance,
            'referees' => $m->referees->map(fn (MemberReferee $r) => [
                'id' => $r->id,
                'full_name' => $r->full_name,
                'phone' => $r->phone,
                'relationship' => $r->relationship,
            ])->values(),
            'collaterals' => $m->collaterals->map(fn (MemberCollateral $c) => [
                'id' => $c->id,
                'item_name' => $c->item_name,
                'estimated_value' => $c->estimated_value,
                'serial_number' => $c->serial_number,
                'description' => $c->description,
                'image_url' => $c->image_path ? Storage::disk('public')->url($c->image_path) : null,
            ])->values(),
            'created_at' => $m->created_at,
        ];
    }

    public function createGroup(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedGenders = implode(',', Member::GENDERS);
        $allowedIdTypes = implode(',', Member::ID_TYPES);

        $data = $request->validate([
            'name' => 'required|string|min:2|unique:groups,name',
            'loan_officer_id' => 'nullable|integer|exists:users,id',
            'members' => 'required|array|min:1',
            'members.*.full_name' => 'required|string|min:2',
            'members.*.phone' => 'required|string|unique:members,phone',
            'members.*.email' => 'nullable|email',
            'members.*.gender' => "nullable|in:{$allowedGenders}",
            'members.*.date_of_birth' => 'nullable|date|before:today',
            'members.*.id_type' => "nullable|in:{$allowedIdTypes}",
            'members.*.id_number' => 'nullable|string|min:4|max:50|unique:members,id_number',
            'members.*.referees' => 'required|array|min:1|max:2',
            'members.*.referees.*.full_name' => 'required|string|min:2',
            'members.*.referees.*.phone' => 'required|string|min:6',
            'members.*.referees.*.relationship' => 'nullable|string|max:60',
            'members.*.collaterals' => 'nullable|array',
            'members.*.collaterals.*.item_name' => 'required|string|min:2|max:120',
            'members.*.collaterals.*.estimated_value' => 'nullable|numeric|min:0',
            'members.*.collaterals.*.serial_number' => 'nullable|string|max:80',
        ]);

        if (Group::whereRaw('LOWER(name) = ?', [strtolower(trim($data['name']))])->exists()) {
            return response()->json(['message' => 'A group with this name already exists.'], 422);
        }

        $loanOfficerId = null;
        if (! $user->can('groups.assign_officer') && $user->hasRole('Loan Officer')) {
            $loanOfficerId = $user->id;
        } elseif (! empty($data['loan_officer_id'])) {
            if (! User::role('Loan Officer')->where('id', $data['loan_officer_id'])->exists()) {
                return response()->json(['message' => 'Selected user must have the Loan Officer role.'], 422);
            }
            $loanOfficerId = (int) $data['loan_officer_id'];
        } elseif ($user->hasRole('Loan Officer')) {
            $loanOfficerId = $user->id;
        }

        $group = Group::create([
            'name' => trim($data['name']),
            'loan_officer_id' => $loanOfficerId,
            'penalty_balance' => 0,
        ]);

        foreach ($data['members'] as $m) {
            $member = Member::create([
                'group_id' => $group->id,
                'full_name' => trim($m['full_name']),
                'phone' => trim($m['phone']),
                'email' => $m['email'] ?? null,
                'gender' => $m['gender'] ?? null,
                'date_of_birth' => $m['date_of_birth'] ?? null,
                'id_type' => $m['id_type'] ?? null,
                'id_number' => $m['id_number'] ?? null,
            ]);
            foreach ($m['referees'] as $ref) {
                MemberReferee::create([
                    'member_id' => $member->id,
                    'full_name' => trim($ref['full_name']),
                    'phone' => trim($ref['phone']),
                    'relationship' => trim($ref['relationship'] ?? '') ?: null,
                ]);
            }
            foreach (($m['collaterals'] ?? []) as $col) {
                MemberCollateral::create([
                    'member_id' => $member->id,
                    'item_name' => trim($col['item_name']),
                    'estimated_value' => $col['estimated_value'] ?? null,
                    'serial_number' => trim($col['serial_number'] ?? '') ?: null,
                ]);
            }
        }

        AuditService::log($user, 'CREATE_GROUP', $group->name, 'Created via API');
        return response()->json($group->load(['loanOfficer.staffProfile', 'members.referees', 'members.collaterals']), 201);
    }

    public function assignGroupOfficer(Request $request, Group $group): JsonResponse
    {
        $data = $request->validate([
            'loan_officer_id' => 'nullable|integer|exists:users,id',
        ]);

        if (! empty($data['loan_officer_id']) && ! User::role('Loan Officer')->where('id', $data['loan_officer_id'])->exists()) {
            return response()->json(['message' => 'Selected user must have the Loan Officer role.'], 422);
        }

        $group->update(['loan_officer_id' => $data['loan_officer_id'] ?? null]);
        AuditService::log($request->user(), 'ASSIGN_GROUP_OFFICER', $group->name, 'Assigned via API');

        return response()->json($group->fresh(['loanOfficer.staffProfile']));
    }

    public function addMember(Request $request, Group $group): JsonResponse
    {
        $allowedGenders = implode(',', Member::GENDERS);
        $allowedIdTypes = implode(',', Member::ID_TYPES);

        $data = $request->validate([
            'full_name' => 'required|string|min:2',
            'phone' => 'required|string|min:8|unique:members,phone',
            'email' => 'nullable|email',
            'gender' => "nullable|in:{$allowedGenders}",
            'date_of_birth' => 'nullable|date|before:today',
            'id_type' => "nullable|in:{$allowedIdTypes}",
            'id_number' => 'nullable|string|min:4|max:50|unique:members,id_number',
            'referees' => 'required|array|min:1|max:2',
            'referees.*.full_name' => 'required|string|min:2',
            'referees.*.phone' => 'required|string|min:6',
            'referees.*.relationship' => 'nullable|string|max:60',
            'collaterals' => 'nullable|array',
            'collaterals.*.item_name' => 'required|string|min:2|max:120',
            'collaterals.*.estimated_value' => 'nullable|numeric|min:0',
            'collaterals.*.serial_number' => 'nullable|string|max:80',
        ]);

        $member = Member::create([
            'group_id' => $group->id,
            'full_name' => trim($data['full_name']),
            'phone' => trim($data['phone']),
            'email' => $data['email'] ?? null,
            'gender' => $data['gender'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
        ]);

        foreach ($data['referees'] as $ref) {
            MemberReferee::create([
                'member_id' => $member->id,
                'full_name' => trim($ref['full_name']),
                'phone' => trim($ref['phone']),
                'relationship' => trim($ref['relationship'] ?? '') ?: null,
            ]);
        }
        foreach (($data['collaterals'] ?? []) as $col) {
            MemberCollateral::create([
                'member_id' => $member->id,
                'item_name' => trim($col['item_name']),
                'estimated_value' => $col['estimated_value'] ?? null,
                'serial_number' => trim($col['serial_number'] ?? '') ?: null,
            ]);
        }

        AuditService::log($request->user(), 'ADD_MEMBER', $member->full_name, "Added to {$group->name} via API");
        return response()->json($this->serializeMember($member->load(['referees', 'collaterals'])), 201);
    }

    public function updateMember(Request $request, Member $member): JsonResponse
    {
        $allowedGenders = implode(',', Member::GENDERS);
        $allowedIdTypes = implode(',', Member::ID_TYPES);

        $data = $request->validate([
            'full_name' => 'required|string|min:2',
            'phone' => 'required|string|unique:members,phone,' . $member->id,
            'email' => 'nullable|email',
            'gender' => "nullable|in:{$allowedGenders}",
            'date_of_birth' => 'nullable|date|before:today',
            'id_type' => "nullable|in:{$allowedIdTypes}",
            'id_number' => 'nullable|string|min:4|max:50|unique:members,id_number,' . $member->id,
            'referees' => 'sometimes|required|array|min:1|max:2',
            'referees.*.full_name' => 'required_with:referees|string|min:2',
            'referees.*.phone' => 'required_with:referees|string|min:6',
            'referees.*.relationship' => 'nullable|string|max:60',
            'collaterals' => 'sometimes|array',
            'collaterals.*.item_name' => 'required_with:collaterals|string|min:2|max:120',
            'collaterals.*.estimated_value' => 'nullable|numeric|min:0',
            'collaterals.*.serial_number' => 'nullable|string|max:80',
        ]);

        $member->update(collect($data)->except(['referees', 'collaterals'])->all());
        if (array_key_exists('referees', $data)) {
            $member->referees()->delete();
            foreach ($data['referees'] as $ref) {
                MemberReferee::create([
                    'member_id' => $member->id,
                    'full_name' => trim($ref['full_name']),
                    'phone' => trim($ref['phone']),
                    'relationship' => trim($ref['relationship'] ?? '') ?: null,
                ]);
            }
        }
        if (array_key_exists('collaterals', $data)) {
            $member->collaterals()->delete();
            foreach ($data['collaterals'] as $col) {
                MemberCollateral::create([
                    'member_id' => $member->id,
                    'item_name' => trim($col['item_name']),
                    'estimated_value' => $col['estimated_value'] ?? null,
                    'serial_number' => trim($col['serial_number'] ?? '') ?: null,
                ]);
            }
        }
        AuditService::log($request->user(), 'UPDATE_MEMBER', $member->full_name, 'Updated via API');
        return response()->json($this->serializeMember($member->fresh(['referees', 'collaterals'])));
    }

    public function templates(): JsonResponse
    {
        return response()->json(CommunicationTemplate::orderBy('id')->get());
    }

    public function saveTemplate(Request $request): JsonResponse
    {
        $allowedCategories = implode(',', array_keys(CommunicationTemplate::CATEGORIES));
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'category' => "required|in:{$allowedCategories}",
            'email_subject' => 'nullable|string|max:200',
            'email_subject_sw' => 'nullable|string|max:200',
            'email_body' => 'nullable|string',
            'email_body_sw' => 'nullable|string',
            'sms_body' => 'nullable|string|max:1000',
            'sms_body_sw' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $baseKey = Str::slug($data['name'], '_') ?: 'tpl';
        $key = $baseKey;
        $suffix = 1;
        while (CommunicationTemplate::where('key', $key)->exists()) {
            $key = $baseKey . '_' . (++$suffix);
        }

        $tpl = CommunicationTemplate::create(array_merge($data, ['key' => $key]));
        AuditService::log($request->user(), 'CREATE_TEMPLATE', $tpl->name, 'Created via API');
        return response()->json($tpl, 201);
    }

    public function updateTemplate(Request $request, CommunicationTemplate $template): JsonResponse
    {
        $allowedCategories = implode(',', array_keys(CommunicationTemplate::CATEGORIES));
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'category' => "required|in:{$allowedCategories}",
            'email_subject' => 'nullable|string|max:200',
            'email_subject_sw' => 'nullable|string|max:200',
            'email_body' => 'nullable|string',
            'email_body_sw' => 'nullable|string',
            'sms_body' => 'nullable|string|max:1000',
            'sms_body_sw' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $template->update($data);
        AuditService::log($request->user(), 'UPDATE_TEMPLATE', $template->name, 'Updated via API');
        return response()->json($template->fresh());
    }

    public function deleteTemplate(Request $request, CommunicationTemplate $template): JsonResponse
    {
        if (in_array($template->key, ['welcome', 'next_payment', 'overdue', 'payment', 'full_payment', 'contact_marketing'], true)) {
            return response()->json(['message' => 'Core system template cannot be deleted.'], 422);
        }
        $name = $template->name;
        $template->delete();
        AuditService::log($request->user(), 'DELETE_TEMPLATE', $name, 'Deleted via API');
        return response()->json(['deleted' => true]);
    }

    public function sendContactMessage(Request $request, NotificationService $notifications): JsonResponse
    {
        $data = $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:members,id',
            'channel' => 'required|in:sms,email,both',
            'email_subject' => 'nullable|string|max:200',
            'email_body' => 'nullable|string',
            'sms_body' => 'nullable|string|max:1000',
        ]);

        $contacts = Member::with('group')->whereIn('id', $data['member_ids'])->get();
        $smsSent = 0;
        $emailSent = 0;

        foreach ($contacts as $contact) {
            $vars = [
                '{name}' => $contact->full_name,
                '{group}' => $contact->group?->name ?? 'NODA ML',
                '{company}' => 'NODA ML',
            ];
            if (in_array($data['channel'], ['sms', 'both'], true) && ! empty($data['sms_body']) && ! empty($contact->phone)) {
                if ($notifications->sendSms($contact->phone, CommunicationTemplate::renderText($data['sms_body'], $vars))) {
                    $smsSent++;
                }
            }
            if (in_array($data['channel'], ['email', 'both'], true) && ! empty($data['email_subject']) && ! empty($data['email_body']) && ! empty($contact->email)) {
                if ($notifications->sendEmail(
                    $contact->email,
                    CommunicationTemplate::renderText($data['email_subject'], $vars),
                    CommunicationTemplate::renderText($data['email_body'], $vars)
                )) {
                    $emailSent++;
                }
            }
        }

        AuditService::log($request->user(), 'SEND_CONTACT_MESSAGE', $contacts->count() . ' contact(s)', "API SMS={$smsSent}, Email={$emailSent}");
        return response()->json(['sms_sent' => $smsSent, 'email_sent' => $emailSent]);
    }

    public function reports(Request $request, ReportService $reports): JsonResponse
    {
        return response()->json(
            $reports->buildAll(
                $request->query('start_date'),
                $request->query('end_date'),
                $request->query('period')
            )
        );
    }

    public function saveBudget(Request $request): JsonResponse
    {
        $allowedCategories = implode(',', Budget::CATEGORIES);
        $data = $request->validate([
            'period' => 'required|regex:/^\d{4}-\d{2}$/',
            'category' => "required|in:{$allowedCategories}",
            'budgeted_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $budget = Budget::updateOrCreate(
            ['period' => $data['period'], 'category' => $data['category']],
            [
                'budgeted_amount' => (float) $data['budgeted_amount'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]
        );

        AuditService::log($request->user(), 'SAVE_BUDGET', "{$data['period']} - {$data['category']}", 'Saved via API');
        return response()->json($budget, 201);
    }

    public function saveFinancialEntry(Request $request): JsonResponse
    {
        $allowedTypes = implode(',', array_keys(FinancialEntry::TYPES));
        $data = $request->validate([
            'type' => "required|in:{$allowedTypes}",
            'category' => 'required|string|min:2|max:100',
            'amount' => 'required|numeric|min:1',
            'entry_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $entry = FinancialEntry::create(array_merge($data, ['recorded_by' => $request->user()->id]));
        AuditService::log($request->user(), 'RECORD_FINANCIAL_ENTRY', $entry->category, 'Saved via API');
        return response()->json($entry, 201);
    }

    public function createLoan(Request $request, LoanCalculatorService $calc, NotificationService $notifications): JsonResponse
    {
        $settings = AdminSetting::current();
        $data = $request->validate([
            'member_id' => 'required|exists:members,id',
            'principal' => "required|numeric|min:{$settings->min_loan_amount}|max:{$settings->max_loan_amount}",
            'term_weeks' => "required|integer|min:1|max:{$settings->max_weeks}",
            'rest_days' => 'nullable|integer|min:1',
        ]);

        $member = Member::findOrFail($data['member_id']);
        $total = $calc->calculateTotalRepayable($data['principal'], $settings->load_rate);
        $weekly = $calc->calculateWeeklyAmount($total, $data['term_weeks']);
        $firstReturn = now()->addDays((int) ($data['rest_days'] ?? 7));

        $loan = Loan::create([
            'member_id' => $member->id,
            'group_id' => $member->group_id,
            'principal' => $calc->round2($data['principal']),
            'term_weeks' => $data['term_weeks'],
            'load_rate' => $settings->load_rate,
            'total_repayable' => $total,
            'weekly_amount' => $weekly,
            'start_date' => now(),
            'first_return_date' => $firstReturn,
            'status' => 'active',
        ]);

        $metrics = $calc->computeMetrics($loan, now(), $settings);
        $notifications->scheduleRemindersForLoan($loan, $member, $metrics, $settings);
        AuditService::log($request->user(), 'CREATE_LOAN', "Loan #{$loan->id}", 'Approved via API');

        return response()->json($loan, 201);
    }

    public function recordPayment(Request $request, LoanCalculatorService $calc): JsonResponse
    {
        $data = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'week_number' => 'required|integer|min:1',
        ]);

        $loan = Loan::findOrFail($data['loan_id']);
        $payment = Payment::create([
            'loan_id' => $loan->id,
            'amount' => $calc->round2($data['amount']),
            'paid_at' => now(),
            'week_number' => $data['week_number'],
            'recorded_by' => $request->user()->id,
        ]);

        $loan->update(['status' => $calc->resolveStatus($loan->fresh('payments'))]);
        AuditService::log($request->user(), 'RECORD_PAYMENT', "Loan #{$loan->id}", "Paid {$data['amount']} via API");

        return response()->json($payment, 201);
    }

    public function runPenaltySweep(Request $request, PenaltyEngineService $engine): JsonResponse
    {
        $count = $engine->runDailySweep();
        AuditService::log($request->user(), 'PENALTY_SWEEP', "{$count} penalty(ies)", 'Triggered via API');
        return response()->json(['applied_count' => $count]);
    }

    public function vodaLipaCallback(Request $request, MpesaReconciliationService $mpesa): JsonResponse
    {
        $secret = env('MPESA_WEBHOOK_SECRET');
        if (! empty($secret) && ! hash_equals($secret, (string) $request->header('X-Mpesa-Signature'))) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        $data = $request->validate([
            'receipt_number' => 'required|string',
            'msisdn' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'lipa_namba' => 'nullable|string',
            'account_reference' => 'nullable|string',
        ]);

        $tx = $mpesa->processCallback($data, $request->user());
        return response()->json($tx, 200);
    }
}
