<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_needs_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('needs_category_id')->constrained('needs_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['consultation_id', 'needs_category_id'], 'consultation_need_unique');
        });

        DB::table('consultations')
            ->select(['id', 'needs_category_id', 'created_at', 'updated_at'])
            ->whereNotNull('needs_category_id')
            ->orderBy('id')
            ->chunkById(500, function ($consultations) {
                $rows = [];

                foreach ($consultations as $consultation) {
                    $rows[] = [
                        'consultation_id' => $consultation->id,
                        'needs_category_id' => $consultation->needs_category_id,
                        'created_at' => $consultation->created_at ?? now(),
                        'updated_at' => $consultation->updated_at ?? now(),
                    ];
                }

                if ($rows !== []) {
                    DB::table('consultation_needs_category')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_needs_category');
    }
};
