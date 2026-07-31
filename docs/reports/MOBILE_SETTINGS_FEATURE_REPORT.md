# MOBILE_SETTINGS_FEATURE_REPORT.md — PayrollPro

**Tanggal:** 13 Juni 2026
**Engineer:** AI Full-Stack Engineer (Codebuff)

---

## 1. Ringkasan Analisis Sistem

Setelah melakukan analisis menyeluruh terhadap kodebase PayrollPro, ditemukan:

### Struktur Settings Existing:
- **Table `settings`** : key (unique), value, group, type, timestamps
- **Model** : `App\Models\Setting` — dengan caching (`Setting::getValue()`)
- **Repository** : `SettingRepository` — dengan cache key `setting:{$key}` dan group cache
- **Service** : `SettingService` — `get()`, `set()`, `getCompanySettings()`, `updateCompanySettings()`
- **Controller** : `SettingController` — index, update, updateBpjs, updatePph21
- **Route** : `/settings` (Admin only, `manage-settings` permission)
- **Vue Page** : `resources/js/Pages/Settings/Index.vue` — desktop-oriented dengan tabel BPJS/PPh21
- **Config Attendance** : `config/attendance.php` — operational hours, QR refresh interval (env-based)

### Findings:
| Item | Status |
|------|--------|
| Settings table sudah ada | ✅ `settings` (key-value) |
| Caching settings | ✅ SettingRepository & Setting model |
| Layout mobile-ready | ✅ AuthenticatedLayout.vue (sidebar collapsible, mobile overlay) |
| Settings UI mobile-friendly | ❌ Desktop-oriented tables, tidak mobile-first |
| Role-based access | ❌ Hanya Admin, belum ada HR/Employee settings |
| Operational hours dari DB | ❌ Hanya dari config file (env) |
| Notification preferences | ❌ Belum ada |

---

## 2. Struktur Settings Existing

### Database: `settings` table
```sql
id          BIGINT PRIMARY KEY
key         VARCHAR(255) UNIQUE
value       TEXT NULLABLE
group       VARCHAR(255) DEFAULT 'general'
type        VARCHAR(255) DEFAULT 'text'
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Cache Keys:
| Key Pattern | Source | Digunakan Oleh |
|-----------|--------|----------------|
| `setting:{$key}` | SettingRepository::get() | SettingService |
| `setting:value:{$key}` | Setting::getValue() | Setting model, AttendanceOperationalHours |
| `settings:group:{$group}` | SettingRepository::getByGroup() | SettingService |

### Existing Settings Data:
- `company_name`, `company_address`, `company_phone`, `company_npwp` (group: company)
- BPJS Config (table `bpjs_configs`)
- PPh21 Config (table `pph21_configs`)

---

## 3. Role dan Akses Settings

| Role | Sebelum | Sesudah |
|------|---------|---------|
| **Admin** | ✅ Semua settings | ✅ Profil Perusahaan, Operasional Absensi, Penggajian & Pajak, Notifikasi |
| **HR** | ❌ Tidak bisa akses | ✅ Operasional Absensi, Notifikasi |
| **Employee** | ❌ Tidak bisa akses | ✅ Notifikasi (personal preferences) |

Routes diupdate dengan role-based middleware:
```
GET  /settings                          → settings.index       (auth + verified)
PUT  /settings                          → settings.update       (auth + verified + role:Admin)
PUT  /settings/attendance               → settings.attendance.update (auth + verified + role:Admin|HR)
PUT  /settings/notifications            → settings.notifications.update (auth + verified)
PUT  /settings/bpjs                     → settings.bpjs.update   (auth + verified + role:Admin)
PUT  /settings/pph21                    → settings.pph21.update  (auth + verified + role:Admin)
```

---

## 4. Fitur Settings yang Dibuat/Diupload

### A. Profil Perusahaan (Admin)
- Nama perusahaan
- Alamat
- Telepon
- Email (baru ditambahkan)
- NPWP

### B. Operasional Absensi (Admin & HR)
- **Jam Mulai Operasional** — setting waktu mulai absensi (default: 06:30)
- **Jam Selesai Operasional** — setting waktu selesai absensi (default: 17:00)
- **Interval Refresh QR** — setting interval refresh QR code dalam detik (default: 300)
- **Zona Waktu** — timezone operasional (default: Asia/Jakarta)
- **Integrasi** : AttendanceOperationalHours service membaca dari DB settings dengan fallback ke config

### C. Penggajian & Pajak (Admin)
- Tarif BPJS (read/edit) — existing, dipertahankan
- Bracket PPh21 (read/edit) — existing, dipertahankan

### D. Notifikasi (Semua Role)
- **Notifikasi Email** — toggle on/off
- **Notifikasi In-App** — toggle on/off
- **Personal preferences** — per-user settings untuk Employee/HR, global untuk Admin

---

## 5. Penyesuaian Mobile Chrome

### Mobile-First Design:
| Fitur | Implementasi |
|-------|-------------|
| Single column layout mobile | ✅ Flex column pada <768px, flex row pada >=768px |
| Navigation cards | ✅ Button list dengan icon, active state, hover state |
| Full-width input fields | ✅ Setiap input w-full |
| Large touch targets | ✅ Button padding 12px, input padding 12px |
| Bottom spacing | ✅ pb-24 (96px) agar tombol tidak tertutup browser bar |
| No horizontal overflow | ✅ Overflow-x-auto untuk tabel, min-w pada konten |
| Sticky save buttons | ✅ Tidak sticky — posisi di bottom form agar natural discroll |
| Scroll to top on section change | ✅ window.scrollTo({ top: 0, behavior: 'smooth' }) |
| Toast notification via Teleport | ✅ Fixed position top-right, slide animation |
| Dark mode support | ✅ Dark variants on all elements |

### Responsive Breakpoints:
- **Mobile** (360-414px): Single column, card navigation, stacked buttons
- **Tablet** (768px): Two-column navigation + content, grid for form fields
- **Desktop** (1024px+): Sidebar navigation (sticky), wider content area

### Mobile Testing Checklist:
- [x] Tidak ada horizontal scroll
- [x] Button minimal 44px touch target
- [x] Input tidak terlalu kecil (py-3)
- [x] Card spacing lega (p-5 md:p-6)
- [x] Save button full-width on mobile
- [x] Sidebar tidak memaksa tampil di mobile (overlay)
- [x] Form mudah discroll
- [x] Loading state tidak freeze (spinner SVG)
- [x] Tabel BPJS/PPh21 overflow-x-auto dengan scroll

---

## 6. Desain UI/UX Profesional Modern Minimalis

### Design Tokens:
- **Cards**: White bg, rounded-2xl, shadow-sm, border-gray-200
- **Header**: Icon circle + title + description
- **Navigation**: Button with icon, active state (primary-50 bg, primary-700 text)
- **Inputs**: Rounded-xl, border-gray-300, focus:ring-primary-500, py-3
- **Buttons**: Rounded-xl, text-sm font-semibold, shadow-sm
- **Toasts**: Fixed top-right, backdrop-blur-md, slide animation
- **Dark mode**: Full dark variant support on all elements

### Color Palette:
- Primary: primary-600 (button), primary-50 (active nav bg)
- Text: gray-900 (headings), gray-500 (descriptions)
- Accent per section: primary (company), amber (attendance), emerald (BPJS), purple (PPh21), sky (notifications)

---

## 7. Database/Migration yang Dibuat

Tidak ada migration baru. Semua data settings disimpan di table `settings` yang sudah ada.

**Data default** di-set melalui `SettingService::getAttendanceSettings()` dengan fallback ke `config('attendance.*')`.

---

## 8. Route/Controller/Service/Model yang Dibuat atau Diubah

### File Baru:
| File | Deskripsi |
|------|-----------|
| `app/Http/Requests/UpdateAttendanceSettingsRequest.php` | Validasi jam operasional, QR interval, timezone |
| `app/Http/Requests/UpdateNotificationSettingsRequest.php` | Validasi toggle notifikasi |

### File Diubah:
| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/SettingController.php` | Role-based sections, index untuk semua role, metode updateAttendance, updateNotifications |
| `app/Services/SettingService.php` | getAttendanceSettings, updateAttendanceSettings, getNotificationSettings (dengan userId), updateNotificationSettings |
| `app/Repositories/SettingRepository.php` | Set juga invalidate cache key `setting:value:{$key}` (fix cache consistency) |
| `app/Services/AttendanceOperationalHours.php` | Baca dari Setting::getValue() dengan fallback ke config |
| `routes/web.php` | Route settings dengan role-based middleware |
| `resources/js/Pages/Settings/Index.vue` | Rewrite total: mobile-first, role-based sections, toast |

---

## 9. Validasi dan Security

### Backend Validation:
| Request Class | Rules |
|--------------|-------|
| `UpdateAttendanceSettingsRequest` | `operational_start`: required, regex HH:MM; `operational_end`: required, regex HH:MM; `qr_refresh_interval`: required, integer, min:30, max:3600; `timezone`: required, string |
| `UpdateNotificationSettingsRequest` | `email_notifications`: boolean; `in_app_notifications`: boolean |
| `UpdateSettingRequest` (existing) | `company_name`: required, max:255; `company_address`: required, max:500; etc. |

### Role Authorization:
| Endpoint | Middleware | Controller Check |
|----------|-----------|-----------------|
| GET /settings | auth, verified | Role-based data filtering |
| PUT /settings | role:Admin | Gate::authorize('manage-settings') |
| PUT /settings/attendance | role:Admin|HR | hasAnyRole(['Admin', 'HR']) |
| PUT /settings/notifications | auth, verified | — (all roles) |
| PUT /settings/bpjs | role:Admin | Gate::authorize('manage-settings') |
| PUT /settings/pph21 | role:Admin | Gate::authorize('manage-settings') |

### Security Measures:
- ✅ Semua route pakai `auth` middleware
- ✅ Role-based middleware dengan Spatie Permission
- ✅ Defense-in-depth: controller juga cek role
- ✅ CSRF protection (Inertia form + PUT requests)
- ✅ Validasi input dengan FormRequest
- ✅ Tidak ada sensitive config di settings
- ✅ Employee tidak bisa akses global settings

---

## 10. Integrasi dengan Attendance/My-QR

AttendanceOperationalHours service sekarang membaca dari dua sumber:

```
Setting::getValue('attendance_operational_start')
    ?? config('attendance.operational_hours.start', '06:30')
```

**Alur integrasi:**
1. Admin/HR buka Settings → Operasional Absensi
2. Ubah jam operasional / QR refresh interval
3. Simpan → data disimpan di table `settings`
4. AttendanceOperationalHours service membaca data baru
5. Halaman `my-qr`, scan, clock-in/out menggunakan data terbaru

**Cache invalidation** : `SettingRepository::set()` membersihkan cache key `setting:value:{$key}` sehingga data langsung生效.

---

## 11. Cache Bug Fix

**Bug ditemukan**: `SettingRepository::set()` hanya invalidate cache key `setting:{$key}`, sedangkan `Setting::getValue()` dan `AttendanceOperationalHours` membaca dari cache key `setting:value:{$key}`.

**Fix**: `SettingRepository::set()` sekarang juga invalidate `setting:value:{$key}`.

---

## 12. Optimasi Performa

- ✅ Settings di-cache dengan TTL 3600 detik
- ✅ Cache di-invalidate saat data diupdate
- ✅ SettingService::getAttendanceSettings() menggunakan fallback config (tidak perlu query jika belum diset)
- ✅ Inertia props minimal — hanya kirim data sesuai role
- ✅ Tidak ada N+1 query
- ✅ Vue components menggunakan computed properties

---

## 13. File yang Dibuat/Diubah

### Files Created (2):
- `app/Http/Requests/UpdateAttendanceSettingsRequest.php`
- `app/Http/Requests/UpdateNotificationSettingsRequest.php`

### Files Modified (6):
- `app/Http/Controllers/SettingController.php`
- `app/Services/SettingService.php`
- `app/Repositories/SettingRepository.php`
- `app/Services/AttendanceOperationalHours.php`
- `routes/web.php`
- `resources/js/Pages/Settings/Index.vue`

---

## 14. Hasil Build/Test/Lint

| Check | Status |
|-------|--------|
| `php artisan test --testsuite=Feature` | ✅ 14/15 passing (1 pre-existing failure) |
| `npm run build` | ✅ Built in 8.47s, no errors |
| `php artisan route:list` | ✅ Settings routes registered correctly |
| `php artisan config:clear` | ✅ Success |
| `php artisan cache:clear` | ✅ Success |

---

## 15. Error yang Ditemukan dan Auto-Fix

### Bug 1: Cache Key Mismatch (auto-fix)
- **Masalah**: `SettingRepository::set()` hanya invalidate `setting:{$key}`, sementara `AttendanceOperationalHours` membaca dari `setting:value:{$key}`
- **Fix**: Tambahkan `Cache::forget("setting:value:{$key}")` di `SettingRepository::set()`

### Bug 2: Toast CSS Scoped + Teleport (auto-fix)
- **Masalah**: `<style scoped>` tidak apply ke element yang di-teleport ke `<body>`
- **Fix**: Ubah `scoped` ke unscoped `<style>` untuk toast transitions

### Bug 3: Personal Notification Preferences (auto-fix)
- **Masalah**: `getNotificationSettings()` selalu baca global key, tidak membaca per-user key
- **Fix**: Tambahkan parameter `$userId`, prioritaskan personal key over global key

### Pre-existing: AttendanceFeatureTest clockOut
- Test `test_first_clock_out_returns_success_and_dispatches_event` gagal (sudah sebelum perubahan)
- Tidak terkait dengan fitur Settings

---

## 16. Catatan Manual

### Yang Perlu Validasi Manual:
1. **Attendance operational hours**: Setelah mengubah jam operasional di Settings, buka halaman `my-qr` untuk verifikasi countdown QR sesuai jam baru.
2. **Cache**: Pastikan `config:clear` atau `cache:clear` dijalankan setelah deployment agar cache keys sinkron.
3. **Role permissions**: Pastikan role HR sudah memiliki permission `manage-attendance` jika ingin mengakses settings absensi.

### Yang Tidak Berubah:
- ✅ Fitur payroll — tidak disentuh
- ✅ Fitur attendance (clock-in/out, QR) — diintegrasikan tapi tidak diubah logic-nya
- ✅ Perhitungan BPJS/PPh21 — tidak berubah
- ✅ Data employee — tidak berubah
- ✅ Security — ditingkatkan (role checks)

---

*Laporan ini dibuat oleh AI Full-Stack Engineer (Codebuff) untuk fitur Settings PayrollPro.*
