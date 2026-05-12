<?php

namespace Filament\TeamChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatus extends Model
{
    protected $table = 'tc_user_statuses';

    protected $fillable = [
        'user_id',
        'display_name',
        'avatar_url',
        'status_text',
        'status_emoji',
        'is_online',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('team-chat.user_model'));
    }

    public function getDisplayName(): string
    {
        return $this->display_name ?? $this->user->name;
    }

    public function getStatusDisplay(): ?string
    {
        if (! $this->status_text && ! $this->status_emoji) {
            return null;
        }

        return trim(($this->status_emoji ?? '').' '.($this->status_text ?? ''));
    }

    public function markOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now(),
        ]);
    }
}
