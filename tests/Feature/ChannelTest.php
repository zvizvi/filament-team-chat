<?php

use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Tests\Fixtures\User;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a channel', function () {
    $channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    expect($channel)->toBeInstanceOf(Channel::class)
        ->and($channel->name)->toBe('general')
        ->and($channel->slug)->toBe('general')
        ->and($channel->isPublic())->toBeTrue();
});

it('can create a private channel', function () {
    $channel = Channel::create([
        'name' => 'secret',
        'slug' => 'secret',
        'type' => 'private',
        'created_by' => $this->user->id,
    ]);

    expect($channel->isPrivate())->toBeTrue();
});

it('can add members to a channel', function () {
    $channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $channel->members()->attach($this->user->id, ['role' => 'owner']);

    $otherUser = User::factory()->create();
    $channel->members()->attach($otherUser->id, ['role' => 'member']);

    expect($channel->members)->toHaveCount(2);
    expect($channel->members->first()->pivot->role)->toBe('owner');
});

it('can access channels through user relationship', function () {
    $channel = Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $channel->members()->attach($this->user->id, ['role' => 'owner']);

    expect($this->user->channels)->toHaveCount(1)
        ->and($this->user->channels->first()->name)->toBe('general');
});

it('can archive a channel', function () {
    $channel = Channel::create([
        'name' => 'old-channel',
        'slug' => 'old-channel',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    expect($channel->isArchived())->toBeFalse();

    $channel->update(['archived_at' => now()]);

    expect($channel->fresh()->isArchived())->toBeTrue();
});

it('enforces unique slugs', function () {
    Channel::create([
        'name' => 'general',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    Channel::create([
        'name' => 'General',
        'slug' => 'general',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);
})->throws(QueryException::class);
