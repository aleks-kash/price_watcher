<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for property search and lowest-price offer aggregation.
 *
 * Covers:
 * - GET /api/properties (search filters, window function lowest price calculation)
 * - Date filtering, guest capacity filtering, city filtering
 * - Exclusion of sold-out and expired offers
 * - Query parameter validation
 */
class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test supplier instance used for seeding test offers.
     *
     * @var Supplier
     */
    private Supplier $supplier;

    /**
     * Test import batch instance linked to seeded offers.
     *
     * @var Import
     */
    private Import $import;

    /**
     * Set up the test environment with a test supplier and import record.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::factory()->create();
        $this->import = Import::factory()->create(['supplier_id' => $this->supplier->id]);
    }

    /**
     * Test that property search selects the lowest-priced offer among competing offers.
     */
    public function test_returns_cheapest_offer_for_property(): void
    {
        $property = Property::factory()->create([
            'city' => 'Kyiv',
        ]);

        // Expensive offer
        Offer::factory()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $property->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 2,
            'price' => 150.00,
            'currency' => 'EUR',
            'available_units' => 5,
            'expires_at' => now()->addDays(5),
        ]);

        // Cheaper offer
        $cheaperOffer = Offer::factory()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $property->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 2,
            'price' => 85.00,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05&guests=2');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $property->id)
            ->assertJsonPath('data.0.best_offer.id', $cheaperOffer->id)
            ->assertJsonPath('data.0.best_offer.price', 85);
    }

    /**
     * Test that only offers matching the exact check-in and check-out dates are returned.
     */
    public function test_filters_by_exact_dates(): void
    {
        $property = Property::factory()->create(['city' => 'Kyiv']);

        // Offer with different dates
        Offer::factory()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $property->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-02', // Different
            'check_out' => '2026-10-06',
            'max_guests' => 2,
            'price' => 100.00,
            'available_units' => 3,
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test that offers accommodating fewer guests than requested are filtered out.
     */
    public function test_filters_by_guests_capacity(): void
    {
        $property = Property::factory()->create(['city' => 'Kyiv']);

        // Offer only for 1 guest
        Offer::factory()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $property->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 1,
            'price' => 100.00,
            'available_units' => 3,
            'expires_at' => now()->addDays(5),
        ]);

        // Search for 3 guests
        $response = $this->getJson('/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05&guests=3');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test that offers with 0 available units are excluded from search results.
     */
    public function test_excludes_sold_out_offers(): void
    {
        $property = Property::factory()->create(['city' => 'Kyiv']);

        Offer::factory()->soldOut()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $property->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 2,
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05&guests=2');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test that offers with an expired timestamp are excluded from search results.
     */
    public function test_excludes_expired_offers(): void
    {
        $property = Property::factory()->create(['city' => 'Kyiv']);

        Offer::factory()->expired()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $property->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 2,
            'available_units' => 5,
        ]);

        $response = $this->getJson('/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05&guests=2');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    /**
     * Test that search results are filtered by property city.
     */
    public function test_filters_by_city(): void
    {
        $propertyKyiv = Property::factory()->create(['city' => 'Kyiv']);
        $propertyLviv = Property::factory()->create(['city' => 'Lviv']);

        Offer::factory()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $propertyKyiv->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 2,
            'available_units' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        Offer::factory()->create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $propertyLviv->id,
            'import_id' => $this->import->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-05',
            'max_guests' => 2,
            'available_units' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $propertyKyiv->id);
    }

    /**
     * Test that property search fails validation if check_in or check_out dates are missing.
     */
    public function test_search_validation_requires_dates(): void
    {
        $response = $this->getJson('/api/properties');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in', 'check_out']);
    }
}
