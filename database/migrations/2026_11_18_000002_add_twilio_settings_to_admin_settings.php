<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->boolean('twilio_enabled')->default(false)->after('mpesa_auto_reconcile');
            $table->string('twilio_account_sid')->nullable()->after('twilio_enabled');
            $table->text('twilio_auth_token')->nullable()->after('twilio_account_sid');
            $table->string('twilio_from_number')->nullable()->after('twilio_auth_token');
        });
    }

    public function down(): void
    {
        Schema::table('admin_settings', function (Blueprint $table) {
            $table->dropColumn([
                'twilio_enabled',
                'twilio_account_sid',
                'twilio_auth_token',
                'twilio_from_number',
            ]);
        });
    }
};
