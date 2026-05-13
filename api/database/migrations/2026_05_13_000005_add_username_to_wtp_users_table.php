<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wtp_users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->unique()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('wtp_users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
