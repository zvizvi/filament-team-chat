<?php

use App\Models\User;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Notifications\NewDirectMessageNotification;
use Filament\TeamChat\Notifications\NewMentionNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->user = User::factory()->create(['name' => 'Alice']);
    $this->bob = User::factory()->create(['name' => 'Bob']);
    $this->actingAs($this->user);

    $this->channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $this->channel->members()->attach([$this->user->id, $this->bob->id]);
});

it('sends notification when user is mentioned', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @Bob check this',
    );

    Notification::assertSentTo($this->bob, NewMentionNotification::class);
});

it('does not send mention notification to self', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Note to @Alice myself',
    );

    Notification::assertNotSentTo($this->user, NewMentionNotification::class);
});

it('sends channel mention notification to all members except sender', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: '@channel important update',
    );

    Notification::assertSentTo($this->bob, NewMentionNotification::class);
    Notification::assertNotSentTo($this->user, NewMentionNotification::class);
});

it('sends DM notification to other participants', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->bob->id);

    app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->user->id,
        body: 'Hey Bob!',
    );

    Notification::assertSentTo($this->bob, NewDirectMessageNotification::class);
    Notification::assertNotSentTo($this->user, NewDirectMessageNotification::class);
});

it('sends DM notification to all group participants except sender', function () {
    $charlie = User::factory()->create(['name' => 'Charlie']);
    $conversation = $this->user->createGroupConversation(
        userIds: [$this->bob->id, $charlie->id],
        name: 'Team',
    );

    app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->user->id,
        body: 'Group message',
    );

    Notification::assertSentTo($this->bob, NewDirectMessageNotification::class);
    Notification::assertSentTo($charlie, NewDirectMessageNotification::class);
    Notification::assertNotSentTo($this->user, NewDirectMessageNotification::class);
});

it('does not send DM notification for channel messages', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Normal channel message',
    );

    Notification::assertNotSentTo($this->bob, NewDirectMessageNotification::class);
});

it('includes correct data in mention notification', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @Bob review this please',
    );

    Notification::assertSentTo($this->bob, function (NewMentionNotification $notification) {
        $data = $notification->toArray($this->bob);

        return $data['sender_name'] === 'Alice'
            && $data['mention_type'] === 'user'
            && str_contains($data['body_preview'], '@Bob');
    });
});

it('includes correct data in DM notification', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->bob->id);

    app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->user->id,
        body: 'Hello via DM',
    );

    Notification::assertSentTo($this->bob, function (NewDirectMessageNotification $notification) {
        $data = $notification->toArray($this->bob);

        return $data['sender_name'] === 'Alice'
            && str_contains($data['body_preview'], 'Hello via DM');
    });
});
