<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LeaveRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected $leave;

    public function __construct(Leave $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        // Gunakan config('app.url') untuk mendapatkan URL lengkap dengan port
        $baseUrl = config('app.url');

        // Tentukan role dari notifiable
        $role = $notifiable->hasRole('manager') ? 'manager' : 'hrd';

        $approveUrl = $baseUrl . '/leave/approve-by-token/' . $this->leave->approval_token . '?role=' . $role;
        $rejectUrl = $baseUrl . '/leave/reject-by-token/' . $this->leave->approval_token . '?role=' . $role;

        $leaveType = $this->translateLeaveType($this->leave->leave_type);

        Log::info('Mengirim email permintaan cuti ke: ' . $notifiable->email . ' sebagai ' . $role);
        Log::info('Link approve: ' . $approveUrl);
        Log::info('Link reject: ' . $rejectUrl);

        return (new MailMessage)
            ->subject('Permintaan Cuti Baru dari ' . $this->leave->user->name)
            ->greeting('Halo ' . $notifiable->name)
            ->line($this->leave->user->name . ' telah mengajukan ' . $this->leave->days . ' hari cuti ' . $leaveType . '.')
            ->line('Dari: ' . $this->leave->from_date->format('d M Y') . ' Sampai: ' . $this->leave->to_date->format('d M Y'))
            ->line('Alasan: ' . $this->leave->reason)
            ->action('Setujui', $approveUrl)
            ->line('Atau')
            ->action('Tolak', $rejectUrl)
            ->line('Mohon tinjau permintaan ini secepatnya.');
    }
    public function toDatabase($notifiable)
    {
        return [
            'leave_id' => $this->leave->id,
            'user_name' => $this->leave->user->name,
            'leave_type' => $this->leave->leave_type,
            'from_date' => $this->leave->from_date->format('d M Y'),
            'to_date' => $this->leave->to_date->format('d M Y'),
            'message' => $this->leave->user->name . ' telah mengajukan cuti ' . $this->translateLeaveType($this->leave->leave_type),
        ];
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
