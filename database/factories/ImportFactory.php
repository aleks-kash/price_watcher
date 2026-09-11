<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Import model instances with test data.
 *
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Import>
     */
    protected $model = Import::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'external_import_id' => 'imp-'.fake()->unique()->uuid(),
            'sent_at' => now(),
            'status' => Import::STATUS_PENDING,
            'total_offers' => 0,
            'processed_offers' => 0,
            'error' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the import batch has completed successfully.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Import::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the import batch is actively being processed.
     *
     * @return static
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Import::STATUS_PROCESSING,
        ]);
    }

    /**
     * Indicate that the import batch has failed with an error.
     *
     * @param  string  $error  The failure reason message
     * @return static
     */
    public function failed(string $error = 'Something went wrong'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Import::STATUS_FAILED,
            'error' => $error,
        ]);
    }
}
