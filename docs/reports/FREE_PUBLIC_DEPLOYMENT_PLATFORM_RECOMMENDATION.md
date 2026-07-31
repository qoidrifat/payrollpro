# Tutorial Deployment Gratis PayrollPro ke Public

Tanggal: 11 Juni 2026  
Project: PayrollPro Laravel 12 + Inertia/Vue + Supabase PostgreSQL  
Target pembaca: pemula yang ingin membuat aplikasi bisa dibuka public dengan biaya Rp0.

## 1. Kesimpulan Cepat

Sistem PayrollPro bisa dibuat public, tetapi **GitHub saja tidak cukup**.

GitHub hanya menyimpan source code. Agar aplikasi Laravel berjalan dan bisa dibuka lewat internet, butuh server atau deployment platform.

Untuk modal akun Google dan biaya awal Rp0, opsi paling realistis untuk demo public adalah:

```text
GitHub
  + Render Free Web Service
  + Supabase Free PostgreSQL
```

Rekomendasi utama:

| Kebutuhan | Platform |
|---|---|
| Menyimpan source code | GitHub |
| Menjalankan Laravel secara public | Render Free Web Service |
| Database PostgreSQL | Supabase Free |
| URL public sementara | Subdomain Render `.onrender.com` |
| Login awal | Akun Google/GitHub |

## 2. Hal yang Harus Dipahami

### 2.1 GitHub Itu Bukan Server Laravel

GitHub cocok untuk:

- Menyimpan kode.
- Membuat repository private/public.
- Melacak perubahan.
- Menjadi sumber auto-deploy ke Render, Koyeb, Vercel, Netlify, atau server lain.

GitHub tidak cocok untuk:

- Menjalankan Laravel full-stack.
- Menjalankan PHP-FPM/Nginx.
- Menjalankan queue worker.
- Menjalankan scheduler Laravel.
- Menyimpan `.env` production.

### 2.2 Deployment Platform Itu Apa

Deployment platform adalah layanan yang menjalankan aplikasi dari repository kamu.

Untuk PayrollPro, platform harus mampu menjalankan:

- PHP Laravel.
- Composer dependencies.
- Node/Vite build untuk Inertia/Vue.
- Koneksi PostgreSQL ke Supabase.
- Public URL.

## 3. Kondisi Khusus Project PayrollPro

Project ini memakai:

- Laravel 12.
- Inertia/Vue.
- Supabase PostgreSQL sebagai database.
- Route health public: `/api/health`.
- API/mobile route berada di `routes/web.php`, bukan `routes/api.php`.
- Browser-side Supabase hanya dipakai terbatas untuk Realtime, bukan CRUD umum.
- Docker deployment perlu ekstensi PostgreSQL PHP seperti `pdo_pgsql` dan dependency `libpq-dev`.

## 4. Checklist Wajib Sebelum Deploy Public

Sebelum push ke GitHub atau deploy ke platform apa pun:

1. Pastikan `.env` tidak ikut di-commit.
2. Pastikan `.gitignore` memblokir `.env`, `storage/logs`, dan file backup sensitif.
3. Ganti password default seperti `password` dan `demo2025`.
4. Rotate/ganti secret yang pernah terlihat di local/report.
5. Set production:

```env
APP_ENV=production
APP_DEBUG=false
```

6. Pastikan `APP_KEY` production dibuat khusus.
7. Pastikan credential Supabase dimasukkan ke dashboard platform, bukan ke repository.
8. Pastikan route Admin/HR/Employee tetap dilindungi auth dan role.
9. Pastikan Supabase RLS/security sudah dicek ulang sebelum benar-benar dipakai public.
10. Jangan menganggap storage lokal Render/Koyeb sebagai penyimpanan permanen.

## 5. Platform Utama: GitHub

### 5.1 Fungsi GitHub

GitHub dipakai untuk:

- Menyimpan source code PayrollPro.
- Menghubungkan repo ke Render/Koyeb/Vercel/Netlify.
- Auto deploy setiap ada push ke branch utama.
- Menjadi backup source code.

### 5.2 Buat Repository dari Website GitHub

1. Buka https://github.com.
2. Login dengan akun Google/GitHub.
3. Klik tombol `+` di kanan atas.
4. Pilih `New repository`.
5. Isi nama repository, contoh:

```text
payrollpro
```

6. Pilih visibility:

| Opsi | Kapan Dipakai |
|---|---|
| Private | Disarankan untuk project payroll karena ada logic bisnis internal |
| Public | Hanya jika memang ingin source code terbuka |

