<?php

namespace App\Filament\Resources\QuestionSets;

use App\Filament\Resources\QuestionSets\Pages\CreateQuestionSet;
use App\Filament\Resources\QuestionSets\Pages\EditQuestionSet;
use App\Filament\Resources\QuestionSets\Pages\ListQuestionSets;
use App\Filament\Resources\QuestionSets\Schemas\QuestionSetForm;
use App\Filament\Resources\QuestionSets\Tables\QuestionSetsTable;
use App\Models\QuestionSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuestionSetResource extends Resource
{
    protected static ?string $model = QuestionSet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return QuestionSetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionSetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionSets::route('/'),
            'create' => CreateQuestionSet::route('/create'),
            'edit' => EditQuestionSet::route('/{record}/edit'),
        ];
    }
}
