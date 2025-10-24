<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The action that was performed.
     */
    protected string $action;

    /**
     * The resource that was affected.
     */
    protected string $resource;

    /**
     * Additional context data.
     */
    protected array $context;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $action, string $resource, array $context = [])
    {
        $this->action = $action;
        $this->resource = $resource;
        $this->context = $context;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'action' => $this->action,
            'resource' => $this->resource,
            'context' => $this->context,
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ];
    }
}
