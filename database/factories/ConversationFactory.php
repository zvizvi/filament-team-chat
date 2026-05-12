<?php

namespace Filament\TeamChat\Database\Factories;

use Filament\TeamChat\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'is_group' => false,
            'name' => null,
        ];
    }

    public function group(?string $name = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_group' => true,
            'name' => $name ?? $this->faker->words(2, true),
        ]);
    }
}
