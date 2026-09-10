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

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @param  array<int, array<string, mixed>>  $offers
     */
    public function __construct(
        public int $importId,
        public array $offers
    ) {}

    /**
     * Execute the job.
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
