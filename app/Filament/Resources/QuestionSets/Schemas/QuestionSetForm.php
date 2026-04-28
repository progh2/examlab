<?php

namespace App\Filament\Resources\QuestionSets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QuestionSetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('exam_id')
                    ->relationship('exam', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('set_type')
                    ->required()
                    ->options([
                        'pastpaper' => 'pastpaper(기출 세트)',
                        'topic' => 'topic(토픽 세트)',
                        'mixed' => 'mixed(혼합)',
                    ])
                    ->default('mixed'),
                Textarea::make('config')
                    ->columnSpanFull(),
            ]);
    }
}
