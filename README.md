# CMS Blog Starter - Laravel Livewire Starterkit

CMS dan starter blog yang sederhana namun kokoh, dibangun dengan **Laravel 13**, **Livewire 4**, **Tailwind CSS v4**, dan **MySQL**. Dilengkapi autentikasi, sistem peran & hak akses (roles & permissions), dashboard admin untuk mengelola konten, dan hardening keamanan di berbagai lapisan.

Dirancang untuk jadi titik awal yang **mudah dipahami dan dikembangkan lebih lanjut** — bukan framework admin panel pihak ketiga yang menyembunyikan logikanya. Setiap komponen Livewire, policy, dan migration ditulis eksplisit supaya bisa langsung dibaca dan diubah.

---

## Fitur

**Publik**
- Beranda editorial: hero, post unggulan, daftar post terbaru, navigasi topik/kategori.
- Halaman blog dengan pencarian live dan filter kategori (Livewire, tanpa reload halaman).
- Halaman detail post dengan rendering Markdown yang aman, related posts, dan tag.

**Admin (`/admin`)**
- Dashboard dengan statistik yang **ter-scope sesuai izin** (Author hanya melihat datanya sendiri).
- CRUD Post lengkap: editor Markdown dengan tab Write/Preview, upload gambar unggulan, kategori, tag, SEO meta title/description, status draft/published.
- CRUD Kategori.
- Manajemen User: buat akun, atur peran, aktifkan/nonaktifkan akun — dengan proteksi supaya admin terakhir tidak bisa dikunci sendiri.
- Pengaturan situs (nama, tagline, deskripsi, footer).

