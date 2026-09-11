<?php

namespace App\Http\Resources;

use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for serializing an import batch with computed progress metrics.
 *
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array{
     *     id: int,
     *     supplier_code: string|null,
     *     external_import_id: string,
     *     sent_at: string|null,
     *     status: string,
     *     total_offers: int,
     *     processed_offers: int,
     *     progress_percentage: float,
     *     error: string|null,
     *     completed_at: string|null,
     *     created_at: string|null,
     *     updated_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $percentage = $this->total_offers > 0
            ? round(($this->processed_offers / $this->total_offers) * 100, 2)
            : 0;

        return [
            'id' => $this->id,
            'supplier_code' => $this->supplier?->code,
            'external_import_id' => $this->external_import_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'status' => $this->status,
            'total_offers' => $this->total_offers,
            'processed_offers' => $this->processed_offers,
            'progress_percentage' => $percentage,
            'error' => $this->error,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
