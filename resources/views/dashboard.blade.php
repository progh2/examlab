<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #0b0b0f; color: #eaeaf2; }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 32px 16px; }
        .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }
        .card { background: #141423; border: 1px solid #23233a; border-radius: 16px; padding: 18px; }
        .muted { color: #b7b7d4; }
        .stat { font-size: 30px; font-weight: 800; margin-top: 8px; }
        .btn { display: inline-block; padding: 11px 14px; border-radius: 12px; border: 1px solid #2b2b46; background: #1b1b2e; color: #eaeaf2; text-decoration: none; font-weight: 700; }
        .btn.primary { background: #f59e0b; border-color: #f59e0b; color: #141423; }
        .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: space-between; }
        select { padding: 10px 12px; border-radius: 10px; border: 1px solid #2b2b46; background: #101022; color: #eaeaf2; }
        form { margin: 0; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="row" style="margin-bottom: 22px;">
        <div>
            <h1 style="margin: 0 0 6px;">학습 대시보드</h1>
            <div class="muted">로그인: {{ auth()->user()->email }}</div>
        </div>
        <div class="row">
            <a class="btn" href="/wrong-note">오답노트</a>
            <a class="btn" href="/review-queue">복습 큐</a>
            <a class="btn" href="/admin">관리자</a>
        </div>
    </div>

    <div class="grid" style="margin-bottom: 18px;">
        <div class="card"><div class="muted">오늘 due 복습</div><div class="stat">{{ $summary['due_count'] }}</div></div>
        <div class="card"><div class="muted">누적 attempts</div><div class="stat">{{ $summary['attempts'] }}</div></div>
        <div class="card"><div class="muted">오답 경험 문항</div><div class="stat">{{ $summary['wrong_questions'] }}</div></div>
        <div class="card"><div class="muted">approved 문항</div><div class="stat">{{ $summary['approved_questions'] }}</div></div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">학습 시작</h2>
        <p class="muted">시험 범위를 선택하지 않으면 현재 테넌트의 전체 approved 문항으로 시작합니다.</p>
        <div class="grid">
            @foreach([
                'review' => ['오늘 복습 시작', 'due/overdue 문항을 먼저 풉니다.'],
                'all' => ['전체 모드 시작', 'approved 문항을 순차 제공합니다.'],
                'wrong' => ['오답 모드 시작', 'wrong_count > 0 문항만 풉니다.'],
                'adaptive' => ['맞춤 모드 시작', 'due 우선, 없으면 취약도 기반으로 풉니다.'],
            ] as $mode => [$label, $desc])
                <form method="GET" action="/practice/{{ $mode }}" class="card">
                    <div style="font-weight: 800; margin-bottom: 6px;">{{ $label }}</div>
                    <div class="muted" style="min-height: 40px;">{{ $desc }}</div>
                    <div style="margin: 14px 0;">
                        <select name="exam_id">
                            <option value="">전체 시험</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->code }} - {{ $exam->name_localized['ko'] ?? $exam->name_localized['en'] ?? 'Exam' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn {{ $mode === 'review' ? 'primary' : '' }}" type="submit">{{ $label }}</button>
                </form>
            @endforeach
        </div>
    </div>
</div>
</body>
</html>

