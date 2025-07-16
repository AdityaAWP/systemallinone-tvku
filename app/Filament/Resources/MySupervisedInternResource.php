<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MySupervisedInternResource\Pages;
use App\Models\Intern;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MySupervisedInternResource extends Resource
{
    protected static ?string $model = Intern::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Anak Magang Bimbingan';
    protected static ?string $modelLabel = 'Anak Magang Bimbingan';
    protected static ?string $pluralModelLabel = 'Daftar Anak Magang Bimbingan';
    protected static ?string $navigationGroup = 'Manajemen Magang';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        if (Auth::guard('web')->check()) {
            /** @var \App\Models\User $user */
            $user = Auth::guard('web')->user();
            return $user->canSuperviseInterns() && Intern::where('supervisor_id', $user->id)->exists();
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
        if (Auth::guard('web')->check()) {
            /** @var \App\Models\User $user */
            $user = Auth::guard('web')->user();
            
            return parent::getEloquentQuery()
                ->where('supervisor_id', $user->id)
                ->with(['supervisor', 'internDivision', 'school']);
        }
        
        return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty if not authenticated
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Anak Magang')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('nis_nim')
                            ->label('NIS/NIM')
                            ->disabled(),
                        Forms\Components\TextInput::make('school.name')
                            ->label('Asal Sekolah/Universitas')
                            ->disabled(),
                        Forms\Components\TextInput::make('internDivision.name')
                            ->label('Divisi Magang')
                            ->disabled(),
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
                    ->label('Nama Anak Magang')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nis_nim')
                    ->label('NIS/NIM')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('school.name')
                    ->label('Asal Sekolah/Universitas')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('internDivision.name')
                    ->label('Divisi')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai Magang')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai Magang')
                    ->date('d/m/Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
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
            'index' => Pages\ListMySupervisedInterns::route('/'),
        ];
    }
}
