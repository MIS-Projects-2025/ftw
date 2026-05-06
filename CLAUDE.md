# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

**Start everything (Laravel + Vite + queue worker + log watcher):**
```bash
composer run dev
```
This runs on port **8005** by default.

**Frontend only:**
```bash
npm run dev
```

**Production build:**
```bash
npm run build
```

**Run tests (Pest):**
```bash
composer run test
# or a single test file:
php artisan test tests/Feature/ExampleTest.php
```

**Code formatting (Laravel Pint):**
```bash
./vendor/bin/pint
```

## Architecture Overview

### Stack
- **Backend:** Laravel 12 (PHP 8.2+), served via `php artisan serve`
- **Frontend:** React 18 + Inertia.js (no full-page reloads; controllers return `Inertia::render(...)`)
- **Styling:** Tailwind CSS v3 + DaisyUI v5 (utility classes) + shadcn/ui New-York style (component library)
- **Build:** Vite 6 with `laravel-vite-plugin`

### Authentication
Authentication is handled by an **external Authify SSO** service running at `http://127.0.0.1:8001`. There are no local user passwords. `AuthMiddleware` validates an SSO token from query string → cookie → session, then hydrates `session('emp_data')` with the user's IDs. The session key `emp_data` is shared to every Inertia page via `HandleInertiaRequests::share()`.

`emp_data` contains: `emp_id`, `emp_name`, `emp_firstname`, `emp_dept_id`, `emp_jobtitle_id`, `emp_prodline_id`, `emp_position_id`, `emp_station_id`, `shift_type`, `team`.

### Database Connections
Three MySQL connections are configured (`config/database.php`):
- **`mysql`** (default) — the app's own database (tables: `admin`, `ftw_records`, `ftw_absence_dates`, `ftw_rest_schedule`, `ftw_sdh_schedule`, `recommendation_ref`, `system_status`, etc.)
- **`masterlist`** — read-only HRIS employee directory (`employee_masterlist`); configured via `MDB_*` env vars
- **`authify`** — SSO session store; configured via `ADB_*` env vars

### HRIS API
Employee details are fetched via an external **HRIS API** (configured in `config/services.php` → `hris.url` / `hris.key`, env vars `HRIS_API_URL` / `HRIS_API_KEY`). Use `HrisApiService` for any employee lookups rather than querying the `masterlist` DB directly.

### Backend Pattern: Controller → Service → Repository
```
Controller  →  Service  →  Repository  →  Eloquent Model
```
- **Controllers** (`app/Http/Controllers/`) resolve routes, validate input, and call services. They return `Inertia::render(PageName, [...props])`.
- **Services** (`app/Services/`) hold business logic. Injected into controllers via constructor DI.
- **Repositories** (`app/Repositories/`) wrap Eloquent; they own all DB queries. Services call repositories.

### Routing
Routes live in `routes/web.php` (entry), which includes:
- `routes/auth.php` — login/logout
- `routes/general.php` — all authenticated routes under the `APP_NAME` prefix, protected by `AuthMiddleware`

Admin-only routes are additionally wrapped in `AdminMiddleware`.

### Frontend Structure
- `resources/js/app.jsx` — Inertia bootstrap; wraps the app in `ThemeProvider` and mounts a global `<Toaster>` (sonner).
- `resources/js/Pages/` — one file per Inertia route; placed in sub-folders matching the controller grouping (e.g. `Pages/Admin/`, `Pages/Ftw/`).
- `resources/js/Layouts/AuthenticatedLayout.jsx` — sidebar + navbar shell used by all authenticated pages.
- `resources/js/Components/ui/` — shadcn primitive components (Button, Dialog, Label, Textarea, etc.). Follow the New-York style; add new ones here.
- `resources/js/lib/utils.js` — exports `cn()` (clsx + tailwind-merge).

### shadcn Components
Components are in `.jsx` (not `.tsx`). The `@` path alias resolves to `resources/js/`. Existing primitives: `avatar`, `button`, `dialog`, `dropdown-menu`, `label`, `separator`, `sonner`, `textarea`, `tooltip`. The theme colours are green-tinted (primary `hsl(142 72% 29%)`) defined as CSS variables in `resources/css/app.css`.

### FTW Domain Models
The core domain has five Eloquent models (all with `$timestamps = false`):
- `FtwRecord` (table `ftw_records`) — main record; `recommendation` (int 1–6) and `process_status` (int 1–8) drive the workflow.
- `FtwAbsenceDate` → HasMany off `FtwRecord`
- `FtwRestSchedule` → HasOne off `FtwRecord`
- `FtwSdhSchedule` → HasOne off `FtwRecord`
- `RecommendationRef` (table `recommendation_ref`) — lookup: 1=Fit to work, 2=Sent home, 3=Send to hospital, 4=Rest (30 mins–1 hr), 5=Unfit to work, 6=Return to work area

**Key business rules for FTW creation:**
- Recommendation **5 (Unfit to Work)**: omit `emp_time_in` — the employee will not be working.
- Recommendation **4 (Rest)**: no "Return to Work" field on the creation form; that action is exposed as a table-row action after the record is saved.
- Shift **3 (Normal)**: disable weekends in date pickers; absent multiplier = ×1. Shifts 1 & 2: all dates allowed; multiplier = ×1.5.
- `process_status` starts at 1 when a clinic user creates; at 2 when a supervisor creates.
