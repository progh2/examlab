<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review Queue</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #0b0b0f; color: #eaeaf2; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 28px 16px; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .card { background: #141423; border: 1px solid #23233a; border-radius: 14px; padding: 18px; margin-top: 14px; }
        .muted { color: #b7b7d4; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 10px; border: 1px solid #2b2b46; background: #1b1b2e; color: #eaeaf2; text-decoration: none; cursor: pointer; }
        .btn.primary { background: #38bdf8; border-color: #38bdf8; color: #08111a; font-weight: 700; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #20203a; border: 1px solid #2b2b46; color: #cfcfe9; font-size: 12px; }
        .overdue { color: #fca5a5; font-weight: 700; }
        h1 { margin: 0 0 6px; }
        h2 { margin: 0 0 8px; font-size: 18px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="row">
        <div>
            <h1>복습 큐</h1>
            <p class="muted">오늘 복습해야 하거나 이미 밀린 문항입니다.</p>
        </div>
        <div class="row">
            <a class="btn primary" href="{{ route('practice.start', ['mode' => 'review']) }}">복습 시작</a>
            <a class="btn" href="{{ route('dashboard') }}">대시보드</a>
        </div>
    </div>

    @forelse($items as $item)
        @php
            $question = $item->question;
            $stem = $question->stem_localized['ko'] ?? ($question->stem_localized['en'] ?? '');
            $isOverdue = $item->due_at && $item->due_at->lt($now->startOfDay());
        @endphp
        <div class="card">
            <div class="row">
                <span class="pill">{{ $question->exam->code ?? 'Exam' }}</span>
                <span class="pill">stage {{ $item->stage }}</span>
                <span class="pill">wrong streak {{ $item->wrong_streak }}</span>
            </div>
            <h2 style="margin-top: 12px;">{{ $stem }}</h2>
            <p class="muted">
                due: <span class="{{ $isOverdue ? 'overdue' : '' }}">{{ optional($item->due_at)->format('Y-m-d H:i') }}</span>
                · last result:
                @if(is_null($item->last_result))
                    -
                @elseif($item->last_result)
                    정답
                @else
                    오답
                @endif
            </p>
        </div>
    @empty
        <div class="card">
            <h2>현재 due/overdue 문항이 없습니다.</h2>
            <p class="muted">맞춤 모드는 취약도 기반 fallback으로 이어서 연습할 수 있습니다.</p>
            <a class="btn primary" href="{{ route('practice.start', ['mode' => 'adaptive']) }}">맞춤 시작</a>
        </div>
    @endforelse
</div>
</body>
</html>
