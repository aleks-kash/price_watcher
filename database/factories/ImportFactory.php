<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

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

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Import::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Import::STATUS_PROCESSING,
        ]);
    }

    public function failed(string $error = 'Something went wrong'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Import::STATUS_FAILED,
            'error' => $error,
        ]);
    }
}
