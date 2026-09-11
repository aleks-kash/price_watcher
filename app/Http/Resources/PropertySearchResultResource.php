<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for serializing a property search result with its best (lowest price) offer.
 *
 * @property int $id Property ID
 * @property string $code Property code
 * @property string $name Property name
 * @property string $city Property city
 * @property int $offer_id Lowest price offer ID
 * @property string $offer_external_id External supplier offer ID
 * @property string $supplier_code Supplier code
 * @property string $supplier_name Supplier name
 * @property float|string $best_price Lowest offer price
 * @property string $best_currency Currency code (e.g. 'EUR')
 * @property int|string $best_available_units Number of available units
 * @property string $best_check_in Check-in date
 * @property string $best_check_out Check-out date
 * @property int|string $best_max_guests Maximum accommodated guests
 * @property string $best_expires_at Offer expiration timestamp
 */
class PropertySearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     city: string,
     *     best_offer: array{
     *         id: int,
     *         external_id: string,
     *         supplier: array{
     *             code: string,
     *             name: string
     *         },
     *         price: float,
     *         currency: string,
     *         available_units: int,
     *         check_in: string,
     *         check_out: string,
     *         max_guests: int,
     *         expires_at: string
     *     }
     * }
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
