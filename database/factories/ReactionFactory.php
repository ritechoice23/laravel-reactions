<?php

namespace Ritechoice23\Reactions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ritechoice23\Reactions\Models\Reaction;

class ReactionFactory extends Factory
{
    protected $model = Reaction::class;

    public function definition(): array
    {
        return [
            'reactor_type' => 'App\\Models\\User',
            'reactor_id' => 1,
            'reaction_type' => $this->faker->randomElement(['like', 'love', 'care', 'celebrate', 'insightful']),
            'reactable_type' => 'App\\Models\\Post',
            'reactable_id' => 1,
        ];
    }
}
