# Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS)
## Yuk Keliling Sentul — Website Rental Motor

- **Nama Produk**: Yuk Keliling Sentul — Website Rental Motor
- **Versi Dokumen**: 1.0
- **Tanggal**: 2026-10-12
- **Status**: Draf untuk review
- **Standar Acuan**: IEEE 830 (disesuaikan)

---

## 1. Pendahuluan

### 1.1 Tujuan

Dokumen ini menjabarkan kebutuhan perangkat lunak untuk **Yuk Keliling Sentul**, sebuah
platform penyewaan motor trail, jeep, dan paket wisata di kawasan Gunung Pancar, Sentul.
Tujuan dokumen adalah:

1. Menjadi acuan bersama antara pengembang, pemilik usaha, dan pengguna dalam memahami
   fungsi serta batasan sistem.
2. Mendefinisikan kebutuhan fungsional dan non-fungsional secara terukur dan dapat diuji.
3. Menjadi dasar verifikasi pada saat pengujian dan penerimaan (acceptance).

### 1.2 Ruang Lingkup

Sistem yang dijelaskan mencakup:

- **Situs publik** (front office): katalog motor, paket wisata, galeri, informasi umum,
  pendaftaran/login, dan alur pemesanan oleh pelanggan.
- **Dashboard pelanggan**: profil, riwayat pemesanan, pembayaran, ulasan, dan notifikasi.
- **Panel administrasi** (back office): pengelolaan armada, penyewaan, pembayaran,
  pelanggan, paket wisata, promo, galeri, ulasan, laporan, pengaturan, dan pengguna admin.
- **Mekanisme pendukung**: notifikasi internal, pengingat terjadwal, penyimpanan berkas
  sensitif, serta ekspor laporan.

Di luar ruang lingkup: integrasi gateway pembayaran online (pembayaran dilakukan dengan
transfer manual + konfirmasi admin), pengiriman email produksi (saat ini `MAIL_MAILER=log`),
dan aplikasi mobile native.

### 1.3 Definisi, Akronim, dan Singkatan

| Istilah | Kepanjangan / Arti |
|---|---|
| SRS | Software Requirements Specification |
| Admin | Pengguna panel belakang (super admin, admin operasional, kasir, staff) |
| Customer / Pelanggan | Pengguna akhir yang menyewa/memesan |
| Armada | Unit motor trail dan jeep yang disewakan |
| Add-on | Layanan tambahan (sepatu, guide, protector) |
| Open Trip | Wisata gabungan mengikuti jadwal yang tersedia |
| Private Trip | Wisata privat dengan tanggal bebas |
| DP | Down Payment / uang muka (untuk pesanan manual WA/IG) |
| H-2 | Dua hari sebelum hari pemakaian |
| Forfeit | Uang hangus (pembatalan pada/after hari H) |
| KTP / SIM | Dokumen identitas yang diunggah pelanggan |
| WA / IG | WhatsApp / Instagram (sumber pesanan manual) |
| Policy | Aturan otorisasi Laravel (siapa boleh mengakses apa) |

### 1.4 Referensi

1. IEEE Std 830-1998 — *Recommended Practice for Software Requirements Specifications*.
2. Dokumentasi Laravel 12 — https://laravel.com/docs
3. Dokumentasi Tailwind CSS 4 — https://tailwindcss.com/docs
4. Dokumentasi Alpine.js — https://alpinejs.dev
5. `README.md` proyek (data contoh, instalasi, aturan bisnis).
6. Kode sumber: `routes/web.php`, `config/permissions.php`, `app/Services/*`, `app/Policies/*`.

### 1.5 Gambaran Umum Dokumen

- **Bagian 2** menjelaskan perspektif, fungsi, pengguna, batasan, serta anggapan/ketergantungan produk.
- **Bagian 3** memuat kebutuhan fungsional (FR) yang terperinci per modul.
- **Bagian 4** memuat kebutuhan non-fungsional.
- **Bagian 5** memuat antarmuka pengguna dan antarmuka sistem.
- **Bagian 6** memuat kebutuhan perangkat keras dan lunak.
- **Bagian 7** memuat kriteria penerimaan.
- **Bagian 8** memuat rencana penjadwalan.
- **Bagian 9** memuat lampiran (akun default, ringkasan data contoh, peta modul).

---

## 2. Deskripsi Umum

### 2.1 Perspektif Produk

Yuk Keliling Sentul adalah aplikasi web **monolitik** berbasis Laravel 12 dengan tampilan
server-rendered (Blade), styling Tailwind CSS 4, dan interaktivitas Alpine.js. Aset frontend
dikompilasi dengan Vite. Data disimpan di MySQL/MariaDB. Seluruh antarmuka berbahasa
Indonesia dengan tema gelap dan aksen kuning sesuai identitas visual Yuk Keliling Sentul.

Produk ini menggantikan proses pemesanan manual (telepon/WhatsApp) menjadi alur daring yang
tetap mempertahankan konfirmasi manusia untuk pembayaran dan pengambilan kendaraan.

### 2.2 Fungsi Produk

Fungsi utama produk:

1. Menampilkan katalog motor trail dan jeep beserta detail, stok, dan harga.
2. Menampilkan paket wisata beserta jadwal open trip.
3. Memproses pemesanan sewa kendaraan dengan kalkulasi harga dan cek ketersediaan.
4. Memproses pemesanan wisata (open trip dan private trip).
5. Mengelola pembayaran transfer dengan verifikasi admin.
6. Menyediakan dashboard pelanggan untuk memantau pesanan dan mengunggah bukti.
7. Menyediakan panel admin untuk operasional (armada, penyewaan, pembayaran, pelanggan,
   wisata, konten, laporan, dan pengaturan).
8. Memberi notifikasi internal dan pengingat otomatis (hampir selesai / terlambat kembali).

### 2.3 Pengguna yang Diharapkan

| Peran | Deskripsi | Akses Utama |
|---|---|---|
| **Super Admin** | Pemilik / pengelola tertinggi | Semua modul, termasuk Pengaturan dan Admin Users |
| **Admin (operasional)** | Mengelola operasional harian | Dashboard, motor, penyewaan, pickup, return, pembayaran, customer, promo, review, galeri, wisata, laporan |
| **Kasir** | Mengelola pembayaran | Dashboard, penyewaan, pembayaran, return |
| **Staff** | Mengelola unit & armada | Dashboard, motor, pickup, return |
| **Customer** | Penyewa / pemesan | Situs publik, booking, dashboard pelanggan |

> Pemetaan hak akses detail tersedia pada Lampiran 9.3.

### 2.4 Batasan

1. Tidak ada integrasi gateway pembayaran daring; seluruh pembayaran via transfer dan
   diverifikasi manual oleh admin.
2. Pengiriman email produksi belum aktif (menggunakan log driver); notifikasi berjalan
   di dalam aplikasi.
3. Pembayaran di website bersifat **lunas penuh**; DP hanya berlaku untuk pesanan manual WA/IG.
4. Perubahan jadwal oleh pelanggan maksimal **H-2** (nilai dapat diubah di Pengaturan).
5. Pembatalan pada/after hari H menyebabkan dana hangus (`forfeited`).
6. Kerusakan selama pemakaian ditanggung pengguna dan dipotong dari deposit saat pengembalian.
7. Berkas identitas (KTP/SIM/bukti bayar) dibatasi ukuran 3–4 MB dan disimpan privat.
8. Aplikasi ditujukan untuk satu penyedia (single-tenant); tidak multi-cabang.

### 2.5 Anggapan dan Ketergantungan

1. Ketersediaan server web (Nginx/Apache) dan cron untuk penjadwalan pengingat.
2. Ketersediaan MySQL 8 / MariaDB 10.6+ dengan kredensial yang dikonfigurasi di `.env`.
3. Admin memiliki nomor WhatsApp aktif untuk kontak dan pesanan manual.
4. Harga dan data contoh (seed) adalah nilai awal dan akan disesuaikan oleh admin.
5. Pengguna memiliki browser modern yang mendukung JavaScript (untuk Alpine.js).

---

## 3. Kebutuhan Fungsional

Kebutuhan fungsional ditulis dengan format **FR-XX** dan dikelompokkan per modul.
Prioritas: **W** = Wajib, **P** = Penting, **O** = Opsional.

