<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Penugasan Keuangan';

    protected static ?string $label = 'Penugasan Keuangan';

    protected static ?string $navigationGroup = 'Administrasi Surat';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        
        if ($user && method_exists($user, 'hasRole')) {
            // For direktur_keuangan: show submitted assignments waiting for approval
            if ($user->hasRole('direktur_keuangan')) {
                return static::getModel()::where('approval_status', Assignment::STATUS_PENDING)
                    ->where('submit_status', Assignment::SUBMIT_SUDAH)
                    ->count() ?: null;
            }
            
            // For manager_keuangan: show unsubmitted assignments
            if ($user->hasRole('manager_keuangan')) {
                return static::getModel()::where('submit_status', Assignment::SUBMIT_BELUM)
                    ->count() ?: null;
            }
            
            // For staff_keuangan: show their pending assignments
            if ($user->hasRole('staff_keuangan')) {
                return static::getModel()::where('approval_status', Assignment::STATUS_PENDING)
                    ->where('created_by', Auth::id())
                    ->count() ?: null;
            }
        }
        
        return static::getModel()::count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'primary' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Assignment')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Dasar')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Jenis Penugasan')
                                    ->options([
                                        Assignment::TYPE_FREE => 'Free',
                                        Assignment::TYPE_PAID => 'Berbayar',
                                        Assignment::TYPE_BARTER => 'Barter',
                                    ])
                                    ->required()
                                    ->live()
                                    ->default(Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\DatePicker::make('created_date')
                                    ->required()
                                    ->label('Tanggal Dibuat')
                                    ->default(Carbon::now()),

                                Forms\Components\DatePicker::make('deadline')
                                    ->required()
                                    ->minDate(Carbon::now())
                                    ->label('Batas Waktu')
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('client')
                                    ->required()
                                    ->label('Klien')
                                    ->maxLength(255)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('spp_number')
                                    ->label('Nomor SPP')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('spk_number')
                                    ->label('Nomor SPK')
                                    ->maxLength(255)
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->label('Keterangan')
                                    ->columnSpanFull()
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),
                            ]),

                        Forms\Components\Tabs\Tab::make('Detail Keuangan')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah Nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\TextInput::make('marketing_expense')
                                    ->label('Biaya Pemasaran')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),
                            ])
                            ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE),

                        Forms\Components\Tabs\Tab::make('Informasi Tambahan')
                            ->schema([
                                Forms\Components\Textarea::make('production_notes')
                                    ->label('Catatan Produksi')
                                    ->columnSpanFull()
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),

                                Forms\Components\Select::make('priority')
                                    ->label('Tingkat Prioritas')
                                    ->options([
                                        Assignment::PRIORITY_NORMAL => 'Normal',
                                        Assignment::PRIORITY_IMPORTANT => 'Penting',
                                        Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                                    ])
                                    ->default(Assignment::PRIORITY_NORMAL)
                                    ->hidden(fn(Forms\Get $get) => $get('type') !== Assignment::TYPE_PAID)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_keuangan')),
                            ]),

                        Forms\Components\Tabs\Tab::make('Status Pengajuan')
                            ->schema([
                                Forms\Components\Select::make('submit_status')
                                    ->label('Status Pengajuan')
                                    ->options([
                                        Assignment::SUBMIT_BELUM => 'Belum Diajukan',
                                        Assignment::SUBMIT_SUDAH => 'Sudah Diajukan',
                                    ])
                                    ->default(Assignment::SUBMIT_BELUM)
                                    ->disabled(fn() => !Auth::user()->hasRole('manager_keuangan'))
                                    ->dehydrated(), // Keep dehydrated here, it's fine

                                Forms\Components\Placeholder::make('submitted_at')
                                    ->label('Tanggal Pengajuan')
                                    ->content(fn(Assignment $record): string => $record->submitted_at ? $record->submitted_at->format('d M Y H:i') : '-')
                                    ->hiddenOn('create'),

                                Forms\Components\Placeholder::make('submitter')
                                    ->label('Diajukan Oleh')
                                    ->content(fn(Assignment $record): string => $record->submitter ? $record->submitter->name : '-')
                                    ->hiddenOn('create'),
                            ])
                            ->hidden(fn($context) => $context === 'create'),

                        Forms\Components\Tabs\Tab::make('Persetujuan')
                            ->schema([
                                Forms\Components\Select::make('approval_status')
                                    ->label('Status Persetujuan')
                                    ->options([
                                        Assignment::STATUS_PENDING => 'Menunggu',
                                        Assignment::STATUS_APPROVED => 'Disetujui',
                                        Assignment::STATUS_DECLINED => 'Ditolak',
                                    ])
                                    ->default(Assignment::STATUS_PENDING)
                                    ->disabled(fn(Assignment $record = null) => 
                                        !Auth::user()->hasAnyRole(['direktur_keuangan', 'direktur_utama']) ||
                                        ($record && $record->submit_status === Assignment::SUBMIT_BELUM)
                                    ),

                                Forms\Components\Placeholder::make('approved_at')
                                    ->label('Tanggal Persetujuan/Penolakan')
                                    ->content(fn(Assignment $record): string => $record->approved_at ? $record->approved_at->format('d M Y H:i') : '-')
                                    ->hiddenOn('create'),

                                Forms\Components\Placeholder::make('approver')
                                    ->label('Disetujui/Ditolak Oleh')
                                    ->content(fn(Assignment $record): string => $record->approver ? $record->approver->name : '-')
                                    ->hiddenOn('create'),
                            ])
                            ->hidden(fn($context) => $context === 'create'),
                    ])
                    ->columnSpanFull(),

                // Hidden field to store creator's ID
                Forms\Components\Hidden::make('created_by')
                    ->default(fn() => Auth::id())
                    ->dehydrated(fn($context) => $context === 'create'),

                // --- FIX ---
                // The conflicting Hidden 'submit_status' field has been removed from here.
                // The Select field in the 'Status Pengajuan' tab is now the only source of this value.
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasAnyRole(['super_admin', 'direktur_keuangan', 'manager_keuangan'])),

                Tables\Columns\TextColumn::make('created_date')
                    ->label('Tanggal Dibuat')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Assignment::TYPE_FREE => 'gray',
                        Assignment::TYPE_PAID => 'success',
                        Assignment::TYPE_BARTER => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::TYPE_FREE => 'Free',
                            Assignment::TYPE_PAID => 'Berbayar',
                            Assignment::TYPE_BARTER => 'Barter',
                            default => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('spp_number')
                    ->label('No.SPP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('spk_number')
                    ->label('No.SPK')
                    ->searchable(),

                Tables\Columns\TextColumn::make('client')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn(Assignment $record) =>
                        $record->deadline->isPast() ? 'danger' : ($record->deadline->isToday() ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::PRIORITY_NORMAL => 'Normal',
                            Assignment::PRIORITY_IMPORTANT => 'Penting',
                            Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                            default => $state,
                        };
                    })
                    ->colors([
                        'secondary' => Assignment::PRIORITY_NORMAL,
                        'warning' => Assignment::PRIORITY_IMPORTANT,
                        'danger' => Assignment::PRIORITY_VERY_IMPORTANT,
                    ]),

                Tables\Columns\TextColumn::make('submit_status')
                    ->label('Status Pengajuan')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Assignment::SUBMIT_BELUM => 'gray',
                        Assignment::SUBMIT_SUDAH => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::SUBMIT_BELUM => 'Belum Diajukan',
                            Assignment::SUBMIT_SUDAH => 'Sudah Diajukan',
                            default => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status Persetujuan')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Assignment::STATUS_PENDING => 'warning',
                        Assignment::STATUS_APPROVED => 'success',
                        Assignment::STATUS_DECLINED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::STATUS_PENDING => 'Menunggu',
                            Assignment::STATUS_APPROVED => 'Disetujui',
                            Assignment::STATUS_DECLINED => 'Ditolak',
                            default => $state,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(Assignment $record) =>
                        $record->approval_status !== Assignment::STATUS_PENDING ||
                        Auth::user()->hasRole('direktur_keuangan') ||
                        (Auth::user()->hasRole('staff_keuangan') && $record->created_by !== Auth::id())),
                Tables\Actions\Action::make('download')
                    ->url(fn(Assignment $assignment) => route('assignment.single', $assignment))
                    ->openUrlInNewTab()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => Auth::user()->hasRole('direktur_keuangan')),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(fn() => Auth::user()->hasRole('direktur_keuangan')),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'view' => Pages\ViewAssignments::route('/{record}'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // Get filter values from request
        $year = request('year');
        $statusFilter = request('status');

        // Apply year filter if selected
        if ($year) {
            $query->whereYear('created_date', $year);
        }

        // Role-based filtering
        if ($user && method_exists($user, 'hasRole')) {
            // Direktur keuangan: only see submitted assignments
            if ($user->hasRole('direktur_keuangan')) {
                $query->where('submit_status', Assignment::SUBMIT_SUDAH);
                
                if ($statusFilter === 'pending') {
                    $query->where('approval_status', Assignment::STATUS_PENDING);
                } elseif ($statusFilter === 'responded') {
                    $query->whereIn('approval_status', [Assignment::STATUS_APPROVED, Assignment::STATUS_DECLINED]);
                }
            }
            
            // Staff keuangan: only see assignments they created
            elseif ($user->hasRole('staff_keuangan')) {
                $query->where('created_by', Auth::id());
            }
            
            // Manager keuangan: see all assignments (for submission management)
            // No additional filtering needed for manager_keuangan
        }

        return $query;
    }
}