<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Done</title>
</head>
<body>
<h1>오늘 복습 세션 완료</h1>
<p>Session #{{ $session->id }}</p>
<p><a href="/dashboard">대시보드로</a></p>
</body>
</html>

