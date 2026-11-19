<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('penalty_balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->decimal('penalty_balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->decimal('principal', 15, 2);
            $table->unsignedInteger('term_weeks');
            $table->decimal('load_rate', 6, 4)->default(0.17);
            $table->decimal('total_repayable', 15, 2);
            $table->decimal('weekly_amount', 15, 2);
            $table->timestamp('start_date');
            $table->timestamp('first_return_date');
            $table->string('status')->default('active'); // active | completed | overdue
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('paid_at');
            $table->unsignedInteger('week_number');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('triggered_by_loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->decimal('amount_per_member', 15, 2);
            $table->decimal('total_penalty', 15, 2);
            $table->timestamp('applied_at');
            $table->string('due_date_key')->index();
            $table->string('reason');
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('channel'); // sms | email
            $table->timestamp('scheduled_date');
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('pending'); // pending | sent | failed
            $table->text('message');
            $table->unsignedInteger('offset_days')->default(0);
            $table->boolean('is_penalty_notice')->default(false);
            $table->timestamps();
        });

        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->json('reminder_lead_days');
            $table->json('sms_enabled_by_offset');
            $table->json('email_enabled_by_offset');
            $table->decimal('penalty_amount_per_member', 15, 2)->default(10000);
            $table->string('penalty_rule')->default('one_time_per_missed_due_date');
            $table->unsignedTinyInteger('penalty_cutoff_hour')->default(10);
            $table->unsignedTinyInteger('penalty_cutoff_minute')->default(30);
            $table->decimal('min_loan_amount', 15, 2)->default(100000);
            $table->decimal('max_loan_amount', 15, 2)->default(3000000);
            $table->unsignedInteger('max_weeks')->default(26);
            $table->decimal('load_rate', 6, 4)->default(0.17);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('actor_role');
            $table->string('action');
            $table->string('target');
            $table->text('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('penalties');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('members');
        Schema::dropIfExists('groups');
    }
};
