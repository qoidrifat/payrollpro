# Tabel Login User Admin, HR, dan Employee

Tanggal pengecekan: 11 Juni 2026  
Database aktif: Supabase PostgreSQL (`DB_CONNECTION=pgsql`)  
Host terverifikasi: `aws-1-ap-south-1.pooler.supabase.com`

## Catatan Keamanan

Password yang dicantumkan di bawah adalah password login yang berhasil diverifikasi terhadap hash password di database Supabase pada 11 Juni 2026.

Catatan penting:

- Database tetap hanya menyimpan password dalam bentuk hash.
- Password pada tabel ini diperoleh dari verifikasi kandidat password seed/default, bukan dari pembacaan balik hash.
- Gunakan data ini hanya untuk kebutuhan development/testing internal.
- Untuk environment publik/produksi, segera ganti semua password default.

## Akun Admin

| User ID | Nama Login | Email/Gmail Login | Password Login | Role | Status Akun | Email | Employee Terhubung | Jabatan | Departemen | Status Employee |
|---:|---|---|---|---|---|---|---|---|---|---|
| 1 | Admin | admin@project-kp.test | `password` | Admin | active | verified | - | - | - | - |

## Akun HR

| User ID | Nama Login | Email/Gmail Login | Password Login | Role | Status Akun | Email | Employee Terhubung | Jabatan | Departemen | Status Employee |
|---:|---|---|---|---|---|---|---|---|---|---|
| 2 | HR Manager | hr@project-kp.test | `password` | HR | active | verified | - | - | - | - |
| 10 | Maya Anggraini | maya.anggraini.8@project-kp.test | `password` | HR | active | verified | Maya Anggraini | Finance & HR | Operations | active |

## Akun Employee

| User ID | Nama Login | Email/Gmail Login | Password Login | Role | Status Akun | Email | Employee ID | Employee Terhubung | Jabatan | Departemen | Status Employee |
|---:|---|---|---|---|---|---|---:|---|---|---|---|
| 3 | Ahmad Fauzi | ahmad.fauzi.1@project-kp.test | `password` | Employee | active | verified | 1 | Ahmad Fauzi | Senior Developer | Engineering | active |
| 4 | Rina Kusuma | rina.kusuma.2@project-kp.test | `password` | Employee | active | verified | 2 | Rina Kusuma | UI/UX Designer | Design | active |
| 5 | Budi Santoso | budi.santoso.3@project-kp.test | `password` | Employee | active | verified | 3 | Budi Santoso | Junior Developer | Engineering | active |
| 6 | Dewi Lestari | dewi.lestari.4@project-kp.test | `password` | Employee | active | verified | 4 | Dewi Lestari | Project Manager | Management | active |
| 7 | Eko Prasetyo | eko.prasetyo.5@project-kp.test | `password` | Employee | active | verified | 5 | Eko Prasetyo | System Administrator | Infrastructure | active |
| 8 | Siti Nurhaliza | siti.nurhaliza.6@project-kp.test | `password` | Employee | active | verified | 6 | Siti Nurhaliza | Content Writer | Marketing | active |
| 9 | Hendra Wijaya | hendra.wijaya.7@project-kp.test | `password` | Employee | active | verified | 7 | Hendra Wijaya | Digital Marketer | Marketing | active |
| 13 | Clara Melani | jessica83@example.net | `password` | Employee | pending | verified | 9 | Hafshah Sihombing | Wartawan | Finance | active |

## Akun di Luar Role Admin/HR/Employee

Akun berikut ikut terdeteksi di tabel `users`, tetapi tidak termasuk scope role Admin, HR, atau Employee.

| User ID | Nama Login | Email/Gmail Login | Password Login | Role | Status Akun | Email | Catatan |
|---:|---|---|---|---|---|---|---|
| 11 | Demo User | demo@payrollpro.test | `demo2025` | Demo | active | unverified | Akun demo, bukan Admin/HR/Employee |
| 12 | Budi Sasmito | budisasmito@payrollpro.com | `password` | - | active | unverified | Belum memiliki role Spatie |

## Ringkasan

| Role | Jumlah |
|---|---:|
| Admin | 1 |
| HR | 2 |
| Employee | 8 |
| Di luar scope Admin/HR/Employee | 2 |

## Catatan Data

- User ID 13 menggunakan nama login `Clara Melani`, tetapi terhubung ke employee `Hafshah Sihombing`.
- User ID 13 masih berstatus akun `pending`, walaupun employee terkait aktif.
- User ID 12 belum memiliki role Spatie, sehingga belum masuk kategori Admin, HR, atau Employee.
- Akun dengan status `pending` kemungkinan belum bisa login normal jika middleware login aplikasi memblokir akun non-active.
