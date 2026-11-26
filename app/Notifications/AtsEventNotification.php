<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AtsEventNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public NotificationCategory $category,
        public string $title,
        public string $message,
        public string $url,
        public array $data = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action('Ver en TalentFlow', url($this->url));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return array_merge([
            'category' => $this->category->value,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ], $this->data);
    }
}
