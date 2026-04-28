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
        .choice.correct { border-color: #22c55e; background: #102718; }
        .choice.wrong { border-color: #ef4444; background: #2b1118; }
        .choices { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 14px; }
        .field-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-top: 14px; }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #20203a; border: 1px solid #2b2b46; color: #cfcfe9; font-size: 12px; }
        .result { margin: 16px 0 0; padding: 12px 14px; border-radius: 12px; }
        .result.correct { background: #102718; border: 1px solid #22c55e; }
        .result.wrong { background: #2b1118; border: 1px solid #ef4444; }
        select { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #2b2b46; background: #101022; color: #eaeaf2; }
        details { margin-top: 14px; padding: 12px 14px; border-radius: 12px; background: #101022; border: 1px solid #2b2b46; }
        summary { cursor: pointer; font-weight: 700; }
        h1 { font-size: 20px; line-height: 1.45; margin: 0 0 8px; }
        label { display: block; margin-bottom: 6px; color: #cfcfe9; font-size: 13px; }
        .error { color: #fecaca; margin-top: 10px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="row" style="margin-bottom: 14px;">
        <div class="row">
            <span class="pill">{{ $modeLabel }}</span>
            <span class="pill">Session #{{ $session->id }}</span>
            <span class="pill">{{ $progress }}</span>
            <span class="pill">lang={{ $lang }}</span>
        </div>
        <div class="row">
            <a class="btn" href="{{ route('dashboard') }}">대시보드</a>
            <a class="btn" href="{{ route('practice.session', ['session' => $session->id, 'lang' => $lang === 'ko' ? 'en' : 'ko']) }}">{{ $lang === 'ko' ? 'English' : '한국어' }}</a>
        </div>
    </div>

    <div class="card">
        <h1>{{ $stem }}</h1>
        <div class="muted">Exam: {{ $examLabel }}</div>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if($alreadyAnswered)
            <div class="choices">
                @foreach($choices as $idx => $choice)
                    @php
                        $displayIndex = $idx + 1;
                        $class = '';
                        if ($displayIndex === $correctDisplayIndex) {
                            $class = 'correct';
                        } elseif ($displayIndex === $selectedDisplayIndex && !$answer->is_correct) {
                            $class = 'wrong';
                        }
                    @endphp
                    <div class="choice {{ $class }}">
                        <div class="muted" style="font-size: 12px; margin-bottom: 4px;">선택지 {{ $displayIndex }}</div>
                        <div>{{ $choice }}</div>
                    </div>
                @endforeach
            </div>

            <div class="result {{ $answer->is_correct ? 'correct' : 'wrong' }}">
                <strong>{{ $answer->is_correct ? '정답입니다.' : '오답입니다.' }}</strong>
                <div class="muted" style="margin-top: 6px;">
                    선택: {{ $selectedDisplayIndex ?? '-' }} / 정답: {{ $correctDisplayIndex ?? '-' }}
                    @if($answer->wrong_reason)
                        · 오답유형: {{ $answer->wrong_reason }}
                    @endif
                    @if($answer->confidence)
                        · confidence: {{ $answer->confidence }}
                    @endif
                </div>
            </div>

            <details>
                <summary>해설 보기</summary>
                <p>{{ $explanation ?: '등록된 해설이 없습니다.' }}</p>
            </details>

            <form method="POST" action="{{ route('practice.next', ['session' => $session->id]) }}" style="margin-top: 16px;">
                @csrf
                <input type="hidden" name="lang" value="{{ $lang }}">
                <button class="btn primary" type="submit">다음</button>
            </form>
        @else
            <form method="POST" action="{{ route('practice.answer', ['session' => $session->id]) }}">
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

                <div class="field-row">
                    <div>
                        <label for="wrong_reason">오답 시 유형</label>
                        <select id="wrong_reason" name="wrong_reason">
                            <option value="">선택 안 함</option>
                            <option value="개념부족">개념부족</option>
                            <option value="헷갈림">헷갈림</option>
                            <option value="실수">실수</option>
                            <option value="시간부족">시간부족</option>
                            <option value="찍음">찍음</option>
                        </select>
                    </div>
                    <div>
                        <label for="confidence">Confidence</label>
                        <select id="confidence" name="confidence">
                            <option value="medium">medium</option>
                            <option value="low">low</option>
                            <option value="high">high</option>
                        </select>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
</body>
</html>

