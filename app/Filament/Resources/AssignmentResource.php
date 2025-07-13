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
            // For direktur_utama: show submitted assignments waiting for approval
            if ($user->hasRole('direktur_utama')) {
                return static::getModel()::where('approval_status', Assignment::STATUS_PENDING)
                    ->where('submit_status', Assignment::SUBMIT_SUDAH)
                    ->count() ?: null;
            }

            // For manager_keuangan: show pending assignments from staff that need to be submitted
            if ($user->hasRole('manager_keuangan')) {
                return static::getModel()::where('submit_status', Assignment::SUBMIT_BELUM)
                    ->whereHas('creator', function ($q) {
                        $q->whereHas('roles', function ($roleQuery) {
                            $roleQuery->where('name', 'staff_keuangan');
                        });
                    })
                    ->count() ?: null;
            }

            // For staff_keuangan: show their own assignments that are still pending (not yet approved/declined)
            if ($user->hasRole('staff_keuangan')) {
                return static::getModel()::where('created_by', Auth::id())
                    ->where('approval_status', Assignment::STATUS_PENDING)
                    ->count() ?: null;
            }
        }

        // Default for other roles or if no specific role matches
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
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),

                                Forms\Components\DatePicker::make('created_date')
                                    ->required()
                                    ->label('Tanggal Dibuat')
                                    ->default(Carbon::now()),

                                Forms\Components\DatePicker::make('deadline')
                                    ->required()
                                    ->minDate(Carbon::now())
                                    ->label('Batas Waktu')
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),

                                Forms\Components\TextInput::make('client')
                                    ->required()
                                    ->label('Klien')
                                    ->maxLength(255)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),

                                Forms\Components\TextInput::make('spp_number')
                                    ->label('Nomor SPP')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),

                                Forms\Components\TextInput::make('spk_number')
                                    ->label('Nomor SPK')
                                    ->maxLength(255)
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),

                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->label('Keterangan')
                                    ->columnSpanFull()
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),
                            ]),

                        Forms\Components\Tabs\Tab::make('Detail Keuangan')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Jumlah Nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),

                                Forms\Components\TextInput::make('marketing_expense')
                                    ->label('Biaya Pemasaran')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->inputMode('decimal')
                                    ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE)
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),
                            ])
                            ->hidden(fn(Forms\Get $get) => $get('type') === Assignment::TYPE_FREE),

                        Forms\Components\Tabs\Tab::make('Informasi Tambahan')
                            ->schema([
                                Forms\Components\Textarea::make('production_notes')
                                    ->label('Catatan Produksi')
                                    ->columnSpanFull()
                                    ->disabled(fn($context) => $context === 'edit' && Auth::user()->hasRole('direktur_utama')),
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
                                    ->dehydrated(),

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
                                Forms\Components\Select::make('priority')
                                    ->label('Tingkat Prioritas')
                                    ->options([
                                        Assignment::PRIORITY_NORMAL => 'Biasa',
                                        Assignment::PRIORITY_IMPORTANT => 'Penting',
                                        Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                                    ])
                                    ->placeholder('Pilih prioritas')
                                    ->hidden(fn(Forms\Get $get) => $get('type') !== Assignment::TYPE_PAID)
                                    // MODIFIED: This field is now enabled for direktur_utama and manager_keuangan
                                    ->disabled(function () {
                                        $user = Auth::user();
                                        return !($user && method_exists($user, 'hasRole') &&
                                            ($user->hasRole('direktur_utama') || $user->hasRole('manager_keuangan')));
                                    }),

                                Forms\Components\Select::make('approval_status')
                                    ->label('Status Persetujuan')
                                    ->options([
                                        Assignment::STATUS_PENDING => 'Menunggu',
                                        Assignment::STATUS_APPROVED => 'Disetujui',
                                        Assignment::STATUS_DECLINED => 'Ditolak',
                                    ])
                                    ->default(Assignment::STATUS_PENDING)
                                    ->disabled(
                                        fn(Assignment $record = null) =>
                                        !Auth::user()->hasAnyRole(['direktur_utama']) ||
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

                Forms\Components\Hidden::make('created_by')
                    ->default(fn() => Auth::id())
                    ->dehydrated(fn($context) => $context === 'create'),
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
                    ->visible(fn() => Auth::user()->hasAnyRole(['super_admin', 'direktur_utama', 'manager_keuangan'])),

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
                        // Jika priority null/kosong (untuk staff_keuangan), tampilkan "-"
                        if (is_null($state)) {
                            return '-';
                        }
                        return match ($state) {
                            Assignment::PRIORITY_NORMAL => 'Biasa',
                            Assignment::PRIORITY_IMPORTANT => 'Penting',
                            Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                            default => $state,
                        };
                    })
                    ->colors([
                        'secondary' => Assignment::PRIORITY_NORMAL,
                        'warning' => Assignment::PRIORITY_IMPORTANT,
                        'danger' => Assignment::PRIORITY_VERY_IMPORTANT,
                        'gray' => null, // Untuk priority yang kosong
                    ])
                    ->color(function ($state) {
                        // Jika priority null, gunakan warna gray
                        if (is_null($state)) {
                            return 'gray';
                        }
                        return match ($state) {
                            Assignment::PRIORITY_NORMAL => 'secondary',
                            Assignment::PRIORITY_IMPORTANT => 'warning',
                            Assignment::PRIORITY_VERY_IMPORTANT => 'danger',
                            default => 'gray',
                        };
                    }),

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
            ->filters([
                // Tabs Filter untuk navigasi cepat berdasarkan status
                Tables\Filters\TernaryFilter::make('status_tab')
                    ->label('Status Assignment')
                    ->placeholder('Semua Status')
                    ->trueLabel('Perlu Tindakan')
                    ->falseLabel('Sudah Selesai')
                    ->queries(
                        true: function (Builder $query) {
                            $user = Auth::user();
                            if ($user?->hasRole('staff_keuangan')) {
                                // Staff: assignment yang pending approval
                                return $query->where('approval_status', Assignment::STATUS_PENDING);
                            } elseif ($user?->hasRole('manager_keuangan')) {
                                // Manager: assignment yang belum disubmit dari staff
                                return $query->where('submit_status', Assignment::SUBMIT_BELUM);
                            }
                            return $query->where('approval_status', Assignment::STATUS_PENDING);
                        },
                        false: fn(Builder $query) => $query->whereIn('approval_status', [Assignment::STATUS_APPROVED, Assignment::STATUS_DECLINED]),
                        blank: fn(Builder $query) => $query,
                    )
                    ->visible(fn() => Auth::user()?->hasAnyRole(['staff_keuangan', 'manager_keuangan']) ?? false),

                // Filter berdasarkan jenis assignment
                Tables\Filters\TernaryFilter::make('type_tab')
                    ->label('Jenis Assignment')
                    ->placeholder('Semua Jenis')
                    ->trueLabel('Berbayar & Barter')
                    ->falseLabel('Free')
                    ->queries(
                        true: fn(Builder $query) => $query->whereIn('type', [Assignment::TYPE_PAID, Assignment::TYPE_BARTER]),
                        false: fn(Builder $query) => $query->where('type', Assignment::TYPE_FREE),
                        blank: fn(Builder $query) => $query,
                    )
                    ->visible(fn() => Auth::user()?->hasAnyRole(['staff_keuangan', 'manager_keuangan']) ?? false),

                // Filter untuk staff dan manager keuangan
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Penugasan')
                    ->options([
                        Assignment::TYPE_FREE => 'Free',
                        Assignment::TYPE_PAID => 'Berbayar',
                        Assignment::TYPE_BARTER => 'Barter',
                    ])
                    ->visible(fn() => Auth::user()?->hasAnyRole(['staff_keuangan', 'manager_keuangan']) ?? false),


                Tables\Filters\SelectFilter::make('submit_status')
                    ->label('Status Pengajuan')
                    ->options([
                        Assignment::SUBMIT_BELUM => 'Belum Diajukan',
                        Assignment::SUBMIT_SUDAH => 'Sudah Diajukan',
                    ])
                    ->visible(fn() => Auth::user()?->hasAnyRole(['staff_keuangan', 'manager_keuangan']) ?? false),

                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Status Persetujuan')
                    ->options([
                        Assignment::STATUS_PENDING => 'Menunggu',
                        Assignment::STATUS_APPROVED => 'Disetujui',
                        Assignment::STATUS_DECLINED => 'Ditolak',
                    ])
                    ->visible(fn() => Auth::user()?->hasAnyRole(['staff_keuangan', 'manager_keuangan']) ?? false),

                Tables\Filters\Filter::make('created_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Tanggal Dibuat Dari'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Tanggal Dibuat Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Tanggal dari: ' . Carbon::parse($data['created_from'])->format('d M Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Tanggal sampai: ' . Carbon::parse($data['created_until'])->format('d M Y');
                        }
                        return $indicators;
                    })
                    ->visible(fn() => Auth::user()?->hasAnyRole(['staff_keuangan', 'manager_keuangan']) ?? false),

            ])
            ->actions([

                // Action untuk Manager Keuangan - Submit ke Direktur
                Tables\Actions\Action::make('submit')
                    ->label('Submit')
                    ->color('success')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Assignment ke Direktur')
                    ->modalDescription('Apakah Anda yakin ingin submit assignment ini ke direktur untuk approval?')
                    ->modalSubmitActionLabel('Ya, Submit')
                    ->action(function (Assignment $record) {
                        $record->update([
                            'submit_status' => Assignment::SUBMIT_SUDAH,
                            'submitted_by' => Auth::id(),
                            'submitted_at' => now(),
                        ]);
                    })
                    ->visible(function (Assignment $record): bool {
                        $user = Auth::user();
                        return $user && $user->hasRole('manager_keuangan') &&
                            $record->submit_status === Assignment::SUBMIT_BELUM;
                    }),

                // Action untuk Manager Keuangan - Tolak Assignment
                Tables\Actions\Action::make('reject_by_manager')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Assignment')
                    ->modalDescription('Apakah Anda yakin ingin menolak assignment ini? Assignment akan dikembalikan ke staff.')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->action(function (Assignment $record) {
                        $record->update([
                            'submit_status' => Assignment::SUBMIT_BELUM,
                            'submitted_by' => Auth::id(),
                            'submitted_at' => now(),
                        ]);
                    })
                    ->visible(function (Assignment $record): bool {
                        $user = Auth::user();
                        return $user && $user->hasRole('manager_keuangan') &&
                            $record->submit_status === Assignment::SUBMIT_BELUM;
                    }),

                // Action untuk Direktur Utama - Approve Assignment with Priority Setting
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->form([
                        Forms\Components\Select::make('priority')
                            ->label('Tingkat Prioritas')
                            ->options([
                                Assignment::PRIORITY_NORMAL => 'Biasa',
                                Assignment::PRIORITY_IMPORTANT => 'Penting',
                                Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                            ])
                            ->required()
                            ->default(fn(Assignment $record) => $record->priority ?? Assignment::PRIORITY_NORMAL)
                            ->visible(fn(Assignment $record) => $record->type === Assignment::TYPE_PAID),
                    ])
                    ->modalHeading('Approve Assignment')
                    ->modalDescription(function (Assignment $record) {
                        $description = 'Apakah Anda yakin ingin menyetujui assignment ini?';
                        if ($record->type === Assignment::TYPE_PAID) {
                            $description .= ' Silakan tentukan tingkat prioritas untuk assignment ini.';
                        }
                        return $description;
                    })
                    ->modalSubmitActionLabel('Ya, Approve')
                    ->action(function (Assignment $record, array $data) {
                        $updateData = [
                            'approval_status' => Assignment::STATUS_APPROVED,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ];

                        // Set priority only for paid assignments and if provided
                        if ($record->type === Assignment::TYPE_PAID && isset($data['priority'])) {
                            $updateData['priority'] = $data['priority'];
                        }

                        $record->update($updateData);
                    })
                    ->visible(function (Assignment $record): bool {
                        $user = Auth::user();
                        return $user && $user->hasRole('direktur_utama') &&
                            $record->submit_status === Assignment::SUBMIT_SUDAH &&
                            $record->approval_status === Assignment::STATUS_PENDING;
                    }),

                // Action untuk Direktur Utama - Reject Assignment
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Assignment')
                    ->modalDescription('Apakah Anda yakin ingin menolak assignment ini?')
                    ->modalSubmitActionLabel('Ya, Reject')
                    ->action(function (Assignment $record) {
                        $record->update([
                            'approval_status' => Assignment::STATUS_DECLINED,
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    })
                    ->visible(function (Assignment $record): bool {
                        $user = Auth::user();
                        return $user && $user->hasRole('direktur_utama') &&
                            $record->submit_status === Assignment::SUBMIT_SUDAH &&
                            $record->approval_status === Assignment::STATUS_PENDING;
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(Assignment $record) =>
                    $record->approval_status !== Assignment::STATUS_PENDING ||
                        Auth::user()->hasRole('direktur_utama') ||
                        (Auth::user()->hasRole('staff_keuangan') && $record->created_by !== Auth::id())),


                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->color('warning') // orange color
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Assignment $assignment) => route('assignment.single', $assignment))
                    ->openUrlInNewTab()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn() => Auth::user()->hasRole('direktur_utama')),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(fn() => Auth::user()->hasRole('direktur_utama')),
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
            // Direktur utama: tampilkan semua assignment yang sudah disubmit manager
            if ($user->hasRole('direktur_utama')) {
                // Base filter: hanya yang sudah disubmit manager
                $query->where('submit_status', Assignment::SUBMIT_SUDAH);

                if ($statusFilter === 'responded') {
                    // Tampilkan yang sudah di-approve/declined
                    $query->whereIn('approval_status', [
                        Assignment::STATUS_APPROVED,
                        Assignment::STATUS_DECLINED,
                    ]);
                } elseif ($statusFilter === 'pending') {
                    // Tampilkan yang pending approval
                    $query->where('approval_status', Assignment::STATUS_PENDING);
                }
                // Jika tidak ada filter status, tampilkan semua (pending, approved, declined)
            }

            // Staff keuangan: only see assignments they created
            elseif ($user->hasRole('staff_keuangan')) {
                $query->where('created_by', Auth::id());
                
                // Handle staff-specific filters
                if ($statusFilter === 'my_pending') {
                    $query->where('approval_status', Assignment::STATUS_PENDING);
                } elseif ($statusFilter === 'my_approved') {
                    $query->where('approval_status', Assignment::STATUS_APPROVED);
                } elseif ($statusFilter === 'my_rejected') {
                    $query->where('approval_status', Assignment::STATUS_DECLINED);
                }
            }

            // Manager keuangan: see all assignments from staff
            elseif ($user->hasRole('manager_keuangan')) {
                // Manager dapat melihat semua assignment dari staff_keuangan
                $query->whereHas('creator', function ($q) {
                    $q->whereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'staff_keuangan');
                    });
                });
                
                // Handle manager-specific filters
                if ($statusFilter === 'need_submission') {
                    $query->where('submit_status', Assignment::SUBMIT_BELUM);
                } elseif ($statusFilter === 'submitted') {
                    $query->where('submit_status', Assignment::SUBMIT_SUDAH);
                }
            }

            // Admin keuangan: see all assignments
            elseif ($user->hasRole('admin_keuangan')) {
                // Admin keuangan dapat melihat semua assignment
                // Handle admin-specific filters
                if ($statusFilter === 'all_pending') {
                    $query->where('approval_status', Assignment::STATUS_PENDING);
                } elseif ($statusFilter === 'all_approved') {
                    $query->where('approval_status', Assignment::STATUS_APPROVED);
                } elseif ($statusFilter === 'all_rejected') {
                    $query->where('approval_status', Assignment::STATUS_DECLINED);
                }
            }
        }

        return $query;
    }
}
