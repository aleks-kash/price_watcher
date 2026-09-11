# Price Watcher (Цінозор) RESTful API

High-performance RESTful API для агрегації пропозицій туристичної нерухомості від кількох зовнішніх постачальників, побудований за допомогою **Laravel 11**, **PHP 8.3**, **MySQL 8.0**, **Redis 7**, and **Docker Compose**.

---

## 🚀 Quick Start (Docker Environment)

### Prerequisites
- Docker & Docker Compose installed and running.
- (Optional) `make` utility for quick commands.

### Використовуйте команду `make`, яка містить усі необхідні команди:

```bash
# Full installation (env copy, container build, composer install, key, migrations & seeders):
make install
```

Інші доступні команди `make`:
- `make help` — Show available commands
- `make install` — Full setup and seed
- `make up` — Start containers in background
- `make down` — Stop containers
- `make restart` — Restart containers
- `make ps` — View container status
- `make test` — Run test suite (or `make test ARGS="--filter=ImportTest"`)
- `make logs` — View container logs

---

### Зміст документації:

* [Ручний запуск установки](docs/installation_details.md)
* [Swagger UI](docs/swagger.md)
* API Endpoints & Usage
  * [Імпорт пропозицій](docs/api_endpoints.md#1-введення-даних-для-offers-асинхронне-імпортування)
  * [Перевірка статусу та прогресу імпорту](docs/api_endpoints.md#2-перевірити-стан-і-хід-імпорту)
  * [Пошук нерухомості](docs/api_endpoints.md#3-пошук-нерухомості-найкраща-ціна)
  * [Створення резервації](docs/api_endpoints.md#4-створити-бронювання-безпечне-бронювання-в-умовах-паралельного-доступу)
* [Technical Architecture & Key Mechanisms](docs/technical_architecture.md)