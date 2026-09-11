<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for offer import ingestion, idempotency, queue dispatch, and status tracking.
 *
 * Covers:
 * - POST /api/imports (submission, schema validation, idempotency handling)
 * - ProcessImportJob (background queue execution, property and offer upserts)
 * - GET /api/imports/{id} (status and progress metrics)
 */
class ImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test supplier instance used across test cases.
     *
     * @var Supplier
     */
    private Supplier $supplier;

    /**
     * Set up the test environment and create a test supplier.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::factory()->create([
            'code' => 'supplier-test',
            'name' => 'Supplier Test',
        ]);
    }

    /**
     * Test that a valid import payload creates a pending import and pushes ProcessImportJob.
     */
    public function test_successful_import_creation_and_queue_dispatch(): void
    {
        Queue::fake();

        $payload = [
            'supplier_code' => $this->supplier->code,
            'external_import_id' => 'imp-abc-123',
            'sent_at' => '2026-09-09T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'off-1',
                    'property_code' => 'prop-1',
                    'property_name' => 'Property One',
                    'property_city' => 'Kyiv',
                    'check_in' => '2026-10-01',
                    'check_out' => '2026-10-05',
                    'max_guests' => 2,
                    'price' => 100.00,
                    'currency' => 'EUR',
                    'available_units' => 3,
                    'expires_at' => '2026-09-30T23:59:59Z',
                ],
            ],
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(202)
            ->assertJsonPath('data.supplier_code', $this->supplier->code)
            ->assertJsonPath('data.external_import_id', 'imp-abc-123')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_offers', 1);

        $this->assertDatabaseHas('imports', [
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'imp-abc-123',
            'status' => 'pending',
            'total_offers' => 1,
        ]);

        Queue::assertPushed(ProcessImportJob::class, function ($job) {
            return count($job->offers) === 1;
        });
    }

    /**
     * Test that re-submitting an existing import returns HTTP 200 with idempotent_replay flag.
     */
    public function test_idempotency_handling_of_duplicate_imports(): void
    {
        Queue::fake();

        $payload = [
            'supplier_code' => $this->supplier->code,
            'external_import_id' => 'imp-idem-1',
            'sent_at' => '2026-09-09T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'off-idem-1',
                    'property_code' => 'prop-idem',
                    'property_name' => 'Idem Hotel',
                    'property_city' => 'Lviv',
                    'check_in' => '2026-10-01',
                    'check_out' => '2026-10-05',
                    'max_guests' => 2,
                    'price' => 80.00,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-09-30T23:59:59Z',
                ],
            ],
        ];

        // First request: Accepted
        $firstResponse = $this->postJson('/api/imports', $payload);
        $firstResponse->assertStatus(202);

        // Second request with exact same supplier and external_import_id: Idempotent OK
        $secondResponse = $this->postJson('/api/imports', $payload);
        $secondResponse->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true)
            ->assertJsonPath('data.external_import_id', 'imp-idem-1');

        // Queue should have only 1 job, not 2
        Queue::assertPushed(ProcessImportJob::class, 1);
        $this->assertDatabaseCount('imports', 1);
    }

    /**
     * Test that executing ProcessImportJob creates properties and offers and completes the import.
     */
    public function test_import_job_execution_populates_properties_and_offers(): void
    {
        $import = Import::factory()->create([
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'imp-job-test',
            'status' => Import::STATUS_PENDING,
            'total_offers' => 2,
        ]);

        $offersData = [
            [
                'external_id' => 'off-job-1',
                'property_code' => 'prop-job-1',
                'property_name' => 'Grand Hotel',
                'property_city' => 'Kyiv',
                'check_in' => '2026-11-01',
                'check_out' => '2026-11-06',
                'max_guests' => 3,
                'price' => 150.00,
                'currency' => 'EUR',
                'available_units' => 4,
                'expires_at' => '2026-10-31T23:59:59Z',
            ],
            [
                'external_id' => 'off-job-2',
                'property_code' => 'prop-job-1',
                'property_name' => 'Grand Hotel',
                'property_city' => 'Kyiv',
                'check_in' => '2026-11-01',
                'check_out' => '2026-11-06',
                'max_guests' => 3,
                'price' => 135.00,
                'currency' => 'EUR',
                'available_units' => 2,
                'expires_at' => '2026-10-31T23:59:59Z',
            ],
        ];

        $job = new ProcessImportJob($import->id, $offersData);
        $job->handle();

        $import->refresh();
        $this->assertEquals(Import::STATUS_COMPLETED, $import->status);
        $this->assertEquals(2, $import->processed_offers);
        $this->assertNotNull($import->completed_at);

        $this->assertDatabaseHas('properties', [
            'code' => 'prop-job-1',
            'name' => 'Grand Hotel',
            'city' => 'Kyiv',
        ]);

        $this->assertDatabaseHas('offers', [
            'supplier_id' => $this->supplier->id,
            'external_id' => 'off-job-1',
            'price' => 150.00,
        ]);

        $this->assertDatabaseHas('offers', [
            'supplier_id' => $this->supplier->id,
            'external_id' => 'off-job-2',
            'price' => 135.00,
        ]);
    }

    /**
     * Test that the GET /api/imports/{id} endpoint returns metrics and current status.
     */
    public function test_import_show_endpoint_returns_metrics(): void
    {
        $import = Import::factory()->create([
            'supplier_id' => $this->supplier->id,
            'status' => Import::STATUS_COMPLETED,
            'total_offers' => 10,
            'processed_offers' => 10,
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_offers', 10)
            ->assertJsonPath('data.processed_offers', 10)
            ->assertJsonPath('data.progress_percentage', 100);
    }

    /**
     * Test that import submission fails validation if the supplier code does not exist.
     */
    public function test_import_validation_fails_for_unknown_supplier(): void
    {
        $payload = [
            'supplier_code' => 'non-existent-supplier',
            'external_import_id' => 'imp-fail',
            'sent_at' => '2026-09-09T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'off-1',
                    'property_code' => 'prop-1',
                    'property_name' => 'Prop',
                    'property_city' => 'Kyiv',
                    'check_in' => '2026-10-01',
                    'check_out' => '2026-10-05',
                    'max_guests' => 2,
                    'price' => 50,
                    'currency' => 'EUR',
                    'available_units' => 1,
                    'expires_at' => '2026-09-30T23:59:59Z',
                ],
            ],
        ];

        $response = $this->postJson('/api/imports', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['supplier_code']);
    }
}
