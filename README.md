# Yuk Keliling Sentul — Website Rental Motor

Platform penyewaan motor trail, jeep & paket wisata di Gunung Pancar (Laravel 12, Blade, Tailwind CSS 4, Alpine.js, Vite, MySQL/MariaDB).
Seluruh UI berbahasa Indonesia. Tampilan mengikuti desain *Yuk Keliling Sentul* (tema gelap + aksen kuning).

## Requirements

| Kebutuhan | Versi |
|---|---|
| PHP | 8.2 atau lebih baru (ekstensi: mbstring, xml, curl, zip, fileinfo, pdo_mysql, openssl, gd/imagick tidak wajib) |
| Composer | 2.x |
| Node.js + NPM | Node 20.19+ / 22+ |
| MySQL / MariaDB | MySQL 8 / MariaDB 10.6+ |

Tidak membutuhkan API berbayar. Semua gambar bawaan berupa SVG lokal (`public/images`) — ganti dengan foto asli lewat dashboard admin.

## Installation

```bash
# 1. Masuk ke folder project
cd rental-motor

# 2. Dependency PHP & environment
composer install
cp .env.example .env
php artisan key:generate
```

Buat database kosong (mis. `rental_motor`), lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rental_motor
DB_USERNAME=root
DB_PASSWORD=
WHATSAPP_NUMBER=081234567890   # opsional, bisa diubah juga lewat Admin → Pengaturan
```

```bash
# 3. Database + data contoh
php artisan migrate --seed
php artisan storage:link

# 4. Frontend
npm install
npm run build

# 5. Jalankan
php artisan serve
```

Buka <http://localhost:8000>.

> `composer.lock` akan dibuat otomatis saat `composer install` pertama. `package-lock.json` sudah disertakan.

## Default Login

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@rentalmotor.test` | `password` |
| Customer | `customer@rentalmotor.test` | `password` |
| Admin (operasional) | `operasional@rentalmotor.test` | `password` |
| Kasir | `kasir@rentalmotor.test` | `password` |
| Staff | `staff@rentalmotor.test` | `password` |

Customer contoh lain: `dinda@`, `andi@`, `rudi@`, `siti@rentalmotor.test` (password `password`).
**Ganti semua password sebelum dipakai di production.**

Data contoh: 70 unit motor trail (9 model), 120 unit jeep (3 model), 4 paket jeep, 3 add-on, 4 paket wisata + jadwal, beberapa booking (sewa, jeep, pesanan manual WA, open & private trip).

Kode promo contoh: `HEMAT20` (20%, min Rp200.000, maks Rp100.000, kuota 100) dan `WEEKEND50` (potongan Rp50.000).

## Development

```bash
npm run dev          # Vite dengan hot reload
php artisan serve    # terminal lain
php artisan test     # menjalankan test (memakai SQLite in-memory)
```

Pengingat otomatis (rental hampir selesai & pengembalian terlambat) dijalankan oleh command `php artisan rental:remind`
(dijadwalkan harian 08:00). Untuk mengaktifkannya di server, tambahkan cron:

```cron
* * * * * cd /path/ke/rental-motor && php artisan schedule:run >> /dev/null 2>&1
```

## Production

1. Set `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domain-anda.com`, kredensial database, dan konfigurasi `MAIL_*` bila ingin kirim email sungguhan.
2. Deploy: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. `php artisan migrate --force` (jalankan `--seed` hanya pada instalasi baru, lalu **ubah password default**).
4. `php artisan storage:link`, lalu `php artisan config:cache route:cache view:cache`.
5. Arahkan *document root* web server ke folder `public/`. Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh user web server.
6. Pasang cron `schedule:run` (lihat di atas) dan HTTPS.

Berkas sensitif (KTP, SIM, bukti pembayaran) disimpan di `storage/app/private` dan **tidak** dapat diakses lewat URL publik; hanya dilayani lewat route `/berkas/...` setelah pemeriksaan policy (pemilik atau admin berwenang).

## Database

Relasi utama (semua memakai foreign key):

```
roles 1─* users 1─1 customers
users 1─* rentals 1─* rental_items *─1 motorcycles *─1 motor_categories
motorcycles 1─* motor_images           rentals 1─1 rental_returns
rentals 1─* payments *─1 payment_methods     rentals *─1 locations
rentals *─1 promos 1─* promo_usages    rentals 1─1 reviews (→ users, motorcycles)
tour_packages 1─* tour_schedules       users 1─* notifications
rental_packages 1─* rentals (paket jeep)   addons (snapshot disimpan di rentals/tour_bookings)
users 1─* tour_bookings *─1 tour_packages (─? tour_schedules)
galleries, settings (key-value)
```

