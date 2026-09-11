<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchPropertiesRequest;
use App\Http\Resources\PropertySearchResultResource;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controller for searching accommodation properties and finding cheapest available offers.
 *
 * Employs MySQL 8 SQL window functions (ROW_NUMBER) to offload deduplication and sorting
 * to the database layer.
 */
class PropertyController extends Controller
{
    /**
     * Search properties with their lowest priced valid offers.
     *
     * Performs database-level window function aggregation to find the cheapest
     * active offer per property matching exact dates, capacity, positive available units,
     * unexpired offers, and optional city filter.
     *
     * @param  SearchPropertiesRequest  $request  Validated query parameters
     * @return AnonymousResourceCollection Paginated collection of properties with best offers
     *
     * @response 200 {"data": [{"id": 1, "code": "hotel-kyiv-1", "name": "Hotel Kyiv", "city": "Kyiv", "best_offer": {"id": 2, "price": 95.0}}]}
     */
    public function index(SearchPropertiesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $checkIn = $validated['check_in'];
        $checkOut = $validated['check_out'];
        $guests = (int) ($validated['guests'] ?? 1);
        $city = $validated['city'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);

        // Subquery: rank offers per property by price ascending directly in the database
        $rankedOffers = Offer::query()
            ->select([
                'id as offer_id',
                'property_id',
                'supplier_id',
                'external_id as offer_external_id',
                'price',
                'currency',
                'available_units',
                'expires_at',
                'check_in',
                'check_out',
                'max_guests',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY property_id ORDER BY price ASC, id ASC) as rn'),
            ])
            ->where('max_guests', '>=', $guests)
            ->where('available_units', '>', 0)
            ->where('expires_at', '>', now());

        if (DB::connection()->getDriverName() === 'sqlite') {
            $rankedOffers->whereDate('check_in', $checkIn)
                ->whereDate('check_out', $checkOut);
        } else {
            $rankedOffers->where('check_in', $checkIn)
                ->where('check_out', $checkOut);
        }

        $query = Property::query()
            ->joinSub($rankedOffers, 'best_offers', function ($join) {
                $join->on('properties.id', '=', 'best_offers.property_id')
                    ->where('best_offers.rn', '=', 1);
            })
            ->join('suppliers', 'best_offers.supplier_id', '=', 'suppliers.id')
            ->select([
                'properties.id',
                'properties.code',
                'properties.name',
                'properties.city',
                'best_offers.offer_id',
                'best_offers.offer_external_id',
                'best_offers.price as best_price',
                'best_offers.currency as best_currency',
                'best_offers.available_units as best_available_units',
                'best_offers.expires_at as best_expires_at',
                'best_offers.check_in as best_check_in',
                'best_offers.check_out as best_check_out',
                'best_offers.max_guests as best_max_guests',
                'suppliers.code as supplier_code',
                'suppliers.name as supplier_name',
            ])
            ->when($city, function ($q, $city) {
                $q->where('properties.city', $city);
            })
            ->orderBy('best_offers.price', 'asc');

        $paginated = $query->paginate($perPage);

        return PropertySearchResultResource::collection($paginated);
    }
}
