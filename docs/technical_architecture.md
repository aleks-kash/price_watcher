## 🛡️ Technical Architecture & Key Mechanisms

### 1. Idempotency Protection
- The `imports` table maintains a composite unique index on `(supplier_id, external_import_id)`.
- When an import request is received, `ImportController::store` checks for existing records matching the supplier and external import ID.
- If previously submitted, it returns the existing record immediately with HTTP 200 OK and `meta.idempotent_replay = true`, preventing duplicate queue jobs or double-processing.

### 2. Concurrency & Race Condition Prevention
- In high-load booking scenarios, multiple users may attempt to reserve the final remaining unit at the exact same millisecond.
- In `ReservationController::store`, operations are wrapped in `DB::transaction()`.
- A pessimistic row lock is acquired:
  ```php
  $lockedOffer = Offer::where('id', $offer->id)->lockForUpdate()->firstOrFail();
  ```
- Any subsequent concurrent transaction requesting the same offer will wait until the lock is released.
- Within the lock, `available_units > 0` and `expires_at > NOW()` are verified.
- The unit is atomically decremented (`$lockedOffer->decrement('available_units')`).
- If units drop to zero, any subsequent queued request immediately encounters `$lockedOffer->available_units <= 0` and fails with **HTTP 409 Conflict**, completely preventing double-booking.

### 3. Database-Level Window Function Aggregation
- Finding the lowest price offer per property is achieved entirely within MySQL 8 using window functions:
  ```sql
  ROW_NUMBER() OVER (PARTITION BY property_id ORDER BY price ASC, id ASC) as rn
  ```
- Subquery joins only `rn = 1`, ensuring only the single cheapest valid offer per property is processed.
- Offloads sorting and deduplication to database indexes, avoiding memory bloat and PHP collection overhead.