7. Jangan centang `Add README`, `Add .gitignore`, atau `Choose a license` jika repo lokal sudah punya file tersebut.
8. Klik `Create repository`.

### 5.3 Push Project Lokal ke GitHub

Jalankan dari root project:

```bash
git status
```

Pastikan `.env` tidak muncul sebagai file yang akan di-commit.

Jika belum ada remote:

```bash
git remote add origin https://github.com/USERNAME/payrollpro.git
```

Commit dan push:

```bash
git add .
git commit -m "Prepare PayrollPro for public deployment"
git branch -M main
git push -u origin main
```

Jika repository sudah punya remote, cek dulu:

```bash
git remote -v
```

### 5.4 Catatan Aman untuk GitHub

Jangan pernah commit:

- `.env`
- database dump berisi data asli
- file backup dari `storage/app/backups`
- credential Supabase
- private key SSH
- access token
- password user

## 6. Platform Database: Supabase Free

### 6.1 Fungsi Supabase

Supabase dipakai sebagai database PostgreSQL managed.

Untuk PayrollPro, Supabase menggantikan MySQL lokal dan sudah dipakai oleh `.env` saat ini.

### 6.2 Buat Project Supabase

1. Buka https://supabase.com.
2. Login menggunakan akun Google/GitHub.
3. Klik `New project`.
4. Pilih organization.
5. Isi nama project, contoh:

```text
payrollpro-production
```

6. Buat database password yang kuat.
7. Pilih region terdekat atau region yang tersedia.
8. Klik `Create new project`.

### 6.3 Ambil Connection String Supabase

1. Buka dashboard Supabase project.
2. Klik `Connect`.
3. Pilih connection string PostgreSQL.
4. Untuk Laravel server-side, gunakan pooler/session connection yang cocok untuk aplikasi backend.
5. Catat:

```text
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

Contoh format Laravel:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-south-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxxxxxxxxxxxxxxxxx
DB_PASSWORD=ISI_PASSWORD_SUPABASE
```

### 6.4 Migrasi Database ke Supabase

Jika Supabase masih kosong:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Jika data dari MySQL lokal sudah pernah dimigrasikan:

```bash
php artisan migrate:status
```

Lalu verifikasi jumlah data penting:

```bash
php artisan tinker
```

Contoh pengecekan di Tinker:

```php
DB::table('users')->count();
DB::table('employees')->count();
DB::table('payroll_items')->count();
DB::table('manual_attendance_requests')->count();
```

### 6.5 Cek Supabase dari CLI

Jika Supabase CLI sudah login dan linked:

```bash
supabase migration list
supabase db lint --linked
```

Catatan:

- `supabase db lint --linked` mengecek remote Supabase.
- `supabase db lint` tanpa `--linked` bisa mengarah ke Supabase lokal.

### 6.6 Catatan Keamanan Supabase

Sebelum public:

- Jangan expose table internal ke `anon`/`authenticated` tanpa RLS yang benar.
- Jangan expose `users.password`, token, reset password token, atau table internal Laravel.
- Jika frontend memakai Supabase Realtime, batasi hanya table/topik yang memang aman.
- Jalankan Advisor/Security check dari dashboard Supabase.

## 7. Platform Utama Hosting: Render Free

Render adalah pilihan paling realistis jika ingin biaya Rp0 dan modal akun Google/GitHub.

### 7.1 Fungsi Render

Render menjalankan aplikasi Laravel dan memberi URL public seperti:

```text
https://payrollpro.onrender.com
```

### 7.2 Kelebihan Render Free

- Bisa deploy dari GitHub.
- Bisa menjalankan Docker.
- Bisa mendapatkan URL public gratis.
- Tidak perlu mengelola VPS manual.
- Cocok untuk demo dan testing public.

### 7.3 Batasan Render Free

- Free service bisa sleep saat idle.
- Request pertama setelah idle bisa lambat.
- Resource terbatas.
- Tidak cocok untuk production serius.
- Queue worker dan scheduler perlu strategi tambahan.
- Storage lokal tidak permanen.

### 7.4 Persiapan File untuk Render

Pastikan project punya:

- `Dockerfile`
- `.dockerignore`
- `composer.json`
- `package.json`
- `vite.config.js`
- `.env.example`

Docker image Laravel harus memasang:

- PHP extensions yang dibutuhkan Laravel.
- `pdo_pgsql`.
- dependency PostgreSQL seperti `libpq-dev`.
- Composer dependencies.
- Node build assets.

### 7.5 Contoh Environment Variables Render

