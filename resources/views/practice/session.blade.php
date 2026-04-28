<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Practice</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #0b0b0f; color: #eaeaf2; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 28px 16px; }
        .card { background: #141423; border: 1px solid #23233a; border-radius: 14px; padding: 18px; }
        .muted { color: #b7b7d4; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 10px; border: 1px solid #2b2b46; background: #1b1b2e; color: #eaeaf2; text-decoration: none; cursor: pointer; }
        .btn.primary { background: #f59e0b; border-color: #f59e0b; color: #141423; font-weight: 700; }
        .choice { display: block; width: 100%; text-align: left; padding: 12px 14px; border-radius: 12px; border: 1px solid #2b2b46; background: #101022; color: #eaeaf2; }
        .choices { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 14px; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #20203a; border: 1px solid #2b2b46; color: #cfcfe9; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="row" style="margin-bottom: 14px;">
        <div class="row">
            <span class="pill">Session #{{ $session->id }}</span>
            <span class="pill">{{ $progress }}</span>
            <span class="pill">lang={{ $lang }}</span>
        </div>
        <div class="row">
            <a class="btn" href="/dashboard">대시보드</a>
        </div>
    </div>

    <div class="card">
        <h1>{{ $stem }}</h1>
        <div class="muted">Exam: {{ $examLabel }}</div>

        @if($alreadyAnswered)
            <p style="margin-top: 12px;" class="muted">이미 답변한 문항입니다. 다음으로 이동하세요.</p>
            <a class="btn primary" href="/practice/session/{{ $session->id }}?lang={{ $lang }}">다음</a>
        @else
            <form method="POST" action="/practice/session/{{ $session->id }}/answer">
                @csrf
                <input type="hidden" name="lang" value="{{ $lang }}">
                <div class="choices">
                    @foreach($choices as $idx => $choice)
                        <button class="choice" type="submit" name="display_choice_index" value="{{ $idx + 1 }}">
                            <div class="muted" style="font-size: 12px; margin-bottom: 4px;">선택지 {{ $idx + 1 }}</div>
                            <div>{{ $choice }}</div>
                        </button>
                    @endforeach
                </div>
            </form>
        @endif
    </div>
</div>
</body>
</html>

