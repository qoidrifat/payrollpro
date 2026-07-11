<h1 align="center">Project-KP</h1>

<p align="center">
  Sistem HR, absensi, payroll, dan status operasional untuk perusahaan Indonesia.
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white">
  <img alt="Vue" src="https://img.shields.io/badge/Vue-3-4FC08D?logo=vue.js&logoColor=white">
  <img alt="Inertia" src="https://img.shields.io/badge/Inertia-2-9553E9">
  <img alt="Tailwind CSS" src="https://img.shields.io/badge/Tailwind_CSS-3-38BDF8?logo=tailwindcss&logoColor=white">
  <img alt="Tests" src="https://img.shields.io/badge/tests-229_passing-16A34A">
</p>

## Ringkasan

Project-KP adalah aplikasi Laravel + Vue/Inertia untuk mengelola data karyawan, absensi, komponen gaji, pemrosesan payroll, slip gaji, laporan, employee self-service, dan status layanan. Sistem ini dirancang untuk kebutuhan HR/payroll lokal Indonesia, termasuk BPJS, PPh21, PTKP, role-based access, audit trail, dan data dummy realistis untuk demo.

Project ini dikembangkan sebagai tugas magang di PT. Maqna Tech Lab, perusahaan teknologi yang berbasis di Bangkalan, Jawa Timur.

Repository saat ini sudah berisi implementasi aplikasi yang cukup luas: service layer, action classes, repository pattern, model multi-tenant, queue jobs, scheduled commands, CI, dan test suite backend.

## Status Sistem Saat Ini

| Area | Kondisi |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+, Inertia server adapter, Breeze auth, Sanctum, Pulse, Sentry, Spatie Permission |
| Frontend | Vue 3, Inertia 2, Tailwind CSS 3, Vite 7, ApexCharts, Heroicons |
| Database | Migration untuk users, employees, attendances, payroll, payslips, settings, approvals, leave/overtime, shift, status page, Pulse |
| Runtime | Queue, scheduler, PDF generation, Excel export, activity logging |
| Testing | `229 passed`, `436 assertions` via `php artisan test` |
| CI | GitHub Actions untuk lint, test, frontend build, dan security audit |
| Dokumentasi | `README.md` sebagai dokumentasi utama, `docs/mobile-api.yaml` untuk spesifikasi API mobile |

## Analisis Sistem

### 1. Domain dan modul

Sistem terbagi ke dalam beberapa domain utama:

- Employee management: data karyawan, status kerja, identitas pajak/BPJS, rekening bank, import/export.
- Attendance: absensi manual, QR signed route, mobile API, GPS/geofence, offline sync, bulk attendance.
- Payroll: payroll run, komponen gaji, BPJS, PPh21, PTKP, lembur, payslip PDF, Excel export.
- Approval dan notification: approval chain, native database notifications, audit event.
- Employee portal: dashboard karyawan, riwayat absensi, payroll, pajak, dan pengajuan cuti.
- System status: public status page, health API, incident management, maintenance schedule.
- Administration: settings, activity log, developer API docs, Pulse dashboard.

### 2. Arsitektur aplikasi

Struktur aplikasi sudah lebih maju dari scaffold Laravel standar:

- `app/Actions`: orchestration layer untuk workflow penting seperti payroll, attendance, approval, dan employee import.
- `app/Services`: business logic utama, termasuk kalkulasi payroll, pajak, BPJS, geofence, anomaly detection, status service, dan incident lifecycle.
- `app/Repositories`: interface dan implementasi Eloquent untuk employee, payroll, dan attendance.
- `app/Models`: model Eloquent untuk core HR/payroll, approval, status page, shift, leave/overtime, BPJS, dan tax config.
- `app/Policies`: authorization policy untuk resource utama.
- `app/Http/Middleware`: tenant resolution, role middleware case-insensitive, CSP, local-only route, dan signed employee validation.

