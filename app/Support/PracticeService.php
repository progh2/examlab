<?php

namespace App\Support;

use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionStat;
use App\Models\ReviewItem;
use App\Models\SessionAnswer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PracticeService
{
    /**
     * @return PracticeSession
     */
    public static function startTodayReviewSession(int $tenantId, int $userId, int $limit = 20): PracticeSession
    {
        $now = CarbonImmutable::now();

        $dueQuestionIds = ReviewItem::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('suspended', false)
            ->where('due_at', '<=', $now)
            ->orderBy('due_at')
            ->limit($limit)
            ->pluck('question_id')
            ->all();

        // 첫 사용자는 due가 비어있을 수 있어, 데모/초기 사용을 위해 approved 문제를 일부 노출합니다.
        if (count($dueQuestionIds) === 0) {
            $dueQuestionIds = Question::query()
                ->whereHas('exam', fn ($q) => $q->where('tenant_id', $tenantId))
                ->where('qa_status', 'approved')
                ->limit(min(10, $limit))
                ->pluck('id')
                ->all();
        }

        return PracticeSession::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'mode' => 'review',
            'started_at' => $now,
            'meta' => [
                'question_ids' => $dueQuestionIds,
                'cursor' => 0,
            ],
        ]);
    }

    /**
     * @return array{question: Question, answer: SessionAnswer, choices: array<int, QuestionChoice>, displayed_to_original: array<int,int>}
     */
    public static function getCurrentQuestion(PracticeSession $session): ?array
    {
        $questionIds = $session->meta['question_ids'] ?? [];
        $cursor = (int) ($session->meta['cursor'] ?? 0);

        if (!is_array($questionIds) || !isset($questionIds[$cursor])) {
            return null;
        }

        $questionId = (int) $questionIds[$cursor];
        /** @var Question $question */
        $question = Question::query()->with('choices')->findOrFail($questionId);

        $answer = SessionAnswer::query()->firstOrCreate(
            ['practice_session_id' => $session->id, 'question_id' => $question->id],
            [],
        );

        $choices = $question->choices->sortBy('choice_index')->values()->all();

        // choice_order가 없으면(처음 표시), 세션 단위로 랜덤 매핑을 만들어 저장합니다.
        // choice_order는 "display_index(1..4) => original_choice_index(1..4)" 형태로 저장합니다.
        if (!$answer->choice_order) {
            $original = [1, 2, 3, 4];
            shuffle($original);

            $displayedToOriginal = [
                1 => $original[0],
                2 => $original[1],
                3 => $original[2],
                4 => $original[3],
            ];

            $answer->forceFill(['choice_order' => $displayedToOriginal])->save();
        }

        /** @var array<int,int> $displayedToOriginal */
        $displayedToOriginal = $answer->choice_order;

        $displayedChoices = [];
        foreach ([1, 2, 3, 4] as $displayIdx) {
            $origIdx = (int) ($displayedToOriginal[$displayIdx] ?? $displayIdx);
            $choice = collect($choices)->firstWhere('choice_index', $origIdx);
            if ($choice) {
                $displayedChoices[] = $choice;
            }
        }

        return [
            'question' => $question,
            'answer' => $answer,
            'choices' => $displayedChoices,
            'displayed_to_original' => $displayedToOriginal,
        ];
    }

    public static function submitAnswer(PracticeSession $session, int $displayedChoiceIndex, ?string $wrongReason = null, ?string $confidence = null): void
    {
        $now = CarbonImmutable::now();

        $payload = self::getCurrentQuestion($session);
        if (!$payload) {
            return;
        }

        /** @var Question $question */
        $question = $payload['question'];
        /** @var SessionAnswer $answer */
        $answer = $payload['answer'];

        $displayedToOriginal = $answer->choice_order ?? [];
        $originalChoiceIndex = (int) ($displayedToOriginal[$displayedChoiceIndex] ?? $displayedChoiceIndex);

        $isCorrect = $originalChoiceIndex === (int) $question->correct_choice_index;

        DB::transaction(function () use ($session, $question, $answer, $now, $originalChoiceIndex, $isCorrect, $wrongReason, $confidence) {
            $answer->forceFill([
                'selected_choice_index' => $originalChoiceIndex,
                'is_correct' => $isCorrect,
                'wrong_reason' => $isCorrect ? null : $wrongReason,
                'confidence' => $confidence,
                'answered_at' => $now,
            ])->save();

            $stat = QuestionStat::query()->firstOrCreate(
                ['tenant_id' => $session->tenant_id, 'user_id' => $session->user_id, 'question_id' => $question->id],
                [],
            );
            $stat->attempts += 1;
            if ($isCorrect) {
                $stat->correct_count += 1;
                $stat->wrong_streak = 0;
            } else {
                $stat->wrong_count += 1;
                $stat->wrong_streak += 1;
            }
            $stat->last_seen_at = $now;
            $stat->last_result = $isCorrect;
            $stat->save();

            $review = ReviewItem::query()->firstOrCreate(
                ['tenant_id' => $session->tenant_id, 'user_id' => $session->user_id, 'question_id' => $question->id],
                ['due_at' => $now],
            );

            $stage = (int) $review->stage;
            if ($isCorrect) {
                // confidence가 low면 stage 상승을 보수적으로
                $stage = ($confidence === 'low') ? $stage : ($stage + 1);
                $review->wrong_streak = 0;
            } else {
                $drop = in_array($wrongReason, ['개념부족', '헷갈림'], true) ? 2 : 1;
                $stage = $stage - $drop;
                $review->wrong_streak += 1;
            }

            $stage = SpacedRepetition::clampStage($stage);

            $review->forceFill([
                'stage' => $stage,
                'last_result' => $isCorrect,
                'last_answered_at' => $now,
                'due_at' => $isCorrect ? SpacedRepetition::nextDueAt($stage, $now) : SpacedRepetition::nextDueAt(0, $now),
            ])->save();
        });

        // 커서 이동(다음 문제)
        $meta = $session->meta ?? [];
        $meta['cursor'] = ((int) ($meta['cursor'] ?? 0)) + 1;
        $session->forceFill(['meta' => $meta])->save();
    }
}

