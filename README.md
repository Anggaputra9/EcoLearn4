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
php artisan db:seed --class=ChangelogSeeder --force
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

> Jika `php83` belum tersedia di pkg, ganti ke versi terbaru yang tersedia (`pkg search php` lalu pilih).

### 2. Aktifkan service

Tambahkan ke `/etc/rc.conf`:

```bash
sudo sysrc nginx_enable=YES
sudo sysrc php_fpm_enable=YES
sudo sysrc mysql_enable=YES
```

Salin contoh konfigurasi PHP:

```bash
sudo cp /usr/local/etc/php.ini-production /usr/local/etc/php.ini
```

Jalankan layanan:

```bash
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

### 4. Konfigurasi PHP-FPM

Edit `/usr/local/etc/php-fpm.d/www.conf`, pastikan:

```ini
user = www
group = www
listen = /var/run/php-fpm.sock
listen.owner = www
listen.group = www
listen.mode = 0660
```

Restart: `sudo service php-fpm restart`.

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
```

Edit `.env` sesuai kredensial, lalu:

```bash
php artisan migrate --force
php artisan db:seed --class=ChangelogSeeder --force
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

`/usr/local/etc/nginx/nginx.conf` (atau buat file include di `/usr/local/etc/nginx/sites/`):

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

Tes & reload:

```bash
sudo nginx -t
sudo service nginx reload
```

### 8. SSL via acme.sh atau certbot

```bash
sudo pkg install -y py39-certbot py39-certbot-nginx
sudo certbot --nginx -d ekoscribe.example.com
```

### 9. Cron scheduler

```bash
crontab -e
* * * * * cd /usr/local/www/ekoscribe && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

### 10. Firewall (PF)

Edit `/etc/pf.conf` untuk membuka port 22, 80, 443, lalu:

```bash
sudo sysrc pf_enable=YES
sudo service pf start
sudo pfctl -f /etc/pf.conf
```

> **Catatan FreeBSD**: pengguna web default adalah `www` (bukan `www-data`). Path socket PHP-FPM bebas Anda atur. Jika memilih TCP (`listen = 127.0.0.1:9000`), ganti `fastcgi_pass` di Nginx menjadi `127.0.0.1:9000`.

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

Lihat [CHANGELOG.md](CHANGELOG.md). Riwayat rilis juga tersedia di panel admin: **Changelog**.

- **v0.2.0** — Glassmorphism, modal universal, konfigurasi AI dinamis, profil + foto, landing page, dashboard adaptif.
- **v0.1.0** — Sistem inti: Materials/Questions/Submissions, generator AI, koreksi esai otomatis.

---

## 📜 Lisensi

MIT.
