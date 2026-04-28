<?php

namespace App\Filament\Resources\QuestionSets\Pages;

use App\Filament\Resources\QuestionSets\QuestionSetResource;
use App\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestionSet extends CreateRecord
{
    protected static string $resource = QuestionSetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = TenantContext::currentTenantId();

        return $data;
    }
}
