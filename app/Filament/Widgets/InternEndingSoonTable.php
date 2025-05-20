<?php

namespace App\Filament\Widgets;

use App\Models\Intern;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InternEndingSoonTable extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $oneMonthFromNow = Carbon::now()->addMonth();
        $now = Carbon::now();

        return $table
            ->query(
                Intern::query()
                    ->where('end_date', '>', $now)
                    ->where('end_date', '<=', $oneMonthFromNow)
                    ->orderBy('end_date', 'asc')
            )
            ->heading('Anak Magang yang Akan Selesai dalam 1 Bulan')
            ->columns([
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
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Sisa Hari')
                    ->getStateUsing(function (Intern $record): int {
                        return $record->end_date->diffInDays(Carbon::now());
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 7 => 'danger',
                        $state <= 14 => 'warning',
                        default => 'info',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Lihat')
                    ->url(fn (Intern $record): string => route('filament.admin.resources.interns.edit', $record))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}