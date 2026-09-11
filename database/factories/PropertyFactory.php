<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Property model instances with test data.
 *
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Property>
     */
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PROP-'.strtoupper(fake()->unique()->bothify('??###')),
            'name' => fake()->company().' Hotel',
            'city' => fake()->city(),
        ];
    }
}