Isi di dashboard Render, bukan di GitHub:

```env
APP_NAME=PayrollPro
APP_ENV=production
APP_KEY=base64:ISI_APP_KEY_PRODUCTION
APP_DEBUG=false
APP_URL=https://payrollpro.onrender.com

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=aws-1-ap-south-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxxxxxxxxxxxxxxxxx
DB_PASSWORD=ISI_PASSWORD_SUPABASE

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
```

Jika memakai Supabase Realtime dari frontend:

```env
VITE_SUPABASE_URL=https://PROJECT_REF.supabase.co
VITE_SUPABASE_ANON_KEY=ISI_ANON_KEY
```

Jangan isi `service_role` key di frontend.

### 7.6 Deploy ke Render dari GitHub

1. Buka https://render.com.
2. Login pakai GitHub/Google.
3. Klik `New`.
4. Pilih `Web Service`.
5. Connect GitHub repository PayrollPro.
6. Pilih branch:

```text
main
```

7. Pilih runtime/deploy type:

```text
Docker
```

8. Pilih plan:

```text
Free
```

9. Isi environment variables.
10. Klik `Create Web Service`.
11. Tunggu build selesai.
12. Buka URL `.onrender.com`.

### 7.7 Command Setelah Deploy

Jika Render shell tersedia, jalankan:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika app sudah memakai database Supabase yang sudah dimigrasikan, jangan menjalankan seeder sembarangan.

### 7.8 Test Setelah Deploy Render

Buka:

```text
https://NAMA-APP.onrender.com/api/health
```

Jika sehat, lanjut test:

1. Login Admin.
2. Login HR.
3. Login Employee.
4. Buka dashboard.
5. Buka `my-qr`.
6. Buka payroll.
7. Cek manual attendance request.

### 7.9 Queue dan Scheduler di Render Free

Untuk demo:

- Bisa sementara gunakan `QUEUE_CONNECTION=sync`.
- Scheduler bisa tidak aktif jika fitur demo tidak memerlukan job terjadwal.

Untuk setup lebih rapi:

- Gunakan service worker terpisah jika plan/platform mendukung.
- Gunakan cron job eksternal untuk memanggil endpoint scheduler internal yang aman.

Namun untuk biaya Rp0, jangan mengharapkan setup queue/scheduler production-grade.

## 8. Alternatif 1: Koyeb

Koyeb bisa menjalankan web app dari GitHub atau Docker, tetapi status free tier dan kebutuhan verifikasi bisa berubah.

### 8.1 Kapan Memilih Koyeb

Pilih Koyeb jika:

- Render tidak cocok.
- Ingin mencoba platform lain yang juga mendukung Docker.
- Siap menghadapi kemungkinan verifikasi kartu/payment method.

### 8.2 Deploy ke Koyeb dari GitHub

1. Buka https://www.koyeb.com.
2. Daftar/login.
3. Klik `Create Web Service`.
4. Pilih `GitHub`.
5. Connect repository PayrollPro.
6. Pilih branch `main`.
7. Pilih Dockerfile deployment jika tersedia.
8. Isi environment variables sama seperti Render.
9. Pilih instance free jika tersedia.
10. Deploy.
11. Buka URL public dari Koyeb.

### 8.3 Catatan Koyeb

- Pastikan membaca halaman pricing terbaru sebelum deploy.
- Jika meminta kartu/verifikasi payment, berarti tidak lagi memenuhi syarat “modal akun Google saja”.
- Laravel tetap perlu `pdo_pgsql` jika connect ke Supabase.
- Queue/scheduler tetap perlu service terpisah atau workaround.

## 9. Alternatif 2: Oracle Cloud Always Free

Oracle Cloud Always Free lebih cocok untuk jangka panjang karena memberi VM, tetapi biasanya tidak cukup hanya akun Google. Umumnya perlu verifikasi kartu debit/kredit.

### 9.1 Kapan Memilih Oracle Cloud

Pilih Oracle jika:

- Ingin server yang lebih permanen.
- Siap setup Linux/VPS.
- Siap install Docker, Nginx, SSL, dan panel deploy.
- Punya metode verifikasi yang diterima Oracle.

### 9.2 Arsitektur Oracle yang Disarankan

```text
Oracle Cloud Always Free VM
    |
    |-- Docker
    |-- Coolify atau Dokploy
    |-- PayrollPro Laravel container
    |-- Queue worker container
    |-- Scheduler/cron
    |
    v
Supabase PostgreSQL
```

### 9.3 Tutorial Oracle dari Nol

