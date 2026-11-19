<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->string('voda_lipa_namba')->default('55443322')->after('load_rate');
            $table->string('voda_business_name')->default('NODA ML MICROFINANCE')->after('voda_lipa_namba');
            $table->boolean('mpesa_auto_reconcile')->default(true)->after('voda_business_name');
        });

        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('msisdn');
            $table->string('lipa_namba');
            $table->decimal('amount', 15, 2);
            $table->string('account_reference')->nullable();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('status')->default('unmatched'); // reconciled | unmatched
            $table->json('raw_payload')->nullable();
            $table->timestamp('transacted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn(['voda_lipa_namba', 'voda_business_name', 'mpesa_auto_reconcile']);
        });
    }
};
