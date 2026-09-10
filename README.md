# Price Watcher (Цінозор) RESTful API

High-performance RESTful API for aggregating travel property offers across multiple external suppliers, built with **Laravel 11**, **PHP 8.3**, **MySQL 8.0**, **Redis 7**, and **Docker Compose**.

---

## 🚀 Quick Start (Docker Environment)

### Prerequisites
- Docker & Docker Compose installed and running.

### 1. Start Docker Compose Stack
```bash
docker compose up -d
```
This spins up 5 containers:
- **`app`** (`price_watcher_app`): PHP 8.3 FPM with `pdo_mysql`, `redis`, `pcntl`, `bcmath`, `zip`, `opcache`.
- **`nginx`** (`price_watcher_nginx`): Web server reverse proxy available at `http://localhost:8080`.
- **`mysql`** (`price_watcher_mysql`): MySQL 8.0 on port `3306`.
- **`redis`** (`price_watcher_redis`): Redis 7 on port `6379`.
- **`queue`** (`price_watcher_queue`): Dedicated background queue worker running `php artisan queue:work`.

### 2. Run Database Migrations & Seeders
```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

Initial seed data creates two default suppliers:
- `supplier-a` (*Supplier Alpha*)
- `supplier-b` (*Supplier Beta*)

### 3. Run Automated Tests
```bash
docker compose exec app php artisan test
```
All feature tests covering imports, idempotency, search aggregations, and concurrency locks will execute.

---

## 📖 Interactive API Documentation (Swagger / OpenAPI)

Interactive Swagger UI is available at:
👉 **[http://localhost:8080/docs/api](http://localhost:8080/docs/api)**

OpenAPI 3.0 JSON specification is available at:
👉 **[http://localhost:8080/docs/api.json](http://localhost:8080/docs/api.json)**

---

## 📡 API Endpoints & Usage

### 1. Ingest Offers (Asynchronous Import)
- **Method**: `POST`
- **URI**: `/api/imports`
- **Headers**: `Content-Type: application/json`, `Accept: application/json`

#### Request Body
```json
{
  "supplier_code": "supplier-a",
  "external_import_id": "imp-2026-09-001",
  "sent_at": "2026-09-09T12:00:00Z",
  "offers": [
    {
      "external_id": "off-101",
      "property_code": "hotel-kyiv-1",
      "property_name": "Hotel Kyiv Center",
      "property_city": "Kyiv",
      "check_in": "2026-10-01",
      "check_out": "2026-10-05",
      "max_guests": 2,
      "price": 120.00,
      "currency": "EUR",
      "available_units": 5,
      "expires_at": "2026-09-30T23:59:59Z"
    },
    {
      "external_id": "off-102",
      "property_code": "hotel-kyiv-1",
      "property_name": "Hotel Kyiv Center",
      "property_city": "Kyiv",
      "check_in": "2026-10-01",
      "check_out": "2026-10-05",
      "max_guests": 2,
      "price": 95.00,
      "currency": "EUR",
      "available_units": 2,
      "expires_at": "2026-09-30T23:59:59Z"
    }
  ]
}
```

#### Response (HTTP 202 Accepted)
```json
{
  "data": {
    "id": 1,
    "supplier_code": "supplier-a",
    "external_import_id": "imp-2026-09-001",
    "sent_at": "2026-09-09T12:00:00+00:00",
    "status": "pending",
    "total_offers": 2,
    "processed_offers": 0,
    "progress_percentage": 0,
    "error": null,
    "completed_at": null
  }
}
```

---

### 2. Check Import Status & Progress
- **Method**: `GET`
- **URI**: `/api/imports/{id}`
- **Headers**: `Accept: application/json`

#### Response (HTTP 200 OK)
```json
{
  "data": {
    "id": 1,
    "supplier_code": "supplier-a",
    "external_import_id": "imp-2026-09-001",
    "sent_at": "2026-09-09T12:00:00+00:00",
    "status": "completed",
    "total_offers": 2,
    "processed_offers": 2,
    "progress_percentage": 100,
    "error": null,
    "completed_at": "2026-09-09T12:00:05+00:00"
  }
}
```

---

### 3. Search Properties (Best Price Discovery)
- **Method**: `GET`
- **URI**: `/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05&guests=2&page=1`
- **Headers**: `Accept: application/json`

#### Query Parameters:
| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `check_in` | string (YYYY-MM-DD) | **Yes** | Check-in date |
| `check_out` | string (YYYY-MM-DD) | **Yes** | Check-out date (must be after check_in) |
| `city` | string | No | Property city filter |
| `guests` | integer | No | Minimum guests capacity (default `1`) |
| `page` | integer | No | Pagination page number |
| `per_page` | integer | No | Results per page (default `15`) |

#### Response (HTTP 200 OK)
```json
{
  "data": [
    {
      "id": 1,
      "code": "hotel-kyiv-1",
      "name": "Hotel Kyiv Center",
      "city": "Kyiv",
      "best_offer": {
        "id": 2,
        "external_id": "off-102",
        "supplier": {
          "code": "supplier-a",
          "name": "Supplier Alpha"
        },
        "price": 95.0,
        "currency": "EUR",
        "available_units": 2,
        "check_in": "2026-10-01",
        "check_out": "2026-10-05",
        "max_guests": 2,
        "expires_at": "2026-09-30T23:59:59+00:00"
      }
    }
  ],
  "links": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### 4. Create Reservation (Concurrency-Safe Booking)
- **Method**: `POST`
- **URI**: `/api/offers/{offer_id}/reservations`
- **Headers**: `Content-Type: application/json`, `Accept: application/json`

#### Request Body
```json
{
  "client_reference": "booking-client-ref-001",
  "customer_name": "Alexander Kash",
  "customer_email": "alex@example.com"
}
```

#### Response (HTTP 201 Created)
```json
{
  "data": {
    "id": 1,
    "offer_id": 2,
    "client_reference": "booking-client-ref-001",
    "customer_name": "Alexander Kash",
    "customer_email": "alex@example.com",
    "created_at": "2026-09-09T13:45:41+00:00",
    "offer": {
      "external_id": "off-102",
      "price": 95.0,
      "currency": "EUR",
      "check_in": "2026-10-01",
      "check_out": "2026-10-05",
      "remaining_units": 1
    }
  }
}
```

#### Error Responses:
- **HTTP 409 Conflict**: `{"message": "No units are available for the selected offer."}` when all units are booked.
- **HTTP 422 Unprocessable Content**: `{"message": "The selected offer has expired."}` when booking an expired offer.

---

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