<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\Section;
use App\Models\Tenant;
use App\Models\Topic;
use Illuminate\Support\Str;
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

        $this->seedQuestion(
            exam: $examKo,
            section: $section1,
            topic: $topic1,
            stem: ['ko' => 'TCP/IP에서 3-way handshake의 목적은?', 'en' => 'What is the purpose of the TCP/IP 3-way handshake?'],
            explanation: ['ko' => '연결을 수립하고 양쪽의 시퀀스 번호를 동기화한다.', 'en' => 'It establishes a connection and synchronizes sequence numbers.'],
            correctIndex: 2,
            choices: [
                1 => ['ko' => '데이터 암호화', 'en' => 'Encrypt data'],
                2 => ['ko' => '연결 수립 및 동기화', 'en' => 'Establish connection and synchronize'],
                3 => ['ko' => '라우팅 테이블 갱신', 'en' => 'Update routing table'],
                4 => ['ko' => '세션 종료', 'en' => 'Terminate session'],
            ],
        );

        $this->seedQuestion(
            exam: $examAws,
            section: $awsDomain1,
            topic: $awsTask1,
            stem: ['ko' => '기계학습 모델의 과적합(overfitting)을 줄이는 방법으로 가장 적절한 것은?', 'en' => 'Which approach is most appropriate to reduce overfitting in a machine learning model?'],
            explanation: ['ko' => '정규화/드롭아웃/데이터 증강 등이 대표적이다.', 'en' => 'Common approaches include regularization, dropout, and data augmentation.'],
            correctIndex: 3,
            choices: [
                1 => ['ko' => '학습률을 항상 크게 유지', 'en' => 'Always keep a high learning rate'],
                2 => ['ko' => '훈련 데이터만으로 평가', 'en' => 'Evaluate only on training data'],
                3 => ['ko' => '정규화 적용', 'en' => 'Apply regularization'],
                4 => ['ko' => '특성 수를 늘리기만 함', 'en' => 'Only increase number of features'],
            ],
        );
    }

    /**
     * @param array<int, array{ko?: string, en?: string}> $choices
     */
    private function seedQuestion(
        Exam $exam,
        Section $section,
        Topic $topic,
        array $stem,
        array $explanation,
        int $correctIndex,
        array $choices,
    ): void {
        $q = Question::query()->create([
            'exam_id' => $exam->id,
            'section_id' => $section->id,
            'topic_id' => $topic->id,
            'qa_status' => 'approved',
            'stem_localized' => $stem,
            'explanation_localized' => $explanation,
            'correct_choice_index' => $correctIndex,
            'difficulty' => 2,
            'source' => ['seed' => true, 'seed_id' => Str::uuid()->toString()],
        ]);

        foreach ($choices as $idx => $textLocalized) {
            QuestionChoice::query()->create([
                'question_id' => $q->id,
                'choice_index' => (int) $idx,
                'text_localized' => $textLocalized,
            ]);
        }
    }
}
