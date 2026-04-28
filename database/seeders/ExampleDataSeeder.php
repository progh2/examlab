<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionStat;
use App\Models\ReviewItem;
use App\Models\Section;
use App\Models\Tenant;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ExampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo-academy'],
            [
                'name' => 'Demo Academy',
            ],
        );

        // 로컬 개발 편의를 위해, Filament 관리자용 기본 사용자(시드)를 하나 만들어 둡니다.
        // 실제 운영에서는 `php artisan make:filament-user` 또는 Google 로그인으로 생성된 계정을 사용하세요.
        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@demo.local'],
            [
                'name' => 'Demo Admin',
                'password' => 'password',
                'is_system_admin' => true,
            ],
        );

        // 관리자 사용자를 demo 테넌트에 소속시키고, 현재 테넌트를 demo로 잡아줍니다.
        $adminUser->tenants()->syncWithoutDetaching([
            $tenant->id => ['role' => 'owner'],
        ]);
        $adminUser->forceFill(['current_tenant_id' => $tenant->id])->save();

        $examKo = Exam::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'INFO-PROC-CRAFT'],
            [
                'name_localized' => ['ko' => '정보처리기능사', 'en' => 'Information Processing Craftsman'],
                'primary_locale' => 'ko',
                'blueprint_type' => 'subject',
            ],
        );

        $examAws = Exam::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'AIF-C01'],
            [
                'name_localized' => ['ko' => 'AWS AI Practitioner', 'en' => 'AWS AI Practitioner (AIF-C01)'],
                'primary_locale' => 'en',
                'blueprint_type' => 'domain',
            ],
        );

        $section1 = Section::query()->firstOrCreate(
            ['exam_id' => $examKo->id, 'key' => 'subject-1'],
            ['name_localized' => ['ko' => '과목 1', 'en' => 'Subject 1'], 'sort_order' => 1],
        );

        $topic1 = Topic::query()->firstOrCreate(
            ['section_id' => $section1->id, 'key' => 'topic-1'],
            ['name_localized' => ['ko' => '기초 개념', 'en' => 'Basics'], 'sort_order' => 1],
        );

        $awsDomain1 = Section::query()->firstOrCreate(
            ['exam_id' => $examAws->id, 'key' => 'domain-1'],
            ['name_localized' => ['ko' => '도메인 1', 'en' => 'Domain 1'], 'sort_order' => 1],
        );

        $awsTask1 = Topic::query()->firstOrCreate(
            ['section_id' => $awsDomain1->id, 'key' => 'task-1'],
            ['name_localized' => ['ko' => '태스크 1', 'en' => 'Task 1'], 'sort_order' => 1],
        );

        $seededQuestions = [];
        foreach ($this->questions() as $seed) {
            $exam = $seed['exam'] === 'ko' ? $examKo : $examAws;
            $section = $seed['exam'] === 'ko' ? $section1 : $awsDomain1;
            $topic = $seed['exam'] === 'ko' ? $topic1 : $awsTask1;

            $seededQuestions[$seed['key']] = $this->seedQuestion(
                exam: $exam,
                section: $section,
                topic: $topic,
                key: $seed['key'],
                stem: $seed['stem'],
                explanation: $seed['explanation'],
                correctIndex: $seed['correct'],
                choices: $seed['choices'],
                difficulty: $seed['difficulty'] ?? 2,
            );
        }

        $now = CarbonImmutable::now();
        foreach (['tcp-handshake', 'db-index', 'ml-overfit', 'ai-bias'] as $offset => $key) {
            $question = $seededQuestions[$key] ?? null;
            if (!$question) {
                continue;
            }

            QuestionStat::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $adminUser->id, 'question_id' => $question->id],
                [
                    'attempts' => 2 + $offset,
                    'correct_count' => $offset === 0 ? 1 : 0,
                    'wrong_count' => 1 + $offset,
                    'last_seen_at' => $now->subHours($offset + 1),
                    'last_result' => false,
                    'wrong_streak' => 1 + $offset,
                ],
            );
        }

        foreach (['tcp-handshake', 'os-process-thread', 'ml-overfit', 'ai-responsibility'] as $offset => $key) {
            $question = $seededQuestions[$key] ?? null;
            if (!$question) {
                continue;
            }

            ReviewItem::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $adminUser->id, 'question_id' => $question->id],
                [
                    'stage' => $offset,
                    'due_at' => $now->subMinutes(60 * ($offset + 1)),
                    'last_result' => $offset % 2 === 0 ? false : true,
                    'last_answered_at' => $now->subDays($offset + 1),
                    'wrong_streak' => $offset % 2 === 0 ? 1 : 0,
                    'suspended' => false,
                ],
            );
        }
    }

    /**
     * @param array<int, array{ko?: string, en?: string}> $choices
     */
    private function seedQuestion(
        Exam $exam,
        Section $section,
        Topic $topic,
        string $key,
        array $stem,
        array $explanation,
        int $correctIndex,
        array $choices,
        int $difficulty = 2,
    ): Question {
        $q = Question::query()
            ->where('exam_id', $exam->id)
            ->where('source->seed_key', $key)
            ->first() ?? new Question(['exam_id' => $exam->id]);

        $q->forceFill([
            'section_id' => $section->id,
            'topic_id' => $topic->id,
            'qa_status' => 'approved',
            'stem_localized' => $stem,
            'explanation_localized' => $explanation,
            'correct_choice_index' => $correctIndex,
            'difficulty' => $difficulty,
            'source' => ['seed' => true, 'seed_key' => $key],
        ])->save();

        foreach ($choices as $idx => $textLocalized) {
            QuestionChoice::query()->updateOrCreate(
                ['question_id' => $q->id, 'choice_index' => (int) $idx],
                ['text_localized' => $textLocalized],
            );
        }

        return $q;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questions(): array
    {
        return [
            [
                'exam' => 'ko',
                'key' => 'tcp-handshake',
                'stem' => ['ko' => 'TCP/IP에서 3-way handshake의 목적은?', 'en' => 'What is the purpose of the TCP/IP 3-way handshake?'],
                'explanation' => ['ko' => '연결을 수립하고 양쪽의 시퀀스 번호를 동기화한다.', 'en' => 'It establishes a connection and synchronizes sequence numbers.'],
                'correct' => 2,
                'choices' => [
                    1 => ['ko' => '데이터 암호화', 'en' => 'Encrypt data'],
                    2 => ['ko' => '연결 수립 및 동기화', 'en' => 'Establish connection and synchronize'],
                    3 => ['ko' => '라우팅 테이블 갱신', 'en' => 'Update routing table'],
                    4 => ['ko' => '세션 종료', 'en' => 'Terminate session'],
                ],
            ],
            [
                'exam' => 'ko',
                'key' => 'db-index',
                'stem' => ['ko' => '데이터베이스 인덱스의 주된 목적은?', 'en' => 'What is the main purpose of a database index?'],
                'explanation' => ['ko' => '인덱스는 검색 조건에 맞는 행을 빠르게 찾도록 돕는다.', 'en' => 'An index helps locate rows matching search conditions quickly.'],
                'correct' => 1,
                'choices' => [
                    1 => ['ko' => '검색 성능 향상', 'en' => 'Improve query performance'],
                    2 => ['ko' => '항상 저장 공간 절감', 'en' => 'Always reduce storage'],
                    3 => ['ko' => '데이터 암호화', 'en' => 'Encrypt data'],
                    4 => ['ko' => '트랜잭션 제거', 'en' => 'Remove transactions'],
                ],
            ],
            [
                'exam' => 'ko',
                'key' => 'os-process-thread',
                'stem' => ['ko' => '프로세스와 스레드의 관계로 옳은 것은?', 'en' => 'Which statement about processes and threads is correct?'],
                'explanation' => ['ko' => '스레드는 프로세스 내부의 실행 단위이며 자원을 일부 공유한다.', 'en' => 'Threads are execution units within a process and share some resources.'],
                'correct' => 3,
                'choices' => [
                    1 => ['ko' => '스레드는 항상 별도 주소 공간을 가진다', 'en' => 'Threads always have separate address spaces'],
                    2 => ['ko' => '프로세스는 CPU 명령을 실행하지 않는다', 'en' => 'Processes never execute CPU instructions'],
                    3 => ['ko' => '스레드는 프로세스 안에서 실행된다', 'en' => 'Threads run within a process'],
                    4 => ['ko' => '둘은 완전히 같은 개념이다', 'en' => 'They are exactly the same concept'],
                ],
            ],
            [
                'exam' => 'ko',
                'key' => 'normalization',
                'stem' => ['ko' => '관계형 데이터베이스 정규화의 효과는?', 'en' => 'What is an effect of relational database normalization?'],
                'explanation' => ['ko' => '정규화는 중복을 줄이고 갱신 이상을 완화한다.', 'en' => 'Normalization reduces redundancy and update anomalies.'],
                'correct' => 4,
                'choices' => [
                    1 => ['ko' => '모든 JOIN 제거', 'en' => 'Remove every JOIN'],
                    2 => ['ko' => '무조건 응답속도 증가', 'en' => 'Always increase response speed'],
                    3 => ['ko' => '키 제약 제거', 'en' => 'Remove key constraints'],
                    4 => ['ko' => '중복과 이상 현상 감소', 'en' => 'Reduce redundancy and anomalies'],
                ],
            ],
            [
                'exam' => 'ko',
                'key' => 'http-status',
                'stem' => ['ko' => 'HTTP 상태 코드 404의 의미는?', 'en' => 'What does HTTP status code 404 mean?'],
                'explanation' => ['ko' => '404는 요청한 리소스를 찾을 수 없음을 의미한다.', 'en' => '404 means the requested resource was not found.'],
                'correct' => 2,
                'choices' => [
                    1 => ['ko' => '서버 내부 오류', 'en' => 'Internal server error'],
                    2 => ['ko' => '리소스를 찾을 수 없음', 'en' => 'Resource not found'],
                    3 => ['ko' => '요청 성공', 'en' => 'Request succeeded'],
                    4 => ['ko' => '권한 없음', 'en' => 'Unauthorized'],
                ],
            ],
            [
                'exam' => 'ko',
                'key' => 'sql-injection',
                'stem' => ['ko' => 'SQL Injection 방어에 가장 직접적인 방법은?', 'en' => 'Which method most directly prevents SQL Injection?'],
                'explanation' => ['ko' => 'Prepared statement는 값과 SQL 구조를 분리해 주입 위험을 줄인다.', 'en' => 'Prepared statements separate values from SQL structure and reduce injection risk.'],
                'correct' => 1,
                'choices' => [
                    1 => ['ko' => 'Prepared statement 사용', 'en' => 'Use prepared statements'],
                    2 => ['ko' => '비밀번호를 평문 저장', 'en' => 'Store passwords in plain text'],
                    3 => ['ko' => '인덱스 모두 삭제', 'en' => 'Delete all indexes'],
                    4 => ['ko' => '로그 비활성화', 'en' => 'Disable logs'],
                ],
            ],
            [
                'exam' => 'aws',
                'key' => 'ml-overfit',
                'stem' => ['ko' => '기계학습 모델의 과적합(overfitting)을 줄이는 방법으로 가장 적절한 것은?', 'en' => 'Which approach is most appropriate to reduce overfitting in a machine learning model?'],
                'explanation' => ['ko' => '정규화/드롭아웃/데이터 증강 등이 대표적이다.', 'en' => 'Common approaches include regularization, dropout, and data augmentation.'],
                'correct' => 3,
                'choices' => [
                    1 => ['ko' => '학습률을 항상 크게 유지', 'en' => 'Always keep a high learning rate'],
                    2 => ['ko' => '훈련 데이터만으로 평가', 'en' => 'Evaluate only on training data'],
                    3 => ['ko' => '정규화 적용', 'en' => 'Apply regularization'],
                    4 => ['ko' => '특성 수를 늘리기만 함', 'en' => 'Only increase number of features'],
                ],
            ],
            [
                'exam' => 'aws',
                'key' => 'ai-bias',
                'stem' => ['ko' => 'AI 모델 편향을 줄이기 위한 활동은?', 'en' => 'Which activity helps reduce bias in an AI model?'],
                'explanation' => ['ko' => '대표성 있는 데이터와 공정성 평가는 편향 완화에 중요하다.', 'en' => 'Representative data and fairness evaluation are important for mitigating bias.'],
                'correct' => 4,
                'choices' => [
                    1 => ['ko' => '소수 집단 데이터 제거', 'en' => 'Remove minority group data'],
                    2 => ['ko' => '평가 지표 숨기기', 'en' => 'Hide evaluation metrics'],
                    3 => ['ko' => '데이터 출처 무시', 'en' => 'Ignore data sources'],
                    4 => ['ko' => '대표성 있는 데이터 수집', 'en' => 'Collect representative data'],
                ],
            ],
            [
                'exam' => 'aws',
                'key' => 'generative-ai',
                'stem' => ['ko' => '생성형 AI의 일반적인 활용 사례는?', 'en' => 'What is a common use case for generative AI?'],
                'explanation' => ['ko' => '생성형 AI는 텍스트, 이미지, 코드 등 새 콘텐츠 생성에 활용된다.', 'en' => 'Generative AI is used to create new text, images, code, and other content.'],
                'correct' => 2,
                'choices' => [
                    1 => ['ko' => '디스크 조각 모음만 수행', 'en' => 'Only defragment disks'],
                    2 => ['ko' => '새로운 콘텐츠 생성', 'en' => 'Create new content'],
                    3 => ['ko' => '라우터 케이블 교체', 'en' => 'Replace router cables'],
                    4 => ['ko' => '전원 공급 장치 테스트', 'en' => 'Test power supplies'],
                ],
            ],
            [
                'exam' => 'aws',
                'key' => 'supervised-learning',
                'stem' => ['ko' => '지도학습(supervised learning)에 필요한 데이터 특징은?', 'en' => 'What data characteristic is required for supervised learning?'],
                'explanation' => ['ko' => '지도학습은 입력과 정답 라벨의 쌍을 사용해 학습한다.', 'en' => 'Supervised learning trains on pairs of inputs and labels.'],
                'correct' => 1,
                'choices' => [
                    1 => ['ko' => '라벨이 있는 예시', 'en' => 'Labeled examples'],
                    2 => ['ko' => '항상 라벨 없는 로그', 'en' => 'Always unlabeled logs'],
                    3 => ['ko' => '암호화 키', 'en' => 'Encryption keys'],
                    4 => ['ko' => '운영체제 커널', 'en' => 'Operating system kernel'],
                ],
            ],
            [
                'exam' => 'aws',
                'key' => 'model-evaluation',
                'stem' => ['ko' => '분류 모델 평가에 쓰이는 지표는?', 'en' => 'Which metric can be used to evaluate a classification model?'],
                'explanation' => ['ko' => '정확도, 정밀도, 재현율 등은 분류 모델 평가 지표다.', 'en' => 'Accuracy, precision, and recall are classification metrics.'],
                'correct' => 3,
                'choices' => [
                    1 => ['ko' => 'CPU 소켓 수', 'en' => 'Number of CPU sockets'],
                    2 => ['ko' => '케이블 길이', 'en' => 'Cable length'],
                    3 => ['ko' => '정밀도와 재현율', 'en' => 'Precision and recall'],
                    4 => ['ko' => '디스크 색상', 'en' => 'Disk color'],
                ],
            ],
            [
                'exam' => 'aws',
                'key' => 'ai-responsibility',
                'stem' => ['ko' => '책임 있는 AI 원칙에 가까운 것은?', 'en' => 'Which item is closest to a responsible AI principle?'],
                'explanation' => ['ko' => '투명성, 공정성, 보안, 개인정보 보호는 책임 있는 AI의 핵심이다.', 'en' => 'Transparency, fairness, security, and privacy are key responsible AI principles.'],
                'correct' => 2,
                'choices' => [
                    1 => ['ko' => '모든 판단 근거 은폐', 'en' => 'Hide every basis for decisions'],
                    2 => ['ko' => '투명성과 공정성 고려', 'en' => 'Consider transparency and fairness'],
                    3 => ['ko' => '개인정보 무제한 수집', 'en' => 'Collect unlimited personal data'],
                    4 => ['ko' => '검증 없이 배포', 'en' => 'Deploy without validation'],
                ],
            ],
        ];
    }
}
