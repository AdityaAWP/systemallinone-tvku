<?php

namespace App\Filament\Exports;

use App\Models\Leave;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\Auth;

class LeaveExporter extends Exporter
{
    protected static ?string $model = Leave::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID Cuti'),
            ExportColumn::make('user.name')->label('Nama Karyawan'),
            ExportColumn::make('leave_type')
                ->label('Jenis Cuti')
                ->formatStateUsing(fn ($state) => match ($state) {
                    'casual' => 'Cuti Tahunan',
                    'medical' => 'Cuti Sakit',
                    'maternity' => 'Cuti Melahirkan',
                    'other' => 'Cuti Lainnya',
                    default => $state
                }),
            ExportColumn::make('from_date')->label('Tanggal Mulai'),
            ExportColumn::make('to_date')->label('Tanggal Selesai'),
            ExportColumn::make('days')->label('Jumlah Hari'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => match ($state) {
                    'pending' => 'Menunggu',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    default => $state
                }),
            ExportColumn::make('reason')->label('Alasan Cuti'),
            ExportColumn::make('approval_manager')
                ->label('Persetujuan Manajer')
                ->formatStateUsing(fn ($state) => $state ? 'Disetujui' : 'Belum Disetujui'),
            ExportColumn::make('approval_hrd')
                ->label('Persetujuan HRD')
                ->formatStateUsing(fn ($state) => $state ? 'Disetujui' : 'Belum Disetujui'),
        ];
    }

    public function getFilteredQuery()
    {
        $query = Leave::query();
        $user = Auth::user();

        if ($user->hasRole('staff')) {
            $query->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $teamUserIds = $user->getTeamUserIds();
            $query->whereIn('user_id', $teamUserIds);
        } elseif (!$user->hasRole('hrd')) {
            // If not HRD, return empty query
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor data cuti telah selesai.';
    }
}