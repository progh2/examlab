<?php

namespace App\Filament\Pages;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\Section;
use App\Models\Topic;
use App\Support\TenantContext;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Forms\Form;
use BackedEnum;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImportQuestions extends Page
{
    protected string $view = 'filament.pages.import-questions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public ?int $examId = null;

    public bool $autoApprove = false;

    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];

    /** @var array<int, string> */
    public array $errors = [];

    public ?TemporaryUploadedFile $csvFile = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('examId')
                    ->label('대상 Exam')
                    ->options(function (): array {
                        $tenantId = TenantContext::currentTenantId();
                        if (!$tenantId) {
                            return [];
                        }

                        return Exam::query()
                            ->where('tenant_id', $tenantId)
                            ->orderBy('code')
                            ->get()
                            ->mapWithKeys(fn (Exam $e) => [$e->id => ($e->code ?: $e->id) . ' - ' . ($e->name_localized['ko'] ?? $e->name_localized['en'] ?? '')])
                            ->all();
                    })
                    ->required(),
                Toggle::make('autoApprove')
                    ->label('업로드한 문제를 승인(approved)으로 등록')
                    ->helperText('끄면 draft로 등록됩니다.'),
                FileUpload::make('csvFile')
                    ->label('CSV 파일')
                    ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ->required()
                    ->disk('local')
                    ->directory('imports')
                    ->preserveFilenames(),
            ]);
    }

    public function import(): void
    {
        $this->errors = [];
        $this->previewRows = [];

        $this->validate([
            'examId' => ['required', 'integer', 'exists:exams,id'],
            'csvFile' => ['required'],
        ]);

        $exam = Exam::query()->findOrFail($this->examId);
        $tenantId = TenantContext::currentTenantId();
        if (!$tenantId || $exam->tenant_id !== $tenantId) {
            $this->errors[] = '현재 테넌트에서 접근 가능한 Exam이 아닙니다.';
            return;
        }

        /** @var TemporaryUploadedFile $file */
        $file = $this->csvFile;
        $path = $file->getRealPath();
        if (!$path) {
            $this->errors[] = '업로드 파일을 읽을 수 없습니다.';
            return;
        }

        $reader = Reader::createFromPath($path, 'r');
        $reader->setHeaderOffset(0);

        $requiredCols = [
            'section_key',
            'topic_key',
            'qa_status',
            'stem_ko',
            'stem_en',
            'choice1_ko', 'choice2_ko', 'choice3_ko', 'choice4_ko',
            'choice1_en', 'choice2_en', 'choice3_en', 'choice4_en',
            'correct_choice_index',
            'explanation_ko',
            'explanation_en',
        ];

        $headers = $reader->getHeader();
        $missing = array_values(array_diff($requiredCols, $headers));
        if (count($missing) > 0) {
            $this->errors[] = 'CSV 헤더가 누락되었습니다: ' . implode(', ', $missing);
            return;
        }

        $rows = iterator_to_array($reader->getRecords(), false);
        if (count($rows) === 0) {
            $this->errors[] = 'CSV에 데이터가 없습니다.';
            return;
        }

        DB::transaction(function () use ($rows, $exam) {
            foreach ($rows as $i => $row) {
                $line = $i + 2; // header=1

                $sectionKey = trim((string) $row['section_key']);
                $topicKey = trim((string) $row['topic_key']);
                $qaStatus = trim((string) $row['qa_status']);
                $correct = (int) $row['correct_choice_index'];

                if ($sectionKey === '') {
                    $this->errors[] = "L{$line}: section_key가 비어있습니다.";
                    continue;
                }
                if ($topicKey === '') {
                    $this->errors[] = "L{$line}: topic_key가 비어있습니다.";
                    continue;
                }
                if (!in_array($qaStatus, ['draft', 'review', 'approved', 'rejected'], true)) {
                    $qaStatus = $this->autoApprove ? 'approved' : 'draft';
                }
                if ($correct < 1 || $correct > 4) {
                    $this->errors[] = "L{$line}: correct_choice_index는 1~4여야 합니다.";
                    continue;
                }

                $section = Section::query()->firstOrCreate(
                    ['exam_id' => $exam->id, 'key' => $sectionKey],
                    ['name_localized' => ['ko' => $sectionKey, 'en' => $sectionKey], 'sort_order' => 0],
                );

                $topic = Topic::query()->firstOrCreate(
                    ['section_id' => $section->id, 'key' => $topicKey],
                    ['name_localized' => ['ko' => $topicKey, 'en' => $topicKey], 'sort_order' => 0],
                );

                $q = Question::query()->create([
                    'exam_id' => $exam->id,
                    'section_id' => $section->id,
                    'topic_id' => $topic->id,
                    'qa_status' => $this->autoApprove ? 'approved' : $qaStatus,
                    'stem_localized' => [
                        'ko' => (string) $row['stem_ko'],
                        'en' => (string) $row['stem_en'],
                    ],
                    'explanation_localized' => [
                        'ko' => (string) $row['explanation_ko'],
                        'en' => (string) $row['explanation_en'],
                    ],
                    'correct_choice_index' => $correct,
                    'difficulty' => isset($row['difficulty']) && $row['difficulty'] !== '' ? (int) $row['difficulty'] : null,
                    'source' => [
                        'import' => true,
                        'line' => $line,
                    ],
                ]);

                for ($idx = 1; $idx <= 4; $idx++) {
                    QuestionChoice::query()->create([
                        'question_id' => $q->id,
                        'choice_index' => $idx,
                        'text_localized' => [
                            'ko' => (string) $row["choice{$idx}_ko"],
                            'en' => (string) $row["choice{$idx}_en"],
                        ],
                    ]);
                }

                if ($i < 5) {
                    $this->previewRows[] = [
                        'line' => $line,
                        'section_key' => $sectionKey,
                        'topic_key' => $topicKey,
                        'stem_ko' => (string) $row['stem_ko'],
                        'correct_choice_index' => $correct,
                    ];
                }
            }
        });
    }
}
