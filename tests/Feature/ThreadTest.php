<?php

use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Message;
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

    $this->parentMessage = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Parent message',
    );
});

it('can create a thread reply', function () {
    $reply = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'Thread reply',
        parentId: $this->parentMessage->id,
    );

    expect($reply->parent_id)->toBe($this->parentMessage->id)
        ->and($reply->messageable_type)->toBe(Channel::class)
        ->and($reply->messageable_id)->toBe($this->channel->id);
});

it('can retrieve replies through parent relationship', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'Reply 1',
        parentId: $this->parentMessage->id,
    );

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Reply 2',
        parentId: $this->parentMessage->id,
    );

    expect($this->parentMessage->replies)->toHaveCount(2)
        ->and($this->parentMessage->replies->first()->body)->toBe('Reply 1')
        ->and($this->parentMessage->replies->last()->body)->toBe('Reply 2');
});

it('can navigate from reply to parent', function () {
    $reply = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'A reply',
        parentId: $this->parentMessage->id,
    );

    expect($reply->parent->id)->toBe($this->parentMessage->id)
        ->and($reply->parent->body)->toBe('Parent message');
});

it('excludes thread replies from main message feed', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'Thread reply',
        parentId: $this->parentMessage->id,
    );

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Top-level message',
    );

    $topLevelMessages = Message::where('messageable_type', Channel::class)
        ->where('messageable_id', $this->channel->id)
        ->whereNull('parent_id')
        ->get();

    expect($topLevelMessages)->toHaveCount(2)
        ->and($topLevelMessages->pluck('body')->all())->toBe(['Parent message', 'Top-level message']);
});

it('counts replies via withCount', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'Reply 1',
        parentId: $this->parentMessage->id,
    );

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Reply 2',
        parentId: $this->parentMessage->id,
    );

    $message = Message::withCount('replies')->find($this->parentMessage->id);

    expect($message->replies_count)->toBe(2);
});

it('identifies thread parent messages', function () {
    expect($this->parentMessage->isThreadParent())->toBeFalse();

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'Reply',
        parentId: $this->parentMessage->id,
    );

    expect($this->parentMessage->fresh()->isThreadParent())->toBeTrue();
});

it('threads work in conversations too', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);

    $dmParent = app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->user->id,
        body: 'DM parent',
    );

    $dmReply = app(SendMessage::class)->execute(
        messageable: $conversation,
        userId: $this->otherUser->id,
        body: 'DM thread reply',
        parentId: $dmParent->id,
    );

    expect($dmReply->parent_id)->toBe($dmParent->id)
        ->and($dmParent->replies)->toHaveCount(1);
});
