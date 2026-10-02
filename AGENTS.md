# AGENTS.md

Canonical instructions for any coding agent (Claude, Gemini, Codex, Antigravity, etc.) working in this repo. `CLAUDE.md` and `GEMINI.md` are symbolic links pointing directly here.

---

## 1. Project Overview

**agungandre.my.id** adalah website portofolio dan platform personal branding milik Agung Andre.
* **Backend:** Laravel 13 (PHP 8.3 / 8.4)
* **Frontend:** Inertia.js v3 + Svelte 5 (menggunakan Runes: `$state`, `$derived`, `$props`)
* **Styling:** Tailwind CSS v4 (CSS-first, `@theme` di `app.css`, tanpa `tailwind.config.js`)
* **Routing/Action Typing:** Laravel Wayfinder (`@/actions/` dan `@/routes/`)
* **Autentikasi & Security:** Laravel Fortify (2FA, Password Reset, Profile Management, Passkeys)
* **Testing:** Pest v4
* **Database Lokal:** SQLite (`database/database.sqlite`)

---

## 2. Local Environment (EnvKit) & Commands

Website lokal ini dikelola melalui **EnvKit** (Nginx + PHP-FPM + SSL):
* **Local URL:** `https://agungandre.test` (SSL otomatis aktif via EnvKit)
* **Root:** `D:\agungandre.my.id`
* **Doc Root:** `D:\agungandre.my.id\public`

### Perintah Esensial
```bash
# Instalasi & Setup Pertama Kali
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --force

# Development Server
npm run dev                  # Menjalankan Vite dev server untuk Svelte & Tailwind
composer dev                 # EnvKit menyediakan web server; jalankan queue + Vite
composer dev:standalone      # Tanpa EnvKit: jalankan serve + queue + Vite

# Testing & Quality
php artisan test             # Menjalankan test suite (Pest)
npm run types:check          # Pemeriksaan tipe Svelte (svelte-check)
npm run lint:check           # ESLint
vendor/bin/pint --dirty --format agent # Formatter kode PHP sesuai standar Laravel

# Production Build
npm run build                # Kompilasi aset Vite untuk production
```

---

## 3. Aturan & Konvensi Pengembangan

### A. Frontend (Svelte 5 + Inertia v3)
1. **Gunakan Runes Svelte 5**:
   * Selalu gunakan sintaks Svelte 5 modern: `$state()`, `$derived()`, `$effect()`, `$props()`.
   * **DILARANG** menggunakan sintaks usang Svelte 3/4 seperti `export let prop`, `$: reactiveVariable`, atau `$$props`.
2. **Inertia v3**:
   * Halaman utama berada di `resources/js/pages/`.
   * Komponen UI berada di `resources/js/components/`.
   * Komunikasi server-client menggunakan `Inertia::render()` dari controller Laravel, bukan view Blade.
   * Tidak ada Axios dalam dependensi inti; gunakan built-in HTTP client Inertia atau `fetch`.
3. **Laravel Wayfinder**:
   * Gunakan import fungsi aksi/rute yang ter-generate dari `@/actions/` atau `@/routes/` untuk navigasi dan submit form bertipe aman (*type-safe*).

### B. Styling (Tailwind CSS v4)
* Tailwind v4 berbasis CSS-first (`resources/css/app.css` dengan `@import "tailwindcss";`).
* **Jangan buat file `tailwind.config.js`** karena konfigurasi tema memakai direktif `@theme` di dalam CSS.

### C. Backend (Laravel 13 & PHP)
* Selalu deklarasikan tipe kembalian (*return type*) dan tipe parameter fungsi secara eksplisit.
* Gunakan PHP 8 constructor property promotion:
  ```php
  public function __construct(public ProjectService $service) {}
  ```
* Gunakan Form Request untuk validasi data input di level controller.
* **Jangan pernah jalankan `php artisan config:cache` di laptop/dev environment.** Cache config membekukan environment dan bisa merusak isolasi database SQLite saat testing. Perintah caching hanya untuk production deployment.

### D. Python & CLI Tooling di Laptop Ini
* Laptop ini menggunakan `uv` sebagai package manager dan runtime tool manager tunggal untuk Python (DILARANG menggunakan `pip install` global atau `python -m venv`).

---

## 4. Alur Branch dan Pull Request

* Kerjakan perubahan di branch `feature/*`, lalu buat PR ke `dev`. CI menjalankan build, Pint, ESLint, pemeriksaan tipe Svelte, dan Pest untuk PR ini.
* Setelah perubahan di `dev` selesai diperbaiki dan diverifikasi, pemilik membuat PR dari `dev` ke `main`.
* Deploy produksi hanya berjalan pada push ke `main` setelah pemeriksaan CI lulus. Jangan arahkan PR fitur langsung ke `main`.

---

## 5. Laravel Boost MCP Integration

Repository ini dilengkapi dengan Laravel Boost MCP server. Manfaatkan tools Boost saat tersedia:
* `search-docs`: Selalu gunakan sebelum melakukan perubahan besar untuk memeriksa dokumentasi versi spesifik paket Laravel/Inertia yang terpasang.
* `database-query` / `database-schema`: Untuk memeriksa skema tabel sebelum membuat migrasi atau model baru.
* `browser-logs`: Untuk membaca error konsol browser saat debugging.
* `get-absolute-url`: Untuk me-resolve skema, domain, dan port yang tepat bagi project URLs.

<laravel-boost-guidelines>
=== foundation rules ===
- php: ^8.3 (Platform 8.3 / Runtime 8.4)
- framework: laravel 13
- frontend: inertia v3 + svelte 5 + tailwind v4
- formatter: pint (--format agent)
- testing: pest v4
</laravel-boost-guidelines>
