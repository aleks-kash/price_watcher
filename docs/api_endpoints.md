## 📡 API Endpoints & Usage

### 1. Введення даних для Offers (Асинхронне імпортування)
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

### 2. Перевірити стан і хід імпорту
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

### 3. Пошук нерухомості (Найкраща ціна)
- **Method**: `GET`
- **URI**: `/api/properties?city=Kyiv&check_in=2026-10-01&check_out=2026-10-05&guests=2&page=1`
- **Headers**: `Accept: application/json`

#### Query Parameters:
| Parameter   | Type    | Required | Description |
| :---        | :---    | :---     | :---        |
| `check_in`  | string  | **Yes**  | Дата заїзду |
| `check_out` | string  | **Yes**  | Дата виїзду (має бути після реєстрації заїзду) |
| `city`      | string  | No       | Фільтр міста нерухомості |
| `guests`    | integer | No       | Мінімальна місткість (кількість гостей) (default `1`) |
| `page`      | integer | No       | Номер сторінки пагінації |
| `per_page`  | integer | No       | Кількість результатів на сторінку (default `15`) |

**Формат дати**: `YYYY-MM-DD` (наприклад, `2026-09-09`)

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

### 4. Створити бронювання (безпечне бронювання в умовах паралельного доступу)
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
- **HTTP 409 Conflict**: `{"message": "No units are available for the selected offer."}` коли всі одиниці заброньовані.
- **HTTP 422 Unprocessable Content**: `{"message": "The selected offer has expired."}` під час бронювання за простроченою пропозицією.