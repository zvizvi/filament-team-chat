<?php

namespace Filament\TeamChat\Models;

use Filament\TeamChat\Concerns\BelongsToTeam;
use Filament\TeamChat\Concerns\HasReadReceipts;
use Filament\TeamChat\Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use BelongsToTeam, HasFactory, HasReadReceipts, SoftDeletes;

    protected static string $factory = ChannelFactory::class;

    protected $table = 'tc_channels';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'topic',
        'type',
        'created_by',
        'archived_at',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('team-chat.user_model'), 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(config('team-chat.user_model'), 'tc_channel_user')
            ->withPivot(['role', 'is_muted', 'joined_at'])
            ->withTimestamps();
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable');
    }

    public function isPublic(): bool
    {
        return $this->type === 'public';
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Build a unique, unicode-safe slug from a channel name.
     *
     * Str::slug() drops non-ASCII characters (e.g. Hebrew) to an empty string,
     * which then collides on the unique slug column. We keep unicode letters and
     * guarantee uniqueness across all teams and soft-deleted rows.
     */
    public static function generateUniqueSlug(string $name, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($name, language: null) ?: 'channel';
        $slug = $base;
        $suffix = 1;

        while (
            static::withoutGlobalScopes()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }
}
