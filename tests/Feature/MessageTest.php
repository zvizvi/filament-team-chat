<?php

use App\Models\User;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Message;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $this->channel->members()->attach($this->user->id, ['role' => 'owner']);
});

it('can send a message to a channel', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hello, world!',
    );

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->body)->toBe('Hello, world!')
        ->and($message->body_html)->toContain('Hello, world!')
        ->and($message->user_id)->toBe($this->user->id)
        ->and($message->messageable_type)->toBe(Channel::class)
        ->and($message->messageable_id)->toBe($this->channel->id);
});

it('converts markdown to html', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: '**bold** and *italic*',
    );

    expect($message->body_html)->toContain('<strong>bold</strong>')
        ->and($message->body_html)->toContain('<em>italic</em>');
});

it('can access messages through channel relationship', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'First message',
    );

    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Second message',
    );

    expect($this->channel->messages)->toHaveCount(2);
});

it('can create a thread reply', function () {
    $parent = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Parent message',
    );

    $reply = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Reply message',
        parentId: $parent->id,
    );

    expect($reply->parent_id)->toBe($parent->id)
        ->and($parent->replies)->toHaveCount(1)
        ->and($parent->replies->first()->body)->toBe('Reply message');
});

it('tracks message edits', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Original message',
    );

    expect($message->isEdited())->toBeFalse();

    $message->update([
        'body' => 'Edited message',
        'body_html' => Str::markdown('Edited message'),
        'edited_at' => now(),
    ]);

    expect($message->fresh()->isEdited())->toBeTrue()
        ->and($message->fresh()->body)->toBe('Edited message');
});

it('can soft delete a message', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'To be deleted',
    );

    $message->delete();

    expect(Message::find($message->id))->toBeNull()
        ->and(Message::withTrashed()->find($message->id))->not->toBeNull();
});

it('can access user messages through relationship', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'My message',
    );

    expect($this->user->messages)->toHaveCount(1)
        ->and($this->user->messages->first()->body)->toBe('My message');
});
