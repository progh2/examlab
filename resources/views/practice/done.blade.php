<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Done</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background: #0f172a; color: #e2e8f0; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 48px 16px; }
        .card { background: #111827; border: 1px solid #243044; border-radius: 18px; padding: 28px; }
        .btn { display: inline-block; margin: 8px 8px 0 0; padding: 10px 14px; border-radius: 10px; background: #f59e0b; color: #111827; text-decoration: none; font-weight: 800; }
        .btn.secondary { background: #1f2937; color: #e5e7eb; border: 1px solid #374151; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <p>Session #{{ $session->id }} · {{ $session->mode }}</p>
        <h1>학습 세션 완료</h1>
        <p>이번 세션의 답변은 오답노트와 복습 큐에 반영되었습니다.</p>
        <a class="btn" href="/dashboard">대시보드로</a>
        <a class="btn secondary" href="/wrong-note">오답노트 보기</a>
        <a class="btn secondary" href="/review-queue">복습 큐 보기</a>
    </div>
</div>
</body>
</html>

