<?php

namespace Database\Seeders;

use App\Models\AdminSetting;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Group;
use App\Models\JobTitle;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Penalty;
use App\Models\Reminder;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\LoanCalculatorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(LocationSeeder::class);
        $this->call(CommunicationTemplateSeeder::class);

        $calc = app(LoanCalculatorService::class);
        AdminSetting::current();

        $umoja = Group::create(['name' => 'Umoja Women Group', 'penalty_balance' => 30000]);
        $maendeleo = Group::create(['name' => 'Maendeleo Youth Group', 'penalty_balance' => 0]);

        $amina = Member::create([
            'group_id' => $umoja->id,
            'full_name' => 'Amina Hassan',
            'phone' => '+255712100001',
            'email' => 'amina@nodaml.co.tz',
            'penalty_balance' => 10000,
        ]);
        $grace = Member::create([
            'group_id' => $umoja->id,
            'full_name' => 'Grace Mwangi',
            'phone' => '+255712100002',
            'email' => 'grace@nodaml.co.tz',
            'penalty_balance' => 10000,
        ]);
        Member::create([
            'group_id' => $umoja->id,
            'full_name' => 'Fatuma Ally',
            'phone' => '+255712100003',
            'email' => null,
            'penalty_balance' => 10000,
        ]);
        $juma = Member::create([
            'group_id' => $maendeleo->id,
            'full_name' => 'Juma Bakari',
            'phone' => '+255712200001',
            'email' => 'juma@nodaml.co.tz',
            'penalty_balance' => 0,
        ]);
        $daniel = Member::create([
            'group_id' => $maendeleo->id,
            'full_name' => 'Daniel Kilonzo',
            'phone' => '+255712200002',
            'email' => 'daniel@nodaml.co.tz',
            'penalty_balance' => 0,
        ]);

        $mainBranch = Branch::firstOrCreate(['name' => 'Main Branch'], ['code' => 'HQ-01', 'location' => 'Dar es Salaam']);
        Branch::firstOrCreate(['name' => 'Mwanza Branch'], ['code' => 'MWZ-02', 'location' => 'Mwanza']);

        $opsDept = Department::firstOrCreate(['name' => 'Operations']);
        $creditDept = Department::firstOrCreate(['name' => 'Credit']);
        Department::firstOrCreate(['name' => 'Finance']);

        $mgrTitle = JobTitle::firstOrCreate(['name' => 'Branch Manager']);
        $loTitle = JobTitle::firstOrCreate(['name' => 'Loan Officer']);
        JobTitle::firstOrCreate(['name' => 'Cashier']);

        $superAdmin = User::create([
            'name' => 'Derick Rweyemamu',
            'email' => 'rweyemamuderick@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'locale' => 'en',
        ]);
        $superAdmin->assignRole('Super Admin');
        StaffProfile::create([
            'user_id' => $superAdmin->id,
            'staff_code' => 'NODA-STF-001',
            'phone' => '+255712000001',
            'branch_id' => $mainBranch->id,
            'department_id' => $opsDept->id,
            'job_title_id' => $mgrTitle->id,
            'hire_date' => now()->subYear(),
            'status' => 'active',
        ]);

        $staff = User::create([
            'name' => 'Neema Mushi (Staff)',
            'email' => 'staff@nodaml.co.tz',
            'password' => Hash::make('admin123'),
            'role' => 'staff',
            'locale' => 'en',
        ]);
        $staff->assignRole('Admin');
        StaffProfile::create([
            'user_id' => $staff->id,
            'staff_code' => 'NODA-STF-002',
            'phone' => '+255712000002',
            'branch_id' => $mainBranch->id,
            'department_id' => $creditDept->id,
            'job_title_id' => $loTitle->id,
            'hire_date' => now()->subMonths(6),
            'status' => 'active',
        ]);

        $aminaUser = User::create([
            'name' => 'Amina Hassan',
            'email' => 'amina@nodaml.co.tz',
            'password' => Hash::make('member123'),
            'role' => 'member',
            'member_id' => $amina->id,
            'locale' => 'en',
        ]);
        $aminaUser->assignRole('Member');

        $jumaUser = User::create([
            'name' => 'Juma Bakari',
            'email' => 'juma@nodaml.co.tz',
            'password' => Hash::make('member123'),
            'role' => 'member',
            'member_id' => $juma->id,
            'locale' => 'sw',
        ]);
        $jumaUser->assignRole('Member');

        // Loan 1: Active on-time
        $l1Total = $calc->calculateTotalRepayable(1000000, 0.17);
        $l1Weekly = $calc->calculateWeeklyAmount($l1Total, 10);
        $loan1 = Loan::create([
            'member_id' => $amina->id,
            'group_id' => $umoja->id,
            'principal' => 1000000,
            'term_weeks' => 10,
            'load_rate' => 0.17,
            'total_repayable' => $l1Total,
            'weekly_amount' => $l1Weekly,
            'start_date' => now()->subDays(16),
            'first_return_date' => now()->subDays(9),
            'status' => 'active',
        ]);
        Payment::create(['loan_id' => $loan1->id, 'amount' => $l1Weekly, 'paid_at' => now()->subDays(9), 'week_number' => 1, 'recorded_by' => $staff->id]);
        Payment::create(['loan_id' => $loan1->id, 'amount' => $l1Weekly, 'paid_at' => now()->subDays(2), 'week_number' => 2, 'recorded_by' => $staff->id]);

        // Loan 2: Overdue
        $l2Total = $calc->calculateTotalRepayable(500000, 0.17);
        $l2Weekly = $calc->calculateWeeklyAmount($l2Total, 10);
        $loan2 = Loan::create([
            'member_id' => $grace->id,
            'group_id' => $umoja->id,
            'principal' => 500000,
            'term_weeks' => 10,
            'load_rate' => 0.17,
            'total_repayable' => $l2Total,
            'weekly_amount' => $l2Weekly,
            'start_date' => now()->subDays(23),
            'first_return_date' => now()->subDays(16),
            'status' => 'overdue',
        ]);
        Payment::create(['loan_id' => $loan2->id, 'amount' => $l2Weekly, 'paid_at' => now()->subDays(16), 'week_number' => 1, 'recorded_by' => $staff->id]);

        // Loan 3: Completed
        $l3Total = $calc->calculateTotalRepayable(400000, 0.17);
        $loan3 = Loan::create([
            'member_id' => $juma->id,
            'group_id' => $maendeleo->id,
            'principal' => 400000,
            'term_weeks' => 4,
            'load_rate' => 0.17,
            'total_repayable' => $l3Total,
            'weekly_amount' => $calc->calculateWeeklyAmount($l3Total, 4),
            'start_date' => now()->subDays(35),
            'first_return_date' => now()->subDays(28),
            'status' => 'completed',
        ]);
        Payment::create(['loan_id' => $loan3->id, 'amount' => $l3Total, 'paid_at' => now()->subDays(10), 'week_number' => 4, 'recorded_by' => $staff->id]);

        // Loan 4: Active
        $l4Total = $calc->calculateTotalRepayable(1500000, 0.17);
        Loan::create([
            'member_id' => $daniel->id,
            'group_id' => $maendeleo->id,
            'principal' => 1500000,
            'term_weeks' => 15,
            'load_rate' => 0.17,
            'total_repayable' => $l4Total,
            'weekly_amount' => $calc->calculateWeeklyAmount($l4Total, 15),
            'start_date' => now()->subDays(4),
            'first_return_date' => now()->addDays(3),
            'status' => 'active',
        ]);

        Penalty::create([
            'group_id' => $umoja->id,
            'triggered_by_loan_id' => $loan2->id,
            'member_id' => $grace->id,
            'amount_per_member' => 10000,
            'total_penalty' => 30000,
            'applied_at' => now()->subDays(8),
            'due_date_key' => "{$loan2->id}-week-2",
            'reason' => 'Late payment on Week 2 by Grace Mwangi',
        ]);

        Reminder::create([
            'loan_id' => $loan1->id,
            'member_id' => $amina->id,
            'channel' => 'sms',
            'scheduled_date' => now()->addDays(2),
            'status' => 'pending',
            'message' => "Hello Amina Hassan, reminder: TZS 117,000 is due soon. — NODA ML",
            'offset_days' => 3,
        ]);

        AuditLog::create([
            'actor_id' => $staff->id,
            'actor_name' => $staff->name,
            'actor_role' => 'staff',
            'action' => 'SEED_SYSTEM',
            'target' => 'NODA ML',
            'details' => 'Initialized 2 groups, 5 members, 4 loans, penalties, and settings.',
        ]);
    }
}
