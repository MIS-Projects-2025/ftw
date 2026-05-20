# FTW — Fit to Work System
## Architecture & Turnover Documentation

> **Last updated:** 2026-05-18  
> **Stack:** Laravel 12 · PHP 8.2 · React 18 · Inertia.js · Tailwind CSS v3 · DaisyUI v5 · shadcn/ui (New-York) · Vite 6

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Technology Stack](#2-technology-stack)
3. [External Services & Integrations](#3-external-services--integrations)
4. [Database Architecture](#4-database-architecture)
5. [Authentication & Authorization](#5-authentication--authorization)
6. [Backend Architecture](#6-backend-architecture)
7. [Frontend Architecture](#7-frontend-architecture)
8. [Role-Based Access](#8-role-based-access)
9. [FTW Workflow — Process Flows](#9-ftw-workflow--process-flows)
10. [API & Route Reference](#10-api--route-reference)
11. [File Structure Reference](#11-file-structure-reference)
12. [Development Guide](#12-development-guide)

---

## 1. System Overview

The **Fit to Work (FTW) System** is a health and safety management application used to record, route, and track employee health assessments in the workplace. It manages a multi-stage approval workflow where clinical staff, supervisors, and employees each play a distinct role.

### Core Business Flow

```
Clinic/Supervisor creates FTW Record
          │
          ▼
 [If Clinic-Created]
 Supervisor Approves / Disapproves
          │
          ▼
 Employee Acknowledges / Rejects
          │
          ▼
     Record Completed
```

### Recommendation Types (rec_id)

| ID | Label | Purpose |
|----|-------|---------|
| 1 | Fit to Work | Employee cleared to return after absence |
| 2 | Sent Home | Employee sent home from duty |
| 3 | Sent to Hospital | Employee requires hospital admission |
| 4 | Rest | Employee needs a rest period (30 min – 1 hr) |
| 5 | Unfit to Work | Employee cannot work |
| 6 | Return to Work Area | (Internal use; not shown in create form) |

### Process Status Values

| Value | Label | Meaning |
|-------|-------|---------|
| 1 | Pending Supervisor | Awaiting supervisor approval (clinic-created records) |
| 2 | Pending Acknowledgement | Awaiting employee acknowledgement |
| 3 | Completed | Workflow finished successfully |
| 6 | Disapproved | Rejected at any stage |

---

## 2. Technology Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Backend framework | Laravel 12 | PHP 8.2+, `php artisan serve` |
| Frontend library | React 18 + Inertia.js | No full-page reloads; controllers return `Inertia::render()` |
| Styling | Tailwind CSS v3 + DaisyUI v5 | Utility classes |
| Component library | shadcn/ui (New-York style) | JSX, not TSX; stored in `resources/js/Components/ui/` |
| Build tool | Vite 6 + `laravel-vite-plugin` | Dev: `npm run dev`, Prod: `npm run build` |
| Testing | Pest (PHP) | `composer run test` |
| Code style | Laravel Pint | `./vendor/bin/pint` |
| Default port | **8005** | Started via `composer run dev` |

### Running the Application

```bash
# Start everything (Laravel + Vite + queue worker + log watcher)
composer run dev

# Frontend only
npm run dev

# Production build
npm run build

# Run tests
composer run test

# Format code
./vendor/bin/pint
```

---

## 3. External Services & Integrations

### 3.1 Authify SSO (Authentication)

- **URL:** `http://127.0.0.1:8001`
- **Purpose:** Manages user login sessions; FTW has no local passwords
- **Token flow:**
  1. User logs into Authify
  2. Authify issues an SSO token
  3. FTW validates the token against the `authify` database on every request
  4. On valid token, employee data is loaded into Laravel session as `emp_data`
- **Config:** `ADB_*` env vars in `config/database.php`
- **Logout endpoint:** `http://127.0.0.1:8001/logout?token={token}&redirect={url}`
- **Login redirect:** `http://127.0.0.1:8001/login?redirect={url}`

### 3.2 HRIS API (Employee Directory)

- **Config:** `HRIS_API_URL` / `HRIS_API_KEY` env vars → `config/services.php` → `hris.*`
- **Service class:** `app/Services/HrisApiService.php`
- **Used for:**
  - Fetching employee names (`GET /api/employees/{id}`)
  - Fetching work details (department, prodline, shift, team, etc.) (`GET /api/employees/{id}/work`)
  - Fetching direct reports to determine supervisor role (`GET /api/employees/direct-reports/{id}`)
  - Searching active employees for record creation (`GET /api/employees/active`)
  - Fetching immediate supervisor for clinic-created records (`GET /api/employees/{id}/work` → approver fields)
  - Bulk employee lookup (`POST /api/employees/bulk`)
  - Salary data for payroll (`GET /api/employees/{id}/salary`)
  - Operation director (`GET /api/employees/operation-director`)

> **Important:** Always use `HrisApiService` for employee lookups — do **not** query the `masterlist` DB directly.

### 3.3 Masterlist Database (Employee Master Data)

- **Connection:** `masterlist` (env vars `MDB_*`)
- **Table:** `employee_masterlist`
- **Purpose:** Read-only HRIS employee directory; used for profile display and password changes
- **Key columns:** `EMPLOYID`, `EMPNAME`, `JOB_TITLE`, `DEPARTMENT`, `PRODLINE`, `STATION`, `DATEHIRED`, `EMAIL`, `PASSWRD`, `ACCSTATUS`

---

## 4. Database Architecture

### 4.1 Database Connections

Three MySQL connections are configured in `config/database.php`:

| Connection | Env Prefix | Purpose |
|-----------|------------|---------|
| `mysql` | `DB_*` | App's own tables (ftw_records, admin, etc.) |
| `masterlist` | `MDB_*` | Read-only HRIS employee directory |
| `authify` | `ADB_*` | SSO session store (authify_sessions) |

### 4.2 App Database Tables (`mysql`)

#### `ftw_records` — Primary FTW record

| Column | Type | Notes |
|--------|------|-------|
| `tbl_id` | INT (PK) | Auto-increment |
| `emp_no` | INT | Employee number |
| `emp_name` | VARCHAR | Employee full name |
| `emp_dept` | VARCHAR | Department |
| `emp_team` | VARCHAR | Team |
| `emp_time_in` | TIME | Time employee checked in (null for Rec 4 Rest) |
| `emp_time_out` | TIME | Time out (nullable) |
| `emp_diagnose` | TEXT | Clinical diagnosis/notes (null for Rec 5) |
| `remarks` | TEXT | General remarks |
| `recommendation` | INT | FK → `recommendation_ref.rec_id` |
| `process_status` | INT | 1=PendingSup, 2=PendingAck, 3=Completed, 6=Disapproved |
| `ftw_file_link` | VARCHAR | Relative path to uploaded file |
| `date_created` | DATETIME | Record creation timestamp |
| `duty_nurse` | INT | Emp ID of clinic staff who created the record |
| `first_aider_name` | VARCHAR | Name of first aider (if applicable) |
| `training_dept` | INT | Training department reference |
| `emp_shift` | INT | 1=Day Shift, 2=Night Shift, 3=Normal |
| `absent_count` | INT | Number of absence days |
| `disapprove_remarks` | TEXT | Reason for disapproval |
| `ftw_date` | DATE | FTW effective date |

#### `ftw_approvals` — Approval workflow audit trail

| Column | Type | Notes |
|--------|------|-------|
| `approval_id` | INT (PK) | Auto-increment |
| `tbl_id` | INT | FK → `ftw_records.tbl_id` |
| `approver_emp` | INT | Employee ID of the approver |
| `role` | VARCHAR | `immediate_sup` or `ack_by` |
| `status` | INT | 0=Pending, 1=Approved, 2=Rejected |
| `action_date` | DATETIME | When action was taken (null if still pending) |
| `remarks` | TEXT | Optional remarks at time of action |

#### `ftw_absence_dates` — Absence dates (child of ftw_records)

| Column | Type | Notes |
|--------|------|-------|
| `absence_id` | INT (PK) | |
| `tbl_id` | INT | FK → `ftw_records.tbl_id` |
| `absence_date` | DATE | One row per absence date |

#### `ftw_rest_schedule` — Rest period (child of ftw_records, HasOne)

| Column | Type | Notes |
|--------|------|-------|
| `rest_id` | INT (PK) | |
| `tbl_id` | INT | FK → `ftw_records.tbl_id` (unique) |
| `rest_date` | DATETIME | Date of rest (set to today on creation) |
| `rest_time_in` | TIME | When rest starts |
| `rest_time_out` | TIME | When employee returns (nullable; set later) |

#### `ftw_sdh_schedule` — Sent home/hospital schedule (child of ftw_records, HasOne)

| Column | Type | Notes |
|--------|------|-------|
| `sdh_id` | INT (PK) | |
| `tbl_id` | INT | FK → `ftw_records.tbl_id` (unique) |
| `sdh_date` | DATETIME | Date sent home/hospital |
| `sdh_time` | TIME | Time sent (nullable) |

#### `recommendation_ref` — Lookup table

| Column | Type | Notes |
|--------|------|-------|
| `rec_id` | INT (PK) | 1–6 |
| `rec_label` | VARCHAR | Human-readable label |

#### `admin` — System administrators

| Column | Type | Notes |
|--------|------|-------|
| `emp_id` | INT (UNIQUE) | Employee ID |
| `emp_name` | VARCHAR | Employee name |
| `emp_role` | VARCHAR | `superadmin`, `admin`, or custom |
| `last_updated_by` | INT | Employee ID of last editor |

#### `system_status` — System maintenance flag

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT (PK) | Always id=1 |
| `status` | VARCHAR | `online` or `maintenance` |
| `message` | TEXT | Maintenance message (nullable) |
| `updated_at` | DATETIME | |

### 4.3 Entity Relationship Diagram

```
recommendation_ref ──< ftw_records >─── ftw_approvals
                            │
                            ├──< ftw_absence_dates
                            ├──○ ftw_rest_schedule
                            └──○ ftw_sdh_schedule
```

---

## 5. Authentication & Authorization

### 5.1 Authentication — AuthMiddleware

**File:** `app/Http/Middleware/AuthMiddleware.php`

Every authenticated request passes through this middleware. The flow:

```
Incoming Request
      │
      ├─ X-Internal-Key header present? ──YES──► Skip auth (internal service bypass)
      │
      ▼
Resolve SSO Token (priority order):
  1. ?key query param
  2. sso_token cookie
  3. session('emp_data.token')
      │
      ├─ No token found? ──► Redirect to Authify login
      │
      ├─ session('emp_data') exists AND token matches? ──YES──►
      │     Refresh cookie (7 days)
      │     Clean URL (remove ?key param if present)
      │     Check maintenance mode ──► Continue request
      │
      └─ Token mismatch or no session ──►
            Query authify_sessions table for token
            │
            ├─ Not found? ──► Clear session + cookie ──► Redirect to login
            │
            ├─ emp_from != null? ──► Render Unauthorized (403)
            │
            └─ Valid session ──►
                  Populate session('emp_data'):
                    token, emp_id, emp_name, emp_firstname,
                    emp_dept_id, emp_jobtitle_id, emp_prodline_id,
                    emp_position_id, emp_station_id, shift_type,
                    team, generated_at
                  Set sso_token cookie (7 days)
                  Clean URL redirect if token was in query
                  Check maintenance mode ──► Continue request
```

**Maintenance Mode Bypass Routes:** `logout`, `system-status.online`, `system-status.maintenance`

### 5.2 Authorization — AdminMiddleware

**File:** `app/Http/Middleware/AdminMiddleware.php`

Applied on top of AuthMiddleware for admin-only routes:

```
Request (already authenticated)
      │
      ▼
Query admin table WHERE emp_id = session('emp_data.emp_id')
      │
      ├─ Not found ──► Redirect to dashboard
      └─ Found ──► Continue to admin route
```

### 5.3 Role Determination at Runtime

Roles are not stored in a "roles" column — they are computed at request time in `FtwController`:

```php
// app/Http/Controllers/General/FtwController.php

$isClinic = $empData['emp_station_id'] === 39;   // Station 39 = Clinic
$isSupervisor = !$isClinic && count($this->hrisApi->fetchDirectReports($empId)) > 0;
```

This computed value is stored in the session for the lifetime of the page visit:
```php
session(['is_supervisor' => $isSupervisor]);
```

---

## 6. Backend Architecture

### 6.1 Pattern: Controller → Service → Repository

```
Routes (web.php / general.php / ftw.php)
    │
    ▼
Controller  ──────► validates input, resolves user context
    │
    ▼
Service  ──────────► business logic, transactions, workflow rules
    │
    ▼
Repository  ────────► Eloquent queries, data persistence
    │
    ▼
Eloquent Models  ───► DB tables
```

All layers use constructor dependency injection.

### 6.2 Controllers

| Controller | File | Responsibility |
|-----------|------|---------------|
| `AuthenticationController` | `Controllers/AuthenticationController.php` | Logout (clears session, redirects to Authify) |
| `DashboardController` | `Controllers/DashboardController.php` | Renders Dashboard page |
| `AdminController` | `Controllers/General/AdminController.php` | Admin CRUD, role management |
| `ProfileController` | `Controllers/General/ProfileController.php` | Profile view, password change |
| `FtwController` | `Controllers/General/FtwController.php` | FTW record create/list/actions |
| `DemoController` | `Controllers/DemoController.php` | DataTable demonstration |

#### FtwController — Method Summary

| Method | Route | Purpose |
|--------|-------|---------|
| `index()` | GET /records | Renders IndexFtw page; sets role in session |
| `create()` | GET /create | Renders CreateFtw form with recommendations |
| `store()` | POST / | Validates + saves new FTW record; handles file upload |
| `historyData()` | GET /data/history | JSON: paginated history records |
| `pendingData()` | GET /data/pending | JSON: paginated pending records |
| `handleAction()` | POST /{id}/action | Approve / Disapprove / Acknowledge / Reject single record |
| `bulkAction()` | POST /bulk-action | Supervisor bulk approve/disapprove |
| `searchEmployees()` | GET /employees | JSON: paginated HRIS employee search |

### 6.3 Services

#### FtwService (`app/Services/FtwService.php`)

**Constants:**
- `REC_NEEDS_ABSENCE = [1, 5]` — Recommendations that require absence dates
- `REC_NEEDS_SDH = [2, 3, 5]` — Recommendations that require SDH schedule
- `REC_NEEDS_REST = [4]` — Recommendations that require rest schedule

**Key Methods:**

| Method | Description |
|--------|-------------|
| `store(array $data, array $empData)` | Transaction-wrapped record creation (creates FtwRecord + child records + approval rows) |
| `handleAction(int $tblId, int $empId, string $action, ?string $remarks)` | Transaction-wrapped single approval action |
| `bulkAction(array $ids, int $empId, string $action, ?string $remarks)` | Bulk approval/disapproval |
| `getHistoryData(...)` | Delegates to repository, returns paginated history |
| `getPendingData(...)` | Delegates to repository; returns empty for clinic users |
| `getFormData()` | Returns recommendations list (excludes rec_id 6) |

**store() — Internal Flow:**
```
1. Determine process_status:
   - Clinic or non-supervisor employee → PENDING_SUP (1)
   - Supervisor → PENDING_ACK (2)

2. Create FtwRecord (core fields)

3. If REC_NEEDS_ABSENCE: save FtwAbsenceDate rows (one per date)

4. If REC_NEEDS_SDH: save FtwSdhSchedule row

5. If REC_NEEDS_REST: save FtwRestSchedule row

6. Create approval rows:
   - If PENDING_SUP (clinic-created):
       → Create ROLE_IMMEDIATE_SUP / STATUS_PENDING row
         (approver_emp = immediate supervisor from HRIS)
   - If PENDING_ACK (supervisor-created):
       → Create ROLE_IMMEDIATE_SUP / STATUS_APPROVED row (auto-approved)
       → Create ROLE_ACK_BY / STATUS_PENDING row (awaiting employee)
```

#### HrisApiService (`app/Services/HrisApiService.php`)

Wraps all HRIS API calls. All methods return `null` or `[]` on failure — callers must handle gracefully.

| Method | HRIS Endpoint | Returns |
|--------|--------------|---------|
| `fetchEmployeeName(int $id)` | GET /api/employees/{id} | `?string` name |
| `fetchWorkDetails(int $id)` | GET /api/employees/{id}/work | `?array` work details |
| `fetchApprovers(int $id)` | GET /api/employees/{id}/work | `?array` [approver1_id, approver1_name, ...] |
| `fetchDirectReports(int $id)` | GET /api/employees/direct-reports/{id} | `array` of report objects |
| `fetchActiveEmployees(string $search, int $page, int $perPage)` | GET /api/employees/active | `array {data, hasMore}` |
| `fetchEmployeesBulk(array $empNos)` | POST /api/employees/bulk | `array` emp_no → details map |
| `fetchOperationDirector()` | GET /api/employees/operation-director | `?array` |
| `fetchSalaryData(int $id, int $empClass)` | GET /api/employees/{id}/salary | `?array` salary fields |

#### DataTableService (`app/Services/DataTableService.php`)

Generic service for server-side data tables with search, sort, date filter, pagination, and CSV export.

```php
$result = $this->dataTable->handle($request, 'mysql', 'admin', [
    'searchColumns' => ['EMPNAME', 'EMPLOYID', 'JOB_TITLE'],
    'defaultSortBy' => 'EMPNAME',
    'filename' => 'admin-export',
    'exportColumns' => ['emp_id', 'emp_name', 'emp_role'],
]);
```

Returns `{ data: Paginator, columns: [] }` or `StreamedResponse` if CSV export is requested.

### 6.4 Repositories

#### FtwRepository (`app/Repositories/FtwRepository.php`)

**Visibility rules in `getHistory()`:**

| Role | Records Visible |
|------|----------------|
| Clinic | All records |
| Supervisor | Own records + all direct reports' records |
| Regular Employee | Only their own records with status Completed (3) or Disapproved (6) |

**Visibility rules in `getPending()`:**

| Role | Records Visible |
|------|----------------|
| Supervisor | Records where they have a pending `immediate_sup` approval row |
| Regular Employee | Records where they have a pending `ack_by` approval row |

**Allowed sort columns:** `tbl_id`, `date_created`, `emp_name`, `emp_dept`, `recommendation`, `process_status`

**formatRecord() output fields:**
`tbl_id`, `emp_no`, `emp_name`, `emp_dept`, `emp_team`, `recommendation`, `rec_label`, `process_status`, `date_created`, `emp_shift`, `emp_shift_label`, `emp_time_in` (HH:MM A), `emp_diagnose`, `absent_count`, `absence_dates` (sorted, formatted), `ftw_file`, `ftw_file_url`, `sdh_date`, `sdh_time`, `remarks`, `rest_date`, `rest_time_in`

### 6.5 Eloquent Models

| Model | Table | Key Relationships |
|-------|-------|------------------|
| `FtwRecord` | `ftw_records` | HasMany `FtwAbsenceDate`, HasOne `FtwRestSchedule`, HasOne `FtwSdhSchedule`, HasMany `FtwApproval`, BelongsTo `RecommendationRef` |
| `FtwApproval` | `ftw_approvals` | BelongsTo `FtwRecord` |
| `FtwAbsenceDate` | `ftw_absence_dates` | BelongsTo `FtwRecord` |
| `FtwRestSchedule` | `ftw_rest_schedule` | BelongsTo `FtwRecord` |
| `FtwSdhSchedule` | `ftw_sdh_schedule` | BelongsTo `FtwRecord` |
| `RecommendationRef` | `recommendation_ref` | HasMany `FtwRecord` |
| `SystemStatus` | `system_status` | — |

**FtwRecord — Process Status Constants:**
```php
PROCESS_STATUS_PENDING_SUP = 1
PROCESS_STATUS_PENDING_ACK = 2
PROCESS_STATUS_COMPLETED   = 3
PROCESS_STATUS_DISAPPROVED = 6
```

**FtwApproval — Status & Role Constants:**
```php
STATUS_PENDING  = 0
STATUS_APPROVED = 1
STATUS_REJECTED = 2

ROLE_IMMEDIATE_SUP = 'immediate_sup'
ROLE_ACK_BY        = 'ack_by'
```

---

## 7. Frontend Architecture

### 7.1 Entry Points

| File | Purpose |
|------|---------|
| `resources/js/app.jsx` | Inertia bootstrap; wraps app in `ThemeProvider`; mounts global `<Toaster>` (sonner) |
| `resources/css/app.css` | CSS variables (primary: `hsl(142 72% 29%)` green-tinted theme) |

### 7.2 Shared Data (via Inertia)

`HandleInertiaRequests::share()` injects `emp_data` into every page automatically. Pages receive:

```js
// usePage().props.emp_data
{
  emp_id, emp_name, emp_firstname, emp_dept_id, emp_jobtitle_id,
  emp_prodline_id, emp_position_id, emp_station_id, shift_type, team
}
```

### 7.3 Layout

**`AuthenticatedLayout.jsx`** — Shell used by all authenticated pages:
- Sidebar navigation
- Top navbar
- Page content slot

### 7.4 Pages

| Page | File | Receives |
|------|------|---------|
| Dashboard | `Pages/Dashboard.jsx` | — |
| FTW Index | `Pages/Ftw/IndexFtw.jsx` | `isSupervisor`, `isClinic` |
| FTW Create | `Pages/Ftw/CreateFtw.jsx` | `recommendations[]`, `canSelectEmployee` |
| Admin | `Pages/Admin/Admin.jsx` | `tableData`, `tableFilters` |
| New Admin | `Pages/Admin/NewAdmin.jsx` | `tableData`, `tableFilters` |
| Profile | `Pages/Profile.jsx` | `profile` |

### 7.5 UI Component Library

shadcn/ui components live in `resources/js/Components/ui/`:

| Component | File |
|-----------|------|
| Avatar | `avatar.jsx` |
| Button | `button.jsx` |
| Dialog | `dialog.jsx` |
| Dropdown Menu | `dropdown-menu.jsx` |
| Label | `label.jsx` |
| Separator | `separator.jsx` |
| Sonner (toasts) | `sonner.jsx` |
| Textarea | `textarea.jsx` |
| Tooltip | `tooltip.jsx` |

**Path alias:** `@` resolves to `resources/js/`  
**Utility:** `resources/js/lib/utils.js` exports `cn()` (clsx + tailwind-merge)

### 7.6 IndexFtw Page (FTW List)

**Tabs:**
- **Pending** — Actions waiting on the current user (hidden for clinic staff)
- **History** — All resolved records visible to the current user

**Per-tab features:**

| Feature | Pending | History |
|---------|---------|---------|
| Search | Yes | Yes |
| Sort | Yes | Yes |
| Pagination | Yes | Yes |
| Supervisor bulk select | Yes | No |
| Approve / Disapprove buttons | Supervisors only | No |
| Acknowledge / Reject buttons | Employees only | No |
| View modal | Yes | Yes |

**Bulk Action Flow (supervisor):**
1. Check multiple rows
2. Bulk action bar appears
3. Click Approve or Disapprove
4. BulkActionDialog opens (optional remarks)
5. POST /ftw/bulk-action

### 7.7 CreateFtw Page

**Form fields by recommendation:**

| Field | Rec 1 (FTW) | Rec 2 (Home) | Rec 3 (Hospital) | Rec 4 (Rest) | Rec 5 (Unfit) |
|-------|:-----------:|:------------:|:----------------:|:------------:|:-------------:|
| Employee selector | ✓ | ✓ | ✓ | ✓ | ✓ |
| Shift | ✓ | ✓ | ✓ | — | ✓ |
| Time In | ✓ | — | — | ✓ (rest) | — |
| Diagnosis | ✓ | ✓ | ✓ | ✓ | — |
| Remarks | — | — | — | — | ✓ |
| Absence dates | ✓ | — | — | — | — |
| SDH Date | — | ✓ | ✓ | — | ✓ |
| SDH Time | — | ✓ | ✓ | — | — |
| File upload | ✓ | — | — | — | — |
| Time Out (read-only) | — | — | — | ✓ (set later) | — |

**File upload:** Saved to `../uploads/ftw_upload/{emp_no}/{datestamp}_{emp_no}_{rec}.{ext}`  
**Allowed types:** pdf, jpg, jpeg, png, doc, docx (max 10 MB)

**Date picker rules:**
- Shift 3 (Normal): weekends disabled in date pickers
- Shifts 1 & 2: all dates allowed
- Absence count multiplier: ×1.5 for shifts 1 & 2, ×1 for shift 3

---

## 8. Role-Based Access

### 8.1 Role Summary

| Role | How Determined | Key Capabilities |
|------|---------------|-----------------|
| **Clinic Staff** | `emp_station_id === 39` | Create records for any employee; view all history |
| **Supervisor** | Not clinic + has HRIS direct reports | Create records for self/reports; approve/disapprove; bulk actions; view team records |
| **Regular Employee** | Not clinic + no direct reports | Create records for self only; acknowledge/reject pending records; view own completed records |
| **Admin** | Row exists in `admin` table | Access admin panel; manage admin users and roles |

> Note: Roles are **not mutually exclusive** — a supervisor could also be an admin.

### 8.2 What Each Role Sees

#### Clinic Staff

- **Create FTW:** Can search for any employee via combobox
- **History tab:** Sees ALL records across all employees
- **Pending tab:** Hidden (clinic cannot approve records)
- **Record actions:** None (cannot approve/acknowledge)

#### Supervisor

- **Create FTW:** Can search for employees (for themselves or direct reports)
- **History tab:** Sees own records + all direct reports' records
- **Pending tab:** Sees records where they are the assigned approver (`immediate_sup`)
- **Record actions:** Approve / Disapprove (single or bulk)
- After approving: system creates `ack_by` approval row for the employee

#### Regular Employee

- **Create FTW:** Form auto-fills their own `emp_no`; cannot select other employees
- **History tab:** Sees only their own records with status Completed (3) or Disapproved (6)
- **Pending tab:** Sees records where they have a pending `ack_by` row
- **Record actions:** Acknowledge / Reject

#### Admin

- **Admin Panel** (`/{app_name}/admin`): Accessible via AdminMiddleware
- **Add Admin** (`/{app_name}/new-admin`): Search employees from masterlist
- **Manage Roles:** Change existing admin's role
- **Remove Admin:** Remove employee from admin table
- An admin changing their own role updates their session immediately

### 8.3 Approval Role Mapping (FtwApproval)

| `role` field | Who acts | Status transitions |
|-------------|----------|-------------------|
| `immediate_sup` | Supervisor | PENDING (0) → APPROVED (1) or REJECTED (2) |
| `ack_by` | Employee | PENDING (0) → APPROVED (1) or REJECTED (2) |

---

## 9. FTW Workflow — Process Flows

### 9.1 Path A: Clinic-Created Record

```
┌─────────────────────────────────────────────────────────┐
│                   CLINIC CREATES RECORD                  │
│                  process_status = 1 (PENDING_SUP)        │
│                  FtwApproval: immediate_sup, PENDING      │
└──────────────────────────┬──────────────────────────────┘
                           │
                           ▼
              ┌────────────────────────┐
              │   SUPERVISOR REVIEWS   │
              │   (sees in Pending tab)│
              └─────────┬──────┬──────┘
                        │      │
              ┌─────────▼─┐  ┌─▼──────────────┐
              │  APPROVE   │  │   DISAPPROVE   │
              └─────────┬──┘  └─┬──────────────┘
                        │       │
                        │       ▼
                        │  process_status = 6 (DISAPPROVED)
                        │  FtwApproval: immediate_sup, REJECTED
                        │  ─────── WORKFLOW ENDS ───────
                        │
                        ▼
          process_status = 2 (PENDING_ACK)
          FtwApproval: immediate_sup, APPROVED
          FtwApproval: ack_by, PENDING (created now)
                        │
                        ▼
              ┌────────────────────────┐
              │   EMPLOYEE REVIEWS     │
              │   (sees in Pending tab)│
              └─────────┬──────┬──────┘
                        │      │
              ┌─────────▼─┐  ┌─▼──────────────┐
              │ACKNOWLEDGE │  │    REJECT       │
              └─────────┬──┘  └─┬──────────────┘
                        │       │
                        │       ▼
                        │  process_status = 6 (DISAPPROVED)
                        │  FtwApproval: ack_by, REJECTED
                        │  ─────── WORKFLOW ENDS ───────
                        │
                        ▼
              process_status = 3 (COMPLETED)
              FtwApproval: ack_by, APPROVED
              ──── WORKFLOW COMPLETE ────
```

### 9.2 Path B: Supervisor-Created Record

```
┌─────────────────────────────────────────────────────────┐
│               SUPERVISOR CREATES RECORD                  │
│              process_status = 2 (PENDING_ACK)            │
│   FtwApproval: immediate_sup, APPROVED (auto)            │
│   FtwApproval: ack_by, PENDING                           │
└──────────────────────────┬──────────────────────────────┘
                           │
                           ▼
              ┌────────────────────────┐
              │   EMPLOYEE REVIEWS     │
              │   (sees in Pending tab)│
              └─────────┬──────┬──────┘
                        │      │
              ┌─────────▼─┐  ┌─▼──────────────┐
              │ACKNOWLEDGE │  │    REJECT       │
              └─────────┬──┘  └─┬──────────────┘
                        │       │
                        │       ▼
                        │  process_status = 6 (DISAPPROVED)
                        │  ─────── WORKFLOW ENDS ───────
                        │
                        ▼
              process_status = 3 (COMPLETED)
              ──── WORKFLOW COMPLETE ────
```

### 9.3 Path C: Regular Employee-Created Record (for self)

Same as Path B — treated identically to supervisor-created records since the employee is also the subject. Process starts at PENDING_ACK (2), requires only their own acknowledgement.

### 9.4 Bulk Action Flow (Supervisor)

```
Supervisor selects multiple records (checkboxes)
        │
        ▼
Bulk Action Bar appears
        │
        ├── Click "Approve All"
        │       │
        │       ▼
        │   BulkActionDialog (optional remarks)
        │       │
        │       ▼
        │   POST /ftw/bulk-action { action: 'approve', ids: [...], remarks: '...' }
        │       │
        │       ▼
        │   For each record:
        │     FtwApproval: immediate_sup → APPROVED
        │     FtwRecord: process_status → PENDING_ACK (2)
        │     If employee exists: create ack_by PENDING row (if not exists)
        │
        └── Click "Disapprove All"
                │
                ▼
            BulkActionDialog (optional remarks)
                │
                ▼
            POST /ftw/bulk-action { action: 'disapprove', ids: [...], remarks: '...' }
                │
                ▼
            For each record:
              FtwApproval: immediate_sup → REJECTED
              FtwRecord: process_status → DISAPPROVED (6)
```

### 9.5 Record State Visibility Summary

| `process_status` | Clinic sees | Supervisor sees | Employee sees |
|:----------------:|:-----------:|:---------------:|:-------------:|
| 1 (PendingSup) | History | **Pending** | Not visible |
| 2 (PendingAck) | History | History | **Pending** |
| 3 (Completed) | History | History | History |
| 6 (Disapproved) | History | History | History |

### 9.6 FTW Creation — Validation Rules Summary

| Field | Rec 1 | Rec 2 | Rec 3 | Rec 4 | Rec 5 |
|-------|:-----:|:-----:|:-----:|:-----:|:-----:|
| `emp_no` | Required | Required | Required | Required | Required |
| `emp_time_in` | Required | — | — | — | — |
| `emp_shift` | Required | Required | Required | — | Required |
| `emp_diagnose` | Required | Required | Required | Required | — |
| `remarks` | Optional | Optional | Optional | Optional | Required |
| `absence_dates[]` | Required | — | — | — | — |
| `absent_count` | Computed | — | — | — | — |
| `sdh_date` | — | Required | Required | — | Required |
| `sdh_time` | — | Optional | Optional | — | — |
| `rest_time_in` | — | — | — | Required | — |
| `ftw_file` | Optional | — | — | — | — |

---

## 10. API & Route Reference

### 10.1 Web Routes

All routes under `/{app_name}` are protected by `AuthMiddleware`.

| Method | URI | Name | Controller@Method | Extra Middleware |
|--------|-----|------|-------------------|-----------------|
| GET | `/` | — | redirect to `/{app_name}` | — |
| GET | `/{app_name}` | `dashboard` | `DashboardController@index` | — |
| GET | `/{app_name}/logout` | `logout` | `AuthenticationController@logout` | — |
| GET | `/{app_name}/unauthorized` | `unauthorized` | — (Inertia::render) | — |
| GET | `/{app_name}/profile` | `profile.index` | `ProfileController@index` | — |
| POST | `/{app_name}/change-password` | `changePassword` | `ProfileController@changePassword` | — |
| GET | `/{app_name}/admin` | `admin` | `AdminController@index` | AdminMiddleware |
| GET | `/{app_name}/new-admin` | `index_addAdmin` | `AdminController@index_addAdmin` | AdminMiddleware |
| POST | `/{app_name}/add-admin` | `addAdmin` | `AdminController@addAdmin` | AdminMiddleware |
| POST | `/{app_name}/remove-admin` | `removeAdmin` | `AdminController@removeAdmin` | AdminMiddleware |
| PATCH | `/{app_name}/change-admin-role` | `changeAdminRole` | `AdminController@changeAdminRole` | AdminMiddleware |

### 10.2 FTW Routes

| Method | URI | Name | Controller@Method |
|--------|-----|------|-------------------|
| GET | `/{app_name}/records` | `ftw.index` | `FtwController@index` |
| GET | `/{app_name}/create` | `ftw.create` | `FtwController@create` |
| POST | `/{app_name}/` | `ftw.store` | `FtwController@store` |
| GET | `/{app_name}/data/history` | `ftw.data.history` | `FtwController@historyData` |
| GET | `/{app_name}/data/pending` | `ftw.data.pending` | `FtwController@pendingData` |
| GET | `/{app_name}/employees` | `ftw.employees` | `FtwController@searchEmployees` |
| POST | `/{app_name}/bulk-action` | `ftw.bulk-action` | `FtwController@bulkAction` |
| POST | `/{app_name}/{id}/action` | `ftw.action` | `FtwController@handleAction` |

### 10.3 JSON Response Schemas

**historyData / pendingData:**
```json
{
  "data": [
    {
      "tbl_id": 1,
      "emp_no": 10001,
      "emp_name": "Juan Dela Cruz",
      "emp_dept": "Production",
      "emp_team": "Team A",
      "recommendation": 1,
      "rec_label": "Fit to Work",
      "process_status": 3,
      "date_created": "2026-05-18 08:00:00",
      "emp_shift": 1,
      "emp_shift_label": "Day Shift",
      "emp_time_in": "08:00 AM",
      "emp_diagnose": "Headache",
      "absent_count": 2,
      "absence_dates": ["May 15, 2026", "May 16, 2026"],
      "ftw_file": "20260518_10001_1.pdf",
      "ftw_file_url": "/uploads/ftw_upload/...",
      "sdh_date": null,
      "sdh_time": null,
      "remarks": null,
      "rest_date": null,
      "rest_time_in": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "total": 47,
    "from": 1,
    "to": 10
  }
}
```

**handleAction / bulkAction:**
```json
{ "success": true }
{ "success": true, "processed": 3 }
```

**searchEmployees:**
```json
{
  "data": [
    { "emp_id": 10001, "emp_name": "Juan Dela Cruz", "department": "Production", ... }
  ],
  "hasMore": true
}
```

### 10.4 Query Parameters for Data Endpoints

| Param | Default | Description |
|-------|---------|-------------|
| `search` | — | Searches `emp_name`, `emp_dept`, `tbl_id` (if numeric) |
| `order_by` | `date_created` | Column to sort by |
| `order_dir` | `desc` | `asc` or `desc` |
| `page` | 1 | Page number |
| `per_page` | 10 | Rows per page (clamped 5–50) |

---

## 11. File Structure Reference

```
FTW/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthenticationController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DemoController.php
│   │   │   └── General/
│   │   │       ├── AdminController.php
│   │   │       ├── FtwController.php
│   │   │       └── ProfileController.php
│   │   └── Middleware/
│   │       ├── AuthMiddleware.php      ← SSO token validation
│   │       └── AdminMiddleware.php     ← Admin table check
│   ├── Models/
│   │   ├── FtwRecord.php
│   │   ├── FtwApproval.php
│   │   ├── FtwAbsenceDate.php
│   │   ├── FtwRestSchedule.php
│   │   ├── FtwSdhSchedule.php
│   │   ├── RecommendationRef.php
│   │   └── SystemStatus.php
│   ├── Repositories/
│   │   ├── FtwRepository.php
│   │   └── SystemStatusRepository.php
│   └── Services/
│       ├── FtwService.php
│       ├── HrisApiService.php
│       ├── DataTableService.php
│       └── SystemStatusService.php
├── config/
│   ├── database.php     ← Three DB connections (mysql, masterlist, authify)
│   └── services.php     ← HRIS API config
├── routes/
│   ├── web.php          ← Root; imports all route files
│   ├── auth.php         ← Login/logout routes
│   ├── general.php      ← Dashboard, Profile, Admin routes
│   └── ftw.php          ← All FTW routes
├── resources/
│   ├── css/
│   │   └── app.css      ← CSS variables, theme
│   └── js/
│       ├── app.jsx      ← Inertia bootstrap
│       ├── lib/
│       │   └── utils.js ← cn() utility
│       ├── Components/
│       │   └── ui/      ← shadcn/ui components
│       ├── Layouts/
│       │   └── AuthenticatedLayout.jsx
│       └── Pages/
│           ├── Dashboard.jsx
│           ├── Profile.jsx
│           ├── Admin/
│           │   ├── Admin.jsx
│           │   └── NewAdmin.jsx
│           └── Ftw/
│               ├── IndexFtw.jsx   ← List + pending actions
│               └── CreateFtw.jsx  ← Create form
└── CLAUDE.md            ← Developer guidance
```

---

## 12. Development Guide

### 12.1 Adding a New FTW Feature

Follow the Controller → Service → Repository pattern:

1. **Route** — Add to `routes/ftw.php`
2. **Controller** — Add method to `FtwController.php`; validate input, call service, return `Inertia::render()` or `JsonResponse`
3. **Service** — Add business logic to `FtwService.php`; wrap DB mutations in `DB::transaction()`
4. **Repository** — Add Eloquent query to `FtwRepository.php`
5. **Frontend** — Add new Page or update existing one in `resources/js/Pages/Ftw/`

### 12.2 Adding a New Admin Route

1. Add route to `routes/general.php` inside the admin middleware group
2. Add method to `AdminController.php`
3. Add frontend page to `resources/js/Pages/Admin/`

### 12.3 Adding a shadcn Component

```bash
# Components go in resources/js/Components/ui/
# Use .jsx extension (not .tsx)
# Import with @ alias: import { Button } from '@/Components/ui/button'
```

### 12.4 Environment Variables Required

```env
# App
APP_NAME=ftw                    # Used as URL prefix
APP_URL=http://localhost:8005

# Primary DB
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Masterlist DB (HRIS read-only)
MDB_HOST=...
MDB_DATABASE=...
MDB_USERNAME=...
MDB_PASSWORD=...

# Authify DB (SSO sessions)
ADB_HOST=...
ADB_DATABASE=...
ADB_USERNAME=...
ADB_PASSWORD=...

# HRIS REST API
HRIS_API_URL=http://...
HRIS_API_KEY=...
```

### 12.5 Key Business Rules to Remember

1. **Clinic station ID is hardcoded as `39`** — `emp_station_id === 39` determines clinic role
2. **Supervisor role is dynamic** — determined per-request via HRIS direct-reports call; not stored
3. **Recommendation 5 (Unfit)** — never set `emp_time_in`; use `remarks` not `emp_diagnose`
4. **Recommendation 4 (Rest)** — `rest_time_out` is set later via a separate action, not on creation
5. **Clinic-created records go through supervisor approval first** — supervisor must approve before employee acknowledgement
6. **Supervisor-created records skip to employee acknowledgement** — `immediate_sup` is auto-approved
7. **Regular employees creating their own records** — treated as supervisor-created (starts at PENDING_ACK)
8. **Shift 3 (Normal)** — disables weekends in date pickers; absence multiplier = ×1 (not ×1.5)
9. **Bulk actions only for supervisors** — verified server-side; clinic users cannot approve
10. **File uploads** — stored outside web root at `../uploads/ftw_upload/{emp_no}/`

---

*This document was generated from codebase analysis on 2026-05-18. Update when architecture changes.*
