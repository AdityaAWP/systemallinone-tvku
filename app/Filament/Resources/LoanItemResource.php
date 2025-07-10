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
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

class LoanItemResource extends Resource
{
    protected static ?string $model = LoanItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Administrasi Umum';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Peminjaman';
    protected static ?string $title = 'Peminjaman';
    protected static ?string $label = 'Data Peminjaman';

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        $pendingCount = LoanItem::where('user_id', $user->id)
            ->where('return_status', 'Belum Dikembalikan')
            ->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }
    
    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $items = Item::all();
        $itemsByCategory = $items->groupBy('category');
        $categorySections = [];

        $isReadOnlyForAdmin = function (?LoanItem $record): bool {
            return $record &&
                $record->approval_admin_logistics === true &&
                auth()->user()->hasRole('admin_logistik') &&
                !auth()->user()->hasRole('super_admin');
        };
        
        $categories = $itemsByCategory->keys()->toArray();
        $chunkSize = max(1, ceil(count($categories) / 2));
        $categoryGroups = array_chunk($categories, $chunkSize);
        
        foreach ($categoryGroups as $groupIndex => $categoryGroup) {
            $columns = [];
            
            foreach ($categoryGroup as $category) {
                $categoryItems = $itemsByCategory[$category];
                
                $itemInputs = $categoryItems->map(function ($item) use ($isReadOnlyForAdmin) { // Pass the closure
                    return Grid::make("item_{$item->id}")
                        ->columns(2)
                        ->schema([
                            TextInput::make("item_{$item->id}_name")
                                ->label($item->name)
                                ->disabled()
                                ->default($item->name)
                                ->columnSpan(1),
                            TextInput::make("item_{$item->id}_quantity")
                                ->name("item_{$item->id}_quantity")
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(function () use ($item) {
                                    return $item->stock; 
                                })
                                ->reactive()
                                ->columnSpan(1)
                                ->disabled($isReadOnlyForAdmin), 
                        ]);
                })->toArray();
                
                $columns[] = Section::make($category)
                    ->schema($itemInputs);
            }
            
            $categorySections[] = Grid::make("category_group_{$groupIndex}")
                ->columns(count($categoryGroup))
                ->schema($columns);
        }
        
        return $form
            ->schema([
                Split::make([
                    Section::make('Keterangan Peminjaman')
                    ->schema([
                        TextInput::make('user.name')
                            ->default($user->name)
                            ->required()
                            ->label('Peminjam')
                            ->disabled(), 
                        TextInput::make('program')
                            ->required()
                            ->label('Program')
                            ->disabled($isReadOnlyForAdmin), 
                        TextInput::make('location')
                            ->required()
                            ->label('Lokasi')
                            ->disabled($isReadOnlyForAdmin), 
                        DatePicker::make('booking_date')
                            ->required()
                            ->label('Tanggal Booking')
                            ->disabled($isReadOnlyForAdmin), 
                        TimePicker::make('start_booking')
                            ->required()
                            ->seconds(false)
                            ->label('Jam Booking')
                            ->disabled($isReadOnlyForAdmin),
                        DatePicker::make('return_date')
                            ->required()
                            ->label('Tanggal Pengembalian')
                            ->disabled($isReadOnlyForAdmin), 
                        Select::make('division')
                            ->options([
                                'produksi' => 'Produksi',
                                'news' => 'News',
                                'studio' => 'Studio',
                                'marketing' => 'Marketing',
                                'lain-lain' => 'Lain-lain',
                            ])
                            ->required()
                            ->label('Divisi')
                            ->disabled($isReadOnlyForAdmin), 
                    ]),
                    Section::make('Review')
                    ->schema([
                        TextInput::make('producer_name')
                            ->required()
                            ->label('Nama Produser')
                            ->disabled($isReadOnlyForAdmin),
                        TextInput::make('producer_telp')
                            ->required()
                            ->label('Telp. Produser')
                            ->disabled($isReadOnlyForAdmin),
                        TextInput::make('crew_name')
                            ->required()
                            ->label('Nama Crew')
                            ->disabled($isReadOnlyForAdmin),
                        TextInput::make('crew_telp')
                            ->required()
                            ->label('Telp. Crew')
                            ->disabled($isReadOnlyForAdmin),
                        TextInput::make('crew_division')
                            ->required()
                            ->label('Divisi Crew')
                            ->disabled($isReadOnlyForAdmin),
                    ])
                ])->columnSpan('full'),
                
                Grid::make('category_sections')
                    ->schema($categorySections)
                    ->columnSpan('full'),                
                Split::make([
                    Section::make('Catatan')
                        ->schema([
                            Textarea::make('notes')
                            ->label('')
                            ->rows(10)
                            ->cols(20)
                            ->disabled($isReadOnlyForAdmin), // START: CHANGE
                        ]),
                        Section::make('Approval')
                        ->visible(fn () => auth()->user()->hasRole('admin_logistik'))
                        ->schema([
                            TextInput::make('approver_name')
                                ->default(fn () => auth()->user()->name)
                                ->label('Nama Approver')
                                ->visible(fn () => auth()->user()->hasRole('admin_logistik'))
                                ->disabled() 
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if (!$state && auth()->user()->hasRole('admin_logistik')) {
                                        $component->state(auth()->user()->name);
                                    }
                                }),
                            TextInput::make('approver_telp')
                                ->default(fn () => auth()->user()->phone ?? auth()->user()->telp ?? '')
                                ->label('Telp. Approver')
                                ->visible(fn () => auth()->user()->hasRole('admin_logistik'))
                                ->disabled()
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if (!$state && auth()->user()->hasRole('admin_logistik')) {
                                        $component->state(auth()->user()->phone ?? auth()->user()->telp ?? '');
                                    }
                                }),
                            Radio::make('approval_admin_logistics')
                                ->label('Approval Admin Logistics')
                                ->boolean()
                                ->hidden(fn () => !auth()->user()->hasRole('admin_logistik'))
                                ->required()
                                ->reactive()
                                ->disabled($isReadOnlyForAdmin) 
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state === true && auth()->user()->hasRole('admin_logistik')) {
                                        $set('approver_name', auth()->user()->name);
                                        $set('approver_telp', auth()->user()->phone ?? auth()->user()->telp ?? '');
                                    }
                                }),
                            Radio::make('return_status')
                                ->label('Status Pengembalian')
                                ->required()
                                ->hidden(fn () => !auth()->user()->hasRole('admin_logistik'))
                                ->options([
                                    'Sudah Dikembalikan' => 'Sudah Dikembalikan',
                                    'Belum Dikembalikan' => 'Belum Dikembalikan',
                                ])
                                ->default('Belum Dikembalikan'),
                        ])
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Peminjam')->searchable(),
                TextColumn::make('program')->label('Program')->searchable(),
                TextColumn::make('location')->label('Lokasi')->searchable(),
                TextColumn::make('booking_date')->label('Tanggal Booking')->date()->sortable(),
                TextColumn::make('start_booking')->label('Jam Peminjaman')->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('H:i')),
                TextColumn::make('items_list')->label('Item')->getStateUsing(function ($record) {
                    return $record->items->map(function ($item) {
                        return "{$item->name} (Qty: {$item->pivot->quantity})";
                    })->implode(', ');
                }),
                TextColumn::make('division')->label('Divisi')->searchable(),
                TextColumn::make('approval_admin_logistics')->label('Logistics Approval')->formatStateUsing(fn ($state) => $state ? 'Approved' : 'Pending')->color(fn ($state) => $state ? 'success' : 'warning'),
                TextColumn::make('return_status')->label('Status Pengembalian')->badge()->color(fn (string $state): string => match ($state) {
                    'Sudah Dikembalikan' => 'success',
                    'Belum Dikembalikan' => 'warning',
                }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        
                        if ($user->hasRole(['super_admin', 'admin_logistik'])) {
                            return true;
                        }

                        if ($record->user_id === $user->id && !$record->approval_admin_logistics) {
                            return true;
                        }
                        
                        return false;
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if ($record->user_id === $user->id && $record->approval_admin_logistics == false) {
                            return true;
                        }
                        
                        return false;
                    }),
                Tables\Actions\Action::make('download') 
                    ->url(fn(LoanItem $loanitem) => route('loanitem.single', $loanitem))
                    ->openUrlInNewTab(),
                
                ]);
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ComponentsSection::make('Keterangan Peminjaman')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Peminjam'),
                        TextEntry::make('program')
                            ->label('Program'),
                        TextEntry::make('location')
                            ->label('Lokasi'),
                        TextEntry::make('booking_date')
                            ->date()
                            ->label('Tanggal Booking'),
                        TextEntry::make('start_booking')
                            ->dateTime()
                            ->label('Jam Booking'),
                        TextEntry::make('return_date')
                            ->date()
                            ->label('Tanggal Pengembalian'),
                        TextEntry::make('division')
                            ->label('Divisi'),
                    ])->columns(2),
                ComponentsSection::make('Review')
                    ->schema([
                        TextEntry::make('producer_name')
                            ->label('Nama Produser'),
                        TextEntry::make('producer_telp')
                            ->label('Telp. Produser'),
                        TextEntry::make('crew_name')
                            ->label('Nama Crew'),
                        TextEntry::make('crew_telp')
                            ->label('Telp. Crew'),
                        TextEntry::make('crew_division')
                            ->label('Divisi Crew'),
                    ])->columns(2),
                ComponentsSection::make('Items')
                    ->schema([
                        TextEntry::make('items_list')
                            ->label('Item')
                            ->getStateUsing(function ($record) {
                                if ($record->items->isEmpty()) {
                                    return 'No items selected';
                                }
                                
                                return $record->items->map(function ($item) {
                                    return "{$item->name} (Qty: {$item->pivot->quantity})";
                                })->implode(', ');
                            })
                    ]),
                ComponentsSection::make('Approval Status')
                    ->schema([
                        TextEntry::make('approver_name')
                            ->label('Approved By')
                            ->visible(fn ($record) => $record->approval_admin_logistics == true),
                        TextEntry::make('approver_telp')
                            ->label('Approver Phone')
                            ->visible(fn ($record) => $record->approval_admin_logistics == true),
                        TextEntry::make('approval_admin_logistics')
                            ->label('Logistics Approval')
                            ->formatStateUsing(fn ($state) => $state ? 'Approved' : 'Pending')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                        TextEntry::make('return_status')
                            ->label('Status Pengembalian')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Sudah Dikembalikan' => 'success',
                                'Belum Dikembalikan' => 'warning',
                                default => 'danger',
                            }),
                    ])->columns(2),
                ComponentsSection::make('Catatan')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notes'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (!Auth::user()->hasRole(['admin_logistik', 'super_admin'])) {
            $query->where('user_id', Auth::id());
        }
        
        return $query;
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
            'view' => Pages\ViewLoanItem::route('/{record}'),
            'edit' => Pages\EditLoanItem::route('/{record}/edit'),
        ];
    }
}