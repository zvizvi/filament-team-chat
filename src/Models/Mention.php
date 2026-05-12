<?php

namespace Filament\TeamChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mention extends Model
{
    protected $table = 'tc_mentions';

    protected $fillable = [
        'message_id',
        'user_id',
        'type',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('team-chat.user_model'));
    }
}
