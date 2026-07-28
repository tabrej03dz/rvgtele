# TeleCRM Setup

## Included
Laravel 13 web CRM with Fortify/Livewire authentication, Spatie roles, multi-company database structure, employees, leads, assignment history, call dispositions/logs, follow-ups, pipeline Kanban, campaigns, products, customers, tasks, orders, payments, reports and audit-ready activity table.

## Install
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```
Login: `admin@example.com` / `password`

## Production
Change demo password immediately. Configure MySQL in `.env`, then run `php artisan migrate --seed`.
