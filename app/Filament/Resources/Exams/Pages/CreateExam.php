<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 공용 시험(tenant_id null)도 가능하지만, 기본값은 현재 테넌트 시험으로 생성합니다.
        $data['tenant_id'] = $data['tenant_id'] ?? TenantContext::currentTenantId();

        return $data;
    }
}
