<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->default(0);
            $table->string('year_month', 4);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['account_id', 'year_month'], 'consultation_sequences_account_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_sequences');
    }
};
