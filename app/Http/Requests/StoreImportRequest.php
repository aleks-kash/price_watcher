<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for ingesting an asynchronous batch of supplier offers.
 *
 * @property-read string $supplier_code Unique supplier code (must exist in suppliers table)
 * @property-read string $external_import_id Unique import batch reference from external supplier
 * @property-read string $sent_at Timestamp when the supplier sent the payload
 * @property-read array<int, array{
 *     external_id: string,
 *     property_code: string,
 *     property_name: string,
 *     property_city: string,
 *     check_in: string,
 *     check_out: string,
 *     max_guests: int|string,
 *     price: float|string,
 *     currency: string,
 *     available_units: int|string,
 *     expires_at: string
 * }> $offers Array of property offers to import
 */
class StoreImportRequest extends FormRequest
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
            'supplier_code' => ['required', 'string', 'exists:suppliers,code'],
            'external_import_id' => ['required', 'string', 'max:255'],
            'sent_at' => ['required', 'date'],
            'offers' => ['required', 'array', 'min:1'],
            'offers.*.external_id' => ['required', 'string', 'max:255'],
            'offers.*.property_code' => ['required', 'string', 'max:255'],
            'offers.*.property_name' => ['required', 'string', 'max:255'],
            'offers.*.property_city' => ['required', 'string', 'max:255'],
            'offers.*.check_in' => ['required', 'date_format:Y-m-d'],
            'offers.*.check_out' => ['required', 'date_format:Y-m-d', 'after_or_equal:offers.*.check_in'],
            'offers.*.max_guests' => ['required', 'integer', 'min:1'],
            'offers.*.price' => ['required', 'numeric', 'min:0'],
            'offers.*.currency' => ['required', 'string', 'size:3'],
            'offers.*.available_units' => ['required', 'integer', 'min:0'],
            'offers.*.expires_at' => ['required', 'date'],
        ];
    }
}
