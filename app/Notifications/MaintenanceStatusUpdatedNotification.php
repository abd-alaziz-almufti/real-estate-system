<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MaintenanceStatusUpdatedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MaintenanceRequest $maintenanceRequest)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification (stored in DB).
     */
    public function toArray(object $notifiable): array
    {
        $statusLabel = ucwords(str_replace('_', ' ', $this->maintenanceRequest->status));

        return [
            'title'      => 'Maintenance Request Updated',
            'message'    => "Your request \"{$this->maintenanceRequest->title}\" status is now {$statusLabel}.",
            'status'     => $this->maintenanceRequest->status,
            'request_id' => $this->maintenanceRequest->id,
            'url'        => '/tenant/maintenance',
        ];
    }

    /**
     * Get the broadcastable representation (sent via WebSocket to BroadcastContext).
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
