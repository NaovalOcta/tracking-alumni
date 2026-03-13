<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrackingFinishedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array  $summary  Summary of tracking results (total, success, skipped)
     */
    public function __construct(
        public array $summary
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'tracking_finished',
            'title'   => 'Tracking Batch Selesai',
            'message' => "Proses pelacakan batch telah selesai. Total: {$this->summary['total']} alumni.",
            'summary' => $this->summary,
            'url'     => route('tracking.index'),
        ];
    }
}
