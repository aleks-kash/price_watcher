<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Room / accommodation offer provided by a supplier for a property.
 *
 * @property int $id Unique offer identifier
 * @property int $supplier_id Foreign key referencing the supplier
 * @property int $property_id Foreign key referencing the property
 * @property int $import_id Foreign key referencing the import batch
 * @property string $external_id Supplier's unique offer ID
 * @property \Illuminate\Support\Carbon $check_in Check-in date
 * @property \Illuminate\Support\Carbon $check_out Check-out date
 * @property int $max_guests Maximum number of guests accommodated
 * @property string $price Offer price formatted with 2 decimal places
 * @property string $currency Currency code in ISO-4217 format (default: 'EUR')
 * @property int $available_units Number of units remaining for booking
 * @property \Illuminate\Support\Carbon $expires_at Expiration timestamp of the offer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Supplier $supplier
 * @property-read \App\Models\Property $property
 * @property-read \App\Models\Import $import
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservation> $reservations
 * @property-read int|null $reservations_count
 */
class Offer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'property_id',
        'import_id',
        'external_id',
        'check_in',
        'check_out',
        'max_guests',
        'price',
        'currency',
        'available_units',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'expires_at' => 'datetime',
            'price' => 'decimal:2',
            'max_guests' => 'integer',
            'available_units' => 'integer',
        ];
    }

    /**
     * Get the supplier that provides this offer.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the property associated with this offer.
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the import batch that created or updated this offer.
     *
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Get all customer reservations made for this offer.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
