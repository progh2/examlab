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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();

            $table->string('qa_status')->default('draft'); // draft | review | approved | rejected

            $table->json('stem_localized'); // 문제 본문
            $table->json('explanation_localized')->nullable(); // 해설

            $table->unsignedTinyInteger('correct_choice_index'); // 1..4

            $table->unsignedTinyInteger('difficulty')->nullable(); // 1..5 (선택)
            $table->json('source')->nullable(); // { year, round, provider, ... }

            $table->timestamps();

            $table->index(['exam_id', 'qa_status']);
            $table->index(['section_id']);
            $table->index(['topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
