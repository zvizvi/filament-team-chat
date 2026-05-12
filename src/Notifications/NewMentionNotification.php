<?php

namespace Filament\TeamChat\Notifications;

use Filament\TeamChat\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Message $message,
        public string $mentionType = 'user',
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user?->name ?? 'Unknown',
            'body_preview' => str($this->message->body)->limit(100)->toString(),
            'mention_type' => $this->mentionType,
            'messageable_type' => $this->message->messageable_type,
            'messageable_id' => $this->message->messageable_id,
        ];
    }
}
