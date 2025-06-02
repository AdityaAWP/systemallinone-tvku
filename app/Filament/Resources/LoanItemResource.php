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
        $pendingCount = LoanItem::where('return_status', 'Belum Dikembalikan')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }
    
    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $items = Item::all();
        $itemsByCategory = $items->groupBy('category');
        $categorySections = [];
        
        $categories = $itemsByCategory->keys()->toArray();
        $categoryGroups = array_chunk($categories, ceil(count($categories) / 2));
        
        foreach ($categoryGroups as $groupIndex => $categoryGroup) {
            $columns = [];
            
            foreach ($categoryGroup as $category) {
                $categoryItems = $itemsByCategory[$category];
                
                $itemInputs = $categoryItems->map(function ($item) {
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
                        // Replace TextInput with Select for division
                        Select::make('division')
                            ->options([
                                'produksi' => 'Produksi',
                                'news' => 'News',
                                'studio' => 'Studio',
                                'marketing' => 'Marketing',
                                'lain-lain' => 'Lain-lain',
                            ])
                            ->required()
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
                
                Grid::make('category_sections')
                    ->schema($categorySections)
                    ->columnSpan('full'),                
                Split::make([
                    Section::make('Catatan')
                        ->schema([
                            Textarea::make('notes')
                            ->label('')
                            ->rows(10)
                            ->cols(20),
                        ]),
                        Section::make('Approval')
                        ->schema([
                            TextInput::make('approver_name')
                                ->required()
                                ->label('Nama'),
                            TextInput::make('approver_telp')
                                ->required()
                                ->label('Telp.'),
                            Radio::make('approval_admin_logistics')
                                ->label('Approval Admin Logistics')
                                ->boolean()
                                ->hidden(fn () => !auth()->user()->hasRole('admin_logistics'))
                                ->required(),
                            Radio::make('return_status')
                                ->label('Status Pengembalian')
                                ->required()
                                ->hidden(fn () => !auth()->user()->hasRole('admin_logistics'))
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
                TextColumn::make('user.name')
                    ->label('Peminjam')
                    ->searchable(),
                TextColumn::make('program')
                    ->label('Program')
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
                TextColumn::make('division')
                    ->label('Divisi')
                    ->searchable(),
                TextColumn::make('approval_admin_logistics')
                    ->label('Logistics Approval')
                    ->formatStateUsing(fn ($state) => $state ? 'Approved' : 'Pending')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
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
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        // Allow edit if user is admin_logistics, super_admin, or the owner
                        return $user->hasRole(['admin_logistics', 'super_admin']) || 
                               $record->user_id === $user->id;
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => Auth::user()->hasRole(['admin_logistics', 'super_admin'])),
                Tables\Actions\Action::make('download') 
                    ->url(fn(LoanItem $loanitem) => route('loanitem.single', $loanitem))
                    ->openUrlInNewTab(),
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole(['admin_logistics', 'super_admin'])),
                    ExportBulkAction::make()
                        ->exporter(LoanItemExporter::class)
                ]),
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

    // Filter records to only show user's own loans unless they have special permissions
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // If user is not admin_logistics or super_admin, only show their own loans
        if (!Auth::user()->hasRole(['admin_logistics', 'super_admin'])) {
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