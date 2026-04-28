<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\PracticeSession;
use App\Models\QuestionStat;
use App\Models\ReviewItem;
use App\Support\PracticeService;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user?->current_tenant_id;
        if (!$user || !$tenantId) {
            return redirect('/');
        }

        $now = CarbonImmutable::now();
        $endOfToday = $now->endOfDay();

        $summary = [
            'due_count' => ReviewItem::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->where('suspended', false)
                ->where('due_at', '<=', $endOfToday)
                ->count(),
            'attempts' => QuestionStat::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->sum('attempts'),
            'wrong_questions' => QuestionStat::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->where('wrong_count', '>', 0)
                ->count(),
            'approved_questions' => PracticeService::approvedQuestionCount($tenantId),
            'sessions' => PracticeSession::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->count(),
        ];

        $exams = Exam::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('code')
            ->get();

        return view('dashboard', [
            'summary' => $summary,
            'exams' => $exams,
        ]);
    }

    public function startMode(Request $request, string $mode): RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user?->current_tenant_id;
        if (!$user || !$tenantId || !in_array($mode, PracticeService::MODES, true)) {
            return redirect()->route('dashboard');
        }

        $examId = $request->integer('exam_id') ?: null;
        if ($examId && !Exam::query()->where('tenant_id', $tenantId)->where('id', $examId)->exists()) {
            $examId = null;
        }

        $session = PracticeService::startSession($mode, $tenantId, $user->id, examId: $examId);

        return redirect()->route('practice.session', ['session' => $session->id]);
    }

    public function startTodayReview(Request $request): RedirectResponse
    {
        return $this->startMode($request, 'review');
    }

    public function showSession(Request $request, PracticeSession $session): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user || $session->user_id !== $user->id) {
            return redirect()->route('dashboard');
        }

        $tenantId = TenantContext::currentTenantId();
        if (!$tenantId || $session->tenant_id !== $tenantId) {
            return redirect()->route('dashboard');
        }

        $lang = $request->query('lang', 'ko');
        if (!in_array($lang, ['ko', 'en'], true)) {
            $lang = 'ko';
        }

        $payload = PracticeService::getCurrentQuestion($session);
        if (!$payload) {
            return view('practice.done', [
                'session' => $session,
            ]);
        }

        $q = $payload['question'];
        $answer = $payload['answer'];
        $choices = $payload['choices'];
        $displayedToOriginal = $payload['displayed_to_original'];

        $questionIds = $session->meta['question_ids'] ?? [];
        $cursor = (int) ($session->meta['cursor'] ?? 0);
        $progress = (is_array($questionIds) ? min($cursor + 1, count($questionIds)) : 0) . ' / ' . (is_array($questionIds) ? count($questionIds) : 0);
        $selectedDisplayIndex = null;
        if ($answer->selected_choice_index) {
            $selectedDisplayIndex = array_search((int) $answer->selected_choice_index, $displayedToOriginal, true);
        }
        $correctDisplayIndex = array_search((int) $q->correct_choice_index, $displayedToOriginal, true);

        return view('practice.session', [
            'session' => $session,
            'lang' => $lang,
            'progress' => $progress,
            'modeLabel' => PracticeService::modeLabel((string) $session->mode),
            'examLabel' => $q->exam->code ?? (string) $q->exam_id,
            'stem' => $q->stem_localized[$lang] ?? ($q->stem_localized['ko'] ?? $q->stem_localized['en'] ?? ''),
            'explanation' => $q->explanation_localized[$lang] ?? ($q->explanation_localized['ko'] ?? $q->explanation_localized['en'] ?? ''),
            'choices' => array_map(fn ($c) => $c->text_localized[$lang] ?? ($c->text_localized['ko'] ?? $c->text_localized['en'] ?? ''), $choices),
            'alreadyAnswered' => (bool) $answer->answered_at,
            'answer' => $answer,
            'selectedDisplayIndex' => $selectedDisplayIndex ? (int) $selectedDisplayIndex : null,
            'correctDisplayIndex' => $correctDisplayIndex ? (int) $correctDisplayIndex : null,
        ]);
    }

    public function submitAnswer(Request $request, PracticeSession $session): RedirectResponse
    {
        $user = $request->user();
        if (!$user || $session->user_id !== $user->id) {
            return redirect()->route('dashboard');
        }

        $tenantId = TenantContext::currentTenantId();
        if (!$tenantId || $session->tenant_id !== $tenantId) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'display_choice_index' => ['required', 'integer', 'min:1', 'max:4'],
            'wrong_reason' => ['nullable', 'string', 'in:개념부족,헷갈림,실수,시간부족,찍음'],
            'confidence' => ['nullable', 'string', 'in:low,medium,high'],
            'lang' => ['nullable', 'string'],
        ]);

        PracticeService::submitAnswer(
            $session,
            (int) $data['display_choice_index'],
            $data['wrong_reason'] ?? null,
            $data['confidence'] ?? null,
        );

        $lang = $data['lang'] ?? 'ko';

        return redirect()->route('practice.session', ['session' => $session->id, 'lang' => $lang]);
    }

    public function nextQuestion(Request $request, PracticeSession $session): RedirectResponse
    {
        $user = $request->user();
        if (!$user || $session->user_id !== $user->id) {
            return redirect()->route('dashboard');
        }

        $tenantId = TenantContext::currentTenantId();
        if (!$tenantId || $session->tenant_id !== $tenantId) {
            return redirect()->route('dashboard');
        }

        PracticeService::advanceSession($session);

        return redirect()->route('practice.session', [
            'session' => $session->id,
            'lang' => $request->input('lang', 'ko'),
        ]);
    }

    public function wrongNote(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user?->current_tenant_id;
        if (!$user || !$tenantId) {
            return redirect()->route('dashboard');
        }

        $sort = $request->query('sort', 'count');
        $items = QuestionStat::query()
            ->with(['question.exam'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('wrong_count', '>', 0)
            ->when($sort === 'recent', fn ($query) => $query->orderByDesc('last_seen_at'))
            ->when($sort !== 'recent', fn ($query) => $query->orderByDesc('wrong_count')->orderByDesc('last_seen_at'))
            ->get();

        return view('wrong-note', [
            'items' => $items,
            'sort' => $sort,
        ]);
    }

    public function reviewQueue(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user?->current_tenant_id;
        if (!$user || !$tenantId) {
            return redirect()->route('dashboard');
        }

        $now = CarbonImmutable::now();
        $endOfToday = $now->endOfDay();
        $items = ReviewItem::query()
            ->with(['question.exam'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('suspended', false)
            ->where('due_at', '<=', $endOfToday)
            ->orderBy('due_at')
            ->get();

        return view('review-queue', [
            'items' => $items,
            'now' => $now,
        ]);
    }
}
