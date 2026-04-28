<?php

namespace App\Http\Controllers;

use App\Models\PracticeSession;
use App\Support\PracticeService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function startTodayReview(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = $user?->current_tenant_id;
        if (!$user || !$tenantId) {
            return redirect()->route('dashboard');
        }

        $session = PracticeService::startTodayReviewSession($tenantId, $user->id);

        return redirect()->route('practice.session', ['session' => $session->id]);
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

        $questionIds = $session->meta['question_ids'] ?? [];
        $cursor = (int) ($session->meta['cursor'] ?? 0);
        $progress = (is_array($questionIds) ? min($cursor + 1, count($questionIds)) : 0) . ' / ' . (is_array($questionIds) ? count($questionIds) : 0);

        return view('practice.session', [
            'session' => $session,
            'lang' => $lang,
            'progress' => $progress,
            'examLabel' => $q->exam->code ?? (string) $q->exam_id,
            'stem' => $q->stem_localized[$lang] ?? ($q->stem_localized['ko'] ?? $q->stem_localized['en'] ?? ''),
            'choices' => array_map(fn ($c) => $c->text_localized[$lang] ?? ($c->text_localized['ko'] ?? $c->text_localized['en'] ?? ''), $choices),
            'alreadyAnswered' => (bool) $answer->answered_at,
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
            'lang' => ['nullable', 'string'],
        ]);

        PracticeService::submitAnswer($session, (int) $data['display_choice_index']);

        $lang = $data['lang'] ?? 'ko';

        return redirect()->route('practice.session', ['session' => $session->id, 'lang' => $lang]);
    }
}
