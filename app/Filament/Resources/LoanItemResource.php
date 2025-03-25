<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanItemResource\Pages;
use App\Models\Item;
use App\Models\LoanItem;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LoanItemResource extends Resource
{
    protected static ?string $model = LoanItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Peminjaman';


    

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $items = Item::all();

        $itemsByCategory = $items->groupBy('category');

        $categorySections = [];
        foreach ($itemsByCategory as $category => $categoryItems) {
            $categorySections[] = Section::make($category)
                ->schema([
                    Select::make('item_id')
                        ->label('Pilih Item')
                        ->options($categoryItems->pluck('name', 'id'))
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Jumlah')
                ]);
        }

        return $form
            ->schema([
                // Existing user and loan information sections
                Split::make([
                    Section::make('Keterangan Peminjaman')
                    ->schema([
                        TextInput::make('user.name')
                            ->default($user->name)
                            ->label('Peminjam'),
                        TextInput::make('program')
                            ->label('Program'),
                        TextInput::make('location')
                            ->label('Lokasi'),
                        TextInput::make('booking_date')
                            ->label('Tanggal Booking'),
                        TextInput::make('start_booking')
                            ->label('Jam Booking'),
                        TextInput::make('return_date')
                            ->label('Tanggal Pengembalian'),
                        TextInput::make('user.division')
                            ->default($user->division['name'])
                            ->label('Divisi'),
                    ]),
                    Section::make('Review')
                    ->schema([
                        TextInput::make('producer_name')
                            ->label('Nama Produser'),
                        TextInput::make('producer_telp')
                            ->label('Telp. Produser'),
                        TextInput::make('crew_name')
                            ->label('Nama Crew'),
                        TextInput::make('crew_telp')
                            ->label('Telp. Crew'),
                        TextInput::make('crew_division')
                            ->label('Divisi Crew'),
                    ])
                ])->columnSpan('full'),
                Split::make([
                    ...($categorySections)
                ])->columnSpan('full'),
                Split::make([
                    Section::make('Catatan')
                        ->schema([
                            Textarea::make('notes')
                            ->label('')
                            ->rows(10)
                            ->cols(20),
                        ]),
                    Section::make('Approval Logistik')
                        ->schema([
                            TextInput::make('approver_name')
                                ->label('Nama'),
                            TextInput::make('approver_telp')
                                ->label('Telp.'),
                            Radio::make('approval_status')
                                ->label('Status')
                                ->options([
                                    'Approve' => 'Approve',
                                    'Decline' => 'Decline',
                                    'Pending' => 'Pending',
                                ])
                        ])
                ])->columnSpan('full'),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Peminjam')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking_date')
                    ->label('Tanggal Booking')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_booking')
                    ->label('Jam Booking')
                    ->time()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approve' => 'success',
                        'Decline' => 'danger',
                        'Pending' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('return_status')
                    ->label('Pengembalian')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sudah Dikembalikan' => 'success',
                        'Belum Dikembalikan' => 'warning',
                    }),
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
            'index' => Pages\ListLoanItems::route('/'),
            'create' => Pages\CreateLoanItem::route('/create'),
            'edit' => Pages\EditLoanItem::route('/{record}/edit'),
        ];
    }
}