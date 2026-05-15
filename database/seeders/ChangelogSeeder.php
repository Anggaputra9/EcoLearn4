<?php

namespace Database\Seeders;

use App\Models\Changelog;
use Illuminate\Database\Seeder;

class ChangelogSeeder extends Seeder
{
    public function run(): void
    {
        Changelog::insert([
            [
                'version' => '0.1.0',
                'title'   => 'Rilis Awal Eko-Scribe',
                'kind'    => 'minor',
                'released_at' => '2026-05-15',
                'notes'   => "- Skema baru: Materials, Questions, Submissions.\n"
                            ."- Multi peran: Administrator, Guru, Siswa.\n"
                            ."- Generator materi & soal esai berbasis Google Gemini.\n"
                            ."- Koreksi otomatis esai siswa: skor 0-100 + umpan balik AI.\n"
                            ."- CRUD pengguna & menu dinamis untuk administrator.\n"
                            ."- Hashing kata sandi bcrypt, role-based middleware.",
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'version' => '0.2.0',
                'title'   => 'Glassmorphism, Modal, Konfigurasi AI Dinamis & Landing Page',
                'kind'    => 'minor',
                'released_at' => '2026-05-15',
                'notes'   => "- Redesign UI total: gaya TailAdmin + glassmorphism (sidebar, navbar, header, kartu).\n"
                            ."- Semua aksi (tambah/edit/hapus) kini menggunakan modal di tempat (tanpa pindah halaman).\n"
                            ."- Filter pencarian + filter kategori di Kelola Pengguna, Kelola Menu, dan Changelog.\n"
                            ."- Halaman \"Konfigurasi AI\": administrator dapat mengatur API key & memilih model Gemini langsung dari UI.\n"
                            ."- Halaman Profil baru: dukungan unggah/hapus foto profil, ganti kata sandi, hapus akun.\n"
                            ."- Halaman Changelog dengan timeline dan kategori (major/minor/patch/hotfix).\n"
                            ."- Landing page publik dengan hero, fitur, dan CTA.\n"
                            ."- Dashboard adaptif per peran (Admin/Guru/Siswa) dengan kartu statistik.\n"
                            ."- Versi aplikasi diekspos di sidebar & footer.",
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'version' => '0.3.0',
                'title'   => 'Kelas, Ujian Anti-Cheat, Multi-Provider AI, Multi Mail & Dark Mode',
                'kind'    => 'minor',
                'released_at' => '2026-05-20',
                'notes'   => "- Sistem Kelas: guru membuat kelas, siswa join via kode unik; materi & ujian bisa terikat ke kelas.\n"
                            ."- Sistem Ujian penuh dengan pengaturan anti-kecurangan: larangan pindah tab (dengan batas maks atau langsung gugur), larang copy-paste, larang klik kanan, fullscreen wajib, acak soal.\n"
                            ."- Mode koreksi ujian: Otomatis AI / Manual oleh Guru / Hybrid; guru dapat meluruskan/mengedit hasil AI.\n"
                            ."- Visibilitas hasil ujian: tampilkan hasil otomatis, opsi rilis manual, leaderboard, izinkan review jawaban.\n"
                            ."- Cetak laporan ujian sebagai PDF (DomPDF) untuk dokumentasi guru.\n"
                            ."- Forum Diskusi pada setiap materi (pertanyaan + balasan), notifikasi email otomatis ke guru/siswa.\n"
                            ."- Multi-provider AI: Gemini, OpenAI, Anthropic, OpenRouter, Groq.\n"
                            ."- API Key Pool: simpan banyak key per provider, urutkan prioritas (drag), tampilkan sisa kuota & terakhir dipakai, auto-rotasi saat limit/error.\n"
                            ."- Multi-provider Email: SMTP/PHPMailer, Brevo, MailerSend, SendPulse — kredensial dikelola dari halaman admin.\n"
                            ."- Dark mode dengan toggle Light/Dark/System; preferensi disimpan di akun.\n"
                            ."- Sidebar dirapikan: \"Buat Materi\" pindah ke modal di halaman Materi Saya.\n"
                            ."- Endpoint AJAX generate materi agar guru bisa preview & edit dalam satu modal sebelum simpan.\n"
                            ."- Buat & edit soal manual + jalankan ulang AI per submission.",
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'version' => '0.3.1',
                'title'   => 'Hotfix Modal, Sidebar Mobile, Dark Mode, App Settings & Mail Key Pool',
                'kind'    => 'hotfix',
                'released_at' => '2026-05-20',
                'notes'   => "- Modal yang sebelumnya tidak terbuka kini berfungsi: setiap modal punya x-data sendiri (event open-modal/close-modal global).\n"
                            ."- Sidebar mobile dapat dibuka via tombol menu di topbar (state x-data dipindah ke <body>, ada backdrop blur).\n"
                            ."- Dark mode kontras teks diperbaiki: tidak ada lagi teks yang sulit dibaca; background gradient gelap khusus, badge dan kartu adaptif.\n"
                            ."- Pengaturan Aplikasi (/admin/app) — administrator dapat mengganti nama aplikasi, tagline, dan footer langsung dari UI tanpa edit .env.\n"
                            ."- Mail Key Pool (/admin/mail-keys) — multi API key per provider mail (Brevo, MailerSend, SendPulse) dengan prioritas, kuota, & rotasi otomatis seperti AI Key Pool.\n"
                            ."- MailService di-refactor untuk menggunakan rotasi key (dengan fallback ke setting tunggal lama).\n"
                            ."- Tema (Light/Dark/System) dapat dipilih cepat dari topbar; preferensi tersimpan di kolom users.theme + localStorage.",
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }
}
