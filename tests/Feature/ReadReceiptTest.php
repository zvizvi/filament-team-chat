<?php

use Filament\TeamChat\Actions\MarkAsRead;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\ReadReceipt;
use Filament\TeamChat\Tests\Fixtures\User;

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

it('returns full unread count when no receipt exists', function () {
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 1');
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 2');
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 3');

    expect($this->channel->unreadCountFor($this->user->id))->toBe(3);
});

it('marks a channel as read', function () {
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 1');
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 2');

    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    expect($this->channel->unreadCountFor($this->user->id))->toBe(0);
    expect(ReadReceipt::count())->toBe(1);
});

it('tracks new unread messages after marking as read', function () {
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 1');
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 2');
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 3');

    expect($this->channel->unreadCountFor($this->user->id))->toBe(2);
});

it('updates existing receipt on subsequent reads', function () {
    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 1');
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'msg 2');
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    expect(ReadReceipt::count())->toBe(1)
        ->and($this->channel->unreadCountFor($this->user->id))->toBe(0);
});

it('does not count thread replies as unread', function () {
    $parent = app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'parent');
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    app(SendMessage::class)->execute($this->channel, $this->otherUser->id, 'reply', $parent->id);

    expect($this->channel->unreadCountFor($this->user->id))->toBe(0);
});

it('tracks unread per user independently', function () {
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'msg 1');

    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    expect($this->channel->unreadCountFor($this->user->id))->toBe(0)
        ->and($this->channel->unreadCountFor($this->otherUser->id))->toBe(1);
});

it('works with conversations too', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    app(SendMessage::class)->execute($conversation, $this->otherUser->id, 'DM 1');
    app(SendMessage::class)->execute($conversation, $this->otherUser->id, 'DM 2');

    expect($conversation->unreadCountFor($this->user->id))->toBe(2);

    app(MarkAsRead::class)->execute($conversation, $this->user->id);

    expect($conversation->unreadCountFor($this->user->id))->toBe(0);
});

it('does nothing when no messages exist', function () {
    app(MarkAsRead::class)->execute($this->channel, $this->user->id);

    expect(ReadReceipt::count())->toBe(0);
});

it('returns zero unread for empty channel', function () {
    expect($this->channel->unreadCountFor($this->user->id))->toBe(0);
});
