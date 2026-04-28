<?php

namespace App\Filament\Resources\QuestionSets;

use App\Filament\Resources\QuestionSets\Pages\CreateQuestionSet;
use App\Filament\Resources\QuestionSets\Pages\EditQuestionSet;
use App\Filament\Resources\QuestionSets\Pages\ListQuestionSets;
use App\Filament\Resources\QuestionSets\Schemas\QuestionSetForm;
use App\Filament\Resources\QuestionSets\Tables\QuestionSetsTable;
use App\Models\QuestionSet;
use App\Support\TenantContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $tenantId = TenantContext::currentTenantId();
        if (!$tenantId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('tenant_id', $tenantId);
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
