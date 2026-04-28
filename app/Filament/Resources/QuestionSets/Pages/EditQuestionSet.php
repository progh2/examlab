<?php

namespace App\Filament\Resources\QuestionSets\Pages;

use App\Filament\Resources\QuestionSets\QuestionSetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuestionSet extends EditRecord
{
    protected static string $resource = QuestionSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
