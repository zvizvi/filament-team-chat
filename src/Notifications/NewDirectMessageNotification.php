<?php

namespace Filament\TeamChat\Notifications;

use Filament\TeamChat\Models\Conversation;
use Filament\TeamChat\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewDirectMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Message $message,
        public Conversation $conversation,
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
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user?->name ?? 'Unknown',
            'body_preview' => str($this->message->body)->limit(100)->toString(),
        ];
    }
}
