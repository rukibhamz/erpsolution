<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class SystemErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The exception that occurred.
     */
    protected Throwable $exception;

    /**
     * The context where the error occurred.
     */
    protected string $context;

    /**
     * The unique error ID.
     */
    protected string $errorId;

    /**
     * Create a new notification instance.
     */
    public function __construct(Throwable $exception, string $context, string $errorId)
    {
        $this->exception = $exception;
        $this->context = $context;
        $this->errorId = $errorId;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('System Error Alert - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A system error has occurred that requires your attention.')
            ->line('**Error Details:**')
            ->line('Error ID: ' . $this->errorId)
            ->line('Context: ' . $this->context)
            ->line('Message: ' . $this->exception->getMessage())
            ->line('File: ' . $this->exception->getFile())
            ->line('Line: ' . $this->exception->getLine())
            ->line('Time: ' . now()->format('Y-m-d H:i:s'))
            ->line('User: ' . (auth()->user() ? auth()->user()->name . ' (' . auth()->user()->email . ')' : 'Guest'))
            ->line('URL: ' . request()->url())
            ->line('Method: ' . request()->method())
            ->action('View System Logs', route('admin.logs.index'))
            ->line('Please investigate this error and take appropriate action.')
            ->salutation('Best regards, ' . config('app.name') . ' System');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'error_id' => $this->errorId,
            'context' => $this->context,
            'message' => $this->exception->getMessage(),
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'user_id' => auth()->id(),
            'url' => request()->url(),
            'method' => request()->method(),
            'timestamp' => now(),
        ];
    }
}
