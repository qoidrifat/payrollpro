# Laporan Penyesuaian Gaji UMK IT Bangkalan ke Supabase

Tanggal eksekusi: 11 Juni 2026  
Database aktif: Supabase PostgreSQL (`DB_CONNECTION=pgsql`)

## 1. Ringkasan

Data gaji employee, komponen gaji, dan payroll item di database Supabase sudah disesuaikan ulang berdasarkan:

- UMK Kabupaten Bangkalan 2026: Rp2.550.274.
- Benchmark pasar gaji IT Indonesia 2026 untuk role software engineering, UI/UX, system administrator, dan role pendukung.
- Prinsip aman HR: gaji existing tidak diturunkan. Jika benchmark target lebih rendah dari gaji saat ini, nominal existing dipertahankan.

## 2. Sumber Acuan

- SK Gubernur Jawa Timur No. 100.3.3.1/937/013/2025 tentang UMK Jawa Timur 2026.
- Data publik pasar gaji IT Indonesia 2026 sebagai pembanding kewajaran role.

## 3. Kebijakan Penyesuaian

- Semua base salary harus berada di atas UMK Bangkalan 2026.
- Role IT/core product dinaikkan mengikuti benchmark lokal yang lebih realistis daripada sekadar UMK.
- Role non-IT tetap disesuaikan agar konsisten dengan struktur perusahaan dan tidak di bawah UMK.
- Tidak ada penurunan gaji pada employee yang sudah memiliki salary lebih tinggi dari target.
- Tunjangan transport dihitung 5% dari base salary.
- Tunjangan makan dihitung 3% dari base salary.
- Tunjangan komunikasi existing tetap dipertahankan pada employee yang sebelumnya sudah memilikinya.
- Potongan existing seperti `Pinjaman Koperasi` tetap dipertahankan.

## 4. Data Employee yang Disesuaikan

| ID | Employee | Role | Department | Salary Baru |
|---:|---|---|---|---:|
| 1 | Ahmad Fauzi | Senior Developer | Engineering | Rp8.250.000 |
| 2 | Rina Kusuma | UI/UX Designer | Design | Rp4.750.000 |
| 3 | Budi Santoso | Junior Developer | Engineering | Rp4.850.000 |
| 4 | Dewi Lestari | Project Manager | Management | Rp8.750.000 |
| 5 | Eko Prasetyo | System Administrator | Infrastructure | Rp5.600.000 |
| 6 | Siti Nurhaliza | Content Writer | Marketing | Rp3.450.000 |
| 7 | Hendra Wijaya | Digital Marketer | Marketing | Rp3.950.000 |
| 8 | Maya Anggraini | Finance & HR | Operations | Rp4.100.000 |
| 9 | Hafshah Sihombing | Wartawan | Finance | Rp4.531.414 |

Catatan: Hafshah Sihombing tidak diturunkan karena salary existing sudah di atas target role dan di atas UMK.

## 5. Sinkronisasi Komponen Gaji

Komponen `Tunjangan Transport` dan `Tunjangan Makan` diperbarui untuk semua employee aktif berdasarkan salary baru. Employee baru yang belum memiliki komponen standar juga dibuatkan komponen tersebut.

## 6. Sinkronisasi Payroll dan PPh21

Payroll item existing pada periode Januari-Mei 2026 sudah dihitung ulang menggunakan salary dan komponen terbaru.

Bonus historis Januari 2026 tetap dipertahankan dari backup agar payroll Januari tidak kehilangan komponen satu kali yang memang sudah ada.

Hasil total payroll setelah sinkronisasi:

| Periode | Employee | Gross | PPh21 Total | Net |
|---|---:|---:|---:|---:|
| Januari 2026 | 8 | Rp66.546.000 | Rp1.358.015,80 | Rp62.099.396,20 |
| Februari 2026 | 8 | Rp47.796.000 | Rp340.674 | Rp45.043.486 |
| Maret 2026 | 8 | Rp47.796.000 | Rp340.674 | Rp45.043.486 |
| April 2026 | 8 | Rp47.796.000 | Rp340.674 | Rp45.043.486 |
| Mei 2026 | 8 | Rp47.796.000 | Rp340.674 | Rp45.043.486 |

## 7. Mengapa Sebagian PPh21 Tetap Rp0

PPh21 Rp0 masih wajar untuk beberapa employee karena penghasilan kena pajak tahunan setelah PTKP, biaya jabatan, dan potongan BPJS masih tidak melewati batas pajak. Nilai Rp0 bukan lagi karena data payroll tidak sinkron.

Pada payroll Mei 2026:

- Ahmad Fauzi: PPh21 Rp137.805.
- Rina Kusuma: PPh21 Rp8.415.
- Budi Santoso: PPh21 Rp13.329.
- Dewi Lestari: PPh21 Rp181.125.
- Eko Prasetyo, Siti Nurhaliza, Hendra Wijaya, dan Maya Anggraini masih Rp0 karena masih di bawah basis pajak setelah perhitungan PTKP.

## 8. Backup

Backup sebelum perubahan dibuat di:

`storage/app/backups/supabase_bangkalan_umk_it_salary_before_20260611-014239.json`

Backup ini memuat snapshot:

- `employees`
- `salary_components`
- `payrolls`
- `payroll_items`

## 9. Verifikasi

Perintah yang sudah dijalankan:

- `php artisan route:list`
- `php artisan test tests\Unit\Services\PayrollCalculatorTest.php tests\Unit\Services\TaxCalculatorTest.php`
- `supabase db lint --linked`

Hasil:

- Route list berhasil.
- 26 test payroll/tax passed.
- Supabase linked lint: `No schema errors found`.

## 10. Catatan

- Tidak ada perubahan schema atau migration.
- Tidak ada perubahan file `.env`.
- Tidak ada perubahan logic aplikasi.
- Perubahan dilakukan langsung ke database Supabase melalui koneksi Laravel yang aktif.
- Payroll historis Januari-Mei 2026 tetap memiliki 8 item sesuai data existing; employee ke-9 tidak ditambahkan ke payroll historis secara otomatis agar tidak mengubah histori tanpa aturan join/payroll eksplisit.
