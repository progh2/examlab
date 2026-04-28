<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('exam_id')
                    ->relationship('exam', 'id')
                    ->required(),
                Select::make('section_id')
                    ->relationship('section', 'id'),
                Select::make('topic_id')
                    ->relationship('topic', 'id'),
                Select::make('qa_status')
                    ->required()
                    ->options([
                        'draft' => 'draft',
                        'review' => 'review',
                        'approved' => 'approved',
                        'rejected' => 'rejected',
                    ])
                    ->default('draft'),
                Group::make()
                    ->schema([
                        Textarea::make('stem_localized.ko')
                            ->label('문제(한국어)')
                            ->rows(4)
                            ->required(),
                        Textarea::make('stem_localized.en')
                            ->label('문제(영문)')
                            ->rows(4),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Group::make()
                    ->schema([
                        Textarea::make('explanation_localized.ko')
                            ->label('해설(한국어)')
                            ->rows(4),
                        Textarea::make('explanation_localized.en')
                            ->label('해설(영문)')
                            ->rows(4),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Select::make('correct_choice_index')
                    ->required()
                    ->options([
                        1 => '1',
                        2 => '2',
                        3 => '3',
                        4 => '4',
                    ]),
                Select::make('difficulty')
                    ->options([
                        1 => '1',
                        2 => '2',
                        3 => '3',
                        4 => '4',
                        5 => '5',
                    ]),
                Textarea::make('source')
                    ->columnSpanFull(),
                Repeater::make('choices')
                    ->relationship()
                    ->schema([
                        Select::make('choice_index')
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
                            ->required(),
                        Textarea::make('text_localized.ko')
                            ->label('보기(한국어)')
                            ->rows(2),
                        Textarea::make('text_localized.en')
                            ->label('보기(영문)')
                            ->rows(2),
                        TextInput::make('image_url')
                            ->label('보기 이미지 URL(옵션)'),
                    ])
                    ->columns(2)
                    ->orderable(false)
                    ->addActionLabel('보기 추가')
                    ->defaultItems(4)
                    ->minItems(4)
                    ->maxItems(4)
                    ->columnSpanFull()
                    ->helperText('보기는 4개 고정입니다. (표시 순서 랜덤은 풀이 세션에서 처리)'),
            ]);
    }
}