Secara desain, pemisahan controller, action, service, repository, policy, dan model sudah membantu menjaga controller tetap berperan sebagai HTTP layer, sementara aturan bisnis berada di service/action.

### 3. Keamanan dan kontrol akses

Fitur keamanan yang sudah terlihat di codebase:

- Role-based access memakai Spatie Permission.
- Role middleware mendukung case-insensitive role dan pemisahan role dengan pipe/comma.
- Field sensitif employee dienkripsi dengan Laravel encrypted cast: NIK, NPWP, rekening bank, BPJS Kesehatan, dan BPJS Ketenagakerjaan.
- CSP dan security headers aktif di non-local/non-testing environment.
- Rate limiter untuk demo login, QR attendance, dan attendance API.
- Failed login dan password reset dicatat melalui listener.
- Sanctum token expiration dikonfigurasi melalui `SANCTUM_TOKEN_EXPIRATION`, default 43200 menit atau 30 hari.

Catatan: `.env.example` mengaktifkan Redis untuk session, cache, dan queue. Untuk developer lokal tanpa Redis, ubah ke driver database/file/sync sesuai kebutuhan.

### 4. Data dan seeding

`DatabaseSeeder` menjalankan:

- `AdminUserSeeder`
- `BpjsConfigSeeder`
- `Pph21ConfigSeeder`
- `PtkpConfigSeeder`
- `DummyDataSeeder`
- `EmployeeUserSeeder`
- `AttendanceDataSeeder`

Data dummy mencakup 8 karyawan, salary components, payroll Januari-Mei 2026, dan absensi Januari-Mei 2026 berdasarkan hari kerja, hari libur, cuti, sakit, WFO/WFH/remote, dan GPS kantor Bangkalan.

### 5. Kualitas dan risiko teknis

Yang sudah kuat:

- Test suite luas untuk service, policy, auth, attendance, employee, payroll, portal, reports, settings, dan payslip.
- Struktur domain cukup modular.
- CI sudah mencakup PHP syntax, Pint, Composer audit, PHPUnit, frontend build, dan file secret check.
- Sensitive employee fields sudah terenkripsi.
- PDF generation punya patch khusus untuk menghindari masalah Imagick/Dompdf di Windows.

Yang perlu diperhatikan:

- Ada satu warning PHPUnit: metadata di doc-comment akan deprecated di PHPUnit 12. Ubah annotation test terkait menjadi attribute PHP.
- Ada dua workflow GitHub Actions (`ci.yml` dan `tests.yml`) dengan versi PHP berbeda. Ini bisa dipertahankan, tapi lebih rapi jika distandarkan.
- File lokal seperti `.env`, `database/database.sqlite`, `.phpunit.result.cache`, `bash.exe.stackdump`, `.gstack/`, dan `.claude/settings.local.json` tidak boleh masuk GitHub.

## Fitur Utama

| Modul | Fitur |
| --- | --- |
| Dashboard | Ringkasan payroll, attendance, employee, report, dan status operasional |
| Employee | CRUD karyawan, import/export Excel, soft delete, data pajak/BPJS/bank |
| Attendance | Manual attendance, QR signed route, mobile clock-in/out, GPS, offline sync, bulk store |
| Payroll | Payroll run, salary components, BPJS, PPh21, PTKP, approval, payslip |
| Reports | Payroll report, tax report, attendance report, export |
| Portal | Dashboard karyawan, attendance history, payroll history, tax info, leave request |
| Status Page | Public status, health API, service management, incident timeline, maintenance |
| Security | RBAC, encrypted employee fields, CSP headers, rate limit, audit/security logging |
| Monitoring | Laravel Pulse dashboard dan scheduled maintenance commands |

## Tech Stack

| Layer | Teknologi |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+, Breeze, Sanctum, Pulse, Sentry, Spatie Permission |
| Frontend | Vue 3, Inertia 2, Tailwind CSS 3, Vite 7 |
| UI/Data | ApexCharts, Heroicons, QRCode |
| Export | DomPDF, Laravel Excel |
| Runtime | Queue worker, scheduler, Redis-ready config |
| CI | GitHub Actions |
| Test | PHPUnit 11 |

