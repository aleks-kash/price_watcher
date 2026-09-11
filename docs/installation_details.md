## Використання прямих команд Docker Compose

### 1. Скопіювати .env (якщо ще не створено)
```bash
cp .env.example .env
```

### 2. Зібрати та запустити контейнери у фоні
```bash
docker compose up -d --build
```
Це запускає 5 контейнерів:
- **`app`** (`price_watcher_app`): PHP 8.3 FPM with `pdo_mysql`, `redis`, `pcntl`, `bcmath`, `zip`, `opcache`.
- **`nginx`** (`price_watcher_nginx`): Зворотний проксі-сервер веб-сервера доступний за адресою `http://localhost:8080`.
- **`mysql`** (`price_watcher_mysql`): MySQL 8.0 on port `3306`.
- **`redis`** (`price_watcher_redis`): Redis 7 on port `6379`.
- **`queue`** (`price_watcher_queue`): Працює спеціалізований фоновий процес обробки черги `php artisan queue:work`.

### 3. Встановити залежності Composer
```bash
docker compose exec app composer install --no-interaction
```

### 4. Згенерувати ключ застосунку Laravel
```bash
docker compose exec app php artisan key:generate --force
```

### 5. Виконати міграції бази даних
```bash
docker compose exec app php artisan migrate --force
```

### 6. Запустити сідери (тестові дані постачальників)
```bash
docker compose exec app php artisan db:seed --force
```

Initial seed data creates two default suppliers:
- `supplier-a` (*Supplier Alpha*)
- `supplier-b` (*Supplier Beta*)

### 7. Запустити автоматизовані тести
```bash
docker compose exec app php artisan test
```

Будуть запущені всі функціональні тести, які охоплюють імпорт, ідемпотентність, агрегацію результатів пошуку та блокування для забезпечення паралельної роботи.
