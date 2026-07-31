# Report Fitur Manual Kendala Absen di my-qr

## 1. Ringkasan Fitur

Fitur **Manual Kendala Absen** sudah ditambahkan untuk halaman Employee `my-qr`.

Employee sekarang dapat mengajukan absen manual saat QR, kamera, lokasi, koneksi, atau perangkat bermasalah. Pengajuan tidak langsung menjadi attendance final. Data masuk sebagai request dengan status:

- `pending`
- `approved`
- `rejected`

Attendance resmi baru dibuat atau diperbarui saat HR/Admin melakukan approve.

## 2. Lokasi Halaman my-qr yang Diperbarui

Halaman yang diperbarui:

- `resources/js/Pages/Attendance/MyQr.vue`

Tambahan di halaman:

- CTA card: **Mengalami kendala absen?**
- Deskripsi: **Ajukan absensi manual jika QR, kamera, lokasi, atau koneksi bermasalah.**
- Tombol: **Kendala absen? Klik manual di sini!**
- Modal pengajuan absen manual.
- Badge/status request manual hari ini.
- Update otomatis melalui Supabase Realtime jika aktif, dengan fallback polling.

## 3. Flow Employee dari Halaman my-qr

1. Employee login.
2. Employee membuka `/my-qr`.
3. Employee melihat QR attendance seperti sebelumnya.
4. Jika ada kendala, employee klik **Kendala absen? Klik manual di sini!**.
5. Modal pengajuan tampil dengan field:
   - tipe pengajuan: `manual_clock_in` atau `manual_clock_out`
   - tanggal
   - jam yang diajukan
   - alasan/keterangan kendala
   - upload bukti opsional
6. Setelah submit, request tersimpan sebagai `pending`.
7. Employee melihat status seperti:
   - **Manual Clock-In Menunggu Verifikasi**
   - **Manual Clock-Out Menunggu Verifikasi**
8. Status berubah otomatis setelah HR/Admin approve atau reject.

## 4. Flow HR/Admin

Halaman HR/Admin baru:

- `/manual-attendance-requests`

Fitur halaman:

- Panel **Pengajuan Absen Manual**
- Summary pending, approved, rejected
- Filter status
- Tabel request:
  - Employee
  - Tanggal
  - Tipe
  - Jam diajukan
  - Alasan
  - Status
  - Aksi approve/reject
- Modal detail
- Konfirmasi approve
- Modal reject dengan alasan wajib
- Empty state rapi
- Supabase Realtime jika aktif, fallback polling 20 detik

## 5. Database/Migration

Migration baru:

- `database/migrations/2026_06_10_000001_create_manual_attendance_requests_table.php`

Tabel baru:

- `manual_attendance_requests`

Kolom utama:

- `company_id`
- `employee_id`
- `attendance_id`
- `request_type`
- `requested_date`
- `requested_time`
- `reason`
- `evidence_path`
- `status`
- `reviewed_by`
- `reviewed_at`
- `rejection_reason`
- `source`
- `metadata`
- timestamps

Kolom tambahan di `attendances`:

- `source`
- `approved_by`
- `approved_at`

Untuk PostgreSQL/Supabase:

- RLS diaktifkan pada `manual_attendance_requests`.
- Akses `anon` dan `authenticated` direvoke karena akses harus lewat Laravel auth, bukan Data API publik.
- Trigger realtime dibuat untuk topic `manual_attendance`.

## 6. Route yang Ditambahkan

Routes baru:

- `GET /manual-attendance-requests`
- `POST /manual-attendance-requests`
- `GET /manual-attendance-requests/my-latest`
- `GET /manual-attendance-requests/poll`
- `POST /manual-attendance-requests/{manualAttendanceRequest}/approve`
- `POST /manual-attendance-requests/{manualAttendanceRequest}/reject`

Semua route berada di middleware `auth` dan `verified`.

Route review dibatasi role:

- `Admin`
- `HR`

## 7. Controller/Service/Model

File backend baru:

- `app/Models/ManualAttendanceRequest.php`
- `app/Enums/ManualAttendanceRequestStatus.php`
- `app/Enums/ManualAttendanceRequestType.php`
- `app/Http/Controllers/ManualAttendanceRequestController.php`
- `app/Http/Requests/StoreManualAttendanceRequest.php`
- `app/Http/Requests/ReviewManualAttendanceRequest.php`
- `app/Services/ManualAttendanceService.php`
- `app/Policies/ManualAttendanceRequestPolicy.php`
- `app/Events/ManualAttendanceRequested.php`
- `app/Events/ManualAttendanceReviewed.php`
- `app/Notifications/ManualAttendanceRequestNotification.php`

File backend yang diubah:

