<?php

use Filament\TeamChat\Models\UserStatus;
use Filament\TeamChat\Tests\Fixtures\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('creates a user status via getOrCreateStatus', function () {
    $status = $this->user->getOrCreateStatus();

    expect($status)->toBeInstanceOf(UserStatus::class)
        ->and($status->user_id)->toBe($this->user->id)
        ->and(UserStatus::count())->toBe(1);
});

it('returns existing status on subsequent calls', function () {
    $first = $this->user->getOrCreateStatus();
    $second = $this->user->fresh()->getOrCreateStatus();

    expect($first->id)->toBe($second->id)
        ->and(UserStatus::count())->toBe(1);
});

it('can mark user as online', function () {
    $this->user->touchOnline();

    $status = $this->user->fresh()->userStatus;
    expect($status->is_online)->toBeTrue()
        ->and($status->last_seen_at)->not->toBeNull();
});

it('can mark user as offline', function () {
    $this->user->touchOnline();
    $this->user->fresh()->userStatus->markOffline();

    $status = $this->user->fresh()->userStatus;
    expect($status->is_online)->toBeFalse()
        ->and($status->last_seen_at)->not->toBeNull();
});

it('can set display name', function () {
    $status = $this->user->getOrCreateStatus();
    $status->update(['display_name' => 'Cool Name']);

    expect($status->getDisplayName())->toBe('Cool Name');
});

it('falls back to user name when no display name set', function () {
    $status = $this->user->getOrCreateStatus();

    expect($status->getDisplayName())->toBe($this->user->name);
});

it('can set and display status text with emoji', function () {
    $status = $this->user->getOrCreateStatus();
    $status->update([
        'status_emoji' => '🏖️',
        'status_text' => '休暇中',
    ]);

    expect($status->getStatusDisplay())->toBe('🏖️ 休暇中');
});

it('returns null for empty status', function () {
    $status = $this->user->getOrCreateStatus();

    expect($status->getStatusDisplay())->toBeNull();
});

it('displays emoji only status', function () {
    $status = $this->user->getOrCreateStatus();
    $status->update(['status_emoji' => '🔴']);

    expect($status->getStatusDisplay())->toBe('🔴');
});

it('displays text only status', function () {
    $status = $this->user->getOrCreateStatus();
    $status->update(['status_text' => '会議中']);

    expect($status->getStatusDisplay())->toBe('会議中');
});

it('accesses user status through relationship', function () {
    $this->user->touchOnline();

    $fresh = User::with('userStatus')->find($this->user->id);
    expect($fresh->userStatus)->not->toBeNull()
        ->and($fresh->userStatus->is_online)->toBeTrue();
});
