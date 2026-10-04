# Pengujian lokal dan browser

Feature tests memakai SQLite `:memory:` dari `phpunit.xml`. Jalankan `php artisan test --compact`; database development tidak dipakai. Jangan menjalankan `config:cache` di development.

Dusk memakai `.env.dusk`, atau `.env.dusk.testing` untuk `--env=testing`, dan database khusus `storage/framework/testing/dusk.sqlite`. Base test case memverifikasi environment, koneksi, dan path sebelum `DatabaseMigrations` berjalan; konfigurasi cache atau database lain membuat pengujian berhenti. File SQLite khusus dibuat otomatis tanpa menyentuh `database/database.sqlite`.

Siapkan aset dan ChromeDriver:

```sh
npm run build
php artisan dusk:chrome-driver --detect
```

Jalankan server pengujian di terminal pertama:

```sh
php artisan serve --env=dusk --host=127.0.0.1 --port=8010
```

Jalankan browser tests di terminal kedua, lalu hentikan server setelah selesai:

```sh
php artisan dusk --without-tty
```

URL default adalah `http://127.0.0.1:8010`, terpisah dari website EnvKit. Dusk sementara mengganti `.env` selama eksekusi dan mengembalikannya setelah selesai; gunakan server khusus di atas dan hindari menjalankan pekerjaan development bersamaan.

Chrome dicari di lokasi umum Windows, Linux, dan macOS. Untuk instalasi khusus, set `DUSK_CHROME_BINARY` ke executable Chrome/Chromium. `DUSK_DRIVER_URL` memilih WebDriver eksternal dan menonaktifkan startup ChromeDriver lokal; tanpa override binary, browser ditentukan oleh layanan tersebut.

Environment Dusk menggunakan kunci enkripsi publik khusus pengujian, session cookie terpisah, mailer array, dan cache array. Jangan memakai kunci ini atau database Dusk untuk production. Feature tests tetap memakai environment `testing` dengan database in-memory.

## Fixture untuk pemeriksaan visual

Selesaikan Dusk terlebih dahulu, lalu hentikan server `artisan serve`. Browser tests menghapus dan membuat ulang skema, sehingga fixture visual harus dibuat setelah pengujian tersebut.

```sh
php tests/Fixtures/seed-portfolio.php --check
php tests/Fixtures/seed-portfolio.php
php -S 127.0.0.1:8010 -t public tests/Fixtures/router.php
```

Command fixture memverifikasi environment `dusk` dan path SQLite khusus sebelum migrasi atau seed. Command menolak database lain dan konfigurasi cache. Tidak ada data yang ditulis ke database development atau `storage/app/public`.

Fixture berisi tiga proyek publik bertanda `[QA]`, satu draft, satu artikel publik, satu artikel terjadwal, dan admin khusus test. Salah satu judul sengaja panjang dan salah satu cover sengaja tidak tersedia. Gambar PNG yang dihasilkan GD bertanda QA dan tersimpan di `public/.qa`, yang diabaikan Git. Tidak ada screenshot atau klaim karya asli dalam fixture ini.

Router khusus test menampilkan media melalui `/.qa`, mengarahkan disk publik ke direktori QA hanya untuk proses tersebut, dan memakai disk yang sama untuk upload artikel. Router tidak menambahkan rute Laravel atau mengubah konfigurasi production. Gunakan localhost:8010 untuk pemeriksaan visual, lalu hentikan server.

Admin memakai `BLOG_OWNER_EMAIL` dari environment Dusk (default `qa-owner@example.test`) dan kata sandi test `qa-preview-password`. Identitas dan kata sandi tersebut hanya dibuat dalam database QA. Jangan menjalankan Dusk saat seed visual sedang digunakan.

## Screenshot dan interaksi Chrome

Harness `tests/visual` memakai WebSocket bawaan Node 22+ dan Chrome DevTools Protocol, tanpa dependency baru. Gunakan profil Chrome khusus yang kosong; jangan arahkan port debug ke profil browser pribadi.

Contoh PowerShell setelah server fixture aktif:

```powershell
$qaChrome = Join-Path $env:ProgramFiles 'Google/Chrome/Application/chrome.exe'
$qaProfile = Join-Path (Get-Location) 'storage/framework/testing/chrome-qa'
Start-Process -FilePath $qaChrome -WindowStyle Hidden -ArgumentList @('--headless=new', '--remote-debugging-port=9223', "--user-data-dir=$qaProfile", '--no-first-run', 'about:blank')
node tests/visual/matrix.mjs public
node tests/visual/matrix.mjs cms
node tests/visual/interactions.mjs
node tests/visual/performance.mjs
```

`matrix.mjs` memeriksa lima lebar pada dua tema, memuat gambar lazy dengan scroll, menyimpan PNG/JSON di `storage/app/qa`, dan menyalin contoh 390/1440px ke `docs/screenshots`. `interactions.mjs` menguji keyboard/fokus, motion, tema, preview, dan validasi memakai fixture QA. Semua pemeriksaan CMS mengarah ke localhost:8010.

Opsional, periksa empty state situs EnvKit secara read-only:

```sh
node tests/visual/matrix.mjs empty https://agungandre.test
node tests/visual/performance.mjs https://agungandre.test/ storage/app/qa/performance-envkit-empty.json
```

Skenario `empty` hanya sesuai ketika database lokal memang kosong. Hentikan proses Chrome yang Anda mulai dan server fixture setelah selesai. Pengujian performa mengubah throttling hanya pada tab pengujian dan mengembalikannya di `finally`. Hasilnya merupakan pengukuran lab, bukan data pengguna nyata atau persentil ke-75.
