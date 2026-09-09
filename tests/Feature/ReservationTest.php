<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();

        $supplier = Supplier::factory()->create();
        $property = Property::factory()->create();

        $this->offer = Offer::factory()->create([
            'supplier_id' => $supplier->id,
            'property_id' => $property->id,
            'price' => 100.00,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => now()->addDays(3),
        ]);
    }

    public function test_successful_reservation_decrements_available_units(): void
    {
        $payload = [
            'client_reference' => 'ref-book-001',
            'customer_name' => 'Alice Walker',
            'customer_email' => 'alice@example.com',
        ];

        $response = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.client_reference', 'ref-book-001')
            ->assertJsonPath('data.customer_name', 'Alice Walker')
            ->assertJsonPath('data.offer.remaining_units', 1);

        $this->assertDatabaseHas('reservations', [
            'offer_id' => $this->offer->id,
            'client_reference' => 'ref-book-001',
        ]);

        $this->offer->refresh();
        $this->assertEquals(1, $this->offer->available_units);
    }

    public function test_cannot_reserve_when_units_are_zero(): void
    {
        $this->offer->update(['available_units' => 0]);

        $payload = [
            'client_reference' => 'ref-book-zero',
            'customer_name' => 'Bob Builder',
            'customer_email' => 'bob@example.com',
        ];

        $response = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);

        $response->assertStatus(409);

        $this->assertDatabaseMissing('reservations', [
            'client_reference' => 'ref-book-zero',
        ]);
    }

    public function test_cannot_reserve_expired_offer(): void
    {
        $this->offer->update(['expires_at' => now()->subMinute()]);

        $payload = [
            'client_reference' => 'ref-book-exp',
            'customer_name' => 'Charlie Chaplin',
            'customer_email' => 'charlie@example.com',
        ];

        $response = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('reservations', [
            'client_reference' => 'ref-book-exp',
        ]);
    }

    public function test_duplicate_client_reference_rejected(): void
    {
        $payload = [
            'client_reference' => 'ref-book-dup',
            'customer_name' => 'Dave Miller',
            'customer_email' => 'dave@example.com',
        ];

        // First succeeds
        $first = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);
        $first->assertStatus(201);

        // Second with duplicate client_reference fails validation
        $second = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);
        $second->assertStatus(422)
            ->assertJsonValidationErrors(['client_reference']);
    }

    public function test_last_unit_exhaustion_prevents_overbooking(): void
    {
        // Offer with only 1 unit
        $this->offer->update(['available_units' => 1]);

        // First user books the only unit
        $first = $this->postJson("/api/offers/{$this->offer->id}/reservations", [
            'client_reference' => 'ref-first',
            'customer_name' => 'First Booker',
            'customer_email' => 'first@example.com',
        ]);
        $first->assertStatus(201)
            ->assertJsonPath('data.offer.remaining_units', 0);

        // Second user attempts booking immediately after
        $second = $this->postJson("/api/offers/{$this->offer->id}/reservations", [
            'client_reference' => 'ref-second',
            'customer_name' => 'Second Booker',
            'customer_email' => 'second@example.com',
        ]);
        $second->assertStatus(409);

        // Database must have exactly 1 reservation and units at 0
        $this->assertDatabaseCount('reservations', 1);
        $this->offer->refresh();
        $this->assertEquals(0, $this->offer->available_units);
    }
}
