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
        Schema::create('session_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_session_id')->constrained('practice_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('selected_choice_index')->nullable(); // 1..4
            $table->boolean('is_correct')->nullable();

            // 보기 섞기(랜덤) 사용 시, 표시된 매핑을 저장해 재현/채점 일관성을 보장합니다.
            // 예: [3,1,4,2] => 화면 1번자리에 원래 3번 보기가 표시됨
            $table->json('choice_order')->nullable();

            $table->string('wrong_reason')->nullable();
            $table->string('confidence')->nullable(); // low | medium | high

            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->boolean('checked')->default(false);
            $table->boolean('explanation_viewed')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['practice_session_id', 'question_id']);
            $table->index(['question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_answers');
    }
};
