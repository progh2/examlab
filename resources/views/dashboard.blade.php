<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
</head>
<body>
<h1>Dashboard</h1>

<p>로그인 완료: {{ auth()->user()->email }}</p>

<p>
    <a href="/admin">관리자(Admin)로 이동</a>
</p>
</body>
</html>

