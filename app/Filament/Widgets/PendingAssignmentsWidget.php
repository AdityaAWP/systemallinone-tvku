<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PendingAssignmentsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        $user = Auth::user();

        if ($user->hasRole('manager_keuangan')) {
            // Manager Keuangan: Submit pengajuan (dari SUBMIT_BELUM ke SUBMIT_SUDAH)
            // Menampilkan assignments yang dibuat staff dengan submit_status = BELUM
            $query = Assignment::query()
                ->where('submit_status', Assignment::SUBMIT_BELUM) // Yang belum diajukan
                ->whereHas('creator', function ($q) {
                    $q->whereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'staff_keuangan');
                    });
                })
                ->with(['creator']) // Load creator relationship
                ->latest('created_at')
                ->limit(10);

            $heading = 'Assignments Pending Manager Submission';
            $description = 'Assignments dari staff yang menunggu submit Anda ke direktur';

            $approveAction = Tables\Actions\Action::make('submit')
                ->label('Submit ke Direktur')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Submit Assignment ke Direktur')
                ->modalDescription('Apakah Anda yakin ingin submit assignment ini ke direktur untuk approval?')
                ->modalSubmitActionLabel('Ya, Submit')
                ->action(function (Assignment $record) {
                    $record->update([
                        'submit_status' => Assignment::SUBMIT_SUDAH, // Ubah status pengajuan
                        'submitted_by' => Auth::id(),
                        'submitted_at' => now(),
                        // approval_status tetap PENDING, akan diubah direktur nanti
                    ]);
                    
                    $this->dispatch('assignment-submitted');
                })
                ->visible(fn (Assignment $record): bool => $record->submit_status === Assignment::SUBMIT_BELUM);

            $rejectAction = Tables\Actions\Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Tolak Assignment')
                ->modalDescription('Apakah Anda yakin ingin menolak assignment ini? Assignment akan dikembalikan ke staff.')
                ->modalSubmitActionLabel('Ya, Tolak')
                ->action(function (Assignment $record) {
                    // Bisa tetap di submit_status BELUM atau buat status khusus untuk ditolak manager
                    $record->update([
                        'submit_status' => Assignment::SUBMIT_BELUM, // Atau buat status REJECTED_BY_MANAGER
                        'submitted_by' => Auth::id(),
                        'submitted_at' => now(),
                        // Bisa tambah field rejection_reason jika perlu
                    ]);
                    
                    $this->dispatch('assignment-rejected');
                })
                ->visible(fn (Assignment $record): bool => $record->submit_status === Assignment::SUBMIT_BELUM);

        } elseif ($user->hasRole('direktur_utama')) {
            // Direktur Utama: Approve pengajuan (dari STATUS_PENDING ke STATUS_APPROVED/STATUS_REJECTED)
            // Menampilkan assignments yang sudah disubmit manager (submit_status = SUDAH)
            $query = Assignment::query()
                ->where('submit_status', Assignment::SUBMIT_SUDAH) // Yang sudah disubmit manager
                ->where('approval_status', Assignment::STATUS_PENDING) // Yang belum di-approve/reject direktur
                ->with(['creator', 'submitter']) // Load relationships
                ->latest('submitted_at')
                ->limit(10);

            $heading = 'Assignments Pending Director Approval';
            $description = 'Assignments yang disubmit manager dan menunggu persetujuan Anda';

            $approveAction = Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Approve Assignment')
                ->modalDescription('Apakah Anda yakin ingin menyetujui assignment ini?')
                ->modalSubmitActionLabel('Ya, Approve')
                ->action(function (Assignment $record) {
                    $record->update([
                        'approval_status' => Assignment::STATUS_APPROVED, // Ubah status persetujuan
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        // submit_status tetap SUDAH
                    ]);
                    
                    $this->dispatch('assignment-approved');
                })
                ->visible(fn (Assignment $record): bool => 
                    $record->submit_status === Assignment::SUBMIT_SUDAH && 
                    $record->approval_status === Assignment::STATUS_PENDING
                );

            $rejectAction = Tables\Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->modalHeading('Reject Assignment')
                ->modalDescription('Apakah Anda yakin ingin menolak assignment ini?')
                ->modalSubmitActionLabel('Ya, Reject')
                ->action(function (Assignment $record) {
                    $record->update([
                        'approval_status' => Assignment::STATUS_REJECTED, // Ubah status persetujuan
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                        // submit_status tetap SUDAH
                    ]);
                    
                    $this->dispatch('assignment-rejected');
                })
                ->visible(fn (Assignment $record): bool => 
                    $record->submit_status === Assignment::SUBMIT_SUDAH && 
                    $record->approval_status === Assignment::STATUS_PENDING
                );

        } else {
            // User tanpa akses
            $query = Assignment::query()->whereRaw('1=0');
            $heading = 'No Access';
            $description = 'Anda tidak memiliki akses untuk melihat data ini';
            $approveAction = null;
            $rejectAction = null;
        }

        return $table
            ->query($query)
            ->heading($heading)
            ->description($description)
            ->columns([
                Tables\Columns\TextColumn::make('spp_number')
                    ->label('No. SPP')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasAnyRole(['manager_keuangan', 'direktur_utama']))
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('submitter.name')
                    ->label('Disubmit Oleh')
                    ->searchable()
                    ->sortable()
                    ->visible(fn() => Auth::user()->hasRole('direktur_utama'))
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('client')
                    ->label('Klien')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::PRIORITY_NORMAL => 'Normal',
                            Assignment::PRIORITY_IMPORTANT => 'Penting',
                            Assignment::PRIORITY_VERY_IMPORTANT => 'Sangat Penting',
                            default => $state,
                        };
                    })
                    ->colors([
                        'secondary' => Assignment::PRIORITY_NORMAL,
                        'warning' => Assignment::PRIORITY_IMPORTANT,
                        'danger' => Assignment::PRIORITY_VERY_IMPORTANT,
                    ]),

                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->color(fn(Assignment $record) =>
                        $record->deadline->isPast() ? 'danger' : ($record->deadline->isToday() ? 'warning' : 'success'))
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn() => Auth::user()->hasRole('manager_keuangan')),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Disubmit')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->visible(fn() => Auth::user()->hasRole('direktur_utama')),

                // Tampilkan Status Pengajuan untuk Manager Keuangan
                Tables\Columns\TextColumn::make('submit_status')
                    ->label('Status Pengajuan')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::SUBMIT_BELUM => 'Belum Diajukan',
                            Assignment::SUBMIT_SUDAH => 'Sudah Diajukan',
                            default => $state,
                        };
                    })
                    ->colors([
                        'warning' => Assignment::SUBMIT_BELUM,
                        'info' => Assignment::SUBMIT_SUDAH,
                    ])
                    ->visible(fn() => Auth::user()->hasRole('manager_keuangan')),

                // Tampilkan Status Persetujuan untuk Direktur Utama
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status Persetujuan')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            Assignment::STATUS_PENDING => 'Pending',
                            Assignment::STATUS_SUBMITTED => 'Submitted',
                            Assignment::STATUS_APPROVED => 'Approved',
                            Assignment::STATUS_DECLINED => 'Declined',
                            Assignment::STATUS_REJECTED => 'Rejected',
                            default => $state,
                        };
                    })
                    ->colors([
                        'warning' => Assignment::STATUS_PENDING,
                        'info' => Assignment::STATUS_SUBMITTED,
                        'success' => Assignment::STATUS_APPROVED,
                        'secondary' => Assignment::STATUS_DECLINED,
                        'danger' => Assignment::STATUS_REJECTED,
                    ])
                    ->visible(fn() => Auth::user()->hasRole('direktur_utama')),
            ])
            ->actions(array_filter([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->url(fn(Assignment $record): string => route('filament.admin.resources.assignments.view', $record))
                    ->icon('heroicon-o-eye')
                    ->color('gray'),
                $approveAction,
                $rejectAction,
            ]))
            ->emptyStateHeading('Tidak ada assignment pending')
            ->emptyStateDescription('Semua assignment sudah diproses.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('direktur_utama') || $user->hasRole('manager_keuangan'));
    }
}