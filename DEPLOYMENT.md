# Deployment J2M ke Vercel (Docker + FrankenPHP)

## Acuan dan hasil audit

Implementasi mengikuti panduan resmi **Deploy Laravel on Vercel with Docker** (3 September 2026), terutama multi-stage build, package discovery setelah source disalin, container service, runtime environment, dan perintah deployment. Tidak membuat aplikasi baru atau mengganti halaman utama dengan contoh JSON.

Referensi resmi:

```text
https://vercel.com/kb/guide/laravel-php-with-docker
https://frankenphp.dev/docs/docker/
https://docs.docker.com/desktop/setup/install/windows-install/
https://vercel.com/docs/git/vercel-for-github
https://vercel.com/docs/vercel-blob
```

Audit source, lockfile, konfigurasi, route, controller, model, migration, seeder, dan test:

- Laravel terkunci di **13.40.0**, PHP project **^8.3**, PHP lokal **8.3.4**. Image `dunglas/frankenphp:1-php8.3-bookworm` mempertahankan minor PHP ini; tidak memaksakan PHP 8.4. Dependency tidak di-update.
- Struktur standar Laravel: `app`, `bootstrap`, `config`, `database`, `lang`, `public`, `resources`, `routes`, `storage`, `tests`.
- Konfigurasi lokal menggunakan **MySQL**, session/cache **database**, queue **sync**, filesystem **local**. Tidak ditemukan file SQLite lokal dalam direktori database. Default fallback config Laravel masih SQLite; image secara eksplisit memilih MySQL tanpa mengubah database lokal.
- Model: User, Category, Product, Cart, CartItem, Order, OrderItem. Semua dipertahankan. Transaksi dan row locking checkout tetap berjalan melalui database.
- Route/controller tetap menyediakan beranda, katalog/filter, detail produk, registrasi/login/logout, keranjang, review/konfirmasi checkout simulasi, riwayat/detail pesanan, admin produk/kategori/pesanan. Middleware auth, admin, CSRF dan throttling tetap berlaku.
- Empat migration menyediakan users/sessions, cache/cache_locks, jobs/job_batches/failed_jobs, dan tabel toko. Tidak ada migration atau seeding otomatis saat build/startup.
- Enam foto produk dalam `C:\laragon\www\j2m\public\images\products` disalin ke image. Pemetaan foto Satu Set Jok Custom dan label **FOTO HANYA ILUSTRASI** tidak diubah. Harga, stok, deskripsi, seeder, dan UI tidak diubah.
- Tidak ditemukan handler upload, form file upload, pemanggilan Storage untuk data permanen, dispatch job aplikasi, atau scheduler aplikasi pada scope `app`, `routes`, `resources`. Admin mengubah data produk, bukan mengunggah foto.
- Vite membangun CSS/JS dari lockfile melalui Node 22 dan `npm ci`. Runtime menerima `public/build/manifest.json` beserta aset hasil build, tidak menerima node_modules. Seluruh Blade dan gambar publik tetap disertakan.

## 1. Prasyarat dan instalasi

Siapkan akun Vercel/GitHub, akses container service pada project/team, Docker daemon Linux, dan database MySQL eksternal yang dapat diakses dari deployment. Database Laragon di localhost tidak otomatis tersedia dari Vercel. Preview dan Production harus memakai database terpisah.

Pada Windows, install Docker Desktop dari dokumentasi resmi di atas, aktifkan virtualisasi dan backend WSL 2 sesuai persyaratan installer, lalu jalankan Docker Desktop dalam mode Linux containers. Periksa `docker version` dan `docker info` berhasil menjangkau daemon.

Install Node.js yang kompatibel dengan Vercel CLI, kemudian:

```powershell
Set-Location 'C:\laragon\www\j2m'
npm install -g vercel
vercel --version
vercel login
docker version
```

Composer 2 dan PHP 8.3 diperlukan hanya untuk menjalankan tooling PHP di host. Docker menyediakan PHP, Composer build, dan Node build sendiri. Untuk rilis yang reproducible sepenuhnya, pin digest image setelah build berhasil diverifikasi; tag minor saat ini mengikuti patch image terbaru.

