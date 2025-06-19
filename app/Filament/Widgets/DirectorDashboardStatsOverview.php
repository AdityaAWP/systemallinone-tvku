<?php

namespace App\Filament\Widgets;

use App\Models\Assignment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DirectorDashboardStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user->hasAnyRole(['direktur_utama', 'admin_keuangan', 'manager_keuangan']) && !$user->hasRole('staff_keuangan');
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user->hasAnyRole(['direktur_utama', 'admin_keuangan', 'manager_keuangan']) || $user->hasRole('staff_keuangan')) {
            return [];
        }

        $stats = [];

        // Stats untuk Manager Keuangan
        if ($user->hasRole('manager_keuangan')) {
            
            // Pending untuk submit oleh manager keuangan (yang belum disubmit)
            $pendingSubmission = Assignment::where('submit_status', Assignment::SUBMIT_BELUM)
                ->whereHas('creator', function ($q) {
                    $q->whereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'staff_keuangan');
                    });
                })
                ->count();

            // Pending submission dengan deadline minggu ini
            $submissionThisWeek = Assignment::where('submit_status', Assignment::SUBMIT_BELUM)
                ->whereHas('creator', function ($q) {
                    $q->whereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'staff_keuangan');
                    });
                })
                ->whereBetween('deadline', [Carbon::now(), Carbon::now()->endOfWeek()])
                ->count();

            // Submitted oleh manager bulan ini (hitung semua yang sudah di-submit ke direktur, bukan hanya yang disubmit oleh user ini)
            $submittedThisMonth = Assignment::where('submit_status', Assignment::SUBMIT_SUDAH)
                ->whereBetween('submitted_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();

            $stats = array_merge($stats, [
                Stat::make('Pending Submit', $pendingSubmission)
                    ->description('Menunggu submit ke direktur')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Perlu Disubmit Minggu Ini', $submissionThisWeek)
                    ->description('Batas waktu minggu ini')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color($submissionThisWeek > 0 ? 'danger' : 'success'),

                Stat::make('Disubmit Bulan Ini', $submittedThisMonth)
                    ->description('Surat yang sudah disubmit ke direktur')
                    ->descriptionIcon('heroicon-m-paper-airplane')
                    ->color('primary'),
            ]);
        }

        // Stats untuk Direktur Utama
        if ($user->hasRole('direktur_utama')) {
            // Pending untuk persetujuan direktur (yang sudah disubmit manager tapi belum di-approve/reject)
            $pendingApproval = Assignment::where('submit_status', Assignment::SUBMIT_SUDAH)
                ->where('approval_status', Assignment::STATUS_PENDING)
                ->count();

            // Pending approval dengan deadline minggu ini
            $approvalThisWeek = Assignment::where('submit_status', Assignment::SUBMIT_SUDAH)
                ->where('approval_status', Assignment::STATUS_PENDING)
                ->whereBetween('deadline', [Carbon::now(), Carbon::now()->endOfWeek()])
                ->count();

            // Approved oleh direktur bulan ini
            $approvedThisMonth = Assignment::where('approval_status', Assignment::STATUS_APPROVED)
                ->whereBetween('approved_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();

            // Rejected oleh direktur bulan ini
            $rejectedThisMonth = Assignment::where('approval_status', Assignment::STATUS_REJECTED)
                ->where('approved_by', $user->id)
                ->whereBetween('approved_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();

            $stats = array_merge($stats, [
                Stat::make('Pending Approval', $pendingApproval)
                    ->description('Menunggu persetujuan Anda')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Perlu Disubmit Minggu Ini', $approvalThisWeek)
                    ->description('Batas waktu minggu ini')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color($approvalThisWeek > 0 ? 'orange' : 'indigo'),

                Stat::make('Disetujui Bulan Ini', $approvedThisMonth)
                    ->description('Surat yang Anda setujui')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Ditolak Bulan Ini', $rejectedThisMonth)
                    ->description('Surat yang Anda tolak')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('danger'),
            ]);
        }

        // Stats untuk Admin Keuangan (view only)
        if ($user->hasRole('admin_keuangan') && !$user->hasAnyRole(['direktur_utama', 'manager_keuangan'])) {
            $belumSubmitCount = Assignment::where('submit_status', Assignment::SUBMIT_BELUM)
                ->where('type', Assignment::TYPE_PAID)
                ->count();

            $sudahSubmitCount = Assignment::where('submit_status', Assignment::SUBMIT_SUDAH)
                ->where('approval_status', Assignment::STATUS_PENDING)
                ->where('type', Assignment::TYPE_PAID)
                ->count();

            $approvedCount = Assignment::where('approval_status', Assignment::STATUS_APPROVED)
                ->where('type', Assignment::TYPE_PAID)
                ->count();

            $rejectedCount = Assignment::where('approval_status', Assignment::STATUS_REJECTED)
                ->where('type', Assignment::TYPE_PAID)
                ->count();

            $stats = array_merge($stats, [
                Stat::make('Belum Submit', $belumSubmitCount)
                    ->description('Menunggu submit manager')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Sudah Submit', $sudahSubmitCount)
                    ->description('Menunggu approval direktur')
                    ->descriptionIcon('heroicon-m-paper-airplane')
                    ->color('info'),

                Stat::make('Approved', $approvedCount)
                    ->description('Sudah disetujui')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Rejected', $rejectedCount)
                    ->description('Ditolak direktur')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('danger'),
            ]);
        }

        return $stats;
    }

    public static function getSort(): int
    {
        return 3; // Ensures this widget is at the top
    }
}