<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('communication_templates', function (Blueprint $table) {
            $table->string('email_subject_sw')->nullable()->after('email_subject');
            $table->text('email_body_sw')->nullable()->after('email_body');
            $table->text('sms_body_sw')->nullable()->after('sms_body');
        });
    }

    public function down(): void
    {
        Schema::table('communication_templates', function (Blueprint $table) {
            $table->dropColumn(['email_subject_sw', 'email_body_sw', 'sms_body_sw']);
        });
    }
};
