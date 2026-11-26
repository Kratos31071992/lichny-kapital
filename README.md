# Платформа «Личный капитал» — Спринт 1

Демонстрационный каркас реализации Спринта №1 по ТЗ: регистрация с подтверждением email,
вход, восстановление пароля, профиль пользователя, админка, очереди уведомлений.

Стек: **Laravel 12 + PHP 8.3 · Vue 3 + Inertia.js · PostgreSQL · Redis · Docker**

---

## Что реализовано (по пунктам ТЗ)

| Пункт ТЗ | Реализация |
|---|---|
| 2. Регистрация и вход | Модальное окно «Вход / Регистрация», обязательное подтверждение email, восстановление пароля, капча + rate-limit |
| 4. Профиль пользователя | Контакты, семья и работа, текстовые блоки, шкала активного дохода, индикатор заполненности, экспорт и удаление данных (152-ФЗ) |
| 5. Админка | Список пользователей со статусами, блокировка/удаление, создание пользователей, журнал действий, уведомление админу о регистрации |
| 6. Фундамент интеграций | Единый сервис уведомлений, очереди Redis, раздел «Настройки интеграций» с тестовой отправкой |
| Стандарт кода | PHPDoc на классы и методы, комментарии «почему/зачем», README на русском |

## Быстрый старт (локально, через Docker)

```bash
# 1. Клонировать репозиторий
git clone <repo-url> && cd <repo>

# 2. Поднять окружение
cp .env.example .env
docker compose up -d

# 3. Установить зависимости и собрать фронтенд
docker compose exec app composer install
docker compose exec app npm install
docker compose exec app npm run build   # или npm run dev для разработки

# 4. Инициализировать приложение
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Открыть: http://localhost

Тестовый администратор после сидера: `admin@example.com` / `password`.

## Запуск без Docker (локальная разработка)

Требования: PHP 8.3+, Composer, Node 20+, SQLite (для тестов).

```bash
composer install
cp .env.example .env
php artisan key:generate
# В .env указать: DB_CONNECTION=pgsql + параметры PostgreSQL, QUEUE_CONNECTION=redis
php artisan migrate --seed
npm install && npm run dev      # фронтенд-сервер Vite
php artisan serve               # бэкенд http://127.0.0.1:8000
php artisan queue:work          # обработка очередей (письма)
```

## Тестирование

```bash
php artisan test
```

Тесты покрывают: регистрацию, подтверждение email, вход, восстановление пароля,
профиль (заполненность, экспорт, удаление аккаунта), админку и права доступа.

## Деплой на production

1. GitHub Actions (`.github/workflows/deploy.yml`): push в ветку `main` →
   тесты → деплой на VPS через [Deployer](https://deployer.org).
2. Атомарные выкладки с откатом (`releases/`, symlink `current`).
3. Ежедневный бэкап PostgreSQL: cron-задача `php artisan backup:database`
   (ротация 14 дней, копии вне сервера — каталог `storage/backups`, рекомендуется
   монтировать внешнее хранилище).
4. HTTPS: certbot + Let's Encrypt, автообновление.

## Структура кода

```
app/
├── Http/Controllers/        # контроллеры веб-приложения
│   ├── Auth/                # регистрация, вход, восстановление пароля
│   ├── ProfileController    # профиль, экспорт, удаление аккаунта
│   └── Admin/               # админка: пользователи, журнал, настройки
├── Models/                  # модели Eloquent + PHPDoc
├── Notifications/           # единый механизм уведомлений (смена драйвера)
├── Services/                # бизнес-логика (ProfileCompletionService и др.)
└── Support/                 # вспомогательные классы (AuditLogger)
resources/js/pages/          # страницы Vue 3 (Inertia)
tests/Feature/               # функциональные тесты
```

## Важное замечание

Это учебно-демонстрационный каркас, написанный по открытому ТЗ для показа
подхода к коду, архитектуры и документации. Вёрстка — упрощённая
(в боевом проекте переносится готовый HTML-прототип заказчика в компоненты Vue).
Капча — демонстрационная (реальная: Яндекс SmartCaptcha). SMS-драйвер — заглушка
(реальный сервис подключается заменой драйвера, см. `app/Notifications`).
