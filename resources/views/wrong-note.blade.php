<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wrong Note</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #0b0b0f; color: #eaeaf2; }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 28px 16px; }
        .card { background: #141423; border: 1px solid #23233a; border-radius: 14px; padding: 18px; margin-bottom: 12px; }
        .muted { color: #b7b7d4; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 10px; border: 1px solid #2b2b46; background: #1b1b2e; color: #eaeaf2; text-decoration: none; }
        .btn.active { background: #f59e0b; border-color: #f59e0b; color: #141423; font-weight: 700; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #20203a; border: 1px solid #2b2b46; color: #cfcfe9; font-size: 12px; }
        h1 { margin: 0 0 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="row" style="margin-bottom: 16px;">
        <div>
            <h1>오답노트</h1>
            <p class="muted">오답 횟수가 있는 문제만 모았습니다.</p>
        </div>
        <div class="row">
            <a class="btn" href="/dashboard">대시보드</a>
            <a class="btn" href="/practice/wrong">오답 모드 시작</a>
        </div>
    </div>

    <div class="row" style="justify-content: flex-start; margin-bottom: 14px;">
        <a class="btn {{ $sort !== 'recent' ? 'active' : '' }}" href="/wrong-note?sort=count">오답 많은 순</a>
        <a class="btn {{ $sort === 'recent' ? 'active' : '' }}" href="/wrong-note?sort=recent">최근 오답 순</a>
    </div>

    @forelse($items as $item)
        @php
            $question = $item->question;
            $stem = $question?->stem_localized['ko'] ?? $question?->stem_localized['en'] ?? '';
        @endphp
        <div class="card">
            <div class="row">
                <div class="row" style="justify-content: flex-start;">
                    <span class="pill">{{ $question?->exam?->code ?? 'Exam' }}</span>
                    <span class="pill">오답 {{ $item->wrong_count }}회</span>
                    <span class="pill">시도 {{ $item->attempts }}회</span>
                    <span class="pill">연속 오답 {{ $item->wrong_streak }}회</span>
                </div>
                <div class="muted">최근 풀이: {{ $item->last_seen_at?->diffForHumans() ?? '없음' }}</div>
            </div>
            <h2 style="font-size: 18px;">{{ $stem }}</h2>
            <p class="muted">
                정답 {{ $item->correct_count }}회 · 마지막 결과
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
            <h2>아직 오답이 없습니다.</h2>
            <p class="muted">전체 모드나 맞춤 모드에서 문제를 풀면 오답노트가 자동으로 채워집니다.</p>
            <a class="btn active" href="/practice/all">전체 모드 시작</a>
        </div>
    @endforelse
</div>
</body>
</html>
