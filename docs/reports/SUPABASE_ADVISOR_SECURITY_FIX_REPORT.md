# Laporan Audit dan Perbaikan Supabase Advisor Security

Tanggal audit: 2026-06-06  
Project ref: `fpiceveqbvgaxdtxxbaq`  
Status eksekusi: migration SQL sudah dibuat, belum diterapkan ke remote Supabase.

## 1. Ringkasan Masalah

Supabase Advisor menandai banyak tabel di schema `public` karena RLS belum aktif pada schema yang terekspos ke PostgREST/Data API. Risiko utamanya adalah tabel Laravel, RBAC, HR, payroll, token, session, cache, queue, telemetry, dan audit bisa terbaca atau termodifikasi melalui role API `anon` atau `authenticated` jika grant masih terbuka.

Audit lokal menunjukkan aplikasi ini memakai Laravel sebagai jalur utama akses database. Frontend memakai `@supabase/supabase-js` hanya untuk Supabase Realtime pada tabel `public.realtime_notifications`; tidak ditemukan pemakaian `supabase.from(...)` untuk CRUD langsung dari browser.

## 2. Jumlah Issue Advisor

- Issue ERROR yang dilaporkan user: 51.
- Pembacaan read-only melalui Supabase MCP menemukan:
  - `rls_disabled_in_public` pada tabel public.
  - `sensitive_columns_exposed` pada `password_reset_tokens.token`, `personal_access_tokens.token`, dan `users.password`.
  - 1 WARN tambahan: `function_search_path_mutable` pada `public.enqueue_realtime_notification`.

Migration yang dibuat menargetkan seluruh kategori di atas tanpa drop/truncate/delete data.

## 3. Daftar Tabel yang Terkena RLS Disabled in Public

`activity_logs`, `approvals`, `attendance_selfies`, `attendances`, `bpjs_configs`, `cache`, `cache_locks`, `companies`, `employees`, `failed_jobs`, `holidays`, `incident_service`, `incident_updates`, `incidents`, `job_batches`, `jobs`, `leave_requests`, `maintenance_schedules`, `migrations`, `model_has_permissions`, `model_has_roles`, `notifications`, `office_locations`, `overtime_requests`, `overtime_rules`, `password_reset_tokens`, `payroll_items`, `payrolls`, `payslips`, `permissions`, `personal_access_tokens`, `pph21_configs`, `ptkp_configs`, `pulse_aggregates`, `pulse_entries`, `pulse_values`, `role_has_permissions`, `roles`, `salary_components`, `service_metrics`, `sessions`, `settings`, `shift_assignments`, `shifts`, `system_services`, `uptime_logs`, `user_notifications`, `users`.

Catatan: `realtime_notifications` sudah punya RLS dari migration Laravel, tetapi tetap direview karena policy lamanya terlalu luas untuk akses anonymous realtime.

## 4. Klasifikasi Tabel

### INTERNAL_SERVER_ONLY

Tabel:
`migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`, `pulse_values`, `pulse_entries`, `pulse_aggregates`.

Aksi:
- Enable RLS.
- Revoke semua akses langsung dari `anon`, `authenticated`, dan `PUBLIC`.
- Tidak membuat policy publik.

Risiko:
- Token reset password, token API, session, queue payload, cache, dan telemetry bisa bocor jika Data API grant terbuka.

### USER_OWNED_DATA

Tabel:
`users`, `employees`, `attendances`, `attendance_selfies`, `leave_requests`, `overtime_requests`, `shift_assignments`, `notifications`, `user_notifications`, `activity_logs`.

Aksi:
- Enable RLS.
- Tetap revoke `anon`/`authenticated` karena aplikasi tidak memakai Supabase Auth untuk CRUD langsung.
- Belum membuat policy `auth.uid()` karena user Laravel tidak otomatis sama dengan `auth.users.id` Supabase.

Risiko:
- Jika dibuat policy asal seperti `to authenticated using (true)`, data personal karyawan, absensi, notifikasi, dan audit bisa bocor antar user/perusahaan.

### PUBLIC_READ_ONLY

Tabel:
`realtime_notifications`.

Aksi:
- Enable RLS.
- Grant hanya `SELECT` ke `anon` dan `authenticated`.
- Ganti policy lama `USING (true)` menjadi filter topic: `attendance`, `payroll`, `leave`.
- Tidak memberi akses INSERT/UPDATE/DELETE.

Risiko:
- Tabel ini masih dapat memberi sinyal event realtime ke browser. Jika informasi event dianggap sensitif, sebaiknya pindahkan realtime ke channel privat/RBAC backend.

### ADMIN_ONLY

Tabel:
`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `companies`, `salary_components`, `payrolls`, `payroll_items`, `payslips`, `settings`, `bpjs_configs`, `pph21_configs`, `ptkp_configs`, `system_services`, `incidents`, `incident_service`, `incident_updates`, `maintenance_schedules`, `service_metrics`, `uptime_logs`, `approvals`, `overtime_rules`, `shifts`, `holidays`, `office_locations`.

Aksi:
- Enable RLS.
- Revoke semua akses langsung dari `anon`, `authenticated`, dan `PUBLIC`.
- Tidak membuat policy admin berbasis JWT karena role Admin/HR/Employee saat ini dikelola Laravel/Spatie, bukan Supabase Auth claims.

Risiko:
- Data payroll, salary, approval, RBAC, status operasional, dan konfigurasi perusahaan sangat sensitif. Policy direct-client hanya aman setelah ada desain RBAC Supabase yang eksplisit.

## 5. SQL Migration yang Dibuat

Forward migration:

`supabase/migrations/20260606155331_fix_advisor_rls_security.sql`

Isi utama:
- `ALTER TABLE ... ENABLE ROW LEVEL SECURITY` untuk seluruh tabel public yang diketahui.
- `REVOKE ALL PRIVILEGES` dari `anon`, `authenticated`, dan `PUBLIC` untuk tabel backend-only/admin/business.
- `GRANT SELECT` hanya untuk `public.realtime_notifications`.
- Policy read-only realtime dengan filter topic.
- `REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public` untuk API roles.
- `ALTER DEFAULT PRIVILEGES` untuk mengurangi exposure table/sequence baru.
- `CREATE OR REPLACE FUNCTION public.enqueue_realtime_notification() ... SET search_path = pg_catalog, public`.

## 6. Rollback Plan

Rollback file:

`supabase/rollbacks/rollback_fix_advisor_rls_security.sql`

Catatan CLI: rollback file sengaja ditempatkan di `supabase/rollbacks/`, bukan `supabase/migrations/`, karena Supabase CLI hanya menerima file migration dengan pola `<timestamp>_name.sql`. Jika file rollback non-timestamp ditempatkan di `supabase/migrations/`, `supabase migration list` akan memberi warning `file name must match pattern`.

Rollback:
- Drop policy realtime yang dibuat migration.
- Mengembalikan policy realtime lama `USING (true)` karena itu policy yang terdokumentasi di migration Laravel sebelumnya.
- Tidak disable RLS.
- Tidak restore broad grants ke tabel internal karena grant lama tidak aman dan belum ada snapshot grant pra-migration yang disetujui.

Jika rollback grant penuh dibutuhkan, ambil snapshot grant sebelum apply dan buat rollback terpisah yang direview manual.

## 7. Cara Menjalankan Migration

Supabase CLI tidak tersedia di shell lokal saat audit ini. Jalur manual yang direkomendasikan:

1. Buka Supabase Dashboard.
2. Pilih project `fpiceveqbvgaxdtxxbaq`.
3. Buka SQL Editor.
4. Review isi file `supabase/migrations/20260606155331_fix_advisor_rls_security.sql`.
5. Jalankan seluruh SQL dalam satu transaksi.
6. Jika gagal, jangan retry membabi buta; baca error, rollback transaksi otomatis jika `commit` belum berjalan.
7. Re-run Supabase Advisor.

Jika Supabase CLI sudah diinstal dan login/link sudah siap, user bisa menjalankan manual:

```powershell
supabase --version
supabase link --project-ref fpiceveqbvgaxdtxxbaq
supabase migration list
supabase db lint --linked
```

Catatan penting: `supabase db lint` tanpa flag menargetkan database lokal Supabase CLI di `127.0.0.1:54322`. Jika local Supabase stack tidak sedang berjalan, error `connectex ... actively refused` adalah normal. Untuk audit remote project yang sudah di-link, gunakan `supabase db lint --linked`.

Untuk apply via CLI, review dulu lalu gunakan workflow migration yang berlaku di environment user.

## 8. Cara Validasi Ulang di Supabase Advisor

1. Jalankan migration.
2. Buka Database > Advisors > Security.
3. Jalankan ulang Advisor.
4. Pastikan issue berikut hilang atau turun:
   - `RLS Disabled in Public`
   - `Sensitive Columns Exposed`
   - `Function Search Path Mutable` untuk `public.enqueue_realtime_notification`
5. Jalankan audit query di bawah untuk verifikasi teknis.

## 9. Audit Queries

```sql
-- Check public tables with RLS status
SELECT
schemaname,
tablename,
rowsecurity
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY tablename;
```

```sql
-- Check policies
SELECT
schemaname,
tablename,
policyname,
permissive,
roles,
cmd,
qual,
with_check
FROM pg_policies
WHERE schemaname = 'public'
ORDER BY tablename, policyname;
```

```sql
-- Check grants
SELECT
table_schema,
table_name,
grantee,
privilege_type
FROM information_schema.role_table_grants
WHERE table_schema = 'public'
AND grantee IN ('anon', 'authenticated', 'public')
ORDER BY table_name, grantee, privilege_type;
```

Tambahan untuk sequence:

```sql
SELECT
sequence_schema,
sequence_name,
grantee,
privilege_type
FROM information_schema.role_usage_grants
WHERE sequence_schema = 'public'
AND grantee IN ('anon', 'authenticated', 'public')
ORDER BY sequence_name, grantee, privilege_type;
```

## 10. Catatan Penting Agar Aplikasi Tidak Error

- Laravel backend sebaiknya tetap memakai koneksi server-side Postgres yang tepat, bukan `anon` atau `authenticated`.
- Jika backend memakai role non-owner yang tidak bypass RLS, akses Laravel bisa terdampak karena tidak ada policy untuk tabel admin/internal. Pastikan koneksi backend memakai role server yang sesuai.
- Frontend Supabase saat ini hanya realtime dan tidak menyimpan session Supabase Auth. Jangan membuat policy `auth.uid()` sampai identitas Laravel dan Supabase Auth dipetakan dengan jelas.
- `realtime_notifications` tetap read-only untuk browser. Jika channel error setelah migration, cek policy topic dan kebutuhan realtime.
- Jangan grant ulang `anon`/`authenticated` ke tabel payroll, employee, user, token, session, atau RBAC tanpa desain policy.

## 11. Sisa Issue yang Belum Bisa Diperbaiki Otomatis

- Business-rule policy untuk employee self-service belum dibuat karena tidak ada bukti frontend melakukan CRUD langsung via Supabase Data API.
- Admin/HR Supabase-RBAC belum dibuat karena role aplikasi ada di Laravel/Spatie, bukan Supabase JWT claims.
- Grant lama tidak direstore di rollback karena tidak ada snapshot grant pra-migration yang aman.
- Supabase CLI tidak tersedia lokal, sehingga `supabase db lint` dan `supabase migration list` harus dijalankan manual oleh user setelah CLI tersedia.

## 12. Rekomendasi Next Step

1. Putuskan apakah aplikasi akan tetap backend-only atau akan memakai Supabase Data API untuk sebagian fitur.
2. Jika ingin direct frontend access, desain mapping identitas Laravel user ke Supabase Auth user terlebih dahulu.
3. Buat private RBAC schema/function jika perlu policy admin, dengan `SECURITY DEFINER` hanya di schema non-exposed dan `search_path` eksplisit.
4. Tambahkan migration checklist: setiap tabel public baru wajib punya RLS, grants eksplisit, dan policy minimal.
5. Pertimbangkan memindahkan tabel Laravel internal ke schema private pada fase refactor terpisah.

## 13. Status Setelah Perubahan yang Disiapkan

Belum ada perubahan diterapkan ke remote database. Setelah migration direview dan dijalankan, ekspektasinya:
- Semua tabel public punya RLS aktif.
- Tabel internal/admin/business tidak bisa diakses langsung oleh `anon`/`authenticated`.
- Tabel dengan kolom sensitif tidak lagi exposed tanpa RLS.
- Function realtime punya `search_path` eksplisit.
- Realtime browser tetap hanya bisa membaca topic yang sudah diketahui aplikasi.

## 14. Hasil Verifikasi Lokal

Sudah dijalankan:
- `php artisan route:list` berhasil, 123 routes terbaca.
- `php artisan test` berhasil, 243 tests passed dengan 506 assertions.
- `npm run build` berhasil.
- Static check migration: tidak ada `USING (true)` pada forward migration, tidak ada `DROP TABLE`, `TRUNCATE`, atau `DELETE FROM`.

Tidak dijalankan otomatis:
- `php artisan migrate:status`, karena dapat memakai koneksi remote Supabase dari `.env`.
- `supabase db lint --linked`, karena saat report awal dibuat Supabase CLI belum tersedia di shell lokal. Setelah CLI tersedia, gunakan flag `--linked` untuk remote lint; `supabase db lint` tanpa flag membutuhkan local Supabase database.
- Apply migration remote, karena workflow ini harus direview dulu sesuai aturan safety.
