<?php

use App\Models\User;
use Filament\TeamChat\Models\Channel;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->actingAs($this->user);
});

it('can join a public channel', function () {
    $channel = Channel::create([
        'name' => 'public-channel',
        'slug' => 'public-channel',
        'type' => 'public',
        'created_by' => $this->otherUser->id,
    ]);

    $channel->members()->syncWithoutDetaching([$this->user->id => ['role' => 'member']]);

    expect($this->user->channels)->toHaveCount(1);
});

it('can leave a channel', function () {
    $channel = Channel::create([
        'name' => 'test-channel',
        'slug' => 'test-channel',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $channel->members()->attach($this->user->id, ['role' => 'member']);
    expect($this->user->fresh()->channels)->toHaveCount(1);

    $this->user->channels()->detach($channel->id);
    expect($this->user->fresh()->channels)->toHaveCount(0);
});

it('can archive a channel as owner', function () {
    $channel = Channel::create([
        'name' => 'to-archive',
        'slug' => 'to-archive',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    $channel->members()->attach($this->user->id, ['role' => 'owner']);

    $channel->update(['archived_at' => now()]);

    expect($channel->fresh()->isArchived())->toBeTrue();
});

it('hides archived channels from active list', function () {
    $active = Channel::create([
        'name' => 'active',
        'slug' => 'active',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);
    $active->members()->attach($this->user->id, ['role' => 'owner']);

    $archived = Channel::create([
        'name' => 'archived',
        'slug' => 'archived',
        'type' => 'public',
        'created_by' => $this->user->id,
        'archived_at' => now(),
    ]);
    $archived->members()->attach($this->user->id, ['role' => 'member']);

    $activeChannels = $this->user->channels()->whereNull('archived_at')->get();

    expect($activeChannels)->toHaveCount(1)
        ->and($activeChannels->first()->name)->toBe('active');
});

it('excludes archived channels from browsable list', function () {
    Channel::create([
        'name' => 'open',
        'slug' => 'open',
        'type' => 'public',
        'created_by' => $this->otherUser->id,
    ]);

    Channel::create([
        'name' => 'closed',
        'slug' => 'closed',
        'type' => 'public',
        'created_by' => $this->otherUser->id,
        'archived_at' => now(),
    ]);

    $browsable = Channel::where('type', 'public')
        ->whereNull('archived_at')
        ->whereNotIn('id', $this->user->channels()->pluck('tc_channels.id'))
        ->get();

    expect($browsable)->toHaveCount(1)
        ->and($browsable->first()->name)->toBe('open');
});

it('does not allow joining archived channels', function () {
    $archived = Channel::create([
        'name' => 'archived',
        'slug' => 'archived',
        'type' => 'public',
        'created_by' => $this->otherUser->id,
        'archived_at' => now(),
    ]);

    // Simulating the guard in joinChannel
    $channel = Channel::where('type', 'public')
        ->whereNull('archived_at')
        ->find($archived->id);

    expect($channel)->toBeNull();
});