## Struktur Project

```text
project-kp/
|-- app/
|   |-- Actions/          Workflow orchestration
|   |-- DTOs/             Data transfer objects
|   |-- Enums/            Type-safe domain constants
|   |-- Events/           Domain events
|   |-- Exports/          Excel export classes
|   |-- Http/             Controllers, requests, middleware, resources
|   |-- Jobs/             Queue jobs
|   |-- Listeners/        Event listeners
|   |-- Models/           Eloquent models
|   |-- Notifications/    Notification classes
|   |-- Policies/         Authorization policies
|   |-- Repositories/     Repository interfaces and Eloquent implementations
|   |-- Scopes/           Tenant/global scopes
|   |-- Services/         Business logic services
|   `-- Traits/           Shared model behavior
|-- database/
|   |-- migrations/
|   |-- factories/
|   `-- seeders/
|-- docs/
|-- resources/
|   |-- js/
|   `-- views/
|-- routes/
|-- tests/
`-- .github/workflows/
```

## Persyaratan

- PHP 8.2 atau lebih baru
- Composer 2
- Node.js 20 dan npm
- MySQL 8, PostgreSQL/Supabase, atau SQLite untuk development
- Redis jika memakai konfigurasi default `.env.example`
- PHP extensions: `bcmath`, `ctype`, `dom`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `pdo_pgsql` atau `pdo_sqlite`, `tokenizer`, `xml`, `zip`

## Instalasi Lokal

```bash
git clone https://github.com/USERNAME/project-kp.git
cd project-kp

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Opsi database SQLite

```bash
touch database/database.sqlite
```

Pastikan `.env` memakai:

```env
DB_CONNECTION=sqlite
```

### Opsi database MySQL

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_kp
DB_USERNAME=root
DB_PASSWORD=
```

### Opsi database Supabase/PostgreSQL

```env
DB_CONNECTION=pgsql
DB_HOST=db.your-project-ref.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-database-password
DB_SSLMODE=require
```

### Queue, cache, dan session lokal

Jika Redis belum tersedia, gunakan konfigurasi lokal yang lebih sederhana:

```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
```

Lalu jalankan:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

## Menjalankan Aplikasi

Mode development manual:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

Atau gunakan script Composer:

```bash
composer run dev
```

URL penting:

| URL | Keterangan |
| --- | --- |
| `http://localhost:8000` | Landing page |
| `http://localhost:8000/login` | Login |
| `http://localhost:8000/demo` | Demo login, local only |
| `http://localhost:8000/dashboard` | Dashboard setelah login |
| `http://localhost:8000/status` | Public status page |
| `http://localhost:8000/api/health` | Health check |
| `http://localhost:8000/api/status` | Status API |
| `http://localhost:8000/developer/api-docs` | Dokumentasi API mobile, admin only |
| `http://localhost:8000/pulse` | Laravel Pulse, admin only |

## Akun Demo

