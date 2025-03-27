<?php

namespace App\Filament\Resources;

use App\Filament\Exports\LoanItemExporter;
use App\Filament\Resources\LoanItemResource\Pages;
use App\Models\Item;
use App\Models\LoanItem;
use Filament\Actions\ExportAction;
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
use Filament\Tables\Actions\ExportBulkAction;
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
                            ->name("left_item_{$item->id}_quantity") // Add this line
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(function () use ($item) {
                                return $item->stock; 
                            })
                            ->reactive()
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
                            ->name("right_item_{$item->id}_quantity") // Add this line
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(function () use ($item) {
                                return $item->stock; 
                            })
                            ->reactive()
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
                            ->required()
                            ->label('Peminjam'),
                        TextInput::make('program')
                            ->required()
                            ->label('Program'),
                        TextInput::make('location')
                            ->required()
                            ->label('Lokasi'),
                        DatePicker::make('booking_date')
                            ->required()
                            ->label('Tanggal Booking'),
                        TimePicker::make('start_booking')
                            ->required()
                            ->seconds(false)
                            ->label('Jam Booking'),
                        DatePicker::make('return_date')
                            ->required()
                            ->label('Tanggal Pengembalian'),
                        TextInput::make('user.division')
                            ->required()
                            ->default($user->division['name'])
                            ->label('Divisi'),
                    ]),
                    Section::make('Review')
                    ->schema([
                        TextInput::make('producer_name')
                            ->required()
                            ->label('Nama Produser'),
                        TextInput::make('producer_telp')
                            ->required()
                            ->label('Telp. Produser'),
                        TextInput::make('crew_name')
                            ->required()
                            ->label('Nama Crew'),
                        TextInput::make('crew_telp')
                            ->required()
                            ->label('Telp. Crew'),
                        TextInput::make('crew_division')
                            ->required()
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
                                ->required()
                                ->label('Nama'),
                            TextInput::make('approver_telp')
                                ->required()
                                ->label('Telp.'),
                            Radio::make('approval_status')
                                ->required()
                                ->label('Approval')
                                ->options([
                                    'Approve' => 'Approve',
                                    'Decline' => 'Decline',
                                    'Pending' => 'Pending',
                                ]),
                            Radio::make('return_status')
                                ->label('Status Pengembalian')
                                ->required()
                                ->options([
                                    'Sudah Dikembalikan' => 'Sudah Dikembalikan',
                                    'Belum Dikembalikan' => 'Belum Dikembalikan',
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
                    ->label('Approval')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approve' => 'success',
                        'Decline' => 'danger',
                        'Pending' => 'warning',
                    }),
                TextColumn::make('return_status')
                    ->label('Status Pengembalian')
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->exporter(LoanItemExporter::class)
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
           
        ]);
    
        // Create the loan item
        $loanItem = LoanItem::create([
          
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