NIK dan nomor SIM customer disimpan terenkripsi (cast `encrypted`, memakai `APP_KEY` — jangan ganti `APP_KEY` setelah ada data).

## Folder Structure

```
app/
  Console/Commands/    rental:remind
  Http/Controllers/    Controller publik/customer; Admin/ (CRUD generik + modul admin); Customer/
  Http/Middleware/     EnsureAdmin, CheckPermission, EnsureActiveUser
  Models/              Eloquent models + relationship
  Policies/            RentalPolicy, CustomerPolicy (akses berkas & booking)
  Services/            BookingService (harga, double-booking), ReportService, NotificationService
  helpers.php          setting(), rupiah(), tgl(), media_url(), wa_link()
config/permissions.php Matriks role → permission
database/              migrations, factories, seeders
resources/             views (Blade), css (Tailwind), js (Alpine, Chart.js, Lucide)
routes/web.php         Seluruh route customer & admin
public/images/         Placeholder SVG lokal (motor, hero, galeri, logo)
storage/               Upload publik (storage/app/public) & berkas privat (storage/app/private)
tests/                 Feature test
```

## Features

**Customer website** — beranda (hero + kotak pencarian tanggal), daftar motor (filter kategori/merek/harga/CC/transmisi/status, sorting, pagination, filter ketersediaan sesuai tanggal), detail motor (galeri, spesifikasi, fasilitas, syarat, kalender ketersediaan, ulasan), paket wisata + jadwal, galeri berkategori, tentang kami, kontak, FAQ, tombol WhatsApp melayang (nomor dari Pengaturan).

**Booking & pembayaran** — alur: pilih motor → tanggal → data penyewa → dokumen (KTP/SIM) → promo → metode bayar → booking. Harga dihitung otomatis di server (tarif harian/mingguan/bulanan terbaik + biaya antar + deposit − diskon). Double booking dicegah di dalam transaksi database dengan penguncian baris motor. Pembayaran manual (lunas): Transfer Bank, QRIS, Cash; upload bukti; status Pending / Menunggu Verifikasi / Dibayar / Ditolak / Refund.

**Dashboard customer** — ringkasan, profil & dokumen, riwayat penyewaan + detail, batalkan booking yang belum dibayar, riwayat pembayaran, rating & review (tampil setelah disetujui admin), notifikasi.

**Dashboard admin** — kartu statistik, grafik (pendapatan 7 hari & bulanan, jumlah rental, motor terlaris), CRUD motor (multi-foto, status Tersedia/Maintenance/Nonaktif), kategori, lokasi, paket wisata, jadwal wisata, promo, galeri, metode pembayaran; manajemen penyewaan dengan alur status
`pending → waiting_payment → paid → confirmed → pickup → rented → returned → completed (+ cancelled)`; proses pengembalian (kondisi, BBM, denda keterlambatan otomatis, biaya kerusakan, sisa deposit); verifikasi/tolak/refund pembayaran; manajemen customer (detail, edit, block/unblock, riwayat); moderasi review; laporan (pendapatan, rental, motor, customer, pembayaran, denda) dengan filter hari/minggu/bulan/tahun/custom, ekspor **CSV** (dibuka di Excel) dan **cetak / simpan PDF** dari browser; pengaturan website; admin users.

**Role & permission** — Super Admin (semua), Admin (motor, rental, customer, payment, promo, review, galeri, wisata, laporan), Kasir (rental, payment, return), Staff (motor, pickup, return). Dikonfigurasi di `config/permissions.php`, ditegakkan lewat middleware `permission:*` dan sidebar yang menyesuaikan role.

**Notifikasi internal** — customer: booking berhasil, pembayaran diterima/ditolak, booking dikonfirmasi, motor siap diambil, rental hampir selesai, rental selesai. Admin: booking baru, pembayaran baru, review baru, pengembalian terlambat.

**Keamanan & SEO** — CSRF, validasi di semua form, password di-hash, upload divalidasi (tipe & ukuran), berkas KTP/SIM/bukti bayar privat, rate limiting login/registrasi, `$fillable` di semua model, policy untuk akses booking & dokumen, meta title/description/Open Graph, favicon, `sitemap.xml`, `robots.txt`, halaman error 403/404/419/429/500 bertema.

### Aturan bisnis yang sudah diterapkan

| Aturan | Implementasi |
|---|---|
| Armada: **70 unit motor trail** dan **120 unit jeep** | Satu baris kendaraan = satu model dengan kolom `stock`. Booking dihitung per hari: overbooking ditolak, unit yang sama bisa dipakai bergantian. Customer bisa memesan beberapa unit sekaligus. |
| **Jeep**: maks 4 orang per jeep, harga per jeep sesuai paket | Menu **Paket Jeep**: *Jeep Saja* Rp1.100.000, *Jeep + Trekking* Rp1.700.000. Jeep = perjalanan 1 hari. |
| **Curug dijadikan paket (+Rp150.000)**, bukan add-on terpisah | Tersedia paket *Jeep Saja + Curug* dan *Jeep + Trekking + Curug*; di wisata ada *Paket Keliling + Curug*. |
| **Add-on**: sepatu Rp100.000, guide Rp200.000, paket protector Rp250.000 | Menu **Add-on** (dihitung per orang / per booking), bisa dipilih saat booking sewa maupun wisata. |
| **Paket bawa motor pribadi** | Paket wisata dengan tanda *Bawa motor pribadi* (tanpa sewa motor). |
| **Private trip** — tetap bisa booking walau tidak ada open trip | Booking wisata: pilih *Open Trip* (gabung jadwal, kuota otomatis) atau *Private Trip* (tanggal bebas, min. besok). |
| **Pembayaran website langsung lunas**, transfer manual diverifikasi admin | Tidak ada DP di website. |
| **DP 50%** hanya untuk pesanan manual WA/IG | Menu **Penyewaan → Catat Pesanan Manual**: sumber (WhatsApp/Instagram/langsung), DP/lunas/belum bayar; DP dicatat + pelunasan menunggu. Persentase di Pengaturan. |
| **Perubahan jadwal maksimal H-2** | Customer ubah sendiri dari dashboard (durasi & harga tetap, stok dicek ulang). Batas diatur di Pengaturan. |
| **Cancel hari H = uang hangus** | Pembatalan pada/after hari H menandai `forfeited`; admin diberi tahu agar tidak merefund. Sebelum hari H admin memproses refund. |
| **Kerusakan selama pemakaian ditanggung pengguna** | Tercantum di syarat sewa, kebijakan checkout, dan FAQ; biaya dicatat saat pengembalian & dipotong dari deposit. |
| **Basecamp Gunung Pancar**: toilet, musola, warung, parkir motor & mobil | Daftar fasilitas dikelola di Pengaturan, tampil di beranda, tentang kami, kontak, dan FAQ. |

### Catatan cakupan

* Tabel `admin_users` tidak dibuat terpisah: akun admin/kasir/staff adalah `users` dengan role non-customer (menu **Admin Users**).
* Harga jeep seed (Rp1.100.000 / 1.700.000, +150.000 untuk curug), harga *Paket Bawa Motor Pribadi* (Rp150.000/orang), dan jumlah per model (mis. Willys 60, Hardtop 40, Premium 20) adalah **data contoh** — sesuaikan di admin.
* Perubahan jadwal oleh customer berlaku untuk sewa kendaraan dan booking wisata; durasi/harga tidak berubah (selisih harga ditangani admin).
* Booking yang tidak dibayar tidak otomatis kedaluwarsa; admin dapat menolak/membatalkannya. Pelunasan setelah DP dicatat sebagai pembayaran terpisah dan diverifikasi manual.
* Email sungguhan belum dikirim (`MAIL_MAILER=log`); notifikasi berjalan di dalam aplikasi.

## Troubleshooting

| Masalah | Solusi |
|---|---|
| `No application encryption key has been specified` | Jalankan `php artisan key:generate` |
| `SQLSTATE[HY000] [1049] Unknown database` / `Connection refused` | Buat database dulu, cek `DB_*` di `.env`, pastikan MySQL berjalan. Lalu `php artisan config:clear` |
| Gambar upload tidak tampil (404) | `php artisan storage:link`; pastikan `APP_URL` benar |
| `The stream or file ... laravel.log could not be opened` / permission denied | `chmod -R ug+rwx storage bootstrap/cache` dan pastikan owner = user web server |
| `Vite manifest not found` / halaman tanpa style | Jalankan `npm install && npm run build` (atau `npm run dev` saat development) |
| Migrasi gagal / tabel sudah ada | Instalasi baru: `php artisan migrate:fresh --seed` (**menghapus semua data**) |
| `Class "..." not found` setelah menambah file | `composer dump-autoload` |
| NIK/SIM tampil error “The payload is invalid” | `APP_KEY` berubah setelah data dibuat. Kembalikan `APP_KEY` lama atau isi ulang data customer |
| Upload KTP/SIM gagal untuk file besar | Naikkan `upload_max_filesize` & `post_max_size` di `php.ini` (batas aplikasi 3–4 MB) |