### 3.1 Situs Publik

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-01 | Sistem menampilkan beranda dengan informasi utama dan fasilitas basecamp. | W |
| FR-02 | Sistem menampilkan katalog motor (`/motor`) dan detail per motor (`/motor/{slug}`) beserta gambar, spesifikasi, stok, dan harga. | W |
| FR-03 | Sistem menampilkan katalog paket wisata (`/paket-wisata`) dan detailnya. | W |
| FR-04 | Sistem menampilkan halaman galeri, tentang kami, kontak, dan FAQ. | W |
| FR-05 | Sistem menyediakan `sitemap.xml`. | O |
| FR-06 | Sistem menampilkan konten dengan tema gelap + aksen kuning, responsif. | W |

### 3.2 Autentikasi

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-07 | Pengunjung dapat mendaftar akun (`/register`). | W |
| FR-08 | Pengunjung dapat login (`/login`) dengan rate limit 6 percobaan/menit. | W |
| FR-09 | Pengguna dapat logout. | W |
| FR-10 | Hanya pengguna **aktif** (tidak diblokir) yang dapat mengakses area pelanggan. | W |
| FR-11 | Pendaftaran dibatasi rate limit 10/menit untuk mencegah spam. | P |

### 3.3 Pemesanan Sewa Kendaraan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-12 | Pelanggan dapat membuat pemesanan sewa (`/booking`) dan menghitung estimasi harga (`/booking/quote`). | W |
| FR-13 | Sistem menolak pemesanan bila stok tidak mencukupi pada rentang tanggal (overbooking ditolak). | W |
| FR-14 | Pelanggan dapat memesan beberapa unit sekaligus dalam satu booking. | W |
| FR-15 | Sistem menghitung harga berdasarkan durasi, model, paket, dan add-on. | W |
| FR-16 | Pelanggan dapat memilih add-on (sepatu, guide, protector). | P |
| FR-17 | Checkout menghasilkan data penyewaan (rental) dengan status awal menunggu pembayaran. | W |
| FR-18 | Pelanggan melihat halaman pembayaran dan mengunggah bukti transfer. | W |

### 3.4 Pemesanan Wisata

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-19 | Pelanggan dapat memesan paket wisata open trip dengan kuota otomatis dari jadwal. | W |
| FR-20 | Pelanggan dapat memesan private trip dengan tanggal bebas (minimal H+1). | W |
| FR-21 | Pelanggan dapat mengunggah bukti pembayaran untuk booking wisata. | W |
| FR-22 | Pelanggan dapat mengajukan perubahan jadwal (maksimal H-2). | P |
| FR-23 | Pelanggan dapat membatalkan booking wisata; pembatalan pada/after H ditandai forfeited. | P |
| FR-24 | Sistem membedakan paket dengan tanda "bawa motor pribadi" (tanpa sewa motor). | P |

### 3.5 Dashboard Pelanggan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-25 | Pelanggan melihat ringkasan akun dan riwayat pemesanan. | W |
| FR-26 | Pelanggan dapat mengubah profil (nama, kontak, dll.). | W |
| FR-27 | Pelanggan melihat detail booking sewa dan mengajukan perubahan jadwal / pembatalan. | W |
| FR-28 | Pelanggan melihat riwayat pembayaran. | W |
| FR-29 | Pelanggan dapat menulis ulasan untuk penyewaan yang selesai. | W |
| FR-30 | Pelanggan melihat notifikasi dan menandai sudah dibaca. | W |
| FR-31 | Pelanggan dapat mengakses berkas privat miliknya (KTP/SIM/bukti) melalui route terproteksi. | W |

### 3.6 Admin — Armada dan Katalog

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-32 | Admin mengelola data motor (CRUD) dan mengubah status ketersediaan. | W |
| FR-33 | Admin mengelola foto motor (unggah, hapus, jadikan utama). | W |
| FR-34 | Admin mengelola kategori motor, lokasi, paket kendaraan, dan add-on. | W |
| FR-35 | Admin mengelola stok per model kendaraan. | W |

### 3.7 Admin — Penyewaan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-36 | Admin melihat daftar dan detail penyewaan. | W |
| FR-37 | Admin dapat mencatat pesanan manual (sumber WA/IG/langsung) dengan status DP/lunas/belum bayar. | W |
| FR-38 | Admin dapat mengonfirmasi, membatalkan, dan menyelesaikan penyewaan. | W |
| FR-39 | Admin dapat menandai "siap diambil" dan "diambil" pada proses pengambilan. | W |
| FR-40 | Admin memproses pengembalian, mencatat kerusakan, dan memotong deposit. | W |

