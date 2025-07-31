<?php

namespace App\Imports;

use App\Models\DailyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class DailyReportImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Cari user berdasarkan NPP atau email
            $user = User::where('npp', $row['npp'])
                       ->orWhere('email', $row['email'])
                       ->first();

            if (!$user) {
                Log::warning("User not found for NPP: {$row['npp']} or Email: {$row['email']}");
                return null;
            }

            // Parse tanggal
            $entryDate = $this->parseDate($row['tanggal']);
            if (!$entryDate) {
                Log::warning("Invalid date format: {$row['tanggal']}");
                return null;
            }

            // Parse waktu
            $checkIn = $this->parseTime($row['waktu_masuk']);
            $checkOut = $this->parseTime($row['waktu_keluar']);

            if (!$checkIn || !$checkOut) {
                Log::warning("Invalid time format - Check In: {$row['waktu_masuk']}, Check Out: {$row['waktu_keluar']}");
                return null;
            }

            // Cek apakah sudah ada data untuk user dan tanggal yang sama
            $existingReport = DailyReport::where('user_id', $user->id)
                                       ->where('entry_date', $entryDate)
                                       ->first();

            if ($existingReport) {
                Log::info("Daily report already exists for user {$user->id} on {$entryDate}. Skipping...");
                return null;
            }

            return new DailyReport([
                'user_id' => $user->id,
                'entry_date' => $entryDate,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'description' => $row['deskripsi'] ?? 'Imported from Excel',
            ]);

        } catch (\Exception $e) {
            Log::error("Error importing row: " . $e->getMessage(), ['row' => $row]);
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'npp' => 'required|string',
            'email' => 'nullable|email',
            'tanggal' => 'required',
            'waktu_masuk' => 'required',
            'waktu_keluar' => 'required',
            'deskripsi' => 'nullable|string',
        ];
    }

    private function parseDate($date): ?string
    {
        try {
            // Coba berbagai format tanggal
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'm-d-Y',
                'Y/m/d',
            ];

            foreach ($formats as $format) {
                $parsedDate = Carbon::createFromFormat($format, $date);
                if ($parsedDate) {
                    return $parsedDate->format('Y-m-d');
                }
            }

            // Jika semua format gagal, coba parse otomatis
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($time): ?string
    {
        try {
            // Coba berbagai format waktu
            $formats = [
                'H:i:s',
                'H:i',
                'h:i A',
                'h:i a',
                'g:i A',
                'g:i a',
            ];

            foreach ($formats as $format) {
                $parsedTime = Carbon::createFromFormat($format, $time);
                if ($parsedTime) {
                    return $parsedTime->format('H:i:s');
                }
            }

            // Jika semua format gagal, coba parse otomatis
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function customValidationMessages()
    {
        return [
            'npp.required' => 'NPP wajib diisi',
            'email.email' => 'Format email tidak valid',
            'tanggal.required' => 'Tanggal wajib diisi',
            'waktu_masuk.required' => 'Waktu masuk wajib diisi',
            'waktu_keluar.required' => 'Waktu keluar wajib diisi',
        ];
    }
}