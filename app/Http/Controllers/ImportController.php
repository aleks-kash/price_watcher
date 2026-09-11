<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for handling external supplier offer imports and progress polling.
 *
 * Implements asynchronous background queuing with Redis and idempotency protection.
 */
class ImportController extends Controller
{
    /**
     * Submit an import payload.
     *
     * Asynchronously ingests supplier offers with idempotency protection.
     * If the import with the same supplier and external_import_id already exists,
     * the existing import status is returned without re-processing.
     *
     * @param  StoreImportRequest  $request  Validated import payload
     * @return JsonResponse HTTP 202 Accepted (new import) or HTTP 200 OK (idempotent replay)
     *
     * @response 202 {"data": {"id": 1, "status": "pending", "total_offers": 10}}
     * @response 200 {"data": {"id": 1, "status": "completed", "total_offers": 10}}
     */
    public function store(StoreImportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $supplier = Supplier::where('code', $validated['supplier_code'])->firstOrFail();

        // Idempotency check: supplier_id + external_import_id
        $existingImport = Import::where('supplier_id', $supplier->id)
            ->where('external_import_id', $validated['external_import_id'])
            ->first();

        if ($existingImport) {
            return (new ImportResource($existingImport))
                ->additional(['meta' => ['idempotent_replay' => true]])
                ->response()
                ->setStatusCode(Response::HTTP_OK);
        }

        $import = Import::create([
            'supplier_id' => $supplier->id,
            'external_import_id' => $validated['external_import_id'],
            'sent_at' => Carbon::parse($validated['sent_at']),
            'status' => Import::STATUS_PENDING,
            'total_offers' => count($validated['offers']),
            'processed_offers' => 0,
        ]);

        ProcessImportJob::dispatch($import->id, $validated['offers']);

        return (new ImportResource($import))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    /**
     * Get import status and progress metrics.
     *
     * Returns the current processing state, metrics, and completion timestamp.
     *
     * @param  Import  $import  The import model instance resolved via route model binding
     * @return ImportResource Serialized import metrics and progress
     */
    public function show(Import $import): ImportResource
    {
        $import->load('supplier');

        return new ImportResource($import);
    }
}
