# MY QR COUNTDOWN OPERATIONAL HOURS REPORT

## 1. Ringkasan fitur yang ditambahkan

Fitur yang ditambahkan pada halaman Employee `my-qr`:

- Countdown realtime menuju refresh QR berikutnya.
- Auto-refresh QR saat countdown mencapai 0.
- QR tidak dibuat dan tidak dirender saat di luar jam operasional.
- Card/tombol "Mengalami kendala absen?" tidak dirender saat di luar jam operasional.
- Empty state profesional saat halaman berada di luar jam operasional.
- Validasi server-side agar scan page, clock-in/out, dan submit pengajuan manual tetap menolak akses di luar jam operasional.

## 2. File halaman `my-qr` yang ditemukan

Halaman aktual:

- `resources/js/Pages/Attendance/MyQr.vue`

Route:

- `GET /my-qr`
- route name: `attendance.my-qr`
- controller: `AttendanceController@myQr`

## 3. Cara sistem menentukan jam operasional

Jam operasional sekarang ditentukan oleh service backend:

- `app/Services/AttendanceOperationalHours.php`

Konfigurasi jam operasional berada di:

- `config/attendance.php`

Nilai default mengikuti logic existing project sebelumnya:

- start: `06:30`
- end: `17:00`
- timezone: `ATTENDANCE_TIMEZONE`, fallback ke `APP_TIMEZONE`, lalu `Asia/Jakarta`

Catatan:

- Sebelum perubahan ini, batas jam operasional tersebar sebagai konstanta di `AttendanceController`.
- Sekarang rule dibuat terpusat agar QR page, scan page, clock-in/out, dan manual attendance memakai sumber yang sama.

## 4. Data backend yang dikirim ke frontend

`AttendanceController::myQr()` sekarang mengirim:

- `attendanceWindow.is_operational_hours`
- `attendanceWindow.server_time`
- `attendanceWindow.operational_start`
- `attendanceWindow.operational_end`
- `attendanceWindow.timezone`
- `attendanceWindow.next_operational_start`
- `attendanceWindow.label`
- `qrRefreshIntervalSeconds`
- `qrExpiresAt`
- `nextQrRefreshAt`

Saat `is_operational_hours = false`, backend tidak mengirim signed QR URL valid:

- `employee.qr_in_url = null`
- `employee.qr_out_url = null`

## 5. Mekanisme countdown refresh QR

Frontend menggunakan `nextQrRefreshAt` dan baseline `attendanceWindow.server_time`.

Mekanisme:

- `now` diinisialisasi dari `server_time`.
- Timer frontend bertambah setiap 1 detik.
- `remainingSeconds` dihitung dari `nextQrRefreshAt - now`.
- Countdown tampil sebagai `QR akan diperbarui dalam MM:SS`.
- Saat countdown mencapai 0, halaman menjalankan `router.reload()` terbatas pada props QR dan attendance window.
- Setelah reload berhasil, `server_time` dan `nextQrRefreshAt` baru akan mereset countdown.
- Jika refresh gagal, muncul pesan ringan dan retry diberi backoff 15 detik agar tidak request terus-menerus.

## 6. Perilaku saat dalam jam operasional

Saat `is_operational_hours = true`:

- QR Clock-In ditampilkan.
- QR Clock-Out tetap mengikuti logic existing `showClockOut`.
- Countdown refresh QR ditampilkan dekat QR.
- Tombol `Refresh QR` tersedia.
- Card "Mengalami kendala absen?" dan tombol manual attendance tersedia.
- Status manual attendance tetap dapat tampil.

## 7. Perilaku saat di luar jam operasional

Saat `is_operational_hours = false`:

- QR code tidak dirender.
- QR card aktif tidak dirender.
- Countdown refresh QR tidak ditampilkan.
- Card "Mengalami kendala absen?" tidak dirender.
- Tombol "Kendala absen? Klik manual di sini!" tidak dirender.
- Modal manual attendance tidak dirender.
- Backend tidak membuat signed QR URL.
- Scan page `scan/in` dan `scan/out` redirect ke `/my-qr` dengan pesan error.
- Submit manual attendance ditolak oleh `ManualAttendanceService`.

Empty state yang tampil:

- title: `Di luar jam operasional absensi`
- description: `QR absensi dan pengajuan kendala absen hanya tersedia pada jam operasional.`
- info: jam operasional dan jam QR tersedia kembali jika tersedia.

## 8. Penyesuaian UI profesional modern minimalis

Perubahan UI:

- Countdown memakai card kecil hijau yang clean dan tidak mengganggu QR.
- Empty state memakai icon clock, card netral, spacing rapi, dan copy singkat.
- Area QR tetap mempertahankan layout compact desktop yang sebelumnya dioptimalkan agar pas satu viewport.
- QR/manual card hanya muncul saat benar-benar aktif.
- Error refresh QR tampil sebagai alert kecil, bukan modal.

## 9. File yang dibuat/diubah

File dibuat:

- `config/attendance.php`
- `app/Services/AttendanceOperationalHours.php`
- `MY_QR_COUNTDOWN_OPERATIONAL_HOURS_REPORT.md`

File diubah:

- `app/Http/Controllers/AttendanceController.php`
- `app/Services/ManualAttendanceService.php`
- `resources/js/Pages/Attendance/MyQr.vue`
- `tests/Feature/AttendanceFeatureTest.php`

File terkait dari perubahan layout sebelumnya tetap digunakan:

- `resources/js/Components/QrCode.vue`
- `resources/js/Layouts/EmployeeLayout.vue`

## 10. Hasil build/lint/test

Hasil check:

- `php -l app/Services/AttendanceOperationalHours.php`
  - berhasil.
- `php -l app/Http/Controllers/AttendanceController.php`
  - berhasil.
- `php -l app/Services/ManualAttendanceService.php`
  - berhasil.
- `php -l config/attendance.php`
  - berhasil.
- `npm run build`
  - berhasil.
- `php artisan route:list`
  - berhasil.
- `vendor/bin/pint --test ...`
  - berhasil setelah auto-fix.
- `php artisan test tests/Feature/AttendanceFeatureTest.php`
  - `12 passed`.
- `php artisan test`
  - `249 passed (524 assertions)`.

Script yang tidak tersedia:

- `npm run lint`
- `npm run format`

`package.json` hanya mendefinisikan:

- `build`
- `dev`

## 11. Error yang ditemukan dan auto-fix yang dilakukan

Error/issue:

- `vendor/bin/pint --test` awalnya menemukan style issue pada `AttendanceController.php`.

Auto-fix:

- Menjalankan Pint terbatas pada file yang diubah.
- Setelah fix, `vendor/bin/pint --test` berhasil.

Error test:

- `php artisan test` awalnya gagal pada 2 test clock-out karena test memakai waktu default test, sedangkan logic baru memakai timezone operational attendance.

Auto-fix:

- `tests/Feature/AttendanceFeatureTest.php` diperbarui dengan helper `travelToOperationalTime()` berbasis timezone attendance.
- Test terkait `my-qr` dan clock-out dibuat eksplisit berada di jam operasional.
- Full suite kemudian berhasil.

## 12. Catatan manual konfigurasi jam operasional

Jam operasional bisa disesuaikan lewat env:

- `ATTENDANCE_TIMEZONE=Asia/Jakarta`
- `ATTENDANCE_OPERATIONAL_START=06:30`
- `ATTENDANCE_OPERATIONAL_END=17:00`
- `ATTENDANCE_QR_REFRESH_INTERVAL_SECONDS=300`

Jika perusahaan ingin fallback 07:00-17:00, ubah `ATTENDANCE_OPERATIONAL_START` menjadi `07:00`.

Tidak ada perubahan database dan tidak ada perubahan payroll calculation.