## 2. Desain container

`C:\laragon\www\j2m\Dockerfile` menggunakan stage base, dependencies, frontend, runtime. Composer menjalankan `install --no-dev --no-scripts`, kemudian `dump-autoload` setelah source tersedia. Hook project menjalankan package discovery. `check-platform-reqs --no-dev` menggagalkan build bila extension/platform tidak cocok. Tidak ada config cache atau optimize build yang membekukan environment.

Ekstensi eksplisit: **ctype, dom, fileinfo, mbstring** untuk requirement dependency, **pdo_mysql** untuk database aktual, **opcache** untuk runtime, dan **zip** untuk ekstraksi paket Composer. Ekstensi dasar seperti filter, hash, iconv, JSON, libxml, OpenSSL, PCRE, PDO, session, tokenizer disediakan PHP base dan diperiksa Composer. Redis, GD, intl dan pdo_pgsql tidak ditambahkan karena belum diperlukan fitur aktif.

Runtime berjalan sebagai `www-data`; hanya storage, bootstrap/cache, serta direktori kerja Caddy yang dibuat writable. Source tetap root-owned. Caddy mendengar `PORT` (default 8080), hanya mengekspos `/app/public`, melayani static asset dan meneruskan route aplikasi ke index.php. TLS diterminasi ingress Vercel. Caddy admin API dimatikan. `.env`, kredensial Composer, data storage host, cache host, SQLite, vendor/node_modules host, .git dan tests tidak masuk build context. Vendor production dibuat ulang dalam build, bukan disalin dari host.

`/health` mengembalikan `{"status":"ok"}` tanpa middleware web/session/auth; `/up` tetap ada. Ini **liveness**, bukan pemeriksaan kesiapan database. Middleware global masih berlaku.

## 3. Environment variables

Masukkan melalui Project Settings > Environment Variables; tentukan scope Development, Preview, Production dengan benar. Jangan commit secret atau menjadikannya build ARG/Docker ENV. Redeploy setelah perubahan environment.

| Variable | Nilai/kebutuhan |
| --- | --- |
| APP_KEY | Wajib: key rahasia stabil milik environment tersebut; jangan rotasi setiap deploy |
| APP_ENV | `production` (juga untuk preview agar perilaku production diuji) |
| APP_DEBUG | `false` |
| APP_NAME | `J2M` |
| APP_URL | URL HTTPS deployment/domain nyata, sesuai environment |
| LOG_CHANNEL | `stderr` |
| LOG_LEVEL | `warning` atau `info` sesuai kebutuhan observability |
| DB_CONNECTION | `mysql` |
| DB_HOST | Host nyata dari provider, bukan localhost Laragon |
| DB_PORT | Port dari provider (MySQL umumnya 3306) |
| DB_DATABASE | Nama database yang benar-benar telah dibuat |
| DB_USERNAME | User database dari provider dengan hak minimum yang diperlukan |
| DB_PASSWORD | Secret database, isi sendiri dari provider |
| SESSION_DRIVER | `database` |
| CACHE_STORE | `database` (bukan CACHE_DRIVER) |
| QUEUE_CONNECTION | `sync`; belum ada job asynchronous aplikasi |
| SESSION_SECURE_COOKIE | `true` pada HTTPS Vercel; `false` hanya untuk Docker HTTP lokal |
| SESSION_SAME_SITE | `lax` |
| SESSION_DOMAIN | Biarkan tidak diisi agar cookie host-only, termasuk preview |
| TRUSTED_PROXIES | `*` hanya pada ingress Vercel yang dipercaya; kosong untuk akses Docker langsung |
| FILESYSTEM_DISK | `local` hanya untuk temporary/non-durable; bukan tempat upload permanen |
| MYSQL_ATTR_SSL_CA | Jika provider meminta CA, path CA yang tersedia dalam container; jangan menunjuk path Windows |
| DB_URL | Opsional alternatif DSN; jangan isi bersamaan dengan DB_* yang bertentangan |
| PORT | Disediakan runtime Vercel; default image 8080 untuk lokal |

`TRUSTED_PROXIES` memungkinkan Laravel membaca scheme/host/IP dari ingress. Jangan gunakan wildcard ketika origin dapat diakses langsung dari jaringan tidak dipercaya. Preview memakai APP_URL dan secret sendiri; jangan menyalin production DB/key ke preview.

Untuk deployment baru, buat key dengan `php artisan key:generate --show` di terminal privat dan simpan di secret manager/dashboard, bukan source atau log CI. Untuk aplikasi yang sudah memiliki data terenkripsi, gunakan key existing yang sesuai, jangan menggantinya sembarangan. Alternatif input dashboard: `vercel env add APP_KEY` dan pilih scope yang benar.

## 4. Build dan menjalankan Docker lokal

Buat file privat `C:\laragon\www\j2m\.env.local` secara manual berisi nilai nyata dari tabel di atas. File sudah di-ignore Git/Docker. Format Docker env-file adalah `KEY=value`, tanpa `export` dan tanpa quote pembungkus. Jangan langsung memakai .env development yang mengaktifkan debug. Untuk HTTP lokal isi APP_URL dengan alamat localhost port 8080 dan SESSION_SECURE_COOKIE=false. Bila memakai MySQL Laragon dari Docker Desktop, gunakan DB_HOST=host.docker.internal dan pastikan izin/network MySQL sesuai; jangan mengubah data production untuk smoke test.

```powershell
Set-Location 'C:\laragon\www\j2m'
docker build --pull -t j2m:local .
docker run --rm j2m:local frankenphp validate --config /etc/frankenphp/Caddyfile --adapter caddyfile
docker run --rm j2m:local php -m
docker run --rm -d --name j2m-local -p 8080:8080 --env-file 'C:\laragon\www\j2m\.env.local' j2m:local
curl.exe --fail http://localhost:8080/health
curl.exe --fail http://localhost:8080/up
curl.exe --fail http://localhost:8080/
curl.exe --fail http://localhost:8080/produk
curl.exe --fail http://localhost:8080/masuk
curl.exe --fail 'http://localhost:8080/images/products/kulit%20motif(1).jpeg'
docker exec j2m-local id
docker exec j2m-local sh -c 'test -w /app/storage && test -w /app/bootstrap/cache && test ! -e /app/.env && test ! -e /app/public/hot && test ! -d /app/node_modules && test ! -d /app/tests'
docker logs j2m-local
```

Buka katalog di browser dan klik detail produk yang benar-benar ada (jangan mengasumsikan ID). DevTools Network harus menunjukkan CSS/JS hasil manifest dan seluruh gambar 200. Uji login customer/admin dengan akun test nyata, authorization admin, keranjang, checkout simulasi, dan logout. Periksa cookie secure pada HTTPS dan tidak ada mixed content. Uji request `/.env` ditolak dan `/composer.json` tidak membocorkan source. Periksa log setelah semua request. Setelah selesai: `docker stop j2m-local`.

Alternatif runtime lokal sesuai panduan: environment harus diekspor dulu; `-L` tidak otomatis membaca `.env.local`. Pada PowerShell, untuk file sederhana KEY=value:

```powershell
Get-Content 'C:\laragon\www\j2m\.env.local' | ForEach-Object {
    if ($_ -match '^([A-Za-z_][A-Za-z0-9_]*)=(.*)$') {
        [Environment]::SetEnvironmentVariable($matches[1], $matches[2], 'Process')
    }
}
vercel dev -L --listen 8080
```

Tutup terminal tersebut setelah selesai agar secret tidak diwariskan ke proses lain.

## 5. Database eksternal dan migration aman

Pilihan paling kecil risikonya adalah **MySQL eksternal**, sama seperti konfigurasi lokal. Siapkan database, TLS/CA, backup, konektivitas, batas koneksi, serta akun runtime dan akun migration. Jangan membuka database ke seluruh internet tanpa kontrol provider. Vercel scaling dapat menambah koneksi; uji batas koneksi dan latensi region.

Jika memindahkan data lokal, lakukan backup konsisten dan uji restore pada database staging. Pindahkan seluruh tabel beserta ID, foreign key, users, produk/harga, keranjang dan pesanan; migration hanya membuat struktur, bukan memindahkan data. Verifikasi jumlah record, harga, relasi, dan login setelah restore. Tidak ada proses transfer data otomatis dalam perubahan ini.

PostgreSQL adalah alternatif, **bukan pengganti otomatis**: tambahkan `pdo_pgsql` ke image, gunakan konfigurasi pgsql yang sudah ada, rencanakan konversi tipe/sequence/data, lalu uji seluruh transaksi/locking dan suite pada PostgreSQL sebelum cutover. SQLite lokal/volume container bukan persistence Vercel; jangan mengunggah database SQLite ke image.

Migration dijalankan **sekali sebagai release step terpisah**, dari komputer/CI tepercaya yang menjangkau database tujuan dengan image commit yang sama. Buat file privat `C:\laragon\www\j2m\.env.production` dengan credential release yang sebenarnya, jangan commit. Backup dahulu, tinjau migration, uji staging, koordinasikan writer, kemudian:

```powershell
docker run --rm --env-file 'C:\laragon\www\j2m\.env.production' j2m:local php artisan migrate:status
docker run --rm --env-file 'C:\laragon\www\j2m\.env.production' j2m:local php artisan migrate --pretend --force
docker run --rm --env-file 'C:\laragon\www\j2m\.env.production' j2m:local php artisan migrate --force
```

Perintah pertama dapat melaporkan tabel migration belum ada untuk database baru. `--pretend` bukan pengganti review/backup. Jangan gunakan migrate:fresh, db:wipe, atau rollback sembarangan. Jangan menjalankan migration bersamaan dari beberapa deployment. Runtime tidak membutuhkan hak DDL setelah release.

Seeder **tidak otomatis dijalankan**. Untuk database kosong yang memang membutuhkan katalog demo, tinjau seeder terlebih dahulu, lalu jalankan `php artisan db:seed --force` melalui container terpisah dengan env yang tepat. Seeder existing memakai firstOrCreate: bukan alat sinkronisasi/restore data. Admin opsional membutuhkan DEMO_ADMIN_EMAIL dan DEMO_ADMIN_PASSWORD milik Anda (password minimal 12 karakter); tidak ada akun/kredensial bawaan baru. Jangan gunakan seeder untuk mereset akun yang sudah ada. Hapus secret bootstrap admin setelah dipakai.

## 6. Penyimpanan permanen dan queue

Session dan cache berada di database eksternal, termasuk rate limit login. Jangan memakai driver array/file untuk deployment multi-instance. Storage lokal hanya temporary dan compiled Blade; log ke stderr. Tidak ada queue worker di container HTTP. Jika menambahkan job asynchronous, sediakan backend queue dan worker eksternal tersendiri; mengganti QUEUE_CONNECTION=database saja tidak menjalankan worker.

Foto existing adalah aset versi repository yang ikut setiap image, bukan upload runtime. Tidak ada fitur upload yang dihapus. Bila upload ditambahkan, pilih object storage eksternal seperti Vercel Blob atau storage kompatibel S3, simpan object key/URL di database, validasi tipe/ukuran file, dan kontrol akses. Vercel Blob bukan driver Laravel bawaan: perlu integrasi API/SDK yang diuji; sekadar FILESYSTEM_DISK=blob tidak bekerja. Disk s3 pada config juga memerlukan adapter Flysystem S3 yang belum terpasang. Token Blob/secret S3 hanya masuk environment; jangan menyimpan upload di public/storage atau filesystem container untuk jangka panjang.

## 7. GitHub, preview, production

