<?php

namespace Filament\TeamChat\Tests\Fixtures;

use Filament\TeamChat\Concerns\HasTeamChat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasTeamChat, Notifiable;

    protected $guarded = [];

    protected $table = 'users';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isChatManager(): bool
    {
        return str_contains((string) $this->name, 'Manager');
    }
}
