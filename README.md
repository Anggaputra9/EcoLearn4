# Eko-Scribe

> Media pembelajaran berbasis **AI** untuk guru dan siswa, dengan tema khas **Ekoteologi** (perpaduan teologi & ekologi).
> Sekali generate, guru dapat memiliki materi, soal, ujian online, hingga laporan PDF; siswa belajar, mengerjakan ujian anti-kecurangan, dan menerima skor + umpan balik AI dalam Bahasa Indonesia.

![Stack](https://img.shields.io/badge/Laravel-12-red) ![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4) ![Tailwind](https://img.shields.io/badge/Tailwind-3-06b6d4) ![License](https://img.shields.io/badge/license-MIT-green)

---

## 🎯 Untuk Siapa?

Eko-Scribe dirancang sebagai **media pembelajaran satu-pintu** yang menyatukan tiga peran:

- **Guru** — fokus mengajar, biarkan AI yang menyiapkan materi, soal, dan koreksi.
- **Siswa** — belajar interaktif, ikut ujian online yang aman, dan menerima feedback yang manusiawi.
- **Administrator** — mengelola pengguna, kelas, dan kunci API/email tanpa menyentuh kode.

---

## ⭐ Mengapa Eko-Scribe?

- **Hemat waktu persiapan mengajar.** Generator materi + soal AI yang sudah disesuaikan untuk tingkat SD/SMP/SMA/Umum.
- **Ujian online yang serius.** Anti pindah tab, anti copy-paste, fullscreen wajib, timer server-side, auto-save jawaban.
- **Koreksi otomatis yang adil.** Skor 0-100 + umpan balik pedagogis; guru tetap bisa meluruskan hasil AI.
- **Multi-provider AI & Email.** Tidak terkunci ke satu vendor: Gemini, OpenAI, Anthropic, OpenRouter, Groq; SMTP, Brevo, MailerSend, SendPulse.
- **Cetak & ekspor profesional.** Materi, slides (PPTX/PDF), infografis (PDF), dan laporan ujian — siap dibagikan.
- **UI ramah mata.** Tema glassmorphism + dark mode (Light/Dark/System) yang persisten per akun.
- **Sepenuhnya Bahasa Indonesia** — termasuk system prompt AI sehingga output sudah lokalisasi.

---

## 👩‍🏫 Fitur untuk Guru

### Materi pembelajaran
- **Generator materi AI** dari modal di halaman *Materi Saya*: cukup isi topik, tingkat, dan jumlah pertemuan — preview, edit, lalu simpan dalam satu langkah.
- **Format keluaran fleksibel**: artikel/handout, **slides** (PPTX & PDF), atau **infografis** (PDF) — masing-masing siap diunduh dan dibagikan.
- **Soft-delete + Histori Materi**: materi yang dihapus bisa dipulihkan, atau dihapus permanen.
- **Versi pertemuan**: setiap materi punya nomor pertemuan otomatis untuk memudahkan tracking kurikulum.

### Soal & ujian
- **Generator soal AI**: esai dan pilihan ganda (MCQ) dengan bobot dan kunci jawaban.
- **Soal manual**: tetap bisa menulis soal sendiri & mengedit hasil AI kapan saja.
- **Ujian online** dengan pengaturan **anti-kecurangan** terkonfigurasi penuh:
  - Larang pindah tab — N kali toleransi atau langsung gugur.
  - Larang copy / cut / paste, larang klik kanan, **fullscreen wajib**.
  - Acak urutan soal, timer hitung mundur server-side, auto-expire saat habis.
  - Auto-save jawaban tiap perubahan agar tidak hilang saat koneksi putus.

### Koreksi & nilai
- **Mode koreksi**: Otomatis AI / Manual / Hybrid.
- **Edit feedback AI** atau **rerun AI** per submission jika hasil dirasa kurang tepat.
- **Visibilitas hasil** dapat diatur: tampil otomatis, rilis manual via email, leaderboard, izinkan review jawaban + feedback.
- **Cetak laporan PDF** ujian (DomPDF) — ringkasan + detail per peserta untuk arsip atau rapat.

### Kelas & komunikasi
- **Sistem Kelas**: buat kelas dengan **kode unik 6 karakter**, regenerasi kode kapan pun, hapus anggota.
- **Materi & ujian terikat ke kelas**: hanya anggota kelas yang dapat mengaksesnya.
- **Forum diskusi** di setiap materi — guru menerima notifikasi email saat siswa bertanya, dan dapat **menandai diskusi terjawab**.

---

## 🧑‍🎓 Fitur untuk Siswa

- **Daftar materi** dengan filter tingkat (SD/SMP/SMA/Umum) — termasuk materi kelas yang diikuti.
- **Gabung kelas** via kode dari guru, lihat semua kelas di menu *Kelas Saya*.
- **Belajar interaktif**: baca materi, unduh PDF/slides/infografis, lalu kerjakan soal di halaman yang sama.
- **Mode ujian** dengan timer hitung mundur, auto-save jawaban, peringatan anti-cheat real-time, dan **rekap pelanggaran** transparan.
- **Hasil & feedback**: skor 0-100 + umpan balik AI (atau koreksi guru) dalam Bahasa Indonesia.
- **Leaderboard kelas** (jika diizinkan guru) untuk gamifikasi sehat.
- **Forum diskusi**: tanya guru tentang materi, dapatkan **notifikasi email** saat dijawab.
- **Login OTP via email** (opsional, dapat diaktifkan dari profil) untuk keamanan ekstra.

---

## 🛡 Fitur untuk Administrator

- Dashboard ringkasan pengguna, materi, dan pengumpulan esai.
- **Kelola Pengguna** & **Kelola Menu** dinamis dengan filter (semua aksi via modal).
- **Konfigurasi AI**: pilih provider default (Gemini, OpenAI, Anthropic, OpenRouter, Groq) + model; daftar model diambil otomatis dari API.
- **AI Key Pool** (`/admin/ai-keys`): banyak API key per provider, urutkan prioritas via drag, lihat sisa kuota & terakhir dipakai. Sistem otomatis berpindah ke key berikutnya jika kuota habis / error.
- **Email & Notif** (`/admin/email`): pilih provider mail (SMTP/PHPMailer, **Brevo**, **MailerSend**, **SendPulse**), tombol *Tes Kirim*, **Mail Key Pool** dengan rotasi otomatis seperti AI Key Pool.
- **Pengaturan Aplikasi** (`/admin/app`): ganti nama, tagline, dan footer aplikasi tanpa edit `.env`.
- **Changelog** (`/admin/changelogs`): dibaca langsung dari `CHANGELOG.md` di root proyek (read-only) — tidak perlu sinkronisasi database.

---

## ✨ Pengalaman & Sistem

- 3 peran (Admin/Guru/Siswa) dilindungi middleware `role:*`.
- **UI glassmorphism** ala TailAdmin: sidebar mengambang, topbar sticky, header per halaman, kartu statistik, modal universal `<x-modal-glass>`.
- **Dark mode** (Light/Dark/System) yang persisten per akun (kolom `theme` + localStorage).
- **Mobile-first**: sidebar dapat dibuka via tombol menu, modal & tabel responsif.
- Hashing kata sandi **bcrypt** (`BCRYPT_ROUNDS=12`) — default Laravel.
- Sebagian besar pengaturan runtime tersimpan di tabel `settings` sehingga **tidak perlu edit `.env`** saat berpindah environment.

---

## 🛠 Tech Stack

| Lapisan | Teknologi |
| --- | --- |
| Framework | Laravel **12.x** |
| PHP | **^8.2** |
| Frontend | Tailwind CSS 3 + Alpine.js + Vite |
| Database | MySQL 8 / MariaDB 10.6+ |
| AI | Gemini (default), OpenAI, Anthropic, OpenRouter, Groq |
| Email | SMTP/PHPMailer, Brevo, MailerSend, SendPulse |
| PDF | `barryvdh/laravel-dompdf` ^3.1 |
| Markdown | `league/commonmark` (untuk render `CHANGELOG.md`) |
| Auth | Laravel Breeze (Blade + sessions) + OTP email opsional |

---

## 📁 Struktur Penting

```
app/
  Http/Controllers/
    Admin/{ChangelogController,SettingController,AiKeyController,MailController,MailKeyController,AppController}.php
    Teacher/{ClassroomController,ExamController,SubmissionController}.php
    Student/{ClassroomController,ExamRunController}.php
    {AdminController,DashboardController,TeacherController,StudentController,ProfileController,DiscussionController}.php
  Http/Middleware/EnsureUserHasRole.php
  Models/{User,Material,Question,Submission,Setting,Classroom,Exam,ExamAttempt,Discussion,AiKey,MailKey,OtpCode}.php
  Services/
    AIService.php                  # multi-provider AI (Gemini/OpenAI/Anthropic/OpenRouter/Groq)
    GeminiAIService.php            # alias backward-compat
    MailService.php                # multi-provider email + key rotation
    MaterialExportService.php      # ekspor PPTX, PDF slides, infografis
    NotificationService.php        # email diskusi & rilis ujian
    ChangelogService.php           # parser CHANGELOG.md (read-only)

database/
  migrations/
    *_create_{materials,questions,submissions,settings}_table.php
    *_create_{ai_keys,mail_keys,classrooms,exams,discussions,otp_codes}_table.php
    *_add_{user_preferences,otp_login_to_users,profile_photo_to_users}.php
    *_add_{mcq_to_questions_and_submissions, meeting_and_softdeletes_to_materials, format_and_outputs_to_materials}.php
  seeders/{MasterDataSeeder,DatabaseSeeder}.php

resources/
  css/app.css                      # tema glassmorphism + dark mode
  views/
    landing.blade.php              # halaman publik
    layouts/{app,guest,sidebar,topbar}.blade.php
    components/{icon,modal-glass,confirm-modal}.blade.php
    {admin,teacher,student,dashboard,profile,auth}/...

CHANGELOG.md                       # sumber kebenaran tunggal halaman /admin/changelogs
```

---

## 🚀 Memulai (Local / Laragon)

### 1. Prasyarat

- PHP **8.2+** (disarankan 8.3) dengan ekstensi `mbstring`, `pdo_mysql`, `intl`, `gd`, `zip`.
- Composer 2.x
- Node.js **18+** (npm 10+).
- MySQL 8 (atau MariaDB 10.6+).
- Akun Google AI Studio + **GEMINI_API_KEY** (https://aistudio.google.com/apikey) — atau key dari provider AI lain.

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
APP_VERSION=0.3.0

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecolearn4
DB_USERNAME=root
DB_PASSWORD=

# Boleh dikosongkan — bisa diisi nanti dari /admin/ai
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
```

### 4. Buat database & migrasi

```bash
mysql -uroot -e "CREATE DATABASE ecolearn4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan migrate:fresh --seed --force
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

### 7. Atur AI & Email

1. Login sebagai administrator.
2. **Konfigurasi AI** (`/admin/ai`) → tab *General* untuk pilih provider/model, tab *Keys* untuk menambah API key & atur prioritas. Tekan **Tes Koneksi AI**.
3. **Email & Notif** (`/admin/email`) → pilih provider mail dan **Tes Kirim** untuk memastikan notifikasi diskusi & rilis ujian dapat dikirim.

Setelah dua langkah di atas, fitur generate materi/soal, koreksi esai, dan notifikasi email sudah aktif.

---

## 🎨 UI / Glassmorphism

- Tema Tailwind kustom di `resources/css/app.css` (kelas `.glass`, `.glass-strong`, `.btn-primary`, dll).
- Komponen modal universal: `<x-modal-glass name="...">…</x-modal-glass>`. Trigger dengan `@click="$dispatch('open-modal', 'nama-modal')"`.
- Komponen ikon: `<x-icon name="users" class="w-5 h-5"/>` (heroicons outline kurasi TailAdmin).
- Toggle Light/Dark/System tersedia di topbar; preferensi tersimpan di kolom `users.theme` + localStorage.

---

## 🔐 Catatan Keamanan

- Kata sandi di-hash dengan **bcrypt** (`BCRYPT_ROUNDS=12`).
- API key (AI & email) **tidak ditampilkan kembali** setelah disimpan; isi ulang untuk mengganti.
- Akses panel admin dibatasi middleware `role:admin`. Begitu pula `role:teacher`, `role:student`.
- Anti-cheat ujian dijalankan berlapis di **client + server**: pelanggaran tercatat di `exam_attempts`.
- Login OTP email dapat diaktifkan dari profil pengguna (kode 6 digit, kedaluwarsa singkat).
- Sebelum deploy production: set `APP_ENV=production`, `APP_DEBUG=false`, gunakan HTTPS, dan rotate API key jika pernah ter-commit.
- `php artisan storage:link` wajib dijalankan agar foto profil & ekspor materi dapat diakses publik.

---

## 🚢 Deploy (ringkas)

1. **Server**: PHP 8.2+, MySQL 8 / MariaDB 10.6+, Nginx/Apache, Composer, Node.
2. Tarik repo + `composer install --no-dev --optimize-autoloader`.
3. `cp .env.example .env`, isi `APP_KEY`, DB, `APP_URL`, `GEMINI_API_KEY`.
4. `php artisan migrate --force`.
5. `npm ci && npm run build`.
6. `php artisan storage:link`.
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
8. Arahkan webroot ke `public/`. Pastikan `storage/` & `bootstrap/cache/` writable.
9. Set up cron Laravel scheduler bila perlu.

> Sebagian besar konfigurasi runtime (AI, email, branding) sekarang dilakukan **dari UI admin**, bukan `.env`. Jadi proses migrasi antar-environment lebih ringan.

---

## 🐧 Hosting di Ubuntu (22.04 / 24.04 LTS)

Panduan ini menggunakan **Nginx + PHP-FPM 8.3 + MySQL 8** dan mengasumsikan domain `ekoscribe.example.com` serta user non-root `deploy`.

### 1. Update sistem & dependensi dasar

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common curl unzip git ca-certificates lsb-release apt-transport-https gnupg
```

### 2. Install PHP 8.3 + ekstensi

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd php8.3-readline
```

Verifikasi: `php -v` dan `php-fpm8.3 -v`.

### 3. Install MySQL 8

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

Buat database & user:

```bash
sudo mysql <<'SQL'
CREATE DATABASE ecolearn4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecolearn'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON ecolearn4.* TO 'ecolearn'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 4. Install Composer & Node.js 20

```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 (NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 5. Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

### 6. Klon project

```bash
sudo mkdir -p /var/www
sudo chown -R deploy:deploy /var/www
cd /var/www
git clone https://github.com/Anggaputra9/EcoLearn4.git ekoscribe
cd ekoscribe

composer install --no-dev --optimize-autoloader
npm ci
cp .env.example .env
php artisan key:generate
```

Edit `.env` (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ekoscribe.example.com`, kredensial DB, `GEMINI_API_KEY`).

```bash
php artisan migrate --force
php artisan storage:link
npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 7. Permission

```bash
sudo chown -R deploy:www-data /var/www/ekoscribe
sudo find /var/www/ekoscribe -type f -exec chmod 644 {} \;
sudo find /var/www/ekoscribe -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/ekoscribe/storage /var/www/ekoscribe/bootstrap/cache
```

### 8. Konfigurasi Nginx

`/etc/nginx/sites-available/ekoscribe`:

```nginx
server {
    listen 80;
    server_name ekoscribe.example.com;
    root /var/www/ekoscribe/public;

    index index.php index.html;
    charset utf-8;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Aktifkan & restart:

```bash
sudo ln -s /etc/nginx/sites-available/ekoscribe /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 9. SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d ekoscribe.example.com
```

### 10. Cron scheduler (opsional)

```bash
crontab -e
# tambahkan:
* * * * * cd /var/www/ekoscribe && php artisan schedule:run >> /dev/null 2>&1
```

### 11. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## 🌀 Hosting di Debian (12 Bookworm)

Langkah hampir identik dengan Ubuntu, hanya beda repo PHP dan beberapa default.

### 1. Update & dependensi

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget gnupg2 ca-certificates lsb-release apt-transport-https git unzip
```

### 2. Tambahkan repo Sury (PHP 8.3) — Debian tidak punya PHP 8.3 di repo default

```bash
sudo curl -sSLo /etc/apt/keyrings/sury-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/etc/apt/keyrings/sury-php.gpg] https://packages.sury.org/php $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/sury-php.list
sudo apt update
```

### 3. Install PHP 8.3 + ekstensi

```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd
```

### 4. Install MariaDB (alternatif MySQL di Debian)

```bash
sudo apt install -y mariadb-server
sudo mysql_secure_installation
```

Buat DB & user (sama seperti contoh Ubuntu di atas).

### 5. Install Composer, Node.js 20, Nginx

```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Nginx
sudo apt install -y nginx
sudo systemctl enable --now nginx
```

### 6. Klon, install, migrasi

Sama persis dengan langkah 6-7 panduan Ubuntu (klon ke `/var/www/ekoscribe`, `composer install`, `npm ci`, `.env`, `migrate --force`, `storage:link`, `npm run build`, cache).

### 7. Konfigurasi Nginx + SSL

Gunakan blok `server` yang sama. Path PHP-FPM Debian juga `/run/php/php8.3-fpm.sock`. Untuk SSL pakai `certbot` dari paket `python3-certbot-nginx`.

### 8. Firewall

Debian default tanpa UFW; gunakan `nftables` atau install UFW:

```bash
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

> Catatan: jika lebih suka **Apache** di Debian/Ubuntu, install `apache2 libapache2-mod-php8.3`, aktifkan `a2enmod rewrite`, lalu arahkan `DocumentRoot` ke `/var/www/ekoscribe/public` dengan `AllowOverride All`.

---

## 😈 Hosting di FreeBSD (13.x / 14.x)

FreeBSD memakai `pkg` dan struktur direktori `/usr/local/...`. Layanan dikelola oleh `service` + `rc.conf`.

### 1. Update & install paket

```bash
sudo pkg update && sudo pkg upgrade -y
sudo pkg install -y nginx mysql80-server \
    php83 php83-extensions php83-mysqli php83-pdo_mysql \
    php83-mbstring php83-tokenizer php83-xml php83-xmlwriter \
    php83-curl php83-zip php83-bcmath php83-intl php83-gd \
    php83-fileinfo php83-session php83-ctype php83-filter \
    php83-openssl php83-pecl-redis \
    git node20 npm-node20 composer
```

### 2. Aktifkan service

```bash
sudo sysrc nginx_enable=YES
sudo sysrc php_fpm_enable=YES
sudo sysrc mysql_enable=YES
sudo cp /usr/local/etc/php.ini-production /usr/local/etc/php.ini
sudo service mysql-server start
sudo service php-fpm start
sudo service nginx start
```

### 3. Amankan MySQL & buat DB

```bash
sudo mysql_secure_installation
sudo mysql <<'SQL'
CREATE DATABASE ecolearn4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ecolearn'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_KUAT';
GRANT ALL PRIVILEGES ON ecolearn4.* TO 'ecolearn'@'localhost';
FLUSH PRIVILEGES;
SQL
```

### 4. PHP-FPM

Edit `/usr/local/etc/php-fpm.d/www.conf`:

```ini
user = www
group = www
listen = /var/run/php-fpm.sock
listen.owner = www
listen.group = www
listen.mode = 0660
```

`sudo service php-fpm restart`.

### 5. Klon project

```bash
sudo mkdir -p /usr/local/www
sudo chown deploy:www /usr/local/www
cd /usr/local/www
git clone https://github.com/Anggaputra9/EcoLearn4.git ekoscribe
cd ekoscribe
composer install --no-dev --optimize-autoloader
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### 6. Permission

```bash
sudo chown -R deploy:www /usr/local/www/ekoscribe
sudo find /usr/local/www/ekoscribe -type d -exec chmod 755 {} \;
sudo find /usr/local/www/ekoscribe -type f -exec chmod 644 {} \;
sudo chmod -R 775 /usr/local/www/ekoscribe/storage /usr/local/www/ekoscribe/bootstrap/cache
```

### 7. Konfigurasi Nginx

`/usr/local/etc/nginx/nginx.conf`:

```nginx
server {
    listen 80;
    server_name ekoscribe.example.com;
    root /usr/local/www/ekoscribe/public;

    index index.php index.html;
    charset utf-8;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

`sudo nginx -t && sudo service nginx reload`.

### 8. SSL & cron

```bash
sudo pkg install -y py39-certbot py39-certbot-nginx
sudo certbot --nginx -d ekoscribe.example.com

crontab -e
* * * * * cd /usr/local/www/ekoscribe && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 9. Firewall (PF)

Edit `/etc/pf.conf` lalu `sudo sysrc pf_enable=YES && sudo service pf start && sudo pfctl -f /etc/pf.conf`.

> **Catatan FreeBSD**: pengguna web default adalah `www` (bukan `www-data`). Jika memilih TCP (`listen = 127.0.0.1:9000`), ganti `fastcgi_pass` di Nginx menjadi `127.0.0.1:9000`.

---

## 🔁 Update / Redeploy (semua OS)

```bash
cd /var/www/ekoscribe        # atau /usr/local/www/ekoscribe di FreeBSD
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
# restart php-fpm agar opcache fresh
sudo systemctl reload php8.3-fpm   # Linux
# sudo service php-fpm reload      # FreeBSD
```

---

## 🧪 Testing & Quality

```bash
php artisan test         # unit/feature test (kerangka tersedia)
./vendor/bin/pint        # formatter PHP
npm run build            # cek build production
```

---

## 🗒 Changelog

Riwayat rilis terdapat di [CHANGELOG.md](CHANGELOG.md) dan ditampilkan di panel admin pada `/admin/changelogs`.
Halaman tersebut **read-only**: kontennya dibaca langsung dari berkas `CHANGELOG.md` di root proyek — cukup edit file itu saat merilis versi baru, tidak perlu seeder atau panel CRUD.

---

## 📜 Lisensi

MIT.
