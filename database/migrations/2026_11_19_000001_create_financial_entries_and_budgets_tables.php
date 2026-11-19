<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // expense | asset | liability | equity | other_income
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->date('entry_date');
            $table->string('description')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('period'); // e.g. 2026-11
            $table->string('category'); // Disbursements | Collections | Interest & Fee Income | Operating Expenses
            $table->decimal('budgeted_amount', 15, 2);
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['period', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('financial_entries');
    }
};
