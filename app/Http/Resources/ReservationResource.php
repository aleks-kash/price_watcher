<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for serializing a booking reservation and its remaining offer capacity.
 *
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array{
     *     id: int,
     *     offer_id: int,
     *     client_reference: string,
     *     customer_name: string,
     *     customer_email: string,
     *     created_at: string|null,
     *     offer: array{
     *         external_id: string|null,
     *         price: float,
     *         currency: string|null,
     *         check_in: string|null,
     *         check_out: string|null,
     *         remaining_units: int
     *     }
     * }
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
