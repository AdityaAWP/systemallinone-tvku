<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LeaveStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        try {
            $url = route('leave.detail', ['id' => $this->leave->id]);

            $status = $this->translateStatus($this->leave->status);
            $leaveType = $this->translateLeaveType($this->leave->leave_type);

            $approver = '';
            if ($this->leave->status != 'pending') {
                if ($this->leave->approval_manager !== null) {
                    $approver = $this->leave->approval_manager ? ' oleh Manager' : ' oleh Manager';
                } else if ($this->leave->approval_hrd !== null) {
                    $approver = $this->leave->approval_hrd ? ' oleh HRD' : ' oleh HRD';
                }
            }

            Log::info('Mengirim email update status cuti ke: ' . $notifiable->email);
            Log::info('Status cuti: ' . $status . $approver);

            $mailMessage = (new MailMessage)
                ->subject('Status Permintaan Cuti: ' . $status)
                ->greeting('Halo ' . $notifiable->name)
                ->line('Status permintaan cuti Anda telah diperbarui sebagai berikut:')
                ->line('Permintaan cuti ' . $leaveType . ' Anda dari ' . $this->leave->from_date->format('d M Y') .
                    ' sampai ' . $this->leave->to_date->format('d M Y') . ' telah ' . $status . $approver . '.');

            if ($this->leave->isRejected() && $this->leave->rejection_reason) {
                $mailMessage->line('Alasan penolakan: ' . $this->leave->rejection_reason);
            }

            return $mailMessage
                ->action('Lihat Detail', $url)
                ->line('Terima kasih telah menggunakan sistem manajemen cuti kami.')
                ->line('Jika ada pertanyaan, silakan hubungi HRD.');
        } catch (\Exception $e) {
            Log::error('Error sending leave status update email: ' . $e->getMessage());
            return (new MailMessage)
                ->subject('Status Permintaan Cuti')
                ->line('Terjadi perubahan status pada permintaan cuti Anda.')
                ->line('Silakan periksa sistem untuk detail lebih lanjut.');
        }
    }

    private function translateStatus($status)
    {
        $translations = [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        return $translations[$status] ?? $status;
    }

    private function translateLeaveType($type)
    {
        $translations = [
            'casual' => 'Tahunan',
            'medical' => 'Sakit',
            'maternity' => 'Melahirkan',
            'other' => 'Lainnya'
        ];

        return $translations[$type] ?? $type;
    }
}
