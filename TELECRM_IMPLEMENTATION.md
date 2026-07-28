# Telecalling Sales CRM – Laravel Web MVP

## Implemented
- Laravel 13 authentication starter preserved
- Spatie role and permission seeding
- Company, branch, team and employee structure
- Leads with source, status, priority, temperature, assignment and soft delete
- Lead assignment history
- Call log and disposition workflow
- Follow-ups, notes and lead timeline
- Sales pipeline and stage movement
- Campaigns, products, customers, tasks, orders and payments
- Owner/manager-style dashboard metrics
- Reports for calls, lead statuses and employee performance
- Company-level isolation checks in controllers
- Dedicated controllers and routes for every major module
- Dedicated responsive CRM sidebar and reusable module views

## Demo
Email: admin@example.com
Password: password

## Setup
1. Configure `.env` database values.
2. Run `composer install`.
3. Run `npm install`.
4. Run `php artisan key:generate`.
5. Run `php artisan migrate:fresh --seed`.
6. Run `npm run build`.
7. Run `php artisan serve`.

## Required PHP extensions
pdo_mysql, mbstring, openssl, curl, fileinfo, xml, dom

## Scope note
This package is the Laravel web CRM MVP. Flutter mobile application, native SIM call tracking, cloud telephony, WhatsApp Business API, Facebook Lead Ads, advanced Excel mapping/import queues, AI call analysis, offline mobile sync and platform-store deployment require separate provider credentials and mobile source code, so they are not represented as fake integrations.
