# E-Report

<p align="center">
  Internal web application for consultation management, lead tracking, reminders, analytics, and operational reporting.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-red?style=for-the-badge&logo=laravel" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Vite-5-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite 5">
  <img src="https://img.shields.io/badge/MySQL-Ready-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL Ready">
</p>

## Overview

E-Report is a Laravel-based business application built to centralize consultation workflows, client prospect management, follow-up reminders, analytics dashboards, and exportable reports in one system.

This repository is structured for teams that need an admin-friendly operational dashboard with clean UI, role-based access, and production-oriented deployment support.

## Key Features

- Consultation and lead management
- Multi-user access with role-based authorization
- Consultation notes and reminder workflow
- Dashboard analytics and PDF reporting
- Notification polling and summary handling
- Master data management
- Import and export support
- Audit-friendly operational flows

## Tech Stack

| Layer | Stack |
| --- | --- |
| Backend | Laravel 11, PHP 8.2 |
| Frontend | Blade, Tailwind CSS, Alpine.js, DaisyUI |
| Build Tool | Vite |
| Database | MySQL |
| Reporting | DOMPDF |
| Queue / Cache Support | Redis via Predis |

## Project Structure

```text
app/                    Core application logic
resources/views/        Blade templates
resources/js/           Frontend entry files
resources/css/          Application styling
routes/                 Web routes
config/                 Framework and app configuration
public/                 Built assets and public files
database.sql            SQL reference / bootstrap database file
DEPLOYMENT.md           Production deployment guide
```

## Quick Start

### 1. Clone repository

```bash
git clone https://github.com/xamonxx/E-report_v0.0.1.git
cd E-report_v0.0.1
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Update database, cache, mail, and queue configuration in `.env` according to your environment.

### 4. Run database setup

Choose one approach:

```bash
php artisan migrate --seed
```

or use the provided SQL bootstrap file:

```bash
database.sql
```

### 5. Start local development

```bash
php artisan serve
npm run dev
```

## Production Notes

- Use `.env.production.example` as the deployment reference
- Build frontend assets before release with `npm run build`
- Configure queue workers when background jobs are enabled
- Review [DEPLOYMENT.md](./DEPLOYMENT.md) for production setup guidance

## Use Cases

- Internal CRM and consultation tracking
- Lead follow-up management
- Admin reporting and analytics
- Multi-role back-office operations

## Security and Quality Focus

- Server-side request validation with Laravel Form Requests
- Role-based access control for admin workflows
- Queue-ready architecture for heavier background processes
- Production asset build pipeline with Vite

## Repository Notes

If you are adopting this project for a new environment, review these files first:

- `.env.example`
- `.env.production.example`
- `config/queue.php`
- `DEPLOYMENT.md`

## License

This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
