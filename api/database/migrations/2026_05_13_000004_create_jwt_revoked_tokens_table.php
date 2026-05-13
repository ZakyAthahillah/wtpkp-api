<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jwt_revoked_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('jti', 64)->unique();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('wtp_users')->cascadeOnDelete();
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jwt_revoked_tokens');
    }
};
