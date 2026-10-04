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

* **Setiap branch `feature/*` baru wajib dibuat dari `origin/dev` terbaru**, termasuk setelah PR sebelumnya selesai. Branch fitur lain, `main`, atau checkout lama tidak boleh menjadi titik awal.
* Mulai dengan working tree bersih, lalu sinkronkan referensi remote dan buat branch tanpa mewarisi upstream `dev`:
  ```bash
  git fetch origin dev
  git switch --no-track -c feature/<nama-fitur> origin/dev
  ```
* Sebelum mulai mengedit, jalankan `git rev-parse HEAD origin/dev`. Kedua hash harus sama untuk memastikan branch baru berawal tepat dari `dev` terbaru.
* Saat pertama kali push, tetapkan upstream branch fitur sendiri: `git push -u origin feature/<nama-fitur>`.
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

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/dusk (DUSK) - v8
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/svelte (INERTIA_SVELTE) - v3
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-svelte/core rules ===

# Inertia + Svelte

- IMPORTANT: Activate `inertia-svelte-development` when working with Inertia Svelte client-side patterns.

</laravel-boost-guidelines>

## 6. Design system

Untuk perubahan UI, ikuti [Studio Engineering design system](docs/design-system.md). Bukti evaluasi ada di [redesign validation](docs/redesign-validation.md); cara menjalankan pengujian terisolasi ada di [testing guide](docs/testing.md).
