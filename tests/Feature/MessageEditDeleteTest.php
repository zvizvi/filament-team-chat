<?php

use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Message;
use Filament\TeamChat\Tests\Fixtures\User;
use Illuminate\Support\Str;

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

it('can edit own message', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Original text',
    );

    $message->update([
        'body' => 'Edited text',
        'body_html' => Str::markdown('Edited text'),
        'edited_at' => now(),
    ]);

    $fresh = $message->fresh();
    expect($fresh->body)->toBe('Edited text')
        ->and($fresh->isEdited())->toBeTrue()
        ->and($fresh->body_html)->toContain('Edited text');
});

it('can soft delete own message', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'To delete',
    );

    $message->delete();

    expect(Message::find($message->id))->toBeNull()
        ->and(Message::withTrashed()->find($message->id))->not->toBeNull();
});

it('preserves thread replies when parent is soft deleted', function () {
    $parent = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Parent',
    );

    $reply = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->otherUser->id,
        body: 'Reply',
        parentId: $parent->id,
    );

    $parent->delete();

    expect(Message::find($reply->id))->not->toBeNull()
        ->and($reply->fresh()->parent_id)->toBe($parent->id);
});

it('updates edited_at timestamp on edit', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Original',
    );

    expect($message->edited_at)->toBeNull();

    $message->update([
        'body' => 'Edited',
        'body_html' => Str::markdown('Edited'),
        'edited_at' => now(),
    ]);

    expect($message->fresh()->edited_at)->not->toBeNull();
});
