<?php

namespace App\Http\Resources;

use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
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
