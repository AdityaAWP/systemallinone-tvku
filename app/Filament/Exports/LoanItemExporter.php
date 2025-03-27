<?php

namespace App\Filament\Exports;

use App\Models\LoanItem;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LoanItemExporter extends Exporter
{
    protected static ?string $model = LoanItem::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')->label('Nama Peminjam'),
            ExportColumn::make('location')->label('Lokasi'),
            ExportColumn::make('program')->label('Program'),
            ExportColumn::make('booking_date')->label('Tanggal Peminjaman'),
            ExportColumn::make('start_booking')->label('Waktu Peminjaman'),
            ExportColumn::make('return_date')->label('Tanggal Pengembalian'),
            ExportColumn::make('items_summary')
            ->label('Item Dipinjam')
            ->state(function (LoanItem $record): string {
                return $record->items->map(function ($item) {
                    return "{$item->name} (Qty: {$item->pivot->quantity})";
                })->implode(', ');
            }),
            ExportColumn::make('producer_name')->label('Nama Produser'),
            ExportColumn::make('producer_telp')->label('Telp Produser'),
            ExportColumn::make('crew_name')->label('Nama Crew'),
            ExportColumn::make('crew_telp')->label('Telp Crew'),
            ExportColumn::make('crew_division')->label('Divisi Crew'),
            ExportColumn::make('approver_name')->label('Nama Approver'),
            ExportColumn::make('approver_telp')->label('Telp Approver'),
            ExportColumn::make('approval_status')->label('Status Persetujuan'),
            ExportColumn::make('return_status')->label('Status Pengembalian'),
            ExportColumn::make('description')->label('Deskripsi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your loan item export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
