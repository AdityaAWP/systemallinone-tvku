<?php

namespace App\Filament\Exports;

use App\Models\DailyReport;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class DailyReportExporter extends Exporter
{
    protected static ?string $model = DailyReport::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')
                ->label('Nama'),
            ExportColumn::make('entry_date')
                ->label('Tanggal'),
            ExportColumn::make('check_in')
                ->label('Check In'),
            ExportColumn::make('check_out')
                ->label('Check Out'),
            ExportColumn::make('work_hours')
                ->label('Jam Kerja'),
            ExportColumn::make('description')
                ->label('Keterangan')
                ->formatStateUsing(fn (string $state): string => strip_tags($state)), // Remove HTML tags here
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your daily report export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}