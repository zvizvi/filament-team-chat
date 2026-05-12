<?php

use App\Models\User;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Resources\ChannelResource\Pages\CreateChannel;
use Filament\TeamChat\Resources\ChannelResource\Pages\EditChannel;
use Filament\TeamChat\Resources\ChannelResource\Pages\ListChannels;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can list channels', function () {
    $channels = collect();

    for ($i = 0; $i < 3; $i++) {
        $channels->push(Channel::create([
            'name' => "channel-$i",
            'slug' => "channel-$i",
            'type' => 'public',
            'created_by' => $this->user->id,
        ]));
    }

    livewire(ListChannels::class)
        ->assertCanSeeTableRecords($channels);
});

it('can create a channel via resource', function () {
    livewire(CreateChannel::class)
        ->fillForm([
            'name' => 'New Channel',
            'slug' => 'new-channel',
            'type' => 'public',
            'description' => 'A test channel',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(Channel::class, [
        'name' => 'New Channel',
        'slug' => 'new-channel',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);
});

it('adds creator as owner on create', function () {
    livewire(CreateChannel::class)
        ->fillForm([
            'name' => 'Owner Test',
            'slug' => 'owner-test',
            'type' => 'public',
        ])
        ->call('create')
        ->assertNotified();

    $channel = Channel::where('slug', 'owner-test')->first();

    expect($channel->members)->toHaveCount(1)
        ->and($channel->members->first()->pivot->role)->toBe('owner');
});

it('can edit a channel', function () {
    $channel = Channel::create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'type' => 'public',
        'created_by' => $this->user->id,
    ]);

    livewire(EditChannel::class, ['record' => $channel->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    assertDatabaseHas(Channel::class, [
        'id' => $channel->id,
        'name' => 'Updated Name',
    ]);
});

it('validates required fields on create', function () {
    livewire(CreateChannel::class)
        ->fillForm([
            'name' => null,
            'slug' => null,
            'type' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'slug' => 'required',
            'type' => 'required',
        ]);
});
