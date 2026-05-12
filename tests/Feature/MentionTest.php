<?php

use App\Models\User;
use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Mention;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Alice']);
    $this->bob = User::factory()->create(['name' => 'Bob']);
    $this->actingAs($this->user);

    $this->channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);
});

it('parses @user mentions and creates mention records', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @Bob check this out',
    );

    expect(Mention::count())->toBe(1);

    $mention = Mention::first();
    expect($mention->type)->toBe('user')
        ->and($mention->user_id)->toBe($this->bob->id)
        ->and($mention->message_id)->toBe($message->id);
});

it('renders @user mentions as styled spans in body_html', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @Bob check this out',
    );

    expect($message->fresh()->body_html)->toContain('tc-mention--user')
        ->and($message->fresh()->body_html)->toContain('@Bob');
});

it('parses @channel mentions', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Attention @channel please review',
    );

    $mention = Mention::first();
    expect($mention->type)->toBe('channel')
        ->and($mention->user_id)->toBeNull()
        ->and($message->fresh()->body_html)->toContain('tc-mention--channel');
});

it('parses @here mentions', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @here quick update',
    );

    $mention = Mention::first();
    expect($mention->type)->toBe('here')
        ->and($mention->user_id)->toBeNull()
        ->and($message->fresh()->body_html)->toContain('tc-mention--here');
});

it('parses multiple mentions in one message', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: '@Alice and @Bob please review @channel',
    );

    expect(Mention::count())->toBe(3);
    expect(Mention::where('type', 'user')->count())->toBe(2);
    expect(Mention::where('type', 'channel')->count())->toBe(1);
});

it('does not create mentions for unknown usernames', function () {
    app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @NonexistentUser check this',
    );

    expect(Mention::count())->toBe(0);
});

it('preserves original body while updating body_html', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Hey @Bob check this',
    );

    expect($message->body)->toBe('Hey @Bob check this')
        ->and($message->fresh()->body_html)->toContain('tc-mention');
});

it('handles messages with no mentions', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: 'Just a normal message',
    );

    expect(Mention::count())->toBe(0)
        ->and($message->body_html)->not->toContain('tc-mention');
});

it('combines markdown and mentions', function () {
    $message = app(SendMessage::class)->execute(
        messageable: $this->channel,
        userId: $this->user->id,
        body: '**Important** @Bob please review',
    );

    $html = $message->fresh()->body_html;
    expect($html)->toContain('<strong>Important</strong>')
        ->and($html)->toContain('tc-mention--user');
});
