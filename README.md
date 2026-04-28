# examlab

자격증 기출문제/문제은행 연습 웹앱(기관/학원 테넌트 지원) 프로젝트입니다.

## 핵심 기능(요약)
- **구글 로그인** 기반 사용자별 학습 이력/통계
- **문항별 시도/정오답 누적** 및 **오답노트 자동 생성**
- 풀이 모드: **전체(순차)** / **맞춤형(취약+복습 큐 기반)** / **오답만**
- **기억망각곡선 기반 복습 스케줄(단계형 간격 반복)**: 오늘/밀린 복습 큐
- **시험 모드(CBT)**: 타이머, 마킹/체크, 미풀이 경고, 제출/채점/해설
- 관리자/강사 콘솔: **문제 등록(이미지 옵션)**, **CSV/엑셀 업로드**, **검색/필터**, **검수 워크플로**, **과제 배정**, **학습 현황 대시보드**
- 시험 구조: **국가자격(과목형)** + **해외/벤더(도메인형)** 혼합 지원(예: 정보처리기능사, AWS AIF-C01)

## 문서
- 기획/설계: `docs/product-plan.md`

## 로컬 실행(개발)
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### MySQL로 전환(옵션)
- **Docker 사용 시**: 아래로 MySQL 8 컨테이너를 띄울 수 있습니다.
```bash
docker compose -f docker-compose.mysql.yml up -d
```
- 그 다음 `.env`에서 DB 설정을 MySQL로 바꾸고 마이그레이션을 실행합니다.
```bash
php artisan migrate:fresh --seed
```

### Google 로그인 설정
- Google Cloud Console에서 **OAuth 클라이언트(웹 애플리케이션)** 를 만들고 아래를 설정합니다.
  - **Authorized redirect URIs**: `http://localhost/auth/google/callback`
- 그리고 프로젝트의 `.env`에 아래 값을 채웁니다.
  - `GOOGLE_CLIENT_ID` = 발급된 Client ID
  - `GOOGLE_CLIENT_SECRET` = 발급된 Client Secret
  - `GOOGLE_REDIRECT_URI` = `http://localhost/auth/google/callback`

로그인은 `http://localhost:8000/auth/google`로 시작할 수 있습니다.

### 관리자(필라멘트) 계정 만들기
```bash
php artisan make:filament-user
```

