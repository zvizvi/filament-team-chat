<?php

use Filament\TeamChat\Actions\SearchMessages;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
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

it('finds messages by keyword', function () {
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'Hello world');
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'Goodbye world');
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'Something else');

    $results = app(SearchMessages::class)->execute($this->user->id, 'world');

    expect($results)->toHaveCount(2);
});

it('returns empty for blank query', function () {
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'Hello');

    $results = app(SearchMessages::class)->execute($this->user->id, '');

    expect($results)->toBeEmpty();
});

it('returns empty for whitespace-only query', function () {
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'Hello');

    $results = app(SearchMessages::class)->execute($this->user->id, '   ');

    expect($results)->toBeEmpty();
});

it('only searches channels the user belongs to', function () {
    $privateChannel = Channel::create([
        'name' => 'secret',
        'slug' => 'secret',
        'type' => 'private',
        'created_by' => $this->otherUser->id,
    ]);
    $privateChannel->members()->attach($this->otherUser->id, ['role' => 'owner']);

    app(SendMessage::class)->execute($this->channel, $this->user->id, 'public secret info');
    app(SendMessage::class)->execute($privateChannel, $this->otherUser->id, 'private secret info');

    $results = app(SearchMessages::class)->execute($this->user->id, 'secret');

    expect($results)->toHaveCount(1)
        ->and($results->first()->body)->toBe('public secret info');
});

it('searches DM conversations', function () {
    $conversation = $this->user->findOrCreateDirectMessage($this->otherUser->id);
    app(SendMessage::class)->execute($conversation, $this->user->id, 'DM secret message');

    $results = app(SearchMessages::class)->execute($this->user->id, 'secret');

    expect($results)->toHaveCount(1)
        ->and($results->first()->body)->toBe('DM secret message');
});

it('does not search DMs the user is not part of', function () {
    $thirdUser = User::factory()->create();
    $otherConversation = $this->otherUser->findOrCreateDirectMessage($thirdUser->id);
    app(SendMessage::class)->execute($otherConversation, $this->otherUser->id, 'private DM secret');

    $results = app(SearchMessages::class)->execute($this->user->id, 'secret');

    expect($results)->toBeEmpty();
});

it('returns results ordered by latest first', function () {
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'first match');
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'other message');
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'second match');

    $results = app(SearchMessages::class)->execute($this->user->id, 'match');

    expect($results)->toHaveCount(2)
        ->and($results->first()->body)->toBe('second match')
        ->and($results->last()->body)->toBe('first match');
});

it('respects the limit parameter', function () {
    for ($i = 0; $i < 5; $i++) {
        app(SendMessage::class)->execute($this->channel, $this->user->id, "message $i");
    }

    $results = app(SearchMessages::class)->execute($this->user->id, 'message', limit: 3);

    expect($results)->toHaveCount(3);
});

it('includes user and messageable in results', function () {
    app(SendMessage::class)->execute($this->channel, $this->user->id, 'test message');

    $results = app(SearchMessages::class)->execute($this->user->id, 'test');

    expect($results->first()->user)->not->toBeNull()
        ->and($results->first()->messageable)->not->toBeNull();
});
