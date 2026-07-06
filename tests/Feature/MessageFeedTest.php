<?php

use Filament\TeamChat\Actions\MarkAsRead;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Livewire\MessageFeed;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Tests\Fixtures\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->actingAs($this->user);

    $this->channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $this->channel->members()->attach([$this->user->id, $this->otherUser->id]);
});

function sendMessages(Channel $channel, int|string $userId, int $count, int $startAt = 1): array
{
    $messages = [];

    foreach (range($startAt, $startAt + $count - 1) as $i) {
        $messages[] = app(SendMessage::class)->execute($channel, $userId, sprintf('msg-%03d', $i));
    }

    return $messages;
}

it('loads only the latest page of messages', function () {
    config()->set('team-chat.pagination.page_size', 10);

    sendMessages($this->channel, $this->otherUser->id, 25);

    $component = livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id]);

    $component
        ->assertSet('hasMoreMessages', true)
        ->assertSee('msg-025')
        ->assertSee('msg-016')
        ->assertDontSee('msg-015');

    expect($component->instance()->getMessagesProperty())->toHaveCount(10);
});

it('loads all messages when there are fewer than a page', function () {
    config()->set('team-chat.pagination.page_size', 10);

    sendMessages($this->channel, $this->otherUser->id, 5);

    $component = livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id]);

    $component
        ->assertSet('hasMoreMessages', false)
        ->assertSee('msg-001');

    expect($component->instance()->getMessagesProperty())->toHaveCount(5);
});

it('loads earlier messages page by page until the full history is loaded', function () {
    config()->set('team-chat.pagination.page_size', 10);

    sendMessages($this->channel, $this->otherUser->id, 25);

    $component = livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id]);

    $component->call('loadMoreMessages')
        ->assertSet('hasMoreMessages', true)
        ->assertSee('msg-006')
        ->assertDontSee('msg-005');

    expect($component->instance()->getMessagesProperty())->toHaveCount(20);

    $component->call('loadMoreMessages')
        ->assertSet('hasMoreMessages', false)
        ->assertSee('msg-001');

    expect($component->instance()->getMessagesProperty())->toHaveCount(25);
});

it('captures the first unread message and renders the new messages divider', function () {
    $read = sendMessages($this->channel, $this->otherUser->id, 3);
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    $unread = sendMessages($this->channel, $this->otherUser->id, 2, startAt: 4);

    livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id])
        ->assertSet('firstUnreadMessageId', $unread[0]->id)
        ->assertSeeHtml('tc-unread-marker')
        ->assertSee(__('team-chat::messages.new_messages'));

    expect($this->channel->unreadCountFor($this->user->id))->toBe(0);
});

it('does not render the divider when everything has been read', function () {
    sendMessages($this->channel, $this->otherUser->id, 3);
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id])
        ->assertSet('firstUnreadMessageId', null)
        ->assertDontSeeHtml('tc-unread-marker');
});

it('does not render the divider when the channel was never read', function () {
    sendMessages($this->channel, $this->otherUser->id, 3);

    livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id])
        ->assertSet('firstUnreadMessageId', null)
        ->assertDontSeeHtml('tc-unread-marker');
});

it('widens the initial window so the first unread message is always loaded', function () {
    config()->set('team-chat.pagination.page_size', 10);

    $read = sendMessages($this->channel, $this->otherUser->id, 5);
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    $unread = sendMessages($this->channel, $this->otherUser->id, 25, startAt: 6);

    $component = livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id]);

    $component
        ->assertSet('firstUnreadMessageId', $unread[0]->id)
        ->assertSee('msg-006')
        ->assertSeeHtml('tc-unread-marker');

    expect($component->get('oldestLoadedMessageId'))->toBeLessThanOrEqual($unread[0]->id);
});

it('resets the window when switching channels', function () {
    config()->set('team-chat.pagination.page_size', 10);

    sendMessages($this->channel, $this->otherUser->id, 25);

    $otherChannel = Channel::create([
        'name' => 'random',
        'slug' => 'random',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);
    $otherChannel->members()->attach([$this->user->id, $this->otherUser->id]);
    sendMessages($otherChannel, $this->otherUser->id, 3, startAt: 100);

    $component = livewire(MessageFeed::class, ['initialType' => 'channel', 'initialId' => $this->channel->id]);
    $component->call('loadMoreMessages');

    $component->dispatch('channel-selected', channelId: $otherChannel->id)
        ->assertSet('hasMoreMessages', false)
        ->assertSee('msg-100');

    expect($component->instance()->getMessagesProperty())->toHaveCount(3);
});
