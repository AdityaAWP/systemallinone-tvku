<?php

namespace App\Filament\Resources\LoanItemResource\Pages;

use App\Filament\Resources\LoanItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListLoanItems extends ListRecords
{
    protected static string $resource = LoanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'Sudah Dikembalikan' => Tab::make('Sudah Dikembalikan')
                ->query(fn($query) => $query->where('return_status', 'Sudah Dikembalikan')),
            'Belum Dikembalikan' => Tab::make('Belum Dikembalikan')
                ->query(fn($query) => $query->where('return_status', 'Belum Dikembalikan')),
           
        ];
    }
}
