# Eko-Scribe

> Platform Pembelajaran Esai Otomatis Berbasis **Ekoteologi** & AI.
> Versi saat ini: **v0.3.0**

Eko-Scribe membantu **guru** menyusun materi dan soal esai bertema ekoteologi (perpaduan teologi & ekologi) secara otomatis melalui beragam provider AI, lalu memungkinkan **siswa** mengerjakan esai/ujian dan langsung mendapatkan **skor 0-100** beserta umpan balik pedagogis dalam Bahasa Indonesia.

![Stack](https://img.shields.io/badge/Laravel-12-red) ![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4) ![Tailwind](https://img.shields.io/badge/Tailwind-3-06b6d4) ![License](https://img.shields.io/badge/license-MIT-green)

---

## ✨ Fitur Utama

### Untuk Administrator
- Dashboard ringkasan: total pengguna, materi, esai.
- **Kelola Pengguna** & **Kelola Menu** dinamis dengan filter (semua aksi via modal).
- **Konfigurasi AI**: pilih provider default (Gemini, OpenAI, Anthropic, OpenRouter, Groq) + model.
- **API Key Pool**: simpan banyak API key per provider, urutkan prioritas (drag), lihat sisa kuota; sistem otomatis berpindah saat satu kehabisan kuota.
- **Email & Notif**: pilih provider mail (SMTP/PHPMailer, **Brevo**, **MailerSend**, **SendPulse**) lalu tes kirim.
- **Manajemen Changelog** (kategori `major / minor / patch / hotfix`).

### Untuk Guru
- Generator **materi pembelajaran** via modal di halaman *Materi Saya* (preview + edit + simpan dalam satu langkah).
- Generator **soal esai** AI atau **soal manual**; setiap soal dapat diedit kapan saja.
- **Kelas**: buat kelas dengan kode unik, kelola anggota, tautkan materi/ujian ke kelas.
- **Ujian penuh** dengan pengaturan **anti-kecurangan**: larang pindah tab (dengan batas maksimal yang dikonfigurasi atau langsung gugur), larang copy-paste, larang klik kanan, fullscreen wajib, acak soal.
- **Mode koreksi**: Otomatis AI / Manual / Hybrid; guru dapat **meluruskan hasil AI**.
- **Visibilitas hasil** dapat diatur (tampil otomatis, rilis manual, leaderboard, review jawaban).
- **Forum diskusi** materi (tanya-jawab dengan siswa) + email notifikasi.
- **Cetak laporan PDF** ujian.

### Untuk Siswa
- Daftar materi dengan filter tingkat (SD/SMP/SMA/Umum) — termasuk materi kelas yang diikuti.
- **Gabung Kelas** via kode unik dari guru.
- **Ujian** dengan timer hitung mundur, auto-save jawaban, peringatan anti-cheat real-time.
- **Forum diskusi**: tanya guru tentang materi dan terima notifikasi email saat dijawab.
- **Hasil & leaderboard** (jika diizinkan guru).

### Sistem
- 3 peran (Admin/Guru/Siswa) dengan middleware `role:*`.
- UI **glassmorphism** ala TailAdmin + **dark mode** (Light/Dark/System) yang persisten per akun.
- **Bahasa Indonesia** untuk seluruh label, pesan error, dan system prompt AI.
- Hashing kata sandi **bcrypt** (default Laravel, `BCRYPT_ROUNDS=12`).

---

## 🛠 Tech Stack

| Lapisan | Teknologi |
| --- | --- |
| Framework | Laravel **12.x** |
| PHP | **^8.2** |
| Frontend | Tailwind CSS 3 + Alpine.js + Vite |
| Database | MySQL 8 |
| AI | Google Gemini (default `gemini-2.0-flash`) |
| Auth | Laravel Breeze (Blade + sessions) |

Komponen pendukung: `barryvdh/laravel-dompdf`, helper `<x-modal-glass>`, `<x-icon>`, dan `App\Services\GeminiAIService`.

---

## 📁 Struktur Penting

```
app/
  Http/Controllers/
    Admin/{ChangelogController,SettingController}.php
    {AdminController,DashboardController,TeacherController,StudentController,ProfileController}.php
  Http/Middleware/EnsureUserHasRole.php
  Models/{User,Material,Question,Submission,Setting,Changelog}.php
  Services/GeminiAIService.php

database/
  migrations/
    2026_05_15_100000_create_materials_table.php
    2026_05_15_100001_create_questions_table.php
    2026_05_15_100002_create_submissions_table.php
    2026_05_15_110000_create_settings_table.php
    2026_05_15_110001_create_changelogs_table.php
    2026_05_15_110002_add_profile_photo_to_users.php
  seeders/{MasterDataSeeder,ChangelogSeeder,DatabaseSeeder}.php

resources/
  css/app.css                    # tema glassmorphism
  views/
    landing.blade.php            # halaman publik
    layouts/{app,guest,sidebar,topbar}.blade.php
    components/{icon,modal-glass}.blade.php
    {admin,teacher,student,dashboard,profile,auth}/...
```

---

## 🚀 Memulai (Local / Laragon)

### 1. Prasyarat

- PHP **8.2+** (disarankan 8.3) dengan ekstensi `mbstring`, `pdo_mysql`, `intl` (opsional, untuk format angka).
- Composer 2.x
- Node.js **18+** (npm 10+).
- MySQL 8 (atau MariaDB 10.6+).
- Akun Google AI Studio + **GEMINI_API_KEY** (https://aistudio.google.com/apikey).

### 2. Klon & Install

```bash
git clone https://github.com/Anggaputra9/EcoLearn4.git
cd EcoLearn4
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 3. Konfigurasi `.env`

Edit minimal:

```env
APP_NAME="Eko-Scribe"
APP_URL=http://localhost:8000
APP_VERSION=0.2.0

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecolearn4
DB_USERNAME=root
DB_PASSWORD=

# Boleh dikosongkan; Anda bisa mengisinya nanti dari /admin/settings
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
```

### 4. Buat database & migrasi

```bash
# Buat DB di MySQL
mysql -uroot -e "CREATE DATABASE ecolearn4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrasi + seeder (akun demo + 2 changelog awal)
php artisan migrate:fresh --seed --force

# Symlink storage untuk avatar
php artisan storage:link
```

### 5. Jalankan

```bash
# Terminal 1 — Laravel
php artisan serve

# Terminal 2 — Vite (dev mode hot reload)
npm run dev
```

Buka http://localhost:8000

### 6. Akun demo

| Peran          | Email                        | Sandi          |
| -------------- | ---------------------------- | -------------- |
| Administrator  | `admin@ekoscribe.id`         | `password123`  |
| Guru           | `guru@ekoscribe.id`          | `password123`  |
| Siswa          | `siswa@ekoscribe.id`         | `password123`  |

> **Penting:** ganti sandi default sebelum deploy ke production.

### 7. Atur AI

Login sebagai administrator → menu **Konfigurasi AI** → masukkan `GEMINI_API_KEY` → pilih model → **Tes Koneksi AI**. Setelah tersimpan, fitur generate materi/soal & koreksi esai sudah siap.

---

## 🎨 UI / Glassmorphism

- Tema Tailwind kustom di `resources/css/app.css` (kelas `.glass`, `.glass-strong`, `.btn-primary`, dll).
- Komponen modal universal: `<x-modal-glass name="...">…</x-modal-glass>`. Trigger dengan `@click="$dispatch('open-modal', 'nama-modal')"`.
- Komponen ikon: `<x-icon name="users" class="w-5 h-5"/>` (heroicons outline kurasi TailAdmin).

---

## 🔐 Catatan Keamanan

- Kata sandi di-hash dengan **bcrypt** (Laravel default). `BCRYPT_ROUNDS=12`.
- API key Gemini **tidak ditampilkan kembali** setelah disimpan; isi ulang untuk mengganti.
- Akses panel admin dibatasi middleware `role:admin`. Begitu pula `role:teacher`, `role:student`.
- Sebelum deploy production: set `APP_ENV=production`, `APP_DEBUG=false`, gunakan HTTPS, dan rotate API key jika pernah ter-commit.
- `php artisan storage:link` wajib dijalankan agar foto profil dapat diakses publik.

---

## 🚢 Deploy (ringkas)

1. **Server**: PHP 8.2+, MySQL 8, Nginx/Apache, Composer, Node.
2. Tarik repo + `composer install --no-dev --optimize-autoloader`.
3. `cp .env.example .env`, isi `APP_KEY`, DB, `APP_URL`, `GEMINI_API_KEY`.
4. `php artisan migrate --force && php artisan db:seed --class=ChangelogSeeder --force`.
5. `npm ci && npm run build`.
6. `php artisan storage:link`.
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
8. Arahkan webroot ke `public/`. Pastikan `storage/` & `bootstrap/cache/` writable.
9. Set up cron Laravel scheduler bila perlu (saat ini opsional).

Untuk deploy modern, container, atau platform PaaS (Forge/Vapor/Railway/Hetzner), ikuti instruksi mereka — tidak ada konfigurasi khusus dari Eko-Scribe.

---

## 🧪 Testing & Quality

```bash
php artisan test         # unit/feature test (kerangka tersedia)
./vendor/bin/pint        # formatter PHP
npm run build            # cek build production
```

---

## 🗒 Changelog

Lihat [CHANGELOG.md](CHANGELOG.md). Riwayat rilis juga tersedia di panel admin: **Changelog**.

- **v0.2.0** — Glassmorphism, modal universal, konfigurasi AI dinamis, profil + foto, landing page, dashboard adaptif.
- **v0.1.0** — Sistem inti: Materials/Questions/Submissions, generator AI, koreksi esai otomatis.

---

## 📜 Lisensi

MIT.
