<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\LeaveQuota;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeaveStatsWidget extends BaseWidget
{
    protected static ?string $title = 'Statistik Cuti';
    protected static ?int $sort = 1;
    use HasWidgetShield;

    public function getStats(): array
    {
        $user = Auth::user();

        $quota = LeaveQuota::getUserQuota($user->id);
        $currentYear = date('Y');

        // Ambil data cuti tahunan yang sudah disetujui
        $approvedCasualLeaves = Leave::where('user_id', $user->id)
            ->where('leave_type', 'casual')
            ->whereYear('from_date', $currentYear)
            ->where('status', 'approved')
            ->get();

        // Hitung total hari cuti tahunan (exclude weekend)
        $approvedCasualLeaveDays = $this->calculateWorkingDays($approvedCasualLeaves);

        // Ambil data cuti sakit yang sudah disetujui
        $medicalLeaves = Leave::where('user_id', $user->id)
            ->where('leave_type', 'medical')
            ->whereYear('from_date', $currentYear)
            ->where('status', 'approved')
            ->get();

        // Hitung total hari cuti sakit (exclude weekend)
        $medicalLeaveDays = $this->calculateWorkingDays($medicalLeaves);

        // Ambil jumlah cuti yang masih menunggu persetujuan
        $pendingLeaves = Leave::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // Ambil data cuti yang pending untuk menghitung hari kerja
        $pendingLeavesData = Leave::where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        // Hitung total hari yang diajukan (exclude weekend)
        $pendingDays = $this->calculateWorkingDays($pendingLeavesData);

        return [
            Stat::make('Cuti Tahunan', $quota->remaining_casual_quota . ' kesempatan')
                ->description($quota->casual_used . ' kali sudah diambil tahun ini')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('purple'),

            Stat::make('Cuti Tahunan', $approvedCasualLeaveDays . ' Hari')
                ->description('Total hari yang sudah disetujui')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('zinc'),

            Stat::make('Cuti Sakit', $medicalLeaveDays . ' Hari')
                ->description('Total hari yang sudah disetujui')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Permintaan Menunggu', $pendingLeaves)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingLeaves > 0 ? 'warning' : 'success'),
        ];
    }

    /**
     * Calculate working days (exclude weekends) for leave records
     */
    private function calculateWorkingDays($leaves)
    {
        $totalWorkingDays = 0;

        foreach ($leaves as $leave) {
            $fromDate = Carbon::parse($leave->from_date);
            $toDate = Carbon::parse($leave->to_date);

            // Hitung hari kerja antara from_date dan to_date
            $workingDays = 0;
            $currentDate = $fromDate->copy();

            while ($currentDate->lte($toDate)) {
                // Cek apakah hari ini bukan weekend (Sabtu = 6, Minggu = 0)
                if (!$currentDate->isWeekend()) {
                    $workingDays++;
                }
                $currentDate->addDay();
            }

            $totalWorkingDays += $workingDays;
        }

        return $totalWorkingDays;
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        $myLeave = request()->input('tableFilters.my_leave.value');
        $currentRoute = request()->route()?->getName();

        // Tampilkan di dashboard untuk semua role
        if (str_contains($currentRoute ?? '', 'filament.admin.pages.dashboard')) {
            return true;
        }

        // Hanya tampil di halaman cuti (LeaveResource)
        $isLeaveRoute = str_contains($currentRoute ?? '', 'filament.admin.resources.leaves.index');
        if (!$isLeaveRoute) {
            return false;
        }

        // Untuk HRD: hanya tampil ketika filter "Cuti Saya" (true)
        if ($user->hasRole('hrd')) {
            return $myLeave === 'true';
        }

        // Untuk role lain: selalu tampil di halaman cuti (karena mereka hanya lihat cuti sendiri)
        return true;
    }

    public static function getSort(): int
    {
        return 2; // Memastikan widget ini berada di atas
    }
}