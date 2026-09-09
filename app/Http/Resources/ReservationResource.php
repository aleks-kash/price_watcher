<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
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
            'offer_id' => $this->offer_id,
            'client_reference' => $this->client_reference,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'created_at' => $this->created_at?->toIso8601String(),
            'offer' => [
                'external_id' => $this->offer?->external_id,
                'price' => (float) $this->offer?->price,
                'currency' => $this->offer?->currency,
                'check_in' => $this->offer?->check_in?->toDateString(),
                'check_out' => $this->offer?->check_out?->toDateString(),
                'remaining_units' => (int) $this->offer?->available_units,
            ],
        ];
    }
}
