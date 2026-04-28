<?php

namespace App\Support;

use App\Models\PracticeSession;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionStat;
use App\Models\ReviewItem;
use App\Models\SessionAnswer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PracticeService
{
    public const MODES = ['all', 'wrong', 'adaptive', 'review'];

    /**
     * @return PracticeSession
     */
    public static function startTodayReviewSession(int $tenantId, int $userId, int $limit = 20): PracticeSession
    {
        return self::startSession('review', $tenantId, $userId, $limit);
    }

    public static function startSession(string $mode, int $tenantId, int $userId, int $limit = 20, ?int $examId = null): PracticeSession
    {
        $now = CarbonImmutable::now();
        $mode = in_array($mode, self::MODES, true) ? $mode : 'all';
        $questionIds = self::questionIdsForMode($mode, $tenantId, $userId, $limit, $examId);

        return PracticeSession::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'mode' => $mode,
            'started_at' => $now,
            'meta' => [
                'question_ids' => $questionIds,
                'cursor' => 0,
                'exam_id' => $examId,
            ],
        ]);
    }

    /**
     * @return array<int, int>
     */
    public static function questionIdsForMode(string $mode, int $tenantId, int $userId, int $limit = 20, ?int $examId = null): array
    {
        return match ($mode) {
            'wrong' => self::wrongQuestionIds($tenantId, $userId, $limit, $examId),
            'adaptive', 'review' => self::adaptiveQuestionIds($tenantId, $userId, $limit, $examId),
            default => self::approvedQuestionQuery($tenantId, $examId)
                ->orderBy('exam_id')
                ->orderBy('id')
                ->limit($limit)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        };
    }

    public static function approvedQuestionCount(int $tenantId, ?int $examId = null): int
    {
        return self::approvedQuestionQuery($tenantId, $examId)->count();
    }

    public static function modeLabel(string $mode): string
    {
        return match ($mode) {
            'wrong' => '오답 모드',
            'adaptive' => '맞춤 모드',
            'review' => '복습 모드',
            default => '전체 모드',
        };
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

        if ($answer->answered_at) {
            return;
        }

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
    }

    public static function advanceSession(PracticeSession $session): void
    {
        $meta = $session->meta ?? [];
        $meta['cursor'] = ((int) ($meta['cursor'] ?? 0)) + 1;
        $questionIds = $meta['question_ids'] ?? [];
        $endedAt = $session->ended_at;
        if (is_array($questionIds) && (int) $meta['cursor'] >= count($questionIds)) {
            $endedAt = CarbonImmutable::now();
        }

        $session->forceFill([
            'meta' => $meta,
            'ended_at' => $endedAt,
        ])->save();
    }

    /**
     * @return Builder<Question>
     */
    private static function approvedQuestionQuery(int $tenantId, ?int $examId = null): Builder
    {
        return Question::query()
            ->whereHas('exam', function ($query) use ($tenantId, $examId) {
                $query->where('tenant_id', $tenantId);
                if ($examId) {
                    $query->where('id', $examId);
                }
            })
            ->where('qa_status', 'approved');
    }

    /**
     * @return array<int, int>
     */
    private static function wrongQuestionIds(int $tenantId, int $userId, int $limit, ?int $examId = null): array
    {
        return QuestionStat::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('wrong_count', '>', 0)
            ->whereHas('question', fn ($query) => $query
                ->where('qa_status', 'approved')
                ->whereHas('exam', function ($examQuery) use ($tenantId, $examId) {
                    $examQuery->where('tenant_id', $tenantId);
                    if ($examId) {
                        $examQuery->where('id', $examId);
                    }
                }))
            ->orderByDesc('wrong_count')
            ->orderByDesc('last_seen_at')
            ->limit($limit)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private static function adaptiveQuestionIds(int $tenantId, int $userId, int $limit, ?int $examId = null): array
    {
        $now = CarbonImmutable::now();

        $dueIds = ReviewItem::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('suspended', false)
            ->where('due_at', '<=', $now)
            ->whereHas('question', fn ($query) => $query
                ->where('qa_status', 'approved')
                ->whereHas('exam', function ($examQuery) use ($tenantId, $examId) {
                    $examQuery->where('tenant_id', $tenantId);
                    if ($examId) {
                        $examQuery->where('id', $examId);
                    }
                }))
            ->orderBy('due_at')
            ->limit($limit)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($dueIds) >= $limit) {
            return $dueIds;
        }

        $fallbackIds = QuestionStat::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereNotIn('question_id', $dueIds)
            ->whereHas('question', fn ($query) => $query
                ->where('qa_status', 'approved')
                ->whereHas('exam', function ($examQuery) use ($tenantId, $examId) {
                    $examQuery->where('tenant_id', $tenantId);
                    if ($examId) {
                        $examQuery->where('id', $examId);
                    }
                }))
            ->orderByDesc('wrong_streak')
            ->orderByDesc('wrong_count')
            ->orderBy('correct_count')
            ->limit($limit - count($dueIds))
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_values(array_unique([...$dueIds, ...$fallbackIds]));
        if (count($ids) >= $limit) {
            return array_slice($ids, 0, $limit);
        }

        $starterIds = self::approvedQuestionQuery($tenantId, $examId)
            ->whereNotIn('id', $ids)
            ->orderBy('exam_id')
            ->orderBy('id')
            ->limit($limit - count($ids))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$ids, ...$starterIds]));
    }
}

