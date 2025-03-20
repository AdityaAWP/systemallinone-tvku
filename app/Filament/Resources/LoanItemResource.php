<?php
namespace App\Filament\Resources;

use App\Filament\Resources\LoanItemResource\Pages;
use App\Models\LoanItem;
use App\Models\User;
use App\Models\Item;
use App\Models\Division;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class LoanItemResource extends Resource
{
    protected static ?string $model = LoanItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard';
    protected static ?string $navigationLabel = 'Peminjaman Barang';

    public static function form(Form $form): Form
    {
        // Get distinct item categories
        $categories = Item::select('category')->distinct()->pluck('category')->toArray();
        
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Keterangan Peminjaman')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Nama')
                                    ->options(User::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        $user = User::find($state);
                                        if ($user) {
                                            $division = $user->division;
                                            $set('crew_division', $division ? $division->name : '');
                                        }
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('location')
                                    ->label('Lokasi')
                                    ->required(),
                                Forms\Components\DatePicker::make('booking_date')
                                    ->label('Tanggal Booking')
                                    ->required(),
                                Forms\Components\TimePicker::make('start_booking')
                                    ->label('Jam Booking')
                                    ->required(),
                                Forms\Components\TextInput::make('crew_division')
                                    ->label('Divisi')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Detail')
                                    ->rows(3)
                                    ->nullable(),
                            ]),
                        
                        Forms\Components\Section::make('Review')
                            ->schema([
                                Forms\Components\TextInput::make('producer_name')
                                    ->label('Nama Producer')
                                    ->required(),
                                Forms\Components\TextInput::make('producer_telp')
                                    ->label('Telp Producer')
                                    ->tel()
                                    ->required(),
                                Forms\Components\TextInput::make('crew_name')
                                    ->label('Nama Crew')
                                    ->required(),
                                Forms\Components\TextInput::make('crew_telp')
                                    ->label('Telp Crew')
                                    ->tel()
                                    ->required(),
                                Forms\Components\DatePicker::make('return_date')
                                    ->label('Tanggal Pengembalian')
                                    ->required(),
                            ]),
                    ]),
                
                // Equipment sections for each category
                Forms\Components\Tabs::make('Categories')
                    ->tabs(function () use ($categories) {
                        $tabs = [];
                        
                        foreach ($categories as $category) {
                            $items = Item::where('category', $category)
                                ->get()
                                ->map(function ($item) {
                                    return [
                                        'id' => $item->id,
                                        'name' => $item->name,
                                        'stock' => $item->stock,
                                    ];
                                })
                                ->toArray();
                                
                            $tabs[] = Forms\Components\Tabs\Tab::make($category)
                                ->schema([
                                    Forms\Components\Repeater::make("items.{$category}")
                                        ->label('')
                                        ->relationship('items')
                                        ->schema([
                                            Forms\Components\Select::make('item_id')
                                                ->label("Pilih $category")
                                                ->options(function () use ($category) {
                                                    return Item::where('category', $category)
                                                        ->pluck('name', 'id');
                                                })
                                                ->reactive()
                                                ->afterStateUpdated(function (callable $set, $state) {
                                                    $item = Item::find($state);
                                                    if ($item) {
                                                        $set('available_stock', $item->stock);
                                                    }
                                                })
                                                ->searchable()
                                                ->required(),
                                            Forms\Components\TextInput::make('available_stock')
                                                ->label('Kode Barang')
                                                ->disabled()
                                                ->dehydrated(false),
                                            Forms\Components\TextInput::make('quantity')
                                                ->label('Jumlah')
                                                ->numeric()
                                                ->default(1)
                                                ->rules([
                                                    function ($get) {
                                                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                            $itemId = $get('item_id');
                                                            $item = Item::find($itemId);
                                                            
                                                            if ($item && $value > $item->stock) {
                                                                $fail("Jumlah tidak boleh melebihi stok tersedia ({$item->stock}).");
                                                            }
                                                        };
                                                    },
                                                ])
                                                ->required(),
                                        ])
                                        ->columns(3),
                                ]);
                        }
                        
                        return $tabs;
                    }),
                
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Section::make('Approval Logistik')
                            ->schema([
                                Forms\Components\TextInput::make('approver_name')
                                    ->label('Nama')
                                    ->nullable(),
                                Forms\Components\TextInput::make('approver_telp')
                                    ->label('Telp')
                                    ->tel()
                                    ->nullable(),
                                Forms\Components\Radio::make('approval_status')
                                    ->label('Approval')
                                    ->options([
                                        'Approve' => 'Approve',
                                        'Decline' => 'Decline',
                                    ])
                                    ->default('Approve')
                                    ->required(),
                            ]),
                            
                        Forms\Components\Section::make('Penyelesaian')
                            ->schema([
                                Forms\Components\TextInput::make('return_handler')
                                    ->label('Status Logistik')
                                    ->nullable(),
                                Forms\Components\Radio::make('return_status')
                                    ->label('Status Pengembalian')
                                    ->options([
                                        'Sudah Dikembalikan' => 'Sudah Dikembalikan',
                                        'Belum Dikembalikan' => 'Belum Dikembalikan',
                                    ])
                                    ->default('Belum Dikembalikan')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama Peminjam'),
                Tables\Columns\TextColumn::make('location')->label('Lokasi'),
                Tables\Columns\TextColumn::make('booking_date')->date()->label('Tanggal Peminjaman'),
                Tables\Columns\TextColumn::make('return_date')->date()->label('Tanggal Pengembalian'),
                Tables\Columns\BadgeColumn::make('approval_status')
                    ->colors([
                        'success' => 'Approve',
                        'danger' => 'Decline',
                    ]),
                Tables\Columns\BadgeColumn::make('return_status')
                    ->colors([
                        'success' => 'Sudah Dikembalikan',
                        'warning' => 'Belum Dikembalikan',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        'Approve' => 'Approve',
                        'Decline' => 'Decline',
                    ]),
                Tables\Filters\SelectFilter::make('return_status')
                    ->options([
                        'Sudah Dikembalikan' => 'Sudah Dikembalikan',
                        'Belum Dikembalikan' => 'Belum Dikembalikan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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