<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for searching properties and discovering best offers.
 *
 * @property-read string|null $city Optional city filter for properties
 * @property-read string $check_in Check-in date in YYYY-MM-DD format
 * @property-read string $check_out Check-out date in YYYY-MM-DD format (after or equal to check_in)
 * @property-read int|null $guests Minimum required guest capacity (default: 1)
 * @property-read int|null $per_page Number of results per page (1-100, default: 15)
 */
class SearchPropertiesRequest extends FormRequest
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
            'city' => ['nullable', 'string', 'max:255'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after_or_equal:check_in'],
            'guests' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
