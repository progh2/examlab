<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\QuestionStat;
use App\Models\ReviewItem;
use App\Models\User;
use App\Support\PracticeService;
use Database\Seeders\ExampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeModesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExampleDataSeeder::class);
        $this->user = User::query()->where('email', 'admin@demo.local')->firstOrFail();
    }

    public function test_dashboard_shows_learning_modes_and_summary(): void
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('학습 대시보드')
            ->assertSee('오늘 due 복습')
            ->assertSee('전체 모드 시작')
            ->assertSee('오답 모드 시작')
            ->assertSee('맞춤 모드 시작');
    }

    public function test_all_mode_session_can_submit_answer_and_advance(): void
    {
        $this->actingAs($this->user)
            ->get('/practice/all')
            ->assertRedirect();

        $session = PracticeSession::query()->latest('id')->firstOrFail();
        $payload = PracticeService::getCurrentQuestion($session);

        $this->assertNotNull($payload);

        $question = $payload['question'];
        $displayedToOriginal = $payload['displayed_to_original'];
        $wrongDisplayIndex = collect($displayedToOriginal)
            ->filter(fn (int $originalIndex): bool => $originalIndex !== (int) $question->correct_choice_index)
            ->keys()
            ->first();

        $this->assertNotNull($wrongDisplayIndex);

        $this->actingAs($this->user)
            ->post("/practice/session/{$session->id}/answer", [
                'display_choice_index' => $wrongDisplayIndex,
                'wrong_reason' => '헷갈림',
                'confidence' => 'low',
                'lang' => 'ko',
            ])
            ->assertRedirect("/practice/session/{$session->id}?lang=ko");

        $this->assertDatabaseHas('session_answers', [
            'practice_session_id' => $session->id,
            'question_id' => $question->id,
            'is_correct' => false,
            'wrong_reason' => '헷갈림',
            'confidence' => 'low',
        ]);
        $this->assertDatabaseHas('question_stats', [
            'tenant_id' => $this->user->current_tenant_id,
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'last_result' => false,
        ]);

        $this->actingAs($this->user)
            ->get("/practice/session/{$session->id}")
            ->assertOk()
            ->assertSee('오답입니다.')
            ->assertSee('해설 보기');

        $this->actingAs($this->user)
            ->post("/practice/session/{$session->id}/next", ['lang' => 'ko'])
            ->assertRedirect("/practice/session/{$session->id}?lang=ko");

        $this->assertSame(1, (int) $session->fresh()->meta['cursor']);
    }

    public function test_wrong_mode_uses_questions_with_wrong_stats(): void
    {
        $this->actingAs($this->user)
            ->get('/practice/wrong')
            ->assertRedirect();

        $session = PracticeSession::query()->latest('id')->firstOrFail();
        $questionIds = $session->meta['question_ids'];

        $this->assertNotEmpty($questionIds);
        foreach ($questionIds as $questionId) {
            $this->assertTrue(
                QuestionStat::query()
                    ->where('tenant_id', $this->user->current_tenant_id)
                    ->where('user_id', $this->user->id)
                    ->where('question_id', $questionId)
                    ->where('wrong_count', '>', 0)
                    ->exists()
            );
        }
    }

    public function test_adaptive_mode_prioritizes_due_review_items(): void
    {
        $this->actingAs($this->user)
            ->get('/practice/adaptive')
            ->assertRedirect();

        $session = PracticeSession::query()->latest('id')->firstOrFail();
        $firstDueQuestionId = ReviewItem::query()
            ->where('tenant_id', $this->user->current_tenant_id)
            ->where('user_id', $this->user->id)
            ->where('suspended', false)
            ->orderBy('due_at')
            ->value('question_id');

        $this->assertSame((int) $firstDueQuestionId, (int) $session->meta['question_ids'][0]);
    }

    public function test_wrong_note_and_review_queue_render_seeded_data(): void
    {
        $this->actingAs($this->user)
            ->get('/wrong-note')
            ->assertOk()
            ->assertSee('오답노트')
            ->assertSee('오답 4회');

        $this->actingAs($this->user)
            ->get('/review-queue')
            ->assertOk()
            ->assertSee('복습 큐')
            ->assertSee('stage');
    }
}
