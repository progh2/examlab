<?php

namespace App\Filament\Resources\Questions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('exam.code')
                    ->label('Exam')
                    ->searchable(),
                TextColumn::make('qa_status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('stem_localized.ko')
                    ->label('문제(ko)')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('stem_localized.en')
                    ->label('문제(en)')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('correct_choice_index')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('difficulty')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('qa_status')
                    ->options([
                        'draft' => 'draft',
                        'review' => 'review',
                        'approved' => 'approved',
                        'rejected' => 'rejected',
                    ]),
                SelectFilter::make('exam_id')
                    ->relationship('exam', 'code')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
