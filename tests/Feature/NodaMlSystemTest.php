<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\Group;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Services\LoanCalculatorService;
use App\Services\PenaltyEngineService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NodaMlSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_totp_2fa_and_email_recovery_reset(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $code = $totp->getCode($secret);

        $this->assertTrue($totp->verifyCode($secret, $code));
        $this->assertFalse($totp->verifyCode($secret, '000000'));

        $staff = User::create([
            'name' => 'Staff Test',
            'email' => 'staff.test@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
            'two_factor_email_token' => hash('sha256', 'recover-token-123'),
            'two_factor_email_expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->get(route('two-factor.recover', [
            'token' => 'recover-token-123',
            'email' => $staff->email,
        ]));

        $response->assertRedirect(route('two-factor.challenge'));
        $this->assertFalse($staff->fresh()->hasTwoFactorEnabled());
    }

    public function test_loan_calculator_last_week_remainder_and_1030_cutoff(): void
    {
        $calc = app(LoanCalculatorService::class);
        $firstReturn = now()->startOfDay()->addDays(14);
        $schedule = $calc->buildSchedule(100000, 0.17, 7, $firstReturn);

        $this->assertCount(7, $schedule);
        $sum = array_sum(array_column($schedule, 'amount'));
        $this->assertEquals(117000.0, round($sum, 2));

        $group = Group::create(['name' => 'Test Group']);
        $member = Member::create(['group_id' => $group->id, 'full_name' => 'Alice', 'phone' => '+255700000001']);
        $loan = Loan::create([
            'member_id' => $member->id,
            'group_id' => $group->id,
            'principal' => 100000,
            'term_weeks' => 4,
            'load_rate' => 0.17,
            'total_repayable' => 117000,
            'weekly_amount' => 29250,
            'start_date' => now()->startOfDay(),
            'first_return_date' => $firstReturn,
            'status' => 'active',
        ]);

        $beforeCutoff = $firstReturn->copy()->setTime(10, 29, 0);
        $afterCutoff = $firstReturn->copy()->setTime(10, 31, 0);

        $this->assertFalse($calc->computeMetrics($loan, $beforeCutoff)['is_overdue']);
        $this->assertTrue($calc->computeMetrics($loan, $afterCutoff)['is_overdue']);
    }

    public function test_penalty_sweep_is_idempotent_and_charges_entire_group(): void
    {
        AdminSetting::current();
        $group = Group::create(['name' => 'Group A']);
        $m1 = Member::create(['group_id' => $group->id, 'full_name' => 'M1', 'phone' => '+255700000011']);
        $m2 = Member::create(['group_id' => $group->id, 'full_name' => 'M2', 'phone' => '+255700000012']);

        $due = now()->subDay()->setTime(10, 0, 0);
        Loan::create([
            'member_id' => $m1->id,
            'group_id' => $group->id,
            'principal' => 100000,
            'term_weeks' => 4,
            'load_rate' => 0.17,
            'total_repayable' => 117000,
            'weekly_amount' => 29250,
            'start_date' => $due->copy()->subDays(7),
            'first_return_date' => $due,
            'status' => 'active',
        ]);

        $engine = app(PenaltyEngineService::class);
        $this->assertEquals(1, $engine->runDailySweep(now()));
        $this->assertEquals(0, $engine->runDailySweep(now())); // Idempotent!

        $this->assertEquals(20000.0, (float) $group->fresh()->penalty_balance);
        $this->assertEquals(10000.0, (float) $m1->fresh()->penalty_balance);
        $this->assertEquals(10000.0, (float) $m2->fresh()->penalty_balance);
    }

    public function test_member_role_cannot_access_staff_routes_or_api_writes(): void
    {
        $group = Group::create(['name' => 'G1']);
        $m = Member::create(['group_id' => $group->id, 'full_name' => 'Member One', 'phone' => '+255700000021']);
        $memberUser = User::create([
            'name' => 'Member One',
            'email' => 'm1@nodaml.co.tz',
            'password' => Hash::make('member123'),
            'role' => 'member',
            'member_id' => $m->id,
        ]);

        $this->actingAs($memberUser)->get(route('staff.groups'))->assertStatus(403);
        $this->actingAs($memberUser, 'sanctum')->postJson('/api/v1/loans', [])->assertStatus(403);
    }

    public function test_language_switch_supports_english_and_swahili(): void
    {
        $this->get(route('locale.switch', 'sw'))->assertRedirect();
        $this->get('/')->assertSee('Mfumo wa Mikopo ya Vikundi');
    }

    public function test_voda_lipa_namba_callback_auto_reconciles_and_is_idempotent(): void
    {
        AdminSetting::current();
        $group = Group::create(['name' => 'Voda Group']);
        $member = Member::create(['group_id' => $group->id, 'full_name' => 'Amina Voda', 'phone' => '+255712100099']);
        $loan = Loan::create([
            'member_id' => $member->id,
            'group_id' => $group->id,
            'principal' => 100000,
            'term_weeks' => 4,
            'load_rate' => 0.17,
            'total_repayable' => 117000,
            'weekly_amount' => 29250,
            'start_date' => now()->subDays(7),
            'first_return_date' => now(),
            'status' => 'active',
        ]);

        $payload = [
            'receipt_number' => 'VODA998877',
            'msisdn' => '0712100099',
            'amount' => 29250,
        ];

        $this->postJson('/api/v1/mpesa/voda-lipa-callback', $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'reconciled')
            ->assertJsonPath('loan_id', $loan->id);

        // Duplicate callback with same receipt must be idempotent
        $this->postJson('/api/v1/mpesa/voda-lipa-callback', $payload)->assertStatus(200);
        $this->assertEquals(1, $loan->payments()->count());
        $this->assertEquals(29250.0, (float) $loan->payments()->sum('amount'));
    }

    public function test_manual_penalty_blocked_when_all_members_paid_or_before_cutoff_time(): void
    {
        AdminSetting::current();
        $staff = User::create([
            'name' => 'Staff Guard',
            'email' => 'guard@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
        ]);
        $group = Group::create(['name' => 'Paid Group']);
        $m = Member::create(['group_id' => $group->id, 'full_name' => 'Paid Member', 'phone' => '+255700000088']);

        $dueToday = now()->startOfDay()->addDays(2);
        $loan = Loan::create([
            'member_id' => $m->id,
            'group_id' => $group->id,
            'principal' => 100000,
            'term_weeks' => 4,
            'load_rate' => 0.17,
            'total_repayable' => 117000,
            'weekly_amount' => 29250,
            'start_date' => now()->subDays(5),
            'first_return_date' => $dueToday,
            'status' => 'active',
        ]);

        $engine = app(PenaltyEngineService::class);

        // 1. Before 10:30 cutoff on due date -> blocked
        try {
            $engine->applyManualPenalty($staff, $group, $m, $loan, 'Too early', $dueToday->copy()->setTime(10, 15, 0));
            $this->fail('Expected DomainException before cutoff time.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('10:30 cutoff', $e->getMessage());
        }

        // 2. When all members in group are paid -> blocked
        $loan->update(['status' => 'completed']);
        try {
            $engine->applyManualPenalty($staff, $group, $m, $loan, 'All paid', $dueToday->copy()->setTime(11, 0, 0));
            $this->fail('Expected DomainException when all members are paid.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('all members', $e->getMessage());
        }
    }

    public function test_spatie_role_permissions_protect_routes_and_actions(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $cashier = User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);
        $cashier->assignRole('Cashier');

        $this->actingAs($cashier)
            ->withSession(['two_factor_passed' => true])
            ->get(route('staff.payments'))
            ->assertStatus(200);

        $this->actingAs($cashier)
            ->withSession(['two_factor_passed' => true])
            ->get(route('staff.settings'))
            ->assertStatus(403);

        $this->actingAs($cashier, 'sanctum')
            ->postJson('/api/v1/loans', [])
            ->assertStatus(403);
    }

    public function test_2fa_persists_across_logout_and_relogin(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();

        $staff = User::create([
            'name' => 'Persistent Staff',
            'email' => 'persist@nodaml.co.tz',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
        $staff->assignRole('Admin');

        // 1. Enroll 2FA
        $this->actingAs($staff)->withSession(['two_factor_pending_secret' => $secret]);
        \Livewire\Livewire::test(\App\Livewire\Auth\TwoFactorChallenge::class)
            ->set('code', $totp->getCode($secret))
            ->call('confirmSetup')
            ->assertSet('showRecoveryCodes', true);

        $this->assertTrue($staff->fresh()->hasTwoFactorEnabled());

        // 2. Log out
        $this->post(route('logout'))->assertRedirect(route('login'));

        // 3. Log back in -> must ask for 2FA code, NOT "Set up 2FA"
        \Livewire\Livewire::test(\App\Livewire\Auth\LoginPage::class)
            ->set('email', 'persist@nodaml.co.tz')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('two-factor.challenge'));

        $this->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertDontSee('Set up 2FA');

        // 4. Verify 6-digit code -> redirects to dashboard
        \Livewire\Livewire::test(\App\Livewire\Auth\TwoFactorChallenge::class)
            ->set('code', $totp->getCode($secret))
            ->call('verify')
            ->assertRedirect(route('staff.groups'));
    }

    public function test_phone_e164_normalization_and_country_flag(): void
    {
        $this->seed(\Database\Seeders\LocationSeeder::class);
        $tz = \App\Models\Country::where('iso2', 'TZ')->first();

        $this->assertEquals('🇹🇿', $tz->flag);
        $this->assertEquals('+255712100001', \App\Services\PhoneHelper::formatE164('+255', '0712 100 001'));
        $this->assertTrue(\App\Services\PhoneHelper::isValidE164('+255712100001'));

        [$code, $local] = \App\Services\PhoneHelper::splitE164('+255712100001');
        $this->assertEquals('+255', $code);
        $this->assertEquals('712100001', $local);
    }

    public function test_roles_crud_protects_system_roles_and_blocks_super_admin_injection(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.roles@nodaml.co.tz',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
        $admin->assignRole('Admin');

        $this->actingAs($admin)->withSession(['two_factor_passed' => true]);

        // Super Admin is hidden from Roles page
        $this->get(route('staff.settings', ['tab' => 'roles']))
            ->assertOk()
            ->assertSee('Director')
            ->assertDontSee('Super Admin');

        // System role (Director) cannot be deleted
        $director = \Spatie\Permission\Models\Role::where('name', 'Director')->first();
        \Livewire\Livewire::test(\App\Livewire\Staff\SettingsManager::class)
            ->call('deleteRole', $director->id)
            ->assertSet('errorMessage', "System role 'Director' is protected and cannot be deleted.");
        $this->assertNotNull(\Spatie\Permission\Models\Role::where('name', 'Director')->first());

        // Custom role can be created and assigned permissions
        \Livewire\Livewire::test(\App\Livewire\Staff\SettingsManager::class)
            ->set('roleName', 'Auditor')
            ->set('rolePermissions', ['groups.view', 'loans.view'])
            ->call('saveRole')
            ->assertHasNoErrors();
        $this->assertTrue(\Spatie\Permission\Models\Role::where('name', 'Auditor')->first()->hasPermissionTo('loans.view'));

        // Super Admin cannot be injected when creating staff
        \Livewire\Livewire::test(\App\Livewire\Staff\SettingsManager::class)
            ->set('newStaffName', 'Hack User')
            ->set('newStaffEmail', 'hack@nodaml.co.tz')
            ->set('newStaffPassword', 'secret123')
            ->set('newStaffCode', 'NODA-STF-999')
            ->set('newStaffRole', 'Super Admin')
            ->call('createStaffUser')
            ->assertHasErrors(['newStaffRole']);
    }

    public function test_loan_group_officer_assignment_and_auto_assignment(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.groups@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
        ]);
        $admin->assignRole('Admin');

        $officerA = User::create([
            'name' => 'Officer Alpha',
            'email' => 'alpha@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
        ]);
        $officerA->assignRole('Loan Officer');

        $officerB = User::create([
            'name' => 'Officer Beta',
            'email' => 'beta@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
        ]);
        $officerB->assignRole('Loan Officer');

        // 1. Loan Officer creates a group -> automatically belongs to him/her
        $this->actingAs($officerA)->withSession(['two_factor_passed' => true]);
        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->set('newGroupName', 'Alpha Group')
            ->set('newGroupLoanOfficerId', $officerB->id) // attempted override ignored
            ->set('newMemberRows', [
                [
                    'full_name' => 'Member One',
                    'phone_code' => '+255',
                    'phone' => '712900001',
                    'email' => '',
                    'referees' => [
                        ['full_name' => 'Ref One', 'phone_code' => '+255', 'phone' => '712990001', 'relationship' => 'Spouse'],
                    ],
                ],
            ])
            ->call('createGroup')
            ->assertHasNoErrors();

        $alphaGroup = Group::where('name', 'Alpha Group')->firstOrFail();
        $this->assertEquals($officerA->id, $alphaGroup->loan_officer_id);

        // 2. Admin can create a group assigned to a chosen officer and reassign an existing group
        $this->actingAs($admin)->withSession(['two_factor_passed' => true]);
        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->set('newGroupName', 'Beta Group')
            ->set('newGroupLoanOfficerId', $officerB->id)
            ->set('newMemberRows', [
                [
                    'full_name' => 'Member Two',
                    'phone_code' => '+255',
                    'phone' => '712900002',
                    'email' => '',
                    'referees' => [
                        ['full_name' => 'Ref Two', 'phone_code' => '+255', 'phone' => '712990002', 'relationship' => 'Sibling'],
                    ],
                ],
            ])
            ->call('createGroup')
            ->assertHasNoErrors();

        $betaGroup = Group::where('name', 'Beta Group')->firstOrFail();
        $this->assertEquals($officerB->id, $betaGroup->loan_officer_id);

        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->call('openAssignOfficer', $alphaGroup->id)
            ->set('assignOfficerUserId', $officerB->id)
            ->call('saveAssignOfficer')
            ->assertHasNoErrors();

        $this->assertEquals($officerB->id, $alphaGroup->fresh()->loan_officer_id);

        // 3. Duplicate group names (including case-insensitive) are rejected
        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->set('newGroupName', 'alpha group')
            ->set('newMemberRows', [
                [
                    'full_name' => 'Member Three',
                    'phone_code' => '+255',
                    'phone' => '712900003',
                    'email' => '',
                    'referees' => [
                        ['full_name' => 'Ref Three', 'phone_code' => '+255', 'phone' => '712990003', 'relationship' => 'Friend'],
                    ],
                ],
            ])
            ->call('createGroup')
            ->assertHasErrors(['newGroupName']);

        // 4. Member KYC (ID type, unique ID number, ID image, profile photo, and at least one referee)
        \Illuminate\Support\Facades\Storage::fake('public');
        $fakeIdImage = \Illuminate\Http\UploadedFile::fake()->image('nida.jpg');
        $fakePhoto = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg');

        // Member without referee is rejected
        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->call('openAddMember', $alphaGroup->id)
            ->set('addMemberName', 'No Referee Member')
            ->set('addMemberPhone', '712900008')
            ->set('addMemberReferees', [])
            ->call('saveAddMember')
            ->assertHasErrors(['addMemberReferees']);

        // Member with more than 2 referees is rejected
        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->call('openAddMember', $alphaGroup->id)
            ->set('addMemberName', 'Too Many Refs')
            ->set('addMemberPhone', '712900007')
            ->set('addMemberReferees', [
                ['full_name' => 'R1', 'phone_code' => '+255', 'phone' => '712990011', 'relationship' => 'Friend'],
                ['full_name' => 'R2', 'phone_code' => '+255', 'phone' => '712990012', 'relationship' => 'Parent'],
                ['full_name' => 'R3', 'phone_code' => '+255', 'phone' => '712990013', 'relationship' => 'Sibling'],
            ])
            ->call('saveAddMember')
            ->assertHasErrors(['addMemberReferees']);

        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->call('openAddMember', $alphaGroup->id)
            ->set('addMemberName', 'KYC Member')
            ->set('addMemberPhone', '712900009')
            ->set('addMemberGender', 'female')
            ->set('addMemberDob', now()->subYears(30)->format('Y-m-d'))
            ->set('addMemberIdType', 'NIDA')
            ->set('addMemberIdNumber', '19900101-12345-00001-01')
            ->set('addMemberIdImage', $fakeIdImage)
            ->set('addMemberPhoto', $fakePhoto)
            ->set('addMemberReferees', [
                ['full_name' => 'Asha Juma', 'phone_code' => '+255', 'phone' => '712990009', 'relationship' => 'Parent'],
            ])
            ->set('addMemberCollaterals', [
                ['item_name' => 'Motorcycle TVS', 'estimated_value' => '1800000', 'serial_number' => 'MC-9921'],
            ])
            ->call('saveAddMember')
            ->assertHasNoErrors();

        $kycMember = Member::where('id_number', '19900101-12345-00001-01')->firstOrFail();
        $this->assertEquals('female', $kycMember->gender);
        $this->assertEquals(30, $kycMember->age);
        $this->assertEquals('NIDA', $kycMember->id_type);
        $this->assertNotNull($kycMember->id_image_path);
        $this->assertNotNull($kycMember->photo_path);
        $this->assertCount(1, $kycMember->referees);
        $this->assertEquals('Asha Juma', $kycMember->referees->first()->full_name);
        $this->assertCount(1, $kycMember->collaterals);
        $this->assertEquals('Motorcycle TVS', $kycMember->collaterals->first()->item_name);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($kycMember->id_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($kycMember->photo_path);

        // Duplicate ID number is rejected
        \Livewire\Livewire::test(\App\Livewire\Staff\GroupsManager::class)
            ->call('openAddMember', $alphaGroup->id)
            ->set('addMemberName', 'Duplicate ID Member')
            ->set('addMemberPhone', '712900010')
            ->set('addMemberIdType', 'NIDA')
            ->set('addMemberIdNumber', '19900101-12345-00001-01')
            ->set('addMemberReferees', [
                ['full_name' => 'Ref Dup', 'phone_code' => '+255', 'phone' => '712990010', 'relationship' => 'Friend'],
            ])
            ->call('saveAddMember')
            ->assertHasErrors(['addMemberIdNumber']);
    }

    public function test_contacts_and_communication_templates_management(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\CommunicationTemplateSeeder::class);

        $admin = User::create([
            'name' => 'Admin Templates',
            'email' => 'admin.templates@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
        ]);
        $admin->assignRole('Admin');

        $group = Group::create(['name' => 'Contact Group']);
        $member = Member::create([
            'group_id' => $group->id,
            'full_name' => 'Neema Contact',
            'phone' => '+255712888001',
            'email' => 'neema.contact@nodaml.co.tz',
        ]);

        $this->actingAs($admin)->withSession(['two_factor_passed' => true]);

        // Contacts page shows member full name, phone, and email
        $this->get(route('staff.contacts'))
            ->assertOk()
            ->assertSee('Neema Contact')
            ->assertSee('+255712888001')
            ->assertSee('neema.contact@nodaml.co.tz');

        // Can add a new contact from ContactsManager
        \Livewire\Livewire::test(\App\Livewire\Staff\ContactsManager::class)
            ->call('openCreateModal')
            ->set('groupId', $group->id)
            ->set('fullName', 'Juma Contact')
            ->set('phone', '712888002')
            ->set('email', 'juma.contact@nodaml.co.tz')
            ->call('saveContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('members', [
            'full_name' => 'Juma Contact',
            'phone' => '+255712888002',
            'email' => 'juma.contact@nodaml.co.tz',
        ]);

        // Templates page shows all 6 core communication templates (including Contact / Marketing)
        $this->get(route('staff.templates'))
            ->assertOk()
            ->assertSee('Welcome Message')
            ->assertSee('Next Payment Reminder')
            ->assertSee('Overdue Payment Notice')
            ->assertSee('Payment Received')
            ->assertSee('Full Payment / Loan Settled')
            ->assertSee('Contact / Marketing Outreach');

        // Can send marketing/outreach message to contacts using the Contact / Marketing template
        \Livewire\Livewire::test(\App\Livewire\Staff\ContactsManager::class)
            ->call('openMessageModal', $member->id)
            ->set('messageChannel', 'both')
            ->call('sendMessages')
            ->assertHasNoErrors();

        // Can create a custom bilingual (EN & SW) Email & SMS template
        \Livewire\Livewire::test(\App\Livewire\Staff\TemplatesManager::class)
            ->call('openCreateModal')
            ->set('name', 'Holiday Greeting')
            ->set('category', 'custom')
            ->set('emailSubject', 'Happy Holidays {name}')
            ->set('emailSubjectSw', 'Sikukuu Njema {name}')
            ->set('emailBody', 'Warm wishes from {company}')
            ->set('emailBodySw', 'Salamu za sikukuu kutoka {company}')
            ->set('smsBody', 'Happy Holidays {name} from {company}!')
            ->set('smsBodySw', 'Sikukuu Njema {name} kutoka {company}!')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $holidayTpl = \App\Models\CommunicationTemplate::where('name', 'Holiday Greeting')->firstOrFail();
        $this->assertEquals('Sikukuu Njema {name}', $holidayTpl->forLocale('sw')['email_subject']);
        $this->assertEquals('Happy Holidays {name}', $holidayTpl->forLocale('en')['email_subject']);
    }

    public function test_executive_reports_financial_entries_and_budgets(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $director = User::create([
            'name' => 'Director Reports',
            'email' => 'director.reports@nodaml.co.tz',
            'password' => Hash::make('secret123'),
            'role' => 'staff',
        ]);
        $director->assignRole('Director');

        $this->actingAs($director)->withSession(['two_factor_passed' => true]);

        $this->get(route('staff.reports'))
            ->assertOk()
            ->assertSee('Financial Statements')
            ->assertSee('Income Statement')
            ->assertSee('Balance Sheet');

        \Livewire\Livewire::test(\App\Livewire\Staff\ReportsManager::class)
            ->call('openEntryModal')
            ->set('entryType', 'expense')
            ->set('entryCategory', 'Office Rent')
            ->set('entryAmount', '450000')
            ->set('entryDate', now()->toDateString())
            ->call('saveEntry')
            ->assertHasNoErrors()
            ->call('openBudgetModal')
            ->set('budgetPeriod', now()->format('Y-m'))
            ->set('budgetCategory', 'Operating Expenses')
            ->set('budgetAmount', '600000')
            ->call('saveBudget')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('financial_entries', ['category' => 'Office Rent', 'amount' => 450000]);
        $this->assertDatabaseHas('budgets', ['period' => now()->format('Y-m'), 'category' => 'Operating Expenses', 'budgeted_amount' => 600000]);

        $token = $director->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/reports')
            ->assertOk()
            ->assertJsonPath('financial_statements.income_statement.operating_expenses', 450000);
    }
}
