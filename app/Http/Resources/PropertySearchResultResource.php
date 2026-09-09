<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertySearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,
            'best_offer' => [
                'id' => $this->offer_id,
                'external_id' => $this->offer_external_id,
                'supplier' => [
                    'code' => $this->supplier_code,
                    'name' => $this->supplier_name,
                ],
                'price' => (float) $this->best_price,
                'currency' => $this->best_currency,
                'available_units' => (int) $this->best_available_units,
                'check_in' => $this->best_check_in,
                'check_out' => $this->best_check_out,
                'max_guests' => (int) $this->best_max_guests,
                'expires_at' => Carbon::parse($this->best_expires_at)->toIso8601String(),
            ],
        ];
    }
}
