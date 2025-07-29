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
            $detailUrl = route('leave.detail', ['id' => $this->leave->id]);

            $statusText = $this->translateStatus($this->leave->status);
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
            Log::info('Status cuti: ' . $statusText . $approver);
            Log::info('Using custom email template: emails.leave-status-updated');

            return (new MailMessage)
                ->subject('Status Permintaan Cuti: ' . $statusText)
                ->view(
                    'emails.leave-status-updated',
                    [
                        'name' => $notifiable->name,
                        'status' => $this->leave->status, // approved, rejected, pending
                        'statusText' => $statusText,
                        'leaveType' => $leaveType,
                        'fromDate' => $this->leave->from_date->format('d M Y'),
                        'toDate' => $this->leave->to_date->format('d M Y'),
                        'days' => $this->leave->days,
                        'reason' => $this->leave->reason,
                        'rejectionReason' => $this->leave->rejection_reason,
                        'approver' => $approver,
                        'detailUrl' => $detailUrl
                    ]
                );
        } catch (\Exception $e) {
            Log::error('Error sending leave status update email: ' . $e->getMessage());
            Log::error('Error stack trace: ' . $e->getTraceAsString());
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
