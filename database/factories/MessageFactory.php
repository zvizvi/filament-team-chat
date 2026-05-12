<?php

namespace Filament\TeamChat\Database\Factories;

use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $body = $this->faker->sentence();

        return [
            'messageable_type' => Channel::class,
            'messageable_id' => 1,
            'user_id' => 1,
            'body' => $body,
            'body_html' => Str::markdown($body),
        ];
    }
}
