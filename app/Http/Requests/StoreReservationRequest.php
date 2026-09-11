<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for creating a customer reservation on an offer.
 *
 * @property-read string $client_reference Unique client reservation reference for idempotency
 * @property-read string $customer_name Full name of the customer booking the reservation
 * @property-read string $customer_email Email address of the customer
 */
class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'client_reference' => ['required', 'string', 'max:255', 'unique:reservations,client_reference'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
        ];
    }
}
