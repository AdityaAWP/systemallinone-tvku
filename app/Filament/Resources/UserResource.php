<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Division;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen Karyawan';
    protected static ?string $navigationLabel = 'Data Karyawan';
    protected static ?string $label = 'Data Karyawan';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $userCount = User::count();
        return $userCount > 0 ? (string) $userCount : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Utama')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('npp')
                            ->label('NPP')
                            ->required()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create'),
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->required()
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        TextInput::make('position')
                            ->label('Jabatan')
                            ->required(),
                        Select::make('divisions')
                            ->label('Divisi')
                            ->required()
                            ->relationship('divisions', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (is_array($state) && count($state) > 0) {
                                    $set('division_id', $state[0]);
                                }
                            }),
                        Select::make('division_id')
                            ->label('Divisi Utama')
                            ->relationship('division', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive(),
                    ])->columns(2),

                Section::make('Informasi Personal')
                    ->schema([
                        Select::make('gender')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ]),
                        TextInput::make('ktp')
                            ->label('Nomor KTP')
                            ->maxLength(16),
                        DatePicker::make('birth')
                            ->label('Tanggal Lahir'),
                        TextInput::make('no_phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20),
                        Select::make('last_education')
                            ->label('Pendidikan Terakhir')
                            ->options([
                                'sd' => 'SD',
                                'smp' => 'SMP',
                                'sma' => 'SMA',
                                'diploma' => 'Diploma',
                                's1' => 'S1',
                                's2' => 'S2',
                                's3' => 'S3',
                            ]),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Division')
                    ->searchable(),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin') || $user->hasRole('hrd')) {
            return parent::getEloquentQuery();
        } else {
            return parent::getEloquentQuery()
                ->where('created_by', $user->id);
        }
    }
}