1. Daftar Oracle Cloud Free Tier.
2. Buat VM Always Free:
   - OS: Ubuntu.
   - Shape: Always Free eligible.
   - Tambahkan SSH key.
3. Buka port:
   - `22` untuk SSH.
   - `80` untuk HTTP.
   - `443` untuk HTTPS.
4. SSH ke server:

```bash
ssh ubuntu@IP_SERVER
```

5. Update server:

```bash
sudo apt update
sudo apt upgrade -y
```

6. Install Docker:

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
```

7. Logout lalu login ulang SSH.
8. Install Coolify atau Dokploy.
9. Connect GitHub repository PayrollPro.
10. Tambahkan environment variables production.
11. Deploy app.
12. Hubungkan domain jika ada.
13. Aktifkan SSL.
14. Jalankan migration:

```bash
php artisan migrate --force
```

### 9.4 Coolify vs Dokploy

| Panel | Cocok Untuk | Catatan |
|---|---|---|
| Coolify | Setup self-hosted yang lebih lengkap | Fitur banyak, konfigurasi lebih detail |
| Dokploy | Setup lebih sederhana | Cocok untuk mulai cepat |

### 9.5 Catatan Oracle

Oracle adalah opsi gratis jangka panjang yang kuat, tetapi bukan yang paling mudah. Untuk user yang hanya punya akun Google tanpa kartu, Render lebih realistis.

## 10. Alternatif 3: Google Cloud Free Tier / Cloud Run

Google Cloud bisa menjalankan container Laravel lewat Cloud Run, tetapi signup resmi biasanya membutuhkan payment method.

### 10.1 Kapan Memilih Google Cloud

Pilih Google Cloud jika:

- Siap memasukkan payment method.
- Ingin deployment container yang scalable.
- Paham Cloud Run, Cloud Build, IAM, dan billing alert.

### 10.2 Alur Deploy Cloud Run

1. Buat Google Cloud project.
2. Aktifkan billing/free trial jika diminta.
3. Aktifkan API:
   - Cloud Run.
   - Cloud Build.
   - Artifact Registry.
4. Connect GitHub repository.
5. Build container dari Dockerfile.
6. Deploy ke Cloud Run.
7. Isi environment variables production.
8. Set Cloud Run service agar public.
9. Test URL Cloud Run.

### 10.3 Catatan Google Cloud

- Ada free tier Cloud Run, tetapi tetap harus hati-hati billing.
- Set budget alert.
- Untuk pemula yang ingin 100% tanpa payment method, Google Cloud bukan pilihan paling aman.

## 11. Alternatif 4: Vercel

Vercel bagus untuk frontend modern, tetapi tidak ideal untuk Laravel monolith.

### 11.1 Kapan Memilih Vercel

Pilih Vercel jika:

- Project hanya frontend.
- Backend Laravel dipisah ke platform lain.
- Vue/Inertia diubah menjadi frontend terpisah.

### 11.2 Kenapa Tidak Direkomendasikan untuk PayrollPro

PayrollPro butuh:

- Laravel server runtime.
- PHP.
- Session backend.
- Middleware auth.
- Payroll processing.
- Queue/scheduler.
- Database server-side.

Vercel tidak cocok sebagai host utama Laravel monolith.

### 11.3 Alur Jika Tetap Ingin Vercel

1. Pisahkan frontend dari Laravel.
2. Deploy frontend ke Vercel.
3. Deploy Laravel API ke Render/Koyeb/Oracle.
4. Hubungkan frontend ke backend API.

Ini butuh refactor besar, jadi tidak disarankan untuk kondisi project sekarang.

## 12. Alternatif 5: Netlify

Netlify mirip Vercel: bagus untuk static/frontend, bukan Laravel monolith.

### 12.1 Kapan Memilih Netlify

Pilih Netlify jika:

- Project static site.
- Landing page.
- Frontend Vue terpisah.
- Tidak butuh Laravel runtime.

### 12.2 Kenapa Tidak Direkomendasikan untuk PayrollPro

PayrollPro bukan static site. Ia membutuhkan Laravel backend yang aktif.

Netlify bisa deploy dari GitHub, tetapi output yang ideal adalah static build, bukan Laravel full-stack.

## 13. Pilihan Akhir Berdasarkan Kondisi

| Kondisi | Pilihan Terbaik |
|---|---|
| Modal hanya akun Google/GitHub, ingin public demo | Render Free + Supabase |
| Ingin gratis lebih stabil jangka panjang dan siap verifikasi kartu | Oracle Always Free + Coolify/Dokploy + Supabase |
| Ingin platform container scalable dan siap billing setup | Google Cloud Run + Supabase |
| Frontend-only/static | Vercel atau Netlify |
| Alternatif Render dengan Docker | Koyeb |

## 14. Tutorial Deployment PayrollPro Paling Disarankan

Ikuti urutan ini:

### Tahap A: Amankan Project Lokal

1. Cek status Git:

```bash
git status
```

2. Pastikan `.env` tidak akan masuk commit.
3. Cek build:

```bash
composer install
npm install
npm run build
php artisan route:list
php artisan test
```

4. Pastikan koneksi Supabase:

```bash
php artisan db:show
php artisan migrate:status
```

### Tahap B: Push ke GitHub

```bash
git add .
git commit -m "Prepare PayrollPro deployment"
git push origin main
```

### Tahap C: Siapkan Supabase

1. Pastikan database Supabase sudah berisi schema/data terbaru.
2. Jalankan:

```bash
php artisan migrate:status
supabase db lint --linked
```

3. Simpan credential database untuk environment Render.

### Tahap D: Deploy ke Render

1. Login Render.
2. Buat Web Service.
3. Connect GitHub repo.
4. Pilih Docker.
5. Isi environment variables.
6. Deploy.
7. Test `/api/health`.
8. Test login semua role.

### Tahap E: Setelah Public

1. Ganti password default.
2. Cek log error.
3. Cek route penting.
4. Cek payroll, attendance, manual attendance, dan dashboard.
5. Jangan publish URL sebelum data dummy/production sudah aman.

## 15. Troubleshooting Umum

### 15.1 Error `could not find driver`

Penyebab:

- Docker/PHP belum punya `pdo_pgsql`.

Solusi:

- Tambahkan install `pdo_pgsql` dan `libpq-dev` di Dockerfile.

### 15.2 Error `APP_KEY missing`

Penyebab:

- `APP_KEY` belum diisi di environment Render.

Solusi:

```bash
php artisan key:generate --show
```

Copy hasilnya ke `APP_KEY`.

### 15.3 Error 500 Setelah Deploy

Cek:

- `APP_DEBUG=false` membuat error tidak terlihat di browser.
- Buka log Render.
- Pastikan env database benar.
- Pastikan migration sudah jalan.
- Pastikan storage/cache permission benar.

### 15.4 Login Gagal

Cek:

- User ada di Supabase.
- Password benar.
- `account_status=active`.
- Role Spatie ada.
- Session table tersedia jika memakai `SESSION_DRIVER=database`.

### 15.5 Asset CSS/JS Tidak Muncul

Cek:

- `npm run build` berhasil.
- Folder build Vite ada.
- Dockerfile menyalin hasil build.
- `APP_URL` sesuai URL Render.

### 15.6 QR atau Realtime Tidak Update

Cek:

- `VITE_SUPABASE_URL` benar.
- `VITE_SUPABASE_ANON_KEY` benar.
- Supabase Realtime enabled untuk table/topik yang dipakai.
- Jangan memakai `service_role` di frontend.

## 16. Referensi Resmi

- GitHub create repository: https://docs.github.com/en/repositories/creating-and-managing-repositories/creating-a-new-repository
- GitHub push existing local project: https://docs.github.com/en/migrations/importing-source-code/using-the-command-line-to-import-source-code/adding-locally-hosted-code-to-github
- Render free deploy: https://render.com/docs/free
- Render first deploy: https://render.com/docs/your-first-deploy
- Render Laravel Docker: https://render.com/docs/deploy-php-laravel-docker
- Render environment variables: https://render.com/docs/configure-environment-variables
- Supabase database connection: https://supabase.com/docs/guides/database/connecting-to-postgres
- Supabase database overview: https://supabase.com/docs/guides/database/overview
- Koyeb deploy with GitHub: https://www.koyeb.com/docs/build-and-deploy/deploy-with-git
- Oracle Always Free resources: https://docs.oracle.com/iaas/Content/FreeTier/freetier_topic-Always_Free_Resources.htm
- Oracle first Linux instance: https://docs.oracle.com/iaas/Content/Compute/tutorials/first-linux-instance/overview.htm
- Google Cloud Run Git deploy: https://docs.cloud.google.com/run/docs/quickstarts/deploy-continuously
- Google Cloud Run pricing: https://cloud.google.com/run/pricing
- Vercel GitHub deployment: https://vercel.com/docs/git/vercel-for-github
- Netlify deploy from repository: https://docs.netlify.com/start/quickstarts/deploy-from-repository/
