<?php

namespace Filament\TeamChat\Livewire;

use Filament\TeamChat\Actions\MarkAsRead;
use Filament\TeamChat\Actions\ToggleReaction;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Conversation;
use Filament\TeamChat\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class MessageFeed extends Component
{
    public ?string $messageableType = null;

    public ?int $messageableId = null;

    public int $lastMessageId = 0;

    /**
     * The first message the user has not read yet, captured before the feed
     * is marked as read so the view can render a "new messages" divider.
     */
    public ?int $firstUnreadMessageId = null;

    /**
     * Lower bound of the loaded message window. Only messages with an id
     * greater than or equal to this are rendered; null means load everything.
     */
    public ?int $oldestLoadedMessageId = null;

    public bool $hasMoreMessages = false;

    public function mount(?string $initialType = null, ?int $initialId = null): void
    {
        if ($initialType === 'channel' && $initialId) {
            $this->loadChannel($initialId);
        } elseif ($initialType === 'conversation' && $initialId) {
            $this->loadConversation($initialId);
        }
    }

    #[On('channel-selected')]
    public function loadChannel(int $channelId): void
    {
        $this->messageableType = Channel::class;
        $this->messageableId = $channelId;
        $this->initializeFeed();
    }

    #[On('conversation-selected')]
    public function loadConversation(int $conversationId): void
    {
        $this->messageableType = Conversation::class;
        $this->messageableId = $conversationId;
        $this->initializeFeed();
    }

    #[On('message-sent')]
    public function refreshMessages(): void
    {
        // Triggers re-evaluation of the messages computed property
    }

    /**
     * Capture the first unread message (before it is marked as read) and
     * position the initial window on the latest page of messages, widened
     * when needed so the first unread message is always included.
     */
    protected function initializeFeed(): void
    {
        $this->lastMessageId = 0;
        $this->firstUnreadMessageId = null;
        $this->oldestLoadedMessageId = null;
        $this->hasMoreMessages = false;

        $lastReadMessageId = $this->messageableType::find($this->messageableId)
            ?->getLastReadMessageIdFor(auth()->id());

        if ($lastReadMessageId) {
            $this->firstUnreadMessageId = $this->baseMessagesQuery()
                ->where('id', '>', $lastReadMessageId)
                ->min('id');
        }

        $windowStartId = $this->baseMessagesQuery()
            ->orderByDesc('id')
            ->skip($this->getPageSize() - 1)
            ->value('id');

        if ($windowStartId && $this->firstUnreadMessageId && $this->firstUnreadMessageId < $windowStartId) {
            $windowStartId = $this->baseMessagesQuery()
                ->where('id', '<', $this->firstUnreadMessageId)
                ->orderByDesc('id')
                ->skip($this->getPageSize() - 1)
                ->value('id');
        }

        $this->oldestLoadedMessageId = $windowStartId;
        $this->hasMoreMessages = $windowStartId
            ? $this->baseMessagesQuery()->where('id', '<', $windowStartId)->exists()
            : false;
    }

    public function loadMoreMessages(): void
    {
        if (! $this->hasMoreMessages || ! $this->oldestLoadedMessageId) {
            return;
        }

        $windowStartId = $this->baseMessagesQuery()
            ->where('id', '<', $this->oldestLoadedMessageId)
            ->orderByDesc('id')
            ->skip($this->getPageSize() - 1)
            ->value('id');

        $this->oldestLoadedMessageId = $windowStartId;
        $this->hasMoreMessages = $windowStartId
            ? $this->baseMessagesQuery()->where('id', '<', $windowStartId)->exists()
            : false;
    }

    public function getMessagesProperty(): Collection
    {
        if (! $this->messageableType || ! $this->messageableId) {
            return collect();
        }

        $messages = $this->baseMessagesQuery()
            ->when($this->oldestLoadedMessageId, fn ($query) => $query->where('id', '>=', $this->oldestLoadedMessageId))
            ->with(['user', 'reactions.user', 'attachments'])
            ->withCount('replies')
            ->orderBy('id')
            ->get();

        if ($messages->isNotEmpty()) {
            $this->lastMessageId = $messages->last()->id;
            $this->markAsRead();
        }

        return $messages;
    }

    protected function baseMessagesQuery(): Builder
    {
        return Message::where('messageable_type', $this->messageableType)
            ->where('messageable_id', $this->messageableId)
            ->whereNull('parent_id');
    }

    protected function getPageSize(): int
    {
        return max(1, (int) config('team-chat.pagination.page_size', 100));
    }

    protected function markAsRead(): void
    {
        if (! $this->messageableType || ! $this->messageableId) {
            return;
        }

        $messageable = $this->messageableType::find($this->messageableId);

        if ($messageable) {
            app(MarkAsRead::class)->execute($messageable, auth()->id());
        }
    }

    public ?int $editingMessageId = null;

    public string $editBody = '';

    public ?int $emojiPickerMessageId = null;

    public function toggleEmojiPicker(int $messageId): void
    {
        $this->emojiPickerMessageId = $this->emojiPickerMessageId === $messageId ? null : $messageId;
    }

    public function addReaction(int $messageId, string $emoji): void
    {
        app(ToggleReaction::class)->execute($messageId, auth()->id(), $emoji);
        $this->emojiPickerMessageId = null;
    }

    public function startEditing(int $messageId): void
    {
        $message = Message::find($messageId);

        if (! $message || $message->user_id !== auth()->id()) {
            return;
        }

        $this->editingMessageId = $messageId;
        $this->editBody = $message->body;
    }

    public function cancelEditing(): void
    {
        $this->editingMessageId = null;
        $this->editBody = '';
    }

    public function saveEdit(): void
    {
        $message = Message::find($this->editingMessageId);

        if (! $message || $message->user_id !== auth()->id() || trim($this->editBody) === '') {
            return;
        }

        $message->update([
            'body' => $this->editBody,
            'body_html' => Str::markdown($this->editBody, ['renderer' => ['soft_break' => '<br>']]),
            'edited_at' => now(),
        ]);

        $this->cancelEditing();
    }

    public function deleteMessage(int $messageId): void
    {
        $message = Message::find($messageId);

        if (! $message || $message->user_id !== auth()->id()) {
            return;
        }

        $message->delete();
    }

    public function openThread(int $messageId): void
    {
        $this->dispatch('open-thread', messageId: $messageId);
    }

    public function showProfile(int|string $userId): void
    {
        $this->dispatch('show-profile', userId: $userId);
    }

    public function render()
    {
        return view('team-chat::livewire.message-feed');
    }
}
