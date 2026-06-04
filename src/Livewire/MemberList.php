<?php

namespace Filament\TeamChat\Livewire;

use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Conversation;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class MemberList extends Component
{
    public bool $isOpen = false;

    public bool $isOwner = false;

    public ?string $messageableType = null;

    public ?int $messageableId = null;

    public int|string|null $addUserId = null;

    #[On('show-members')]
    public function open(string $type, int $id): void
    {
        $this->messageableType = $type;
        $this->messageableId = $id;
        $this->addUserId = null;

        $channel = $type === 'channel' ? Channel::with('members')->find($id) : null;
        $this->isOwner = $channel
            && $channel->members->where('id', auth()->id())->first()?->pivot?->role === 'owner';

        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function getMembersProperty(): Collection
    {
        if (! $this->messageableType || ! $this->messageableId) {
            return collect();
        }

        if ($this->messageableType === 'channel') {
            $channel = Channel::find($this->messageableId);

            return $channel?->members()->with('userStatus')->orderBy('name')->get() ?? collect();
        }

        $conversation = Conversation::find($this->messageableId);

        return $conversation?->participants()->with('userStatus')->orderBy('name')->get() ?? collect();
    }

    public function getAddableUsersProperty(): Collection
    {
        if (! $this->isOwner || $this->messageableType !== 'channel' || ! $this->messageableId) {
            return collect();
        }

        $channel = Channel::find($this->messageableId);

        if (! $channel) {
            return collect();
        }

        $userModel = config('team-chat.user_model');

        $query = $userModel::query()->whereKeyNot($channel->members()->get()->modelKeys());

        if ($scope = config('team-chat.user_scope')) {
            $query->{$scope}();
        }

        return $query->orderBy('name')->get(['id', 'name']);
    }

    public function addMember(): void
    {
        if (! $this->isOwner || $this->messageableType !== 'channel' || ! $this->messageableId) {
            return;
        }

        $this->validate(['addUserId' => 'required']);

        $channel = Channel::find($this->messageableId);

        if (! $channel) {
            return;
        }

        $channel->members()->syncWithoutDetaching([$this->addUserId => ['role' => 'member']]);

        $userModel = config('team-chat.user_model');

        if ($added = $userModel::find($this->addUserId)) {
            $channel->transferOwnershipOnManagerJoin($added);
        }

        $this->addUserId = null;
    }

    public function removeMember(int|string $userId): void
    {
        if (! $this->isOwner || $this->messageableType !== 'channel' || ! $this->messageableId) {
            return;
        }

        $channel = Channel::find($this->messageableId);

        if (! $channel) {
            return;
        }

        $member = $channel->members()->wherePivot('user_id', $userId)->first();

        // Never remove the channel owner.
        if ($member?->pivot?->role === 'owner') {
            return;
        }

        $channel->members()->detach($userId);
    }

    public function showProfile(int|string $userId): void
    {
        $this->dispatch('show-profile', userId: $userId);
    }

    public function render()
    {
        return view('team-chat::livewire.member-list');
    }
}
