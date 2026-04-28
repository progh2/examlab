<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable(); // 예: "AIF-C01", "INFO-PROC-CRAFT"
            $table->json('name_localized'); // { "ko": "...", "en": "..." }
            $table->string('primary_locale', 8)->default('ko');
            $table->string('blueprint_type')->default('hybrid'); // subject | domain | hybrid
            $table->timestamps();

            $table->index(['tenant_id']);
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
