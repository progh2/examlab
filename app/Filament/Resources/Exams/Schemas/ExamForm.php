<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('공용 시험이면 비워두고, 기관 전용 시험이면 테넌트를 선택합니다.'),
                TextInput::make('code'),
                Group::make()
                    ->schema([
                        TextInput::make('name_localized.ko')
                            ->label('시험명(한국어)')
                            ->required(),
                        TextInput::make('name_localized.en')
                            ->label('시험명(영문)')
                            ->helperText('해외 시험은 영문을 함께 넣어두면 학습 화면에서 토글/병기가 가능합니다.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Select::make('primary_locale')
                    ->required()
                    ->options([
                        'ko' => 'ko',
                        'en' => 'en',
                    ])
                    ->default('ko'),
                Select::make('blueprint_type')
                    ->required()
                    ->options([
                        'subject' => 'subject(과목형)',
                        'domain' => 'domain(도메인형)',
                        'hybrid' => 'hybrid(혼합)',
                    ])
                    ->default('hybrid'),
            ]);
    }
}
