<?php

use App\Models\User;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Actions\ToggleReaction;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Reaction;
use Illuminate\Database\QueryException;

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

    $this->message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'React to me!',
    );
});

it('can add a reaction to a message', function () {
    $added = app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');

    expect($added)->toBeTrue()
        ->and(Reaction::count())->toBe(1)
        ->and($this->message->reactions)->toHaveCount(1)
        ->and($this->message->reactions->first()->emoji)->toBe('👍');
});

it('can remove a reaction by toggling', function () {
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');
    $removed = app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');

    expect($removed)->toBeFalse()
        ->and(Reaction::count())->toBe(0);
});

it('allows multiple users to react with the same emoji', function () {
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '❤️');
    app(ToggleReaction::class)->execute($this->message->id, $this->otherUser->id, '❤️');

    expect($this->message->fresh()->reactions)->toHaveCount(2);
});

it('allows a user to add multiple different reactions', function () {
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '❤️');
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '🚀');

    expect($this->message->fresh()->reactions)->toHaveCount(3);
});

it('prevents duplicate reactions via unique constraint', function () {
    Reaction::create([
        'message_id' => $this->message->id,
        'user_id' => $this->user->id,
        'emoji' => '👍',
    ]);

    Reaction::create([
        'message_id' => $this->message->id,
        'user_id' => $this->user->id,
        'emoji' => '👍',
    ]);
})->throws(QueryException::class);

it('can access reaction user', function () {
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');

    $reaction = Reaction::first();

    expect($reaction->user->id)->toBe($this->user->id)
        ->and($reaction->message->id)->toBe($this->message->id);
});

it('cascades delete when message is deleted', function () {
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');
    app(ToggleReaction::class)->execute($this->message->id, $this->otherUser->id, '❤️');

    expect(Reaction::count())->toBe(2);

    $this->message->forceDelete();

    expect(Reaction::count())->toBe(0);
});

it('can group reactions by emoji', function () {
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '👍');
    app(ToggleReaction::class)->execute($this->message->id, $this->otherUser->id, '👍');
    app(ToggleReaction::class)->execute($this->message->id, $this->user->id, '❤️');

    $grouped = $this->message->fresh()->reactions->groupBy('emoji');

    expect($grouped)->toHaveCount(2)
        ->and($grouped['👍'])->toHaveCount(2)
        ->and($grouped['❤️'])->toHaveCount(1);
});