**Akun & Akses**
- Login, registrasi, lupa/reset password, verifikasi email, konfirmasi password — dibangun dengan Livewire (pola yang sama dengan Laravel Breeze stack Livewire).
- Halaman profil self-service (ganti nama/email/password, hapus akun) untuk **semua** pengguna, termasuk yang tanpa peran CMS.
- Tiga peran siap pakai: **Admin**, **Editor**, **Author**, dengan tujuh permission granular (lihat bagian [Roles & Permissions](#roles--permissions)).

---

## Tech stack

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | **8.4** | |
| Laravel | **^13.0** | struktur skeleton "ramping" (tanpa `Kernel.php`/`Handler.php`, konfigurasi terpusat di `bootstrap/app.php`) |
| Livewire | **^4.0** | komponen ditulis dalam format *multi-file* klasik (class + view terpisah) untuk kejelasan maksimal, bukan format single-file baru — lihat catatan di bawah |
| Tailwind CSS | **^4.3** | konfigurasi CSS-first (`@theme` di `resources/css/app.css`), tanpa `tailwind.config.js` |
| Spatie `laravel-permission` | **^6.9** | roles & permissions |
| MySQL | 8.0+ | |
| Vite | ^8.0 | via `laravel-vite-plugin` ^3.2 dan `@tailwindcss/vite` ^4.3 |

> **Kenapa komponen Livewire multi-file, bukan single-file (default baru Livewire 4)?** Format single-file mencampur class PHP dan template Blade dalam satu file — ringkas, tapi lebih sulit dibaca untuk yang baru belajar Livewire dan lebih sulit di-diff. Starter ini memilih keterbacaan: class di `app/Livewire/...`, view di `resources/views/livewire/...`. Format single-file tetap didukung penuh oleh Livewire 4 jika Anda ingin migrasikan komponen manapun (`php artisan make:livewire NamaBaru` tanpa flag `--mfc`).

---

## Persyaratan

- PHP **8.4** beserta ekstensi umum (`pdo_mysql`, `mbstring`, `xml`, `curl`, `gd` atau `imagick` untuk validasi gambar)
- Composer 2.x
- Node.js **20.19+ atau 22.12+**, npm
- MySQL 8.0+ (atau MariaDB 10.6+)

---

## Instalasi (lingkungan lokal baru)

```bash
# 1. Masuk ke folder proyek
cd laravel-cms-blog

# 2. Install dependency PHP
composer install

# 3. Salin file environment lalu buat application key
cp .env.example .env
php artisan key:generate

# 4. Buat database MySQL-nya (lewat CLI, TablePlus, phpMyAdmin, dll)
mysql -u root -p -e "CREATE DATABASE cms_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Isi kredensial database Anda di .env
#    DB_DATABASE=cms_blog
#    DB_USERNAME=root
#    DB_PASSWORD=...

# 6. Jalankan migration + seeder (roles, akun demo, kategori, contoh post)
php artisan migrate --seed

# 7. Buat symlink storage (untuk gambar unggulan post)
php artisan storage:link

# (Khusus Linux/Mac) pastikan folder storage & cache bisa ditulis webserver
chmod -R 775 storage bootstrap/cache

# 8. Install dependency frontend & build asset
npm install
npm run build

# 9. Jalankan server lokal
php artisan serve
```

Buka **http://localhost:8000** — beranda publik akan langsung terisi contoh post. Untuk masuk ke dashboard, buka **http://localhost:8000/login**.

Untuk pengembangan sehari-hari, jalankan `npm run dev` di terminal terpisah (Hot Module Reload) alih-alih `npm run build`.

### Akun demo (hasil seeding)

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `AdminPass123!` |
| Editor | `editor@example.com` | `EditorPass123!` |
| Author | `author@example.com` | `AuthorPass123!` |

⚠️ **Ini hanya untuk pengembangan lokal.** Seeder (`database/seeders/AdminUserSeeder.php`) sengaja hanya memakai password tetap ini saat `APP_ENV=local`. Di environment lain, seeder otomatis men-generate password acak 20 karakter dan menampilkannya **satu kali** di console — kecuali Anda mengisi `ADMIN_SEED_PASSWORD` di `.env` sendiri. Selalu ganti password akun admin sebelum men-deploy ke mana pun selain komputer Anda sendiri.

---

## Menjalankan dengan Docker

Kalau Anda tidak ingin memasang PHP, Composer, Node, dan MySQL di komputer Anda, gunakan cara ini. Cukup butuh **Docker** beserta **Docker Compose v2** — dependency PHP, dependency frontend, dan database semuanya dijalankan di dalam container.

```bash
# 1. Masuk ke folder proyek
cd laravel-cms-blog

# 2. Build image lalu jalankan aplikasi + MySQL
docker compose up --build
```

Buka **https://localhost** — sama seperti instalasi manual, beranda publik akan langsung terisi contoh post (kredensial demo ada di bagian [Akun demo](#akun-demo-hasil-seeding) di atas). Sertifikat yang dipakai adalah **self-signed**, jadi browser akan menampilkan peringatan keamanan — aman untuk ditekan "Lanjutkan" selama pengembangan lokal.

### Apa yang dilakukan otomatis

Saat pertama kali dijalankan, image dan entrypoint mengurus semuanya tanpa langkah manual:

- **Build aset frontend** di dalam image (stage Node 22: `npm ci` + `npm run build`), jadi hasil build Vite sudah tersedia di `public/build`.
- **Membuat `.env`** dari `.env.example` bila belum ada, lalu men-generate `APP_KEY` otomatis.
- **Membuat symlink** `storage:link` untuk gambar unggulan.
- **Men-generate sertifikat TLS self-signed** (lewat `openssl`) bila belum ada, supaya Nginx bisa langsung melayani HTTPS.
- **Menunggu MySQL siap** (probe koneksi PDO, bukan sekadar `sleep`) sebelum melanjutkan.
- **Menjalankan migration + seeder** (roles, akun admin, kategori, contoh post) pada boot pertama.

Sedangkan datanya persisten lewat tiga volume: `dbdata` untuk database MySQL, `storage` untuk file upload serta log, dan `certs` untuk sertifikat TLS self-signed — isinya tetap ada walau container di-restart, sehingga sertifikat tidak di-generate ulang setiap kali boot.

### Perintah yang sering dipakai

```bash
# Buka shell / tinker di dalam container aplikasi
docker compose exec app php artisan tinker

# Jalankan perintah artisan lain
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force

# Lihat log aplikasi secara live
docker compose logs -f app

# Hentikan container (data & volume tetap aman)
docker compose down

# Hentikan container dan hapus volume database (data hilang)
docker compose down -v
```

### Konfigurasi & port

Port yang dipetakan ke host: **80** dan **443** untuk aplikasi (HTTP di port 80 otomatis mengembalikan redirect `301` ke HTTPS di port 443) dan **3306** untuk MySQL (supaya bisa disambung dengan klien desktop seperti TablePlus). Karena aplikasi disajikan lewat HTTPS dengan sertifikat **self-signed**, browser akan memperingatkan saat pertama dibuka — cukup lanjutkan selama pengembangan lokal. Kalau port tersebut sudah dipakai di komputer Anda, ubah atau hapus pemetaannya di `docker-compose.yml`.

Semua nilai di bawah dapat di-override dari `.env` (kalau ada) atau dari variabel environment saat memanggil `docker compose`:

| Variabel | Default | Keterangan |
|---|---|---|
| `APP_ENV` | `local` | |
| `APP_DEBUG` | `true` | |
| `APP_URL` | `https://localhost` | |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `mysql` | nama service database, bukan `127.0.0.1` |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | `cms_blog` | |
| `DB_USERNAME` | `cms_user` | |
| `DB_PASSWORD` | `secret` | |
| `DB_ROOT_PASSWORD` | `root` | password root MySQL (hanya dipakai di dalam container) |
| `RUN_MIGRATIONS` | `true` | jalankan `migrate --force` saat boot |
| `RUN_SEEDERS` | `true` | jalankan `db:seed --force` saat boot |
| `ADMIN_SEED_EMAIL` | `admin@example.com` | akun admin hasil seeding |
| `ADMIN_SEED_PASSWORD` | `AdminPass123!` | lihat peringatan di bawah |

> Kalau Anda ingin mengeset `RUN_SEEDERS=false` (misalnya supaya data demo tidak ditambahkan lagi saat restart), cukup jalankan `RUN_SEEDERS=false docker compose up -d` atau ubah nilainya di `.env`.

Kedua service juga dilengkapi **healthcheck**: MySQL dicek dengan `mysqladmin ping` (interval 10s, timeout 5s, retries 10, start period 40s) dan service `app` baru dijalankan setelah MySQL berstatus `healthy`; sementara container aplikasi sendiri dicek lewat endpoint `curl -fsSk https://127.0.0.1/up` bawaan Laravel (opsi `-k` karena sertifikat self-signed).

⚠️ **Kredensial di `docker-compose.yml` hanya untuk pengembangan lokal.** Jangan pernah memakai password default ini (`secret`, `root`, `AdminPass123!`) di server produksi — ganti semuanya lewat `.env` atau variabel environment, dan aktifkan `APP_DEBUG=false`.

---

## Struktur proyek

```
app/
├── Enums/PostStatus.php           Status post (draft/published) sebagai PHP enum
├── Http/
│   ├── Controllers/               Hanya untuk halaman non-interaktif (Home, Blog show, Auth callback)
│   └── Middleware/
│       ├── SecurityHeaders.php    CSP, X-Frame-Options, dst — lihat bagian Keamanan
│       └── EnsureUserIsNotBlocked.php
├── Livewire/
│   ├── Auth/                      Login, Register, ForgotPassword, ResetPassword, VerifyEmail, ConfirmPassword
│   ├── Profile/Edit.php           Halaman akun self-service
│   ├── Blog/PostList.php          Blog publik (pencarian + filter kategori)
│   └── Admin/
│       ├── Dashboard.php
│       ├── Posts/{PostIndex,PostForm}.php
│       ├── Categories/CategoryManager.php
│       ├── Users/UserManager.php
│       └── Settings/SettingsForm.php
├── Models/                        User, Post, Category, Tag, Setting
├── Policies/                      PostPolicy, CategoryPolicy, UserPolicy — SATU tempat aturan otorisasi
├── Providers/                     AppServiceProvider (rate limiter, aturan password), AuthServiceProvider (registrasi policy)
└── Services/
    ├── MarkdownRenderer.php       Satu-satunya tempat Markdown → HTML dikonversi (lihat Keamanan)
    └── SecurityLog.php            Log kejadian sensitif ke channel terpisah

database/
├── migrations/
├── seeders/                       RolesAndPermissionsSeeder, AdminUserSeeder, CategorySeeder, PostSeeder, SettingSeeder
└── factories/

resources/views/
├── components/
│   ├── layout/{app,admin,guest}.blade.php   Tiga layout: publik, admin (sidebar), auth
│   └── forms/                                Input, label, button, dll — dipakai ulang di semua form
├── livewire/                       View untuk tiap komponen Livewire (mengikuti struktur app/Livewire)
├── home.blade.php, blog/show.blade.php
└── errors/                         403/404/419/429/500/503 kustom, mandiri (tidak butuh DB/Vite)

tests/
├── Feature/Auth/                  Login, rate limiting, registrasi tanpa peran
├── Feature/Admin/                 Access control per peran, IDOR, tamper-proofing, upload berbahaya, manajemen user
├── Feature/PublicSiteTest.php     Draft tersembunyi, XSS, SQL injection
└── Unit/MarkdownRendererTest.php
```

---

## Roles & Permissions

Menggunakan [`spatie/laravel-permission`](https://spatie.be/docs/laravel-permission). Tujuh permission granular, dikelompokkan ke tiga peran bawaan (lihat `database/seeders/RolesAndPermissionsSeeder.php`):

| Permission | Admin | Editor | Author |
|---|:---:|:---:|:---:|
| `view dashboard` | ✅ | ✅ | ✅ |
| `manage own posts` (buat/ubah/hapus post **miliknya sendiri**) | ✅ | ✅ | ✅ |
| `manage all posts` (buat/ubah/hapus **semua** post) | ✅ | ✅ | – |
| `publish posts` | ✅ | ✅ | – |
| `manage categories` | ✅ | ✅ | – |
| `manage users` | ✅ | – | – |
| `manage settings` | ✅ | – | – |

Semua pengecekan izin ada di `app/Policies/*.php` dan dipanggil ulang di **dalam setiap action method** Livewire (bukan cuma di `mount()`) — lihat bagian Keamanan untuk alasannya.

Menambah peran baru: buat lewat `Role::findOrCreate('nama', 'web')`, beri permission dengan `->syncPermissions([...])`, lalu tambahkan pilihannya di `UserManager` — tidak perlu mengubah policy karena semua policy membaca permission, bukan nama peran secara langsung (kecuali aturan "admin terakhir" yang memang sengaja mengecek peran `admin` secara eksplisit).

---

## Keamanan

Poin-poin dari permintaan awal (RCE, LFI, XSS, SQL injection, tamper/bypass) dan bagaimana masing-masing ditangani di starter ini:

**SQL Injection** — Seluruh query memakai Eloquent/Query Builder dengan parameter binding (termasuk pencarian `LIKE`, lihat `Post::scopeSearch()`). Tidak ada `DB::raw()`/`whereRaw()` yang menyisipkan input pengguna secara langsung. Diuji di `tests/Feature/PublicSiteTest.php` dengan payload seperti `' OR '1'='1` dan `'; DROP TABLE posts; --`.

**XSS** — Blade meng-escape semua output `{{ }}` secara default. Satu-satunya tempat HTML dirender mentah (`{!! !!}`) adalah isi post, yang **selalu** melalui `App\Services\MarkdownRenderer` — satu titik konfigurasi yang men-strip HTML mentah dari sumber Markdown dan menonaktifkan skema link berbahaya (`javascript:`, `data:`, dll). Diuji dengan payload `<script>`, `onerror=`, `<iframe>`, dan link `javascript:` di `tests/Unit/MarkdownRendererTest.php` dan `tests/Feature/PublicSiteTest.php`.

**Upload file (jalur RCE paling umum)** — Upload gambar divalidasi lewat rule `image` (memeriksa data gambar sungguhan, bukan cuma ekstensi), dibatasi ke `jpg,jpeg,png,webp` (SVG sengaja **tidak** diizinkan karena bisa membawa `<script>`), maksimum 2 MB, dan disimpan dengan nama acak (bukan nama asli file). File yang tersimpan di `storage/app/public` disajikan sebagai file statis lewat symlink `public/storage` — **tidak pernah dieksekusi sebagai PHP**, jadi bahkan seandainya validasi kebobolan, tidak ada jalur eksekusi kode. Diuji dengan mengunggah web shell PHP asli (`<?php system($_GET['c']); ?>`) berekstensi `.php` maupun `.jpg`.

**LFI / path traversal** — Tidak ada `include()`/`require()` berdasar input pengguna. Post/kategori diambil lewat Eloquent (ID atau slug tervalidasi), bukan path file mentah.

**Tamper / bypass otorisasi** — Ini yang paling mudah luput di aplikasi Livewire, karena *public property* komponen secara teknis bisa dikirim ulang dari browser dengan nilai berbeda dari yang ditampilkan UI. Starter ini menangani dengan:
- Setiap action method (save, delete, publish, ubah peran) **memanggil ulang policy check di dalam method itu sendiri** — bukan cuma di `mount()`, karena `mount()` hanya jalan sekali saat halaman dibuka, sedangkan method Livewire lain bisa dipanggil kapan saja.
- Properti sensitif (ID post/kategori/user yang sedang diedit) ditandai `#[Locked]`, sehingga Livewire menolak keras jika ada percobaan mengubahnya dari sisi klien.
- Field `status` (publish) selalu dicek ulang terhadap permission `publish posts` saat *save*, apa pun nilai yang dikirim form — Author yang mencoba mem-publish lewat request yang dimanipulasi tetap akan disimpan sebagai draft. Diuji di `test_author_cannot_publish_even_if_the_request_is_tampered_with`.
- `user_id` (penulis) tidak pernah diambil dari input form, selalu dari `Auth::user()` di server.
- Admin tidak bisa mengubah peran/menonaktifkan dirinya sendiri, dan admin aktif terakhir tidak bisa didemosi/dinonaktifkan/dihapus siapa pun — mencegah CMS terkunci dari dalam.

**Autentikasi** — Password di-hash dengan bcrypt (rounds diatur di `.env`), aturan kompleksitas password lewat `Password::defaults()` (`AppServiceProvider`), rate limiting brute-force yang ketat di form login (5 percobaan per kombinasi email+IP, plus batas per-IP terpisah), akun yang dinonaktifkan admin langsung ter-logout di request berikutnya (`EnsureUserIsNotBlocked`), dan mengganti password otomatis mencabut sesi di perangkat lain (`Auth::logoutOtherDevices()`).

**Header keamanan HTTP** — `SecurityHeaders` middleware menambahkan `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, dan `Strict-Transport-Security` (saat HTTPS) di setiap response, termasuk halaman error.

> Satu trade-off yang disengaja dan didokumentasikan secara terbuka: CSP mengizinkan `'unsafe-eval'` (script) dan `'unsafe-inline'` (style) karena Alpine.js — yang dibundel Livewire — mengevaluasi ekspresi directive lewat `new Function()` dan mengubah `x-show` lewat inline style. ini bukan celah yang lupa ditutup, tapi kompromi yang wajar untuk aplikasi berbasis Livewire/Alpine. Jika Anda butuh CSP yang lebih ketat, Alpine menyediakan build CSP-safe terpisah (`@alpinejs/csp`) dengan ekspresi directive yang lebih terbatas.

**CSRF** — Bawaan Laravel (aktif otomatis untuk semua form dan request Livewire).

**Mass assignment** — Semua model memakai `$fillable` eksplisit, tidak ada `$guarded = []`. Kolom sensitif (`is_active`, `email_verified_at` pada `User`, `user_id` pada `Post`) sengaja **tidak** masuk `$fillable` — hanya bisa diset lewat `forceFill()` di kode tepercaya (seeder, `UserManager`), tidak pernah dari mass-assignment request.

**Logging keamanan** — Login gagal, lockout, perubahan peran, aktivasi/nonaktifasi/penghapusan user, dan perubahan setting dicatat ke channel `security` terpisah (`storage/logs/security-*.log`, rotasi harian, retensi 90 hari) lewat `App\Services\SecurityLog` — lihat `config/logging.php`.

**Yang perlu Anda lakukan sendiri sebelum production**: set `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` (di balik HTTPS), pastikan MySQL berjalan dengan user berhak terbatas (bukan `root`), aktifkan HTTPS (lewat reverse proxy/load balancer), dan jalankan `composer audit` / `npm audit` secara berkala.

Tidak ada sistem yang bisa diklaim 100% "tidak bisa diretas" — yang bisa dijamin adalah semua kelas kerentanan yang diminta di atas ditangani dengan praktik standar industri, teruji lewat test otomatis, bukan sekadar asumsi.

---

## Testing

```bash
php artisan test
```

Mencakup: alur login/registrasi + rate limiting, access control per peran untuk setiap route admin, percobaan IDOR (Author membuka/menghapus post orang lain), percobaan tamper status publish, upload file berbahaya (web shell, SVG dengan script, file menyamar), proteksi admin terakhir, XSS tersimpan, dan SQL injection di pencarian.

Test memakai SQLite in-memory (lihat `phpunit.xml`) supaya cepat dan tidak menyentuh database MySQL Anda.

---

## Ide pengembangan lanjutan

Starter ini sengaja tidak menyertakan (supaya tetap sederhana), tapi skemanya sudah siap dikembangkan ke arah:
- **Komentar** pada post (tabel belum ada — tambahkan migration + moderasi di admin).
- **Media library** penuh (saat ini hanya satu gambar unggulan per post).
- **Two-factor authentication** (Laravel Fortify bisa ditambahkan di atas struktur yang ada).
- **API** (Laravel Sanctum) jika suatu saat perlu dikonsumsi dari aplikasi lain.
- Halaman **About/Contact** statis maupun dinamis.

---

## Lisensi

MIT — silakan pakai, ubah, dan kembangkan sesuka Anda.
