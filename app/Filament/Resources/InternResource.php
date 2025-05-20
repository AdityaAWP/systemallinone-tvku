<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InternResource\Pages;
use App\Models\Intern;
use App\Models\InternSchool;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InternResource extends Resource
{
    protected static ?string $model = Intern::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Data Anak Magang';
    protected static ?string $modelLabel = 'Anak Magang';
    protected static ?string $pluralModelLabel = 'Daftar Anak Magang';
    protected static ?string $navigationGroup = 'Manajemen Magang';
    protected static ?int $navigationSort = 5;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required(),
                        Forms\Components\TextInput::make('nis_nim')
                            ->label('NIS/NIM')
                            ->maxLength(50),
                        // Menambahkan pemilihan tipe institusi terlebih dahulu
                        Forms\Components\Select::make('institution_type')
                            ->label('Tipe Institusi')
                            ->options([
                                'Perguruan Tinggi' => 'Perguruan Tinggi',
                                'SMA/SMK' => 'SMA/SMK',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('school_id', null)),
                        // School selector yang bergantung pada tipe institusi yang dipilih
                        Forms\Components\Select::make('school_id')
                            ->label('Sekolah/Instansi')
                            ->options(function (Forms\Get $get) {
                                $type = $get('institution_type');
                                if (!$type) return [];
                                
                                return InternSchool::where('type', $type)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('division')
                            ->label('Divisi')
                            ->options([
                                'IT' => 'IT',
                                'Produksi' => 'Produksi',
                                'DINUS FM' => 'DINUS FM',
                                'TS' => 'TS',
                                'MCR' => 'MCR',
                                'DMO' => 'DMO',
                                'Wardrobe' => 'Wardrobe',
                                'News' => 'News',
                                'Humas dan Marketing' => 'Humas dan Marketing',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('no_phone')
                            ->label('Telepon Magang')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('institution_supervisor')
                            ->label('Pembimbing Asal')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('college_supervisor')
                            ->label('Pembimbing TVKU')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('college_supervisor_phone')
                            ->label('Telepon Pembimbing')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Mulai Magang')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Selesai Magang')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('school.name')
                    ->label('Sekolah/Instansi')
                    ->sortable(),
                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_phone')
                    ->label('Telepon Magang'),
                Tables\Columns\TextColumn::make('institution_supervisor')
                    ->label('Pembimbing Asal'),
                Tables\Columns\TextColumn::make('college_supervisor_phone')
                    ->label('Telepon Pembimbing'),
                Tables\Columns\TextColumn::make('college_supervisor')
                    ->label('Pembimbing TVKU'),
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
                        $now = Carbon::now();
                        $start = Carbon::parse($record->start_date);
                        $end = Carbon::parse($record->end_date);
                        $hampirStart = $end->copy()->subMonth();

                        if ($now->lessThan($start)) {
                            return 'Datang';
                        } elseif ($now->greaterThanOrEqualTo($hampirStart) && $now->lessThanOrEqualTo($end)) {
                            return 'Hampir';
                        } elseif ($now->between($start, $hampirStart->subSecond())) {
                            return 'Active';
                        } else {
                            return 'Selesai';
                        }
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Datang' => 'warning',
                        'Hampir' => 'danger',
                        'Selesai' => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Batal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->color('primary')
                    ->icon('heroicon-o-document')
                    ->form([
                        Forms\Components\Select::make('institution_type')
                            ->label('Pilih Tipe Institusi')
                            ->options([
                                'all' => 'Semua',
                                'Perguruan Tinggi' => 'Perguruan Tinggi',
                                'SMA/SMK' => 'SMA/SMK',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        // Redirect dengan parameter filter
                        redirect()->route('admin.interns.pdf', [
                            'type' => $data['institution_type'],
                        ]);
                    }),
                    
                Tables\Actions\Action::make('export_excel')
                    ->label('Download Excel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\Select::make('institution_type')
                            ->label('Pilih Tipe Institusi')
                            ->options([
                                'all' => 'Semua',
                                'Perguruan Tinggi' => 'Perguruan Tinggi',
                                'SMA/SMK' => 'SMA/SMK',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        // Redirect dengan parameter filter
                        redirect()->route('admin.interns.excel', [
                            'type' => $data['institution_type'],
                        ]);
                    }),
                    
                Tables\Actions\Action::make('pre_register')
                    ->label('Pre-Register Anak Magang')
                    ->color('primary')
                    ->icon('heroicon-o-user-plus')
                    ->modalHeading('Pre-Register Anak Magang')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->required(),
                        Forms\Components\Select::make('institution_type')
                            ->label('Tipe Institusi')
                            ->options([
                                'Perguruan Tinggi' => 'Perguruan Tinggi',
                                'SMA/SMK' => 'SMA/SMK',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('school_id', null)),
                        Forms\Components\Select::make('school_id')
                            ->label('Pilih Institusi')
                            ->options(function (Forms\Get $get) {
                                $type = $get('institution_type');
                                if (!$type) return [];
                                
                                return InternSchool::where('type', $type)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        Intern::create([
                            'name' => $data['username'],
                            'school_id' => $data['school_id'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterns::route('/'),
            'create' => Pages\CreateIntern::route('/create'),
            'edit' => Pages\EditIntern::route('/{record}/edit'),
        ];
    }
}