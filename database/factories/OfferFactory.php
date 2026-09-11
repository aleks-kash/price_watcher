<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Offer model instances with test data.
 *
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Offer>
     */
    protected $model = Offer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'property_id' => Property::factory(),
            'import_id' => Import::factory(),
            'external_id' => 'off-'.fake()->unique()->uuid(),
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
            'max_guests' => fake()->numberBetween(1, 4),
            'price' => fake()->randomFloat(2, 50, 500),
            'currency' => 'EUR',
            'available_units' => fake()->numberBetween(1, 10),
            'expires_at' => now()->addDays(3),
        ];
    }

    /**
     * Indicate that the offer has already expired.
     *
     * @return static
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the offer is sold out (zero units remaining).
     *
     * @return static
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_units' => 0,
        ]);
    }
}