### 3.8 Admin — Pembayaran

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-41 | Admin memverifikasi, menolak, dan merefund pembayaran. | W |
| FR-42 | Admin mengelola metode pembayaran (bank/ewallet, nomor tujuan). | W |
| FR-43 | Pelunasan DP dicatat sebagai pembayaran terpisah dan diverifikasi manual. | P |

### 3.9 Admin — Pelanggan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-44 | Admin melihat daftar dan detail pelanggan. | W |
| FR-45 | Admin mengubah data pelanggan. | P |
| FR-46 | Admin dapat memblokir/mengaktifkan kembali pelanggan. | W |

### 3.10 Admin — Wisata

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-47 | Admin melihat daftar booking wisata dan detailnya. | W |
| FR-48 | Admin memverifikasi, menolak, menyelesaikan, dan membatalkan booking wisata. | W |
| FR-49 | Admin mengelola paket wisata dan jadwal wisata (CRUD). | W |

### 3.11 Admin — Konten, Laporan, dan Pengaturan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-50 | Admin mengelola promo (CRUD) dengan batas minimum, maksimum, kuota. | W |
| FR-51 | Admin mengelola galeri (CRUD). | W |
| FR-52 | Admin memoderasi ulasan (setujui/tolak/hapus). | W |
| FR-53 | Admin melihat laporan dan mengekspor data laporan. | W |
| FR-54 | Admin (super) mengelola pengaturan global (nomor WhatsApp, persentase DP, batas H-2, fasilitas basecamp). | W |
| FR-55 | Admin (super) mengelola akun admin/staff/kasir (Admin Users). | W |

### 3.12 Notifikasi dan Berkas

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-56 | Sistem membuat notifikasi internal untuk kejadian penting (booking, pembayaran, pengembalian). | W |
| FR-57 | Sistem mengirim pengingat otomatis rental hampir selesai dan pengembalian terlambat (cron harian 08:00). | W |
| FR-58 | Berkas sensitif disimpan di `storage/app/private` dan hanya diakses lewat route `/berkas/...` setelah lolos policy. | W |

---

## 4. Kebutuhan Non-Fungsional

### 4.1 Kinerja

- **NFR-P-01**: Halaman katalog dan detail harus dapat dimuat dalam kondisi normal di bawah 3 detik pada server spesifikasi minimum (Bagian 6.1).
- **NFR-P-02**: Endpoint sensitif dibatasi rate limit: login 6/menit, register 10/menit, quote 60/menit.
- **NFR-P-03**: Sistem memanfaatkan cache dan optimasi (`config:cache`, `route:cache`, `view:cache`) pada mode produksi.
- **NFR-P-04**: Ukuran unggahan berkas identitas dibatasi 3–4 MB.

### 4.2 Keamanan

- **NFR-S-01**: Seluruh kata sandi disimpan dalam bentuk hash (bcrypt), bukan plaintext.
- **NFR-S-02**: Seluruh akses area pelanggan dan admin memerlukan autentikasi.
- **NFR-S-03**: Otorisasi berbasis role-permission (`config/permissions.php`) dan policy
  (`CustomerPolicy`, `RentalPolicy`, `TourBookingPolicy`).
- **NFR-S-04**: Berkas sensitif (KTP/SIM/bukti bayar) tidak dapat diakses via URL publik.
- **NFR-S-05**: NIK/SIM dienkripsi menggunakan application key (data invalid bila key berubah).
- **NFR-S-06**: Form dilindungi CSRF dan input divalidasi server-side.
- **NFR-S-07**: Konfigurasi rahasia disimpan di `.env` dan tidak di-commit ke repositori.
- **NFR-S-08**: HTTPS wajib pada deployment produksi.

### 4.3 Keandalan

- **NFR-R-01**: Pencatatan booking dilakukan dalam transaksi database untuk mencegah data parsial.
- **NFR-R-02**: Pengingat otomatis dijalankan melalui scheduler Laravel dengan cron `schedule:run`.
- **NFR-R-03**: Log aplikasi tercatat di `storage/logs` untuk penelusuran masalah.
- **NFR-R-04**: Sistem mendukung `php artisan migrate` dan `migrate:fresh --seed` untuk pemulihan instalasi.

