<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->index(
                ['email', 'ip_address', 'successful', 'attempted_at'],
                'login_attempts_email_ip_success_attempted_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndex('login_attempts_email_ip_success_attempted_idx');
        });
    }
};
