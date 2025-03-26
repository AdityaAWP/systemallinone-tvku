<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanItemResource\Pages;
use App\Models\Item;
use App\Models\LoanItem;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Request;

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
            $itemCount = $categoryItems->count();
            $midPoint = ceil($itemCount / 2);
    
            $leftColumn = $categoryItems->take($midPoint)->map(function ($item) {
                return Grid::make("left_item_{$item->id}")
                    ->columns(2)
                    ->schema([
                        TextInput::make("left_item_{$item->id}_name")
                            ->label($item->name)
                            ->disabled()
                            ->default($item->name)
                            ->columnSpan(1),
                        TextInput::make("left_item_{$item->id}_quantity")
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1)
                    ]);
            })->toArray();
    
            $rightColumn = $categoryItems->slice($midPoint)->map(function ($item) {
                return Grid::make("right_item_{$item->id}")
                    ->columns(2)
                    ->schema([
                        TextInput::make("right_item_{$item->id}_name")
                            ->label($item->name)
                            ->disabled()
                            ->default($item->name)
                            ->columnSpan(1),
                        TextInput::make("right_item_{$item->id}_quantity")
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1)
                    ]);
            })->toArray();
    
            $categorySections[] = Section::make($category)
                ->columns(2)
                ->schema([
                    Grid::make('left_column')
                        ->schema($leftColumn),
                    Grid::make('right_column')
                        ->schema($rightColumn)
                ]);
        }
    
        return $form
            ->schema([
                Split::make([
                    Section::make('Keterangan Peminjaman')
                    ->schema([
                        TextInput::make('user.name')
                            ->default($user->name)
                            ->label('Peminjam'),
                        TextInput::make('program')
                        ->nullable()
                        ->default('s')
                            ->label('Program'),
                        TextInput::make('location')
                            ->label('Lokasi'),
                        DatePicker::make('booking_date')
                            ->label('Tanggal Booking'),
                        TimePicker::make('start_booking')
                            ->label('Jam Booking'),
                        TimePicker::make('return_date')
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
                
                Split::make($categorySections)->columnSpan('full'),
                
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
                TextColumn::make('user.name')
                    ->label('Peminjam')
                    ->searchable(),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('booking_date')
                    ->label('Tanggal Booking')
                    ->date()
                    ->sortable(),
                TextColumn::make('items_list')
                    ->label('Item')
                    ->getStateUsing(function ($record) {
                        return $record->items->map(function ($item) {
                            return "{$item->name} (Qty: {$item->pivot->quantity})";
                        })->implode(', ');
                    }),
                TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approve' => 'success',
                        'Decline' => 'danger',
                        'Pending' => 'warning',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
    public static function createRecord(Request $request)
    {
        dd($request->all());
        $validatedData = $request->validate([
            'location' => 'required',
            'booking_date' => 'required|date',
            'start_booking' => 'required',
            // Add other validation rules
        ]);
    
        // Create the loan item
        $loanItem = LoanItem::create([
            'user_id' => auth()->id(),
            'location' => $validatedData['location'],
            'booking_date' => $validatedData['booking_date'],
            'start_booking' => $validatedData['start_booking'],
            // Add other fields
        ]);
    
        // Process the items
        $itemsData = $request->except(array_keys($validatedData));
        foreach ($itemsData as $key => $value) {
            if (str_contains($key, '_quantity') && $value > 0) {
                $itemId = str_replace(['left_item_', 'right_item_', '_quantity'], '', $key);
                $loanItem->items()->attach($itemId, ['quantity' => $value]);
            }
        }
    
        return redirect()->route('filament.resources.loan-items.index');
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