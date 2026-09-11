<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Asynchronous job to process an imported batch of supplier offers.
 *
 * Handles background ingestion of property offers:
 * - Marks the import batch as processing.
 * - Executes in a database transaction for consistency.
 * - Upserts properties by code and updates their metadata.
 * - Upserts supplier offers by composite key (supplier_id, external_id).
 * - Updates import metrics, status (completed/failed), and completion timestamp.
 */
class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the queued job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @param  int  $importId  ID of the Import model being processed
     * @param  array<int, array{
     *     external_id: string,
     *     property_code: string,
     *     property_name: string,
     *     property_city: string,
     *     check_in: string,
     *     check_out: string,
     *     max_guests: int|string,
     *     price: float|string,
     *     currency: string,
     *     available_units: int|string,
     *     expires_at: string
     * }>  $offers  List of raw offer payloads to ingest
     */
    public function __construct(
        public int $importId,
        public array $offers
    ) {}

    /**
     * Execute the job: process property and offer upserts inside a database transaction.
     *
     * @throws \Throwable If an error occurs during processing
     */
    public function handle(): void
    {
        $import = Import::find($this->importId);
        if (! $import) {
            Log::error("ProcessImportJob: Import #{$this->importId} not found.");

            return;
        }

        $import->update([
            'status' => Import::STATUS_PROCESSING,
        ]);

        try {
            DB::transaction(function () use ($import) {
                $processedCount = 0;

                foreach ($this->offers as $offerData) {
                    $property = Property::firstOrCreate(
                        ['code' => $offerData['property_code']],
                        [
                            'name' => $offerData['property_name'],
                            'city' => $offerData['property_city'],
                        ]
                    );

                    // Update property details if city or name changed
                    if ($property->name !== $offerData['property_name'] || $property->city !== $offerData['property_city']) {
                        $property->update([
                            'name' => $offerData['property_name'],
                            'city' => $offerData['property_city'],
                        ]);
                    }

                    Offer::updateOrCreate(
                        [
                            'supplier_id' => $import->supplier_id,
                            'external_id' => $offerData['external_id'],
                        ],
                        [
                            'property_id' => $property->id,
                            'import_id' => $import->id,
                            'check_in' => $offerData['check_in'],
                            'check_out' => $offerData['check_out'],
                            'max_guests' => (int) $offerData['max_guests'],
                            'price' => $offerData['price'],
                            'currency' => strtoupper($offerData['currency']),
                            'available_units' => (int) $offerData['available_units'],
                            'expires_at' => Carbon::parse($offerData['expires_at']),
                        ]
                    );

                    $processedCount++;
                }

                $import->update([
                    'status' => Import::STATUS_COMPLETED,
                    'processed_offers' => $processedCount,
                    'completed_at' => now(),
                    'error' => null,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error("ProcessImportJob failed for Import #{$this->importId}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $import->update([
                'status' => Import::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
