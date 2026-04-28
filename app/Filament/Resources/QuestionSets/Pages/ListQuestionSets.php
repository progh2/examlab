<?php

namespace App\Filament\Resources\QuestionSets\Pages;

use App\Filament\Resources\QuestionSets\QuestionSetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestionSets extends ListRecords
{
    protected static string $resource = QuestionSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
