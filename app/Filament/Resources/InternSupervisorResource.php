<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InternSupervisorResource\Pages;
use App\Models\Intern;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InternSupervisorResource extends Resource
{
    protected static ?string $model = Intern::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Pembimbingan';
    protected static ?string $modelLabel = 'Data Pembimbingan';
    protected static ?string $pluralModelLabel = 'Data Pembimbingan Magang';
    protected static ?string $navigationGroup = 'Manajemen Magang';
    protected static ?int $navigationSort = 7;

    public static function canViewAny(): bool
    {
        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();
            // Debug: tambahkan log untuk melihat role user
            Log::info('InternSupervisorResource canViewAny check', [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames(),
                'has_super_admin' => $user->hasRole('super_admin'),
                'has_admin_magang' => $user->hasRole('admin_magang')
            ]);
            return $user->hasRole(['super_admin', 'admin_magang']);
        }
        return false;
    }

    public static function canCreate(): bool
    {
        return false; // Read-only resource
    }

    public static function canEdit($record): bool
    {
        return false; // Read-only resource
    }

    public static function canDelete($record): bool
    {
        return false; // Read-only resource
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('supervisor_id')
            ->with(['supervisor', 'internDivision', 'school']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pembimbingan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Anak Magang')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('supervisor_name')
                            ->label('Nama Pembimbing')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn($record) => $record?->supervisor?->name ?? 'Belum ada pembimbing'),
                        
                        Forms\Components\TextInput::make('supervisor_email')
                            ->label('Email Pembimbing')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn($record) => $record?->supervisor?->email ?? '-'),
                        
                        Forms\Components\TextInput::make('division_name')
                            ->label('Divisi Magang')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn($record) => $record?->internDivision?->name ?? 'Belum ada divisi'),
                        
                        Forms\Components\TextInput::make('school_name')
                            ->label('Asal Sekolah/Universitas')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn($record) => $record?->school?->name ?? 'Belum ada sekolah'),
                        
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Mulai Magang')
                            ->disabled(),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Selesai Magang')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('Nama Pembimbing')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('internDivision.name')
                    ->label('Divisi Magang')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('school.name')
                    ->label('Asal Sekolah/Universitas')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai Magang')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai Magang')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Magang')
                    ->getStateUsing(function (Intern $record): string {
                        return $record->getInternshipStatus();
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Akan Datang' => 'warning',
                        'Hampir Selesai' => 'danger',
                        'Selesai' => 'gray',
                        default => 'secondary',
                    }),
                
                Tables\Columns\TextColumn::make('journals_count')
                    ->label('Total Journal')
                    ->counts('journals')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supervisor_id')
                    ->label('Pembimbing')
                    ->options(function () {
                        return User::whereNotNull('id')
                            ->whereHas('directSupervisedInterns')
                            ->pluck('name', 'id');
                    })
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('internDivision')
                    ->label('Divisi')
                    ->relationship('internDivision', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Magang')
                    ->options([
                        'Akan Datang' => 'Akan Datang',
                        'Aktif' => 'Aktif',
                        'Hampir Selesai' => 'Hampir Selesai',
                        'Selesai' => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail'),
                Tables\Actions\Action::make('viewJournals')
                    ->label('Lihat Journal')
                    ->icon('heroicon-o-book-open')
                    ->color('info')
                    ->url(fn (Intern $record): string => 
                        route('filament.admin.resources.journals.index', [
                            'tableFilters' => [
                                'intern_id' => ['value' => $record->id]
                            ]
                        ])
                    ),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInternSupervisors::route('/'),
            'view' => Pages\ViewInternSupervisor::route('/{record}'),
        ];
    }
}
