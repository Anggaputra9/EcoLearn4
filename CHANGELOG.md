# Changelog

Semua perubahan signifikan untuk **Eko-Scribe** dicatat di sini.
Format mengikuti gaya [Keep a Changelog](https://keepachangelog.com/), dan proyek ini patuh [Semantic Versioning](https://semver.org/).

---

## [0.3.0] – 2026-05-20

### Added
- **Sistem Kelas (Classroom)** — guru membuat kelas dengan kode unik 6 karakter; siswa join via kode dari menu *Kelas Saya*. Materi & ujian bisa terikat ke kelas (akses dibatasi ke anggota).
- **Sistem Ujian (Exam) lengkap** dengan pengaturan anti-kecurangan paling ultimate:
  - Larang pindah tab dengan **batas maksimal yang dapat dikonfigurasi** (0 = langsung gugur, atau N kali toleransi).
  - Larang copy/cut/paste, larang klik kanan, fullscreen wajib, acak urutan soal.
  - Auto-save jawaban setiap perubahan, timer hitung mundur server-side, auto-expire saat habis.
- **Mode koreksi**: Otomatis AI / Manual oleh guru / Hybrid. Guru dapat **mengedit hasil AI** untuk meluruskannya, atau menjalankan ulang AI per submission.
- **Visibilitas hasil** dapat diatur guru: tampilkan otomatis setelah submit, opsi rilis manual (kirim email), tampilkan/sembunyikan leaderboard, izinkan review jawaban + feedback.
- **Cetak laporan PDF** ujian (DomPDF) — ringkasan + detail per peserta.
- **Forum Diskusi** pada setiap materi (thread + balasan); guru dapat menandai diskusi *terjawab*.
- **Notifikasi email otomatis**: pertanyaan baru ke guru, jawaban guru ke siswa, hasil ujian dirilis ke siswa.
- **Multi-provider AI**: Google Gemini, OpenAI, Anthropic, OpenRouter, Groq.
- **API Key Pool** (`/admin/ai-keys`) — administrator dapat menyimpan banyak API key per provider, mengurutkan prioritas via drag, melihat sisa kuota & terakhir dipakai. Sistem otomatis berpindah ke key berikutnya jika satu kehabisan kuota / error.
- **Multi-provider Email**: SMTP/PHPMailer, **Brevo**, **MailerSend**, **SendPulse** — kredensial dikelola dari `/admin/mail` dengan tombol *Tes Kirim*.
- **Dark mode** dengan toggle Light / Dark / System; preferensi disimpan di kolom `theme` user.
- **Sidebar dirapikan** — *Buat Materi* dipindah dari sidebar ke modal di halaman *Materi Saya* (single-step generate + edit + simpan).
- **Endpoint AJAX** `POST /teacher/materials/generate-ajax` agar guru bisa preview & edit hasil AI sebelum menyimpan.
- **Buat & edit soal manual** (selain generate AI), serta **rerun AI** per submission saat koreksi.

### Changed
- `GeminiAIService` menjadi alias dari `AIService` baru (backwards compatible).
- `Material` mendukung kolom `classroom_id` (opsional); siswa hanya melihat materi publik atau kelas yang diikuti.
- `Submission` mendapat kolom `exam_attempt_id`, `manually_graded`, `graded_by`; status enum diperluas dengan `submitted`.

### Migrations baru
- `2026_05_20_100000_create_ai_keys_table` — pool API key multi-provider.
- `2026_05_20_100001_create_classrooms_table` — kelas + member + relasi materi-kelas.
- `2026_05_20_100002_create_exams_table` — ujian + attempt + kolom anti-cheat di submission.
- `2026_05_20_100003_create_discussions_table` — forum diskusi materi.
- `2026_05_20_100004_add_user_preferences` — kolom `theme` pada users.

### Dependencies
- `barryvdh/laravel-dompdf` ^3.1 — pembuatan PDF laporan ujian.

---

## [0.2.0] – 2026-05-15

### Added
- **UI/UX baru** — gaya TailAdmin + glassmorphism untuk seluruh shell aplikasi: sidebar mengambang, topbar sticky, header per halaman, kartu statistik, badge, dan tombol berlapis kaca.
- **Komponen modal universal** (`<x-modal-glass>`). Semua aksi tambah/edit/hapus di area Admin & Guru kini menggunakan modal di tempat, tidak berpindah halaman.
- **Filter pencarian + filter kategori** di:
  - Kelola Pengguna (cari nama/email + filter peran).
  - Kelola Menu (cari nama/URL + filter peran).
  - Materi Saya (Guru) & Daftar Materi (Siswa) — filter tingkat (SD/SMP/SMA/Umum).
  - Changelog admin (cari versi/judul + filter jenis rilis).
- **Konfigurasi AI di UI** — administrator dapat mengubah `GEMINI_API_KEY` dan memilih `model` Gemini langsung dari `/admin/settings`. Daftar model diambil otomatis dari API; tersedia tombol *Tes Koneksi AI*.
- **Halaman Profil** baru — unggah/hapus foto profil, perbarui informasi akun, ganti kata sandi, hapus akun (modal konfirmasi).
- **Manajemen Changelog** — CRUD versi rilis langsung di panel admin, dengan kategori: `major`, `minor`, `patch`, `hotfix`. Disimpan di tabel `changelogs`.
- **Landing page** publik (`/`) — hero, fitur, CTA dengan gaya glassmorphism + Bahasa Indonesia.
- **Dashboard adaptif** per peran (Admin / Guru / Siswa) dengan kartu statistik dan ringkasan terbaru.
- **Sistem ikon TailAdmin-style** — komponen `<x-icon>` dengan 20+ ikon outline yang dikurasi.
- **Versi aplikasi** diekspos di sidebar dan footer (`config('app.version')`).
- **Halaman login & register** baru bergaya glass.

### Changed
- `GeminiAIService` membaca konfigurasi dari tabel `settings` (override dinamis), fallback ke `.env`. Mode JSON menggunakan `responseMimeType` resmi.
- `AdminController` ditulis ulang dengan eloquent + paginasi + filter; CRUD pengguna & menu kini didukung modal.
- `routes/web.php` diorganisasi ulang dengan middleware `role:admin|teacher|student` dan named routes.

### Migrations baru
- `2026_05_15_110000_create_settings_table` — penyimpanan konfigurasi runtime (AI key, model, dll).
- `2026_05_15_110001_create_changelogs_table` — riwayat rilis.
- `2026_05_15_110002_add_profile_photo_to_users` — kolom `profile_photo_path`.

### Removed
- View profil lama (`profile/partials/*`) dan `layouts/navigation.blade.php` (digantikan sidebar + topbar).

---

## [0.1.0] – 2026-05-15

### Added
- Skema baru: tabel `materials`, `questions`, `submissions` plus seeder.
- Multi peran: Administrator, Guru, Siswa (kolom `role_id` di `users`).
- `TeacherController` — generator materi & soal esai berbasis Gemini.
- `StudentController` — pengerjaan esai dengan koreksi otomatis AI (skor 0-100 + feedback pedagogis).
- `AdminController` — CRUD pengguna & menu navigasi dinamis.
- Middleware kustom `EnsureUserHasRole` untuk pembatasan akses berbasis peran.
- Hashing kata sandi menggunakan **bcrypt** (default Laravel) dengan `BCRYPT_ROUNDS=12`.
- Akun demo: `admin@ekoscribe.id`, `guru@ekoscribe.id`, `siswa@ekoscribe.id` (sandi `password123`).

### Removed
- Kontroler & migrasi stub lama (`Soal`, `Ujian`, `Jawaban`, `Materi`) yang masih kosong.
- Konfigurasi model AI yang tidak valid (`gemini-3.1-flash-lite`).