### 4.4 Usability

- **NFR-U-01**: Seluruh UI berbahasa Indonesia.
- **NFR-U-02**: Tampilan konsisten dengan identitas Yuk Keliling Sentul (tema gelap + aksen kuning).
- **NFR-U-03**: Tampilan responsif untuk perangkat desktop dan seluler.
- **NFR-U-04**: Alur booking harus jelas (pilih → cek harga → checkout → bayar → unggah bukti).
- **NFR-U-05**: Pesan validasi dan error ditampilkan dalam bahasa Indonesia.

---

## 5. Antarmuka

### 5.1 Antarmuka Pengguna

Antarmuka pengguna dirender server-side menggunakan Blade dan distyling dengan Tailwind CSS 4,
dengan interaktivitas ringan via Alpine.js. Halaman utama meliputi:

- **Publik**: beranda, katalog motor, detail motor, paket wisata, detail wisata, galeri,
  tentang kami, kontak, FAQ, login, register.
- **Pelanggan**: dashboard (profil, booking, wisata, pembayaran, ulasan, notifikasi),
  halaman booking/checkout, halaman pembayaran.
- **Admin**: dashboard ringkasan, modul motor, penyewaan, pembayaran, customer, wisata,
  promo, galeri, review, laporan, pengaturan, admin users.

### 5.2 Antarmuka Sistem

| Antarmuka | Deskripsi |
|---|---|
| Basis data | MySQL 8 / MariaDB 10.6+ melalui Laravel Eloquent (PDO) |
| Penyimpanan file | Disk lokal Laravel: `public` (gambar produk) dan `private` (dokumen identitas) |
| Email | Driver `MAIL_MAILER=log` (belum mengirim sungguhan) |
| Penjadwalan | Laravel Scheduler via cron sistem (`php artisan schedule:run`) |
| Kontak eksternal | Nomor WhatsApp yang dikonfigurasi di Pengaturan (tautan kontak) |
| Aset frontend | Vite (`npm run build` / `npm run dev`) |

---

## 6. Kebutuhan Sistem

### 6.1 Perangkat Keras

- **Server minimum**: 1 vCPU, 1 GB RAM, 20 GB storage (untuk volume trafik kecil-menengah).
- **Storage**: menyesuaikan jumlah gambar armada/galeri dan dokumen pelanggan; disarankan
  mulai 10 GB untuk media.
- **Klien**: perangkat dengan browser modern (Chrome/Firefox/Safari/Edge).

> Spesifikasi produksi final menyesuaikan estimasi trafik dan jumlah data pelanggan.

### 6.2 Perangkat Lunak

| Komponen | Versi / Catatan |
|---|---|
| PHP | 8.2+ (ekstensi: mbstring, xml, curl, zip, fileinfo, pdo_mysql, openssl; gd/imagick opsional) |
| Composer | 2.x |
| Node.js + NPM | Node 20.19+ / 22+ |
| Database | MySQL 8 / MariaDB 10.6+ |
| Web server | Nginx atau Apache (document root ke `public/`) |
| Framework | Laravel 12 |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Vite |
| Scheduler | Cron sistem untuk `php artisan schedule:run` |

---

## 7. Kriteria Penerimaan

Sistem dinyatakan **diterima** apabila seluruh butir berikut terpenuhi dan terverifikasi:

1. **AC-01** — Seluruh kebutuhan fungsional berprioritas **Wajib** (W) berjalan sesuai spesifikasi pada Bagian 3.
2. **AC-02** — Pengujian otomatis (PHPUnit, SQLite in-memory) lulus tanpa kegagalan
   (`php artisan test`).
3. **AC-03** — Seluruh alur kunci teruji end-to-end: registrasi/login, booking sewa,
   booking wisata, unggah bukti, verifikasi admin, pengambilan, dan pengembalian.