Seeder membuat akun dasar berikut:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@project-kp.test` | `password` |
| HR | `hr@project-kp.test` | `password` |

`EmployeeUserSeeder` juga membuat akun untuk setiap karyawan dummy dengan format:

```text
first_name.last_name.employee_id@project-kp.test
```

Contoh yang umum setelah seed awal:

| Nama | Email | Password |
| --- | --- | --- |
| Ahmad Fauzi | `ahmad.fauzi.1@project-kp.test` | `password` |
| Rina Kusuma | `rina.kusuma.2@project-kp.test` | `password` |
| Maya Anggraini | `maya.anggraini.8@project-kp.test` | `password` |

## Route Utama

Route publik:

- `GET /`
- `GET /status`
- `GET /api/health`
- `GET /api/status`
- `GET /demo` untuk local/testing

Route authenticated:

- `/dashboard`
- `/employees`
- `/attendances`
- `/salary-config`
- `/payroll`
- `/reports/payroll`
- `/reports/tax`
- `/reports/attendance`
- `/leave-requests`
- `/settings`
- `/activity-log`
- `/notifications`
- `/profile`
- `/portal/*`
- `/my-qr`
- `/pulse`

Mobile attendance API:

| Method | Endpoint | Auth |
| --- | --- | --- |
| `GET` | `/api/mobile/status` | Sanctum |
| `POST` | `/api/mobile/clock-in` | Sanctum |
| `POST` | `/api/mobile/clock-out` | Sanctum |
| `POST` | `/api/mobile/sync-offline` | Sanctum |

QR attendance:

| Method | Endpoint | Catatan |
| --- | --- | --- |
| `GET` | `/scan/in/{employee}` | Signed URL |
| `GET` | `/scan/out/{employee}` | Signed URL |
| `POST` | `/scan/clock-in/{employee}` | Authenticated |
| `POST` | `/scan/clock-out/{employee}` | Authenticated |

## Command Penting

```bash
# Test
php artisan test
composer run test

# Build frontend
npm run build

# Development server
composer run dev

# Queue worker
php artisan queue:work --tries=3 --timeout=60

# Scheduler
php artisan schedule:run

# Clear cache
php artisan optimize:clear
```

Scheduled commands yang sudah terdaftar:

| Command | Jadwal | Fungsi |
| --- | --- | --- |
| `PurgeActivityLogs` | Harian 00:00 | Menghapus activity log lama, default 90 hari |
| `DatabaseBackup` | Harian 02:00 | Backup database dengan retensi 30 hari |
| `queue:restart` | Setiap jam | Restart worker untuk mengurangi risiko memory leak |

## Testing

Hasil verifikasi lokal terakhir:

```text
Tests: 229 passed (436 assertions)
Duration: 12.21s
```

Catatan test:

- Ada warning PHPUnit 11 tentang metadata doc-comment pada salah satu test. Ini belum menggagalkan test, tetapi sebaiknya diganti ke PHP attribute sebelum upgrade PHPUnit 12.
- Test mencakup unit service, policy, auth, attendance, employee, employee portal, leave request, payroll, payslip template, report, setting, health endpoint, dan status page.

## CI/CD

Repository memiliki workflow GitHub Actions:

| Workflow | Fungsi |
| --- | --- |
| `.github/workflows/ci.yml` | Lint/static check, PHPUnit dengan MySQL, frontend build, security audit |
| `.github/workflows/tests.yml` | Test dan build tambahan dengan PHP 8.3 |

Saran maintenance: standardisasi versi PHP dan cache strategy jika ingin pipeline lebih konsisten.

## Deployment

### VPS atau server mandiri

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build

php artisan migrate --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jalankan queue worker dengan Supervisor/systemd:

```bash
php artisan queue:work --tries=3 --timeout=60
```

Aktifkan scheduler di cron:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Shared hosting

- Arahkan document root ke folder `public/`.
- Upload source code tanpa `.env`, `node_modules/`, `vendor/`, `storage/logs/`, dan database lokal.
- Jalankan `composer install --no-dev` jika tersedia.
- Build asset di lokal/CI lalu upload `public/build/` jika server tidak mendukung Node.
- Set `.env` production secara manual di server.

## File yang Tidak Boleh Masuk Git

Pastikan file/folder berikut tetap di-ignore:

```text
.env
.env.backup
.env.production
node_modules/
vendor/
public/build/
public/storage/
storage/logs/*.log
database/*.sqlite
database/*.db
.phpunit.result.cache
.gstack/
.claude/settings.local.json
bash.exe.stackdump
-w
```

## Dokumentasi Terkait

- `docs/mobile-api.yaml`

## Lisensi

Project ini dirilis menggunakan lisensi MIT. Lihat file [LICENSE](https://opensource.org/license/mit) untuk detail lengkap.
