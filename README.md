# utn-student-requests-module

Laravel 13 + Livewire 4 + Fortify (starter kit oficial), organizado con arquitectura hexagonal (Domain / Application / Infrastructure). Ver [ARCHITECTURE.md](ARCHITECTURE.md) para la convención de capas y cómo agregar un módulo nuevo.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
composer dev
```