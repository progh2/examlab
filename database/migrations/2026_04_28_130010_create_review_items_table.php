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
        Schema::create('review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('stage')->default(0);
            $table->timestamp('due_at')->index();
            $table->boolean('last_result')->nullable();
            $table->timestamp('last_answered_at')->nullable();
            $table->unsignedInteger('wrong_streak')->default(0);
            $table->boolean('suspended')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'question_id']);
            $table->index(['tenant_id', 'user_id', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_items');
    }
};