4. **AC-04** — Overbooking tertolak dan stok terjaga konsisten saat pemesanan paralel.
5. **AC-05** — Akses tanpa izin (role/permission/policy) tertolak, termasuk akses berkas privat.
6. **AC-06** — Tidak ada rahasia (`.env`, kredensial) yang tersimpan di repositori.
7. **AC-07** — Pengingat otomatis berjalan sesuai jadwal dan menghasilkan notifikasi yang benar.
8. **AC-08** — Seluruh UI berbahasa Indonesia, responsif, dan sesuai tema visual.
9. **AC-09** — Kriteria non-fungsional pada Bagian 4 terpenuhi (rate limit, ukuran unggah,
   hash password, HTTPS produksi).
10. **AC-10** — Dokumentasi instalasi dan operasional (`README.md`) akurat dan dapat dijalankan.

---

## 8. Penjadwalan

Berikut rencana indikatif. Sistem sudah terimplementasi; tahapan ini untuk keperluan
pelacakan penyelesaian dan penerimaan, dan dapat disesuaikan dengan kesepakatan pemangku kepentingan.

| Tahap | Aktivitas | Status |
|---|---|---|
| 1 | Analisis kebutuhan & desain | Selesai |
| 2 | Implementasi fungsional inti (katalog, booking, pembayaran) | Selesai |
| 3 | Implementasi panel admin & role-permission | Selesai |
| 4 | Pengujian unit & fitur | Selesai (perlu review) |
| 5 | Uji penerimaan pengguna (UAT) | Tertunda |
| 6 | Deployment produksi & konfigurasi cron/HTTPS | Tertunda |
| 7 | Serah terima & pelatihan admin | Tertunda |

---

## 9. Lampiran

### 9.1 Akun Default (data contoh — wajib diganti sebelum produksi)

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@rentalmotor.test` | `password` |
| Admin (operasional) | `operasional@rentalmotor.test` | `password` |
| Kasir | `kasir@rentalmotor.test` | `password` |
| Staff | `staff@rentalmotor.test` | `password` |
| Customer | `customer@rentalmotor.test` | `password` |

### 9.2 Ringkasan Data Contoh

- 70 unit motor trail (9 model), 120 unit jeep (3 model), 4 paket jeep, 3 add-on.
- 4 paket wisata + jadwal; beberapa booking (sewa, jeep, pesanan manual WA, open & private trip).
- Promo: `HEMAT20` (20%, min Rp200.000, maks Rp100.000, kuota 100) dan `WEEKEND50` (potongan Rp50.000).
- Add-on: sepatu Rp100.000, guide Rp200.000, protector Rp250.000.
- Paket jeep: *Jeep Saja* Rp1.100.000, *Jeep + Trekking* Rp1.700.000, tambahan curug +Rp150.000.

### 9.3 Matriks Hak Akses (Role × Modul)

| Modul (permission) | Super Admin | Admin | Kasir | Staff |
|---|---|---|---|---|
| dashboard | ✔ | ✔ | ✔ | ✔ |
| motor | ✔ | ✔ | — | ✔ |
| rental | ✔ | ✔ | ✔ | — |
| pickup | ✔ | ✔ | — | ✔ |
| return | ✔ | ✔ | ✔ | ✔ |
| payment | ✔ | ✔ | ✔ | — |
| customer | ✔ | ✔ | — | — |
| promo | ✔ | ✔ | — | — |
| review | ✔ | ✔ | — | — |
| gallery | ✔ | ✔ | — | — |
| tour | ✔ | ✔ | — | — |
| report | ✔ | ✔ | — | — |
| settings | ✔ | — | — | — |
| users | ✔ | — | — | — |

### 9.4 Peta Entitas Data Utama

- **Identitas**: `users`, `customers`, `roles`
- **Katalog**: `motorcycles`, `motor_categories`, `motor_images`, `locations`, `rental_packages`, `addons`
- **Transaksi**: `rentals`, `rental_items`, `rental_returns`, `payments`, `payment_methods`
- **Promo**: `promos`, `promo_usages`
- **Wisata**: `tour_packages`, `tour_schedules`, `tour_bookings`
- **Konten**: `galleries`, `reviews`, `settings`, `user_notifications`

---

*Dokumen ini disusun berdasarkan kode sumber dan dokumentasi proyek per 2026-10-12.
Perubahan kode yang memengaruhi perilaku sistem harus direfleksikan kembali ke dokumen ini.*
