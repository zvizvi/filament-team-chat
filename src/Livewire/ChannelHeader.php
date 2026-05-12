<?php

namespace Filament\TeamChat\Livewire;

use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Conversation;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class ChannelHeader extends Component
{
    public ?string $headerType = null;

    public ?string $headerName = null;

    public ?string $headerDescription = null;

    public ?int $headerModelId = null;

    public int $memberCount = 0;

    public bool $isOwner = false;

    public bool $isEditing = false;

    public string $editName = '';

    public string $editTopic = '';

    public string $editType = 'public';

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
        $channel = Channel::with('members')->find($channelId);

        if ($channel) {
            $this->headerType = 'channel';
            $this->headerModelId = $channelId;
            $this->headerName = $channel->name;
            $this->headerDescription = $channel->topic;
            $this->memberCount = $channel->members->count();
            $this->isOwner = $channel->members->where('id', auth()->id())->first()?->pivot?->role === 'owner';
            $this->isEditing = false;
        }
    }

    #[On('conversation-selected')]
    public function loadConversation(int $conversationId): void
    {
        $conversation = Conversation::with('participants')->find($conversationId);

        if ($conversation) {
            $this->headerType = 'conversation';
            $this->headerModelId = $conversationId;
            $this->headerName = $conversation->getDisplayNameForUser(auth()->user());
            $this->headerDescription = $conversation->is_group ? 'グループDM' : 'ダイレクトメッセージ';
            $this->memberCount = $conversation->participants->count();
            $this->isOwner = false;
            $this->isEditing = false;
        }
    }

    public function startEditing(): void
    {
        if ($this->headerType !== 'channel' || ! $this->isOwner) {
            return;
        }

        $channel = Channel::find($this->headerModelId);

        if (! $channel) {
            return;
        }

        $this->editName = $channel->name;
        $this->editTopic = $channel->topic ?? '';
        $this->editType = $channel->type;
        $this->isEditing = true;
    }

    public function cancelEditing(): void
    {
        $this->isEditing = false;
    }

    public function saveChannel(): void
    {
        if ($this->headerType !== 'channel' || ! $this->isOwner) {
            return;
        }

        $this->validate([
            'editName' => 'required|string|max:255',
            'editType' => 'required|in:public,private',
        ]);

        $channel = Channel::find($this->headerModelId);

        if (! $channel) {
            return;
        }

        $channel->update([
            'name' => $this->editName,
            'slug' => Str::slug($this->editName),
            'topic' => $this->editTopic ?: null,
            'type' => $this->editType,
        ]);

        $this->headerName = $channel->name;
        $this->headerDescription = $channel->topic;
        $this->isEditing = false;
        $this->dispatch('channel-updated');
    }

    public function showMembers(): void
    {
        if ($this->headerType && $this->headerModelId) {
            $this->dispatch('show-members', type: $this->headerType, id: $this->headerModelId);
        }
    }

    public function archiveChannel(): void
    {
        if ($this->headerType !== 'channel' || ! $this->headerModelId || ! $this->isOwner) {
            return;
        }

        $channel = Channel::find($this->headerModelId);

        if (! $channel) {
            return;
        }

        $channel->update(['archived_at' => now()]);
        $this->dispatch('channel-archived');
    }

    public function render()
    {
        return view('team-chat::livewire.channel-header');
    }
}
