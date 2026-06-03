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

it('generates a slug from a channel name', function () {
    expect(Channel::generateUniqueSlug('My Channel'))->toBe('my-channel');
});

it('falls back to a default slug when the name has nothing sluggable', function () {
    expect(Channel::generateUniqueSlug('🎉'))->toBe('channel');
});

it('generates distinct slugs for channels with the same name', function () {
    Channel::create([
        'name' => 'General',
        'slug' => Channel::generateUniqueSlug('General'),
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    expect(Channel::generateUniqueSlug('General'))->toBe('general-2');
});

it('ignores the given channel id when regenerating its own slug', function () {
    $channel = Channel::create([
        'name' => 'General',
        'slug' => Channel::generateUniqueSlug('General'),
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    expect(Channel::generateUniqueSlug('General', $channel->id))->toBe('general');
});