1. Review `git diff` dan pastikan tidak ada secret, dump DB, atau .env yang tracked. Commit file deployment bersama source J2M yang ada dan push ke repository GitHub Anda.
2. Di Vercel pilih Add New Project/Import Git Repository, beri integrasi akses hanya ke repository yang diperlukan. Root Directory adalah root J2M, bukan public.
3. Pastikan Dockerfile, Caddyfile, vercel.json ditemukan; jangan menambahkan runtime PHP komunitas lama, build artisan serve, atau output directory frontend sebagai pengganti service.
4. Isi environment Preview/Production sebelum deployment. Jika project/team menolak schema services/container, periksa versi CLI dan ketersediaan fitur dengan Vercel; jangan mengganti format secara spekulatif.
5. Hubungkan CLI ke project yang sama, siapkan schema/database preview secara terpisah, lalu deploy:

```powershell
Set-Location 'C:\laragon\www\j2m'
vercel link
vercel deploy
```

6. Uji URL preview: /health, /up, beranda, katalog/detail, CSS/JS/gambar, login/admin, keranjang/checkout. Deployment Protection dapat meminta autentikasi; jangan mematikannya hanya demi curl.
7. Setelah backup/release migration production selesai dan preview lulus:

```powershell
vercel deploy --prod
```

8. Atur domain dan APP_URL HTTPS sebenarnya. Uji lagi production, periksa runtime/build log. Integrasi Git dapat membuat preview untuk branch/PR dan production pada branch production; hindari push ke branch production sebelum database/env siap. Rollback image tidak otomatis membalikkan migration.

## 8. Troubleshooting

| Gejala | Pemeriksaan |
| --- | --- |
| Build Composer gagal | Periksa akses registry, composer.lock, extension/platform; jangan pakai ignore-platform-reqs atau composer update sebagai jalan pintas |
| Package discovery gagal | Pastikan source/bootstrap tersedia dan cache writable; provider tidak boleh butuh DB/secret saat build |
| Manifest/CSS/JS hilang | Periksa stage npm ci/build, public/build/manifest.json, tidak ada public/hot; uji URL aset dari HTML |
| 500 / No application encryption key | Isi APP_KEY pada scope deployment yang benar dan redeploy |
| SQLSTATE / sessions/cache tidak ditemukan | Periksa DB_* nyata, TLS, jaringan, dan migration pada database tujuan |
| Login berulang/419 | Session database, APP_KEY stabil, host cookie, SESSION_SECURE_COOKIE, HTTPS forwarded headers/TRUSTED_PROXIES |
| Mixed content/redirect HTTP | Periksa proxy trust dan APP_URL HTTPS; jangan menonaktifkan CSRF |
| Permission denied | Periksa user www-data dan test -w; host bind mount dapat menimpa permission image |
| Container tidak listen | Periksa PORT dan Caddy validation, docker logs; jangan hardcode port lain di command runtime |
| Upload hilang setelah redeploy | Filesystem ephemeral; gunakan object storage, bukan storage:link sebagai solusi persistence |
| Health OK tetapi katalog gagal | Health hanya liveness; cek database, migration, dan aset secara terpisah |

Jangan mengaktifkan APP_DEBUG pada deployment publik. Bagikan log yang sudah disensor, bukan credential.

## 9. Status verifikasi

Pemeriksaan host berhasil: `composer validate --no-check-publish`, `composer check-platform-reqs --no-dev`, build Vite `npm run build`, serta suite Laravel **23 test / 451 assertions**. Suite menggunakan SQLite in-memory yang terisolasi, bukan database MySQL existing. Test mencakup katalog/foto/label, auth, keranjang, checkout, admin, struktur view/aset, health JSON tanpa query database, dan /up. PHP lint serta git diff --check juga lulus.

Docker CLI/daemon dan Vercel CLI tidak tersedia pada lingkungan pengerjaan. **Build/run Docker, validasi Caddy melalui FrankenPHP, permission Linux aktual, dan deployment Vercel belum diverifikasi**. Instruksi di atas adalah checklist yang harus dijalankan setelah Docker tersedia. Pemeriksaan Laravel/test host tidak menggantikan integration test container atau database eksternal. Tidak ada database existing yang dimigrasi, di-seed, atau diubah untuk deployment ini.