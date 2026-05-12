<?php

use App\Models\User;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Conversation;
use Filament\TeamChat\Models\Message;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a 1-on-1 direct message', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    expect($conversation)->toBeInstanceOf(Conversation::class)
        ->and($conversation->is_group)->toBeFalse()
        ->and($conversation->participants)->toHaveCount(2);
});

it('returns the same conversation for existing 1-on-1 DM', function () {
    $first = $this->user->findOrCreateDirectMessage($this->otherUser->id);
    $second = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    expect($first->id)->toBe($second->id);
});

it('can create a group conversation', function () {
    $thirdUser = User::factory()->create();

    $conversation = $this->user->createGroupConversation(
        userIds: [$this->otherUser->id, $thirdUser->id],
        name: 'Project Team',
    );

    expect($conversation->is_group)->toBeTrue()
        ->and($conversation->name)->toBe('Project Team')
        ->and($conversation->participants)->toHaveCount(3);
});

it('includes the creator in group conversation participants', function () {
    $conversation = $this->user->createGroupConversation(
        userIds: [$this->otherUser->id],
        name: 'Team',
    );

    $participantIds = $conversation->participants->pluck('id')->all();

    expect($participantIds)->toContain($this->user->id)
        ->and($participantIds)->toContain($this->otherUser->id);
});

it('can send a message in a conversation', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    $message = app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->user->id,
        body: 'Hello via DM!',
    );

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->messageable_type)->toBe(Conversation::class)
        ->and($message->messageable_id)->toBe($conversation->id)
        ->and($message->body)->toBe('Hello via DM!');
});

it('can access messages through conversation relationship', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->user->id,
        body: 'First DM',
    );

    app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->otherUser->id,
        body: 'Reply DM',
    );

    expect($conversation->messages)->toHaveCount(2);
});

it('can access conversations through user relationship', function () {
    $this->user->findOrCreateDirectMessage($this->otherUser->id);

    expect($this->user->conversations)->toHaveCount(1);
});

it('displays other user name for 1-on-1 DMs', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    $displayName = $conversation->getDisplayNameForUser($this->user);

    expect($displayName)->toBe($this->otherUser->name);
});

it('displays group name for group DMs', function () {
    $conversation = $this->user->createGroupConversation(
        userIds: [$this->otherUser->id],
        name: 'Cool Group',
    );

    $displayName = $conversation->getDisplayNameForUser($this->user);

    expect($displayName)->toBe('Cool Group');
});

it('does not duplicate participants in group conversation', function () {
    $conversation = $this->user->createGroupConversation(
        userIds: [$this->otherUser->id, $this->user->id],
    );

    expect($conversation->participants)->toHaveCount(2);
});
