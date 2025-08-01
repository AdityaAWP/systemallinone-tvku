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

    private $importedCount = 0;
    private $skippedCount = 0;
    private $errorCount = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            Log::info("Processing row: ", $row);

            // Cari user berdasarkan NPP atau email
            $user = null;

            // Prioritas pencarian: NPP dulu, kemudian email
            if (!empty($row['npp'])) {
                $user = User::where('npp', trim($row['npp']))->first();
            }

            if (!$user && !empty($row['email'])) {
                $user = User::where('email', trim($row['email']))->first();
            }

            if (!$user) {
                Log::warning("User not found for NPP: {$row['npp']} or Email: {$row['email']}");
                $this->errorCount++;
                return null;
            }

            Log::info("Found user: {$user->name} (ID: {$user->id})");

            // Parse tanggal
            $entryDate = $this->parseDate($row['tanggal']);
            if (!$entryDate) {
                Log::warning("Invalid date format: {$row['tanggal']}");
                $this->errorCount++;
                return null;
            }

            Log::info("Parsed date: {$entryDate}");

            // Parse waktu
            $checkIn = $this->parseTime($row['waktu_masuk']);
            $checkOut = $this->parseTime($row['waktu_keluar']);

            if (!$checkIn || !$checkOut) {
                Log::warning("Invalid time format - Check In: {$row['waktu_masuk']}, Check Out: {$row['waktu_keluar']}");
                $this->errorCount++;
                return null;
            }

            Log::info("Parsed times - Check In: {$checkIn}, Check Out: {$checkOut}");

            // Cek apakah sudah ada data untuk user dan tanggal yang sama
            $existingReport = DailyReport::where('user_id', $user->id)
                ->where('entry_date', $entryDate)
                ->first();

            if ($existingReport) {
                Log::info("Daily report already exists for user {$user->id} on {$entryDate}. Skipping...");
                $this->skippedCount++;
                return null;
            }

            // Buat daily report baru
            $dailyReport = new DailyReport([
                'user_id' => $user->id,
                'entry_date' => $entryDate,
                'check_in' => $entryDate . ' ' . $checkIn, // Format datetime untuk check_in
                'check_out' => $entryDate . ' ' . $checkOut, // Format datetime untuk check_out
                'description' => !empty($row['deskripsi']) ? $row['deskripsi'] : 'Imported from Excel',
            ]);

            Log::info("Successfully created daily report for user {$user->name} on {$entryDate}");
            $this->importedCount++;

            return $dailyReport;
        } catch (\Exception $e) {
            Log::error("Error importing row: " . $e->getMessage(), ['row' => $row]);
            $this->errorCount++;
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'npp' => 'nullable|string',
            'email' => 'nullable|email',
            'tanggal' => 'required',
            'waktu_masuk' => 'required',
            'waktu_keluar' => 'required',
            'deskripsi' => 'nullable|string|max:1000',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'npp.string' => 'NPP harus berupa string',
            'email.email' => 'Format email tidak valid',
            'tanggal.required' => 'Tanggal wajib diisi',
            'waktu_masuk.required' => 'Waktu masuk wajib diisi',
            'waktu_keluar.required' => 'Waktu keluar wajib diisi',
            'deskripsi.max' => 'Deskripsi tidak boleh lebih dari 1000 karakter',
        ];
    }

    private function parseDate($date): ?string
    {
        try {
            // Jika $date adalah angka (Excel date serial number)
            if (is_numeric($date)) {
                // Excel base date is 1900-01-01, but Excel incorrectly treats 1900 as a leap year
                $excelEpoch = Carbon::create(1900, 1, 1)->subDays(2);
                return $excelEpoch->addDays($date)->format('Y-m-d');
            }

            // Jika sudah dalam format string, bersihkan dulu
            $date = trim($date);

            // Coba berbagai format tanggal
            $formats = [
                'Y-m-d',
                'd/m/Y',
                'm/d/Y',
                'd-m-Y',
                'm-d-Y',
                'Y/m/d',
                'd.m.Y',
                'Y.m.d',
            ];

            foreach ($formats as $format) {
                try {
                    $parsedDate = Carbon::createFromFormat($format, $date);
                    if ($parsedDate && $parsedDate->format($format) === $date) {
                        return $parsedDate->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Jika semua format gagal, coba parse otomatis
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error("Failed to parse date: {$date}. Error: " . $e->getMessage());
            return null;
        }
    }

    private function parseTime($time): ?string
    {
        try {
            // Jika $time adalah angka decimal (Excel time format)
            if (is_numeric($time)) {
                // Excel time is stored as fraction of day
                $totalSeconds = round($time * 24 * 60 * 60);
                $hours = intval($totalSeconds / 3600);
                $minutes = intval(($totalSeconds % 3600) / 60);
                $seconds = $totalSeconds % 60;
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            // Clean up the time string
            $time = trim($time);

            // Coba berbagai format waktu
            $formats = [
                'H:i:s',
                'H:i',
                'h:i A',
                'h:i a',
                'g:i A',
                'g:i a',
                'H.i.s',
                'H.i',
            ];

            foreach ($formats as $format) {
                try {
                    $parsedTime = Carbon::createFromFormat($format, $time);
                    if ($parsedTime) {
                        return $parsedTime->format('H:i:s');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Jika semua format gagal, coba parse otomatis
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            Log::error("Failed to parse time: {$time}. Error: " . $e->getMessage());
            return null;
        }
    }

    // Getter methods untuk mendapatkan jumlah data yang diproses
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    // Method untuk mendapatkan ringkasan hasil import
    public function getSummary(): array
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errorCount,
            'total_processed' => $this->importedCount + $this->skippedCount + $this->errorCount
        ];
    }
}
