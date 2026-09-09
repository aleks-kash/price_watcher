<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends Controller
{
    /**
     * Create a reservation for an offer.
     *
     * Uses pessimistic row-level locking (SELECT ... FOR UPDATE) inside a database
     * transaction to ensure atomic unit decrement and prevent race conditions or
     * double-booking of remaining units.
     *
     * @response 201 {"data": {"id": 1, "client_reference": "ref-123", "customer_name": "John Doe"}}
     * @response 409 {"message": "No units are available for the selected offer."}
     * @response 422 {"message": "The selected offer has expired."}
     */
    public function store(StoreReservationRequest $request, Offer $offer): JsonResponse
    {
        $validated = $request->validated();

        $reservation = DB::transaction(function () use ($offer, $validated) {
            /** @var Offer $lockedOffer */
            $lockedOffer = Offer::where('id', $offer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOffer->expires_at->isPast()) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'The selected offer has expired.');
            }

            if ($lockedOffer->available_units <= 0) {
                abort(Response::HTTP_CONFLICT, 'No units are available for the selected offer.');
            }

            $reservation = Reservation::create([
                'offer_id' => $lockedOffer->id,
                'client_reference' => $validated['client_reference'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
            ]);

            $lockedOffer->decrement('available_units');

            $reservation->setRelation('offer', $lockedOffer->fresh());

            return $reservation;
        });

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
