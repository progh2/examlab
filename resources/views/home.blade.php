<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'examlab') }}</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #0b0b0f; color: #eaeaf2; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 48px 20px; }
        .card { background: #141423; border: 1px solid #23233a; border-radius: 14px; padding: 20px; }
        .grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
        @media (min-width: 840px) { .grid { grid-template-columns: 1fr 1fr; } }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 10px; border: 1px solid #2b2b46; background: #1b1b2e; color: #eaeaf2; text-decoration: none; }
        .btn.primary { background: #f59e0b; border-color: #f59e0b; color: #141423; font-weight: 700; }
        .muted { color: #b7b7d4; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #20203a; border: 1px solid #2b2b46; color: #cfcfe9; font-size: 12px; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        h1 { font-size: 32px; margin: 0 0 8px; }
        h2 { font-size: 16px; margin: 0 0 8px; }
        ul { margin: 8px 0 0 18px; padding: 0; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="row" style="justify-content: space-between; margin-bottom: 16px;">
        <div class="row">
            <span class="pill">examlab</span>
            @auth
                <span class="pill">로그인됨: {{ auth()->user()->email }}</span>
            @else
                <span class="pill">로그인 필요</span>
            @endauth
        </div>
        <div class="row">
            <a class="btn" href="/admin">관리자</a>
            <a class="btn" href="/welcome">Laravel welcome</a>
        </div>
    </div>

    <h1>자격증 기출문제 연습</h1>
    <p class="muted">전체/맞춤/오답 + 복습 큐(간격 반복) 기반으로 학습을 진행합니다.</p>

    <div class="grid" style="margin-top: 18px;">
        <div class="card">
            <h2>학습 시작</h2>
            <p class="muted">구글 로그인으로 시작하고, 이후 대시보드에서 오늘 복습/과제/세트를 선택합니다.</p>
            <div class="row" style="margin-top: 10px;">
                <a class="btn primary" href="/auth/google">Google 로그인</a>
                <a class="btn" href="/dashboard">대시보드</a>
            </div>
        </div>

        <div class="card">
            <h2>관리자(콘텐츠 등록)</h2>
            <p class="muted">Exam/Question/QuestionSet CRUD + CSV Import로 문항을 일괄 등록할 수 있습니다.</p>
            <div class="row" style="margin-top: 10px;">
                <a class="btn" href="/admin">/admin 열기</a>
            </div>
            <ul class="muted">
                <li>테넌트 스코프 적용(현재 테넌트 기준으로만 노출)</li>
                <li>문항은 ko/en 병기 + 보기 4개 고정</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>

