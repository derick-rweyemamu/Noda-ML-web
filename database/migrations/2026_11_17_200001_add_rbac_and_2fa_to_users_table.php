<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('password'); // staff | member
            $table->unsignedBigInteger('member_id')->nullable()->after('role');
            $table->string('locale', 5)->default('en')->after('member_id');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('two_factor_email_token')->nullable();
            $table->timestamp('two_factor_email_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'member_id', 'locale',
                'two_factor_secret', 'two_factor_recovery_codes',
                'two_factor_confirmed_at', 'two_factor_email_token',
                'two_factor_email_expires_at',
            ]);
        });
    }
};
