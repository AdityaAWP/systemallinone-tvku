<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveManagerReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $pendingLeaves;
    protected $upcomingLeaves;
    protected $staffWithLowQuota;

    public function __construct($pendingLeaves, $upcomingLeaves, $staffWithLowQuota)
    {
        $this->pendingLeaves = $pendingLeaves;
        $this->upcomingLeaves = $upcomingLeaves;
        $this->staffWithLowQuota = $staffWithLowQuota;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Pengingat Cuti Karyawan')
            ->greeting('Halo ' . $notifiable->name);
        
        // Pending leaves section
        if ($this->pendingLeaves->count() > 0) {
            $mailMessage->line('Anda memiliki ' . $this->pendingLeaves->count() . ' permintaan cuti yang menunggu persetujuan:');
            
            foreach ($this->pendingLeaves as $leave) {
                $mailMessage->line('- ' . $leave->user->name . ': ' . $this->translateLeaveType($leave->leave_type) . 
                    ' (' . $leave->from_date->format('d M Y') . ' - ' . $leave->to_date->format('d M Y') . ')');
            }
            
            $mailMessage->action('Tinjau Permintaan Cuti', route('filament.admin.resources.leaves.index'));
        }
        
        // Upcoming leaves section
        if ($this->upcomingLeaves->count() > 0) {
            $mailMessage->line('Cuti karyawan yang akan datang dalam 7 hari ke depan:');
            
            foreach ($this->upcomingLeaves as $leave) {
                $mailMessage->line('- ' . $leave->user->name . ': ' . $this->translateLeaveType($leave->leave_type) . 
                    ' (' . $leave->from_date->format('d M Y') . ' - ' . $leave->to_date->format('d M Y') . ')');
            }
        }
        
        // Low quota staff section
        if (count($this->staffWithLowQuota) > 0) {
            $mailMessage->line('Karyawan dengan sisa kuota cuti tahunan rendah:');
            
            foreach ($this->staffWithLowQuota as $staff) {
                $mailMessage->line('- ' . $staff['name'] . ': Sisa ' . $staff['remaining'] . ' hari');
            }
        }
        
        return $mailMessage->line('Silakan login ke sistem untuk melihat detail lebih lanjut.');
    }
    
    private function translateLeaveType($type)
    {
        $translations = [
            'casual' => 'Cuti Tahunan',
            'medical' => 'Cuti Sakit',
            'maternity' => 'Cuti Melahirkan',
            'other' => 'Cuti Lainnya'
        ];

        return $translations[$type] ?? $type;
    }
}