<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('id_type')->nullable()->after('email');
            $table->string('id_number')->nullable()->unique()->after('id_type');
            $table->string('id_image_path')->nullable()->after('id_number');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['id_number']);
            $table->dropColumn(['id_type', 'id_number', 'id_image_path']);
        });
    }
};
