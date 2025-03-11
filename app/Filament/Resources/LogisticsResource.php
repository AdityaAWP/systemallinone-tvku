<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogisticsResource\Pages;
use App\Filament\Resources\LogisticsResource\RelationManagers;
use App\Models\Item;
use App\Models\Logistics;
use Filament\Forms;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LogisticsResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationLabel = 'Logistics';
    protected static ?string $label = 'Logistics';

    
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                Radio::make('category')
                    ->label('Kategori Barang')
                    ->required()
                    ->columnSpanFull()
                    ->options([
                        'video' => 'Video',
                        'audio' => 'Audio',
                        'lighting' => 'Lighting',
                        'lain-lain' => 'Lain-lain',
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListLogistics::route('/'),
            'create' => Pages\CreateLogistics::route('/create'),
            'edit' => Pages\EditLogistics::route('/{record}/edit'),
        ];
    }
}
