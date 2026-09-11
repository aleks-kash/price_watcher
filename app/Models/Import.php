<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Asynchronous import batch for external supplier offers.
 *
 * @property int $id Unique import batch identifier
 * @property int $supplier_id Foreign key referencing the supplier
 * @property string $external_import_id Unique batch identifier from external supplier
 * @property \Illuminate\Support\Carbon $sent_at Timestamp when the supplier sent the payload
 * @property string $status Processing status ('pending', 'processing', 'completed', 'failed')
 * @property int $total_offers Total number of offers in the import payload
 * @property int $processed_offers Number of offers successfully processed
 * @property string|null $error Error message if processing failed
 * @property \Illuminate\Support\Carbon|null $completed_at Timestamp when processing completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Supplier $supplier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Offer> $offers
 * @property-read int|null $offers_count
 */
class Import extends Model
{
    use HasFactory;

    /** Import has been received and queued for processing */
    public const STATUS_PENDING = 'pending';

    /** Import is currently being processed by a queue worker */
    public const STATUS_PROCESSING = 'processing';

    /** Import completed successfully */
    public const STATUS_COMPLETED = 'completed';

    /** Import failed due to an error during processing */
    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'external_import_id',
        'sent_at',
        'status',
        'total_offers',
        'processed_offers',
        'error',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_offers' => 'integer',
            'processed_offers' => 'integer',
        ];
    }

    /**
     * Get the supplier that initiated this import batch.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get all offers imported within this batch.
     *
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