- `app/Http/Controllers/AttendanceController.php`
- `app/Models/Attendance.php`
- `app/Models/Employee.php`
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`

## 8. Validasi dan Keamanan

Validasi yang diterapkan:

- Employee hanya bisa submit untuk employee record miliknya sendiri.
- Employee tidak bisa approve/reject.
- HR/Admin saja yang bisa review.
- Duplicate request dicegah untuk kombinasi employee, tanggal, dan tipe jika status masih `pending` atau sudah `approved`.
- `reason` wajib minimal 10 karakter.
- Tanggal tidak boleh melewati hari ini.
- Jam tidak boleh jauh di masa depan.
- Manual Clock-Out dicegah jika belum ada Clock-In resmi pada tanggal tersebut.
- Approve/reject memakai database transaction.
- Request yang sudah diproses tidak bisa diproses ulang.
- Audit/security log dicatat melalui `SecurityLogger` dan `Auditable`.

## 9. Realtime atau Fallback Polling

Implementasi memakai stack existing:

- `resources/js/composables/useSupabaseRealtime.js`
- `resources/js/lib/supabase.js`
- tabel `realtime_notifications`

Topic baru:

- `manual_attendance`

Fallback:

- Employee page polling setiap 15 detik.
- HR/Admin page polling setiap 20 detik.

Tidak ada konfigurasi broadcasting Laravel baru yang dibuat.

## 10. File yang Dibuat/Diubah

File baru utama:

- `MANUAL_ATTENDANCE_MY_QR_FEATURE_REPORT.md`
- `app/Enums/ManualAttendanceRequestStatus.php`
- `app/Enums/ManualAttendanceRequestType.php`
- `app/Events/ManualAttendanceRequested.php`
- `app/Events/ManualAttendanceReviewed.php`
- `app/Http/Controllers/ManualAttendanceRequestController.php`
- `app/Http/Requests/StoreManualAttendanceRequest.php`
- `app/Http/Requests/ReviewManualAttendanceRequest.php`
- `app/Models/ManualAttendanceRequest.php`
- `app/Notifications/ManualAttendanceRequestNotification.php`
- `app/Policies/ManualAttendanceRequestPolicy.php`
- `app/Services/ManualAttendanceService.php`
- `database/migrations/2026_06_10_000001_create_manual_attendance_requests_table.php`
- `resources/js/Components/ManualAttendanceModal.vue`
- `resources/js/Components/ManualAttendanceStatusBadge.vue`
- `resources/js/Pages/ManualAttendanceRequests/Index.vue`
- `tests/Feature/ManualAttendanceRequestFeatureTest.php`

File diubah utama:

- `app/Http/Controllers/AttendanceController.php`
- `app/Models/Attendance.php`
- `app/Models/Employee.php`
- `app/Providers/AppServiceProvider.php`
- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/js/Pages/Attendance/MyQr.vue`
- `routes/web.php`

Catatan: repository sudah memiliki banyak file dirty/untracked sebelum pekerjaan ini. `vendor/bin/pint --dirty` ikut memformat sebagian file yang sudah dirty. Tidak dilakukan revert massal agar tidak menghapus perubahan lain yang bukan bagian dari task ini.

## 11. Hasil Test/Build/Lint

Berhasil:

- `vendor\bin\pint --dirty`
- `php artisan migrate --path=database/migrations/2026_06_10_000001_create_manual_attendance_requests_table.php`
- `php artisan migrate:status`
- `php artisan route:list`
- `php artisan test`
- `npm run build`

Hasil test final:

- `249 passed`
- `524 assertions`

Build final:

- `npm run build` sukses.

Tidak tersedia:

- `npm run lint` gagal karena script `lint` tidak ada di `package.json`.
- `npm run format` gagal karena script `format` tidak ada di `package.json`.

## 12. Error yang Ditemukan dan Auto-Fix

Error build:

- `ManualAttendanceStatusBadge.vue` memiliki parsing error karena object lookup dalam computed kurang aman.
- Diperbaiki dengan syntax computed object lookup yang valid.

Error test:

- Test awal gagal karena policy `review` belum didaftarkan eksplisit.
- Diperbaiki dengan `Gate::policy(ManualAttendanceRequest::class, ManualAttendanceRequestPolicy::class)` di `AppServiceProvider`.

Error test tenant:

- Direct-create test data tidak membawa `company_id`, sehingga tenant scope bisa membuat row tidak terbaca saat request.
- Diperbaiki di test dengan company context eksplisit.

Error assertion SQLite:

- SQLite menyimpan `date` sebagai `YYYY-MM-DD 00:00:00` pada assertion tertentu.
- Assertion disesuaikan agar tidak false negative lintas driver.

## 13. Catatan Manual

Untuk production Supabase/PostgreSQL:

- Jalankan migration di environment target.
- Pastikan `VITE_SUPABASE_REALTIME_ENABLED=true`, `VITE_SUPABASE_URL`, dan publishable key tersedia jika ingin realtime aktif.
- Jika env realtime tidak aktif, fitur tetap berjalan dengan fallback polling.
- Jika menggunakan upload bukti, pastikan `php artisan storage:link` sudah dibuat agar file di disk `public` bisa diakses sesuai kebutuhan aplikasi.
