<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InternSchoolResource\Pages;
use App\Models\InternSchool;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class InternSchoolResource extends Resource
{
    protected static ?string $model = InternSchool::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Master Data Sekolah';

    protected static ?string $modelLabel = 'Sekolah/Instansi';

    protected static ?string $pluralModelLabel = 'Master Data Sekolah dan Perguruan Tinggi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Sekolah/Instansi')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Kategori')
                    ->options([
                        'Perguruan Tinggi' => 'Perguruan Tinggi',
                        'SMA/SMK' => 'SMA/SMK',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Sekolah/Instansi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Kategori')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Kategori')
                    ->options([
                        'Perguruan Tinggi' => 'Perguruan Tinggi',
                        'SMA/SMK' => 'SMA/SMK',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInternSchools::route('/'),
            'create' => Pages\CreateInternSchool::route('/create'),
            'edit' => Pages\EditInternSchool::route('/{record}/edit'),
        ];
    }
}