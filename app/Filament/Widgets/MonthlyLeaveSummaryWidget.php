<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MonthlyLeaveSummaryWidget extends BaseWidget
{
    protected static ?string $heading = 'Rekap Pengajuan Cuti Pegawai Bulanan';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()->hasRole('hrd');
    }

    public function table(Table $table): Table
    {
        $currentYear = Carbon::now()->year;

        return $table
            ->query(
                User::query()
                    ->whereHas('leaves', function($query) use ($currentYear) {
                        $query->whereYear('from_date', $currentYear)
                              ->where('status', 'approved');
                    })
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable(),
                
                // January
                Tables\Columns\TextColumn::make('jan_leaves')
                    ->label('Jan')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 1)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // February
                Tables\Columns\TextColumn::make('feb_leaves')
                    ->label('Feb')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 2)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // March
                Tables\Columns\TextColumn::make('mar_leaves')
                    ->label('Mar')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 3)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // April
                Tables\Columns\TextColumn::make('apr_leaves')
                    ->label('Apr')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 4)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // May
                Tables\Columns\TextColumn::make('may_leaves')
                    ->label('Mei')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 5)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // June
                Tables\Columns\TextColumn::make('jun_leaves')
                    ->label('Jun')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 6)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // July
                Tables\Columns\TextColumn::make('jul_leaves')
                    ->label('Jul')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 7)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // August
                Tables\Columns\TextColumn::make('aug_leaves')
                    ->label('Agu')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 8)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // September
                Tables\Columns\TextColumn::make('sep_leaves')
                    ->label('Sep')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 9)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // October
                Tables\Columns\TextColumn::make('oct_leaves')
                    ->label('Okt')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 10)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // November
                Tables\Columns\TextColumn::make('nov_leaves')
                    ->label('Nov')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 11)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // December
                Tables\Columns\TextColumn::make('dec_leaves')
                    ->label('Des')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        return $record->leaves()
                            ->whereMonth('from_date', 12)
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days') ?: '-';
                    }),
                
                // Total
                Tables\Columns\TextColumn::make('total_leaves')
                    ->label('Total')
                    ->getStateUsing(function (User $record) use ($currentYear) {
                        $total = $record->leaves()
                            ->whereYear('from_date', $currentYear)
                            ->where('status', 'approved')
                            ->sum('days');
                        return $total ?: '-';
                    })
                    ->formatStateUsing(fn ($state) => $state === '-' ? $state : $state . ' Hari'),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption(10);
    }
}