# Kasir Toko — Praktik Laravel Hari 8

Contoh katalog toko sekolah yang membaca data dari database, disusun berdasarkan [materi Hari 8: Database, Migration, Model dan Eloquent](https://docs.google.com/presentation/d/1vBXAfxh3fZwoVKlrkMomu94uBzNuhdVC32pLl3VTJsc/edit?usp=sharing). Targetnya adalah mengenal koneksi MySQL/MariaDB, migration, seeder, model, relasi, Eloquent, dan Blade. CRUD produk serta pembayaran belum termasuk materi hari ini.

Untuk kelanjutan materi CRUD produk Hari 9 yang sudah ada di proyek ini, lihat [Catatan demo Hari 9](CATATAN_DEMO_HARI_9.md).

## Menjalankan di VS Code dev container

1. Buka proyek template di dev container dan pastikan service `app` serta `db` (MariaDB) aktif. Buka terminal **di dalam container aplikasi**, lalu masuk ke folder proyek:

   ```sh
   cd /workspace/Praktik_Hari_9/kasir-h8
   php -v
   composer -V
   php -m | grep pdo_mysql
   composer install
   ```

2. Siapkan database kosong bernama `kasir_h8` di MariaDB, misalnya melalui phpMyAdmin pada port yang dikonfigurasi oleh template. Gunakan akun yang memiliki hak membuat tabel dan foreign key pada database tersebut. Container MariaDB bawaan template mungkin hanya membuat database `app`; buat `kasir_h8` secara terpisah bila belum ada.
3. Jika belum ada, salin `.env.example` menjadi `.env`. Untuk koneksi dari container `app`, sesuaikan bagian berikut dengan akun MariaDB lokal. `db` adalah nama service Docker, sedangkan `127.0.0.1` di dalam container menunjuk ke container aplikasi sendiri.

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=kasir_h8
   DB_USERNAME=root
   DB_PASSWORD=ISI_PASSWORD_MARIADB_LOKAL
   ```

   Password root sesuai konfigurasi Compose setempat; jangan memasukkan password asli ke `.env.example` atau mengunggah `.env`. Jika menggunakan akun lain, pastikan akun tersebut memiliki akses ke `kasir_h8`.

4. Jalankan perintah berikut dari folder proyek:

   ```sh
   php artisan key:generate       # hanya jika APP_KEY belum ada
   php artisan config:clear
   php artisan migrate:status
   php artisan migrate
   php artisan db:seed --class=KatalogSeeder
   php artisan serve --host=0.0.0.0 --port=8000
   ```

   Buka port **8000** yang diteruskan VS Code, lalu kunjungi `/katalog`. Halaman `/` juga mengarah ke katalog. `php artisan db:seed` menjalankan seeder yang sama melalui `DatabaseSeeder`.

Seeder membuat 3 kategori dan 6 produk; menjalankannya lagi tidak menggandakan data. Seeder memakai nama kategori dan SKU sebagai kunci, dan **memperbarui** nama, harga, stok, serta kategori untuk keenam SKU contoh. Karena itu gunakan database latihan jika data tersebut sudah diubah. Hindari `migrate:fresh` pada database yang berisi data yang ingin disimpan karena perintah itu menghapus tabel.

## Peta materi ke kode

| Konsep | Contoh di proyek |
| --- | --- |
| Koneksi database | `.env` dan `config/database.php` |
| Migration | `database/migrations/2026_11_18_*`: `kategori`, `produk`, `transaksi`, dan `detail_transaksi` |
| Seeder | `database/seeders/KatalogSeeder.php` |
| Model dan relasi | `app/Models/Kategori.php` (`hasMany`) dan `app/Models/Produk.php` (`belongsTo`) |
| Query dan eager loading | `app/Http/Controllers/KatalogController.php`: `with('kategori')`, `where`, `orderBy`, dan `get` |
| Route dan Blade | `routes/web.php` dan `resources/views/katalog/index.blade.php` |

Relasi utama: `kategori` 1—N `produk`; `transaksi` 1—N `detail_transaksi`; `produk` 1—N `detail_transaksi`. Harga satuan pada detail disimpan terpisah agar harga saat penjualan tercatat. Tabel transaksi disiapkan sebagai struktur untuk praktik berikutnya; halaman ini belum membuat transaksi.

## Alur demo dan QA

- Buka `/katalog`: produk diurutkan berdasarkan nama; **Kopi Ende** berkategori **Minuman**, harganya **Rp 15.000**, dan stoknya **12 tersedia**. **Teh Kotak** berlabel **Habis**.
- Coba pencarian nama dan kata yang tidak ditemukan. Saat database atau hasil pencarian kosong, katalog menampilkan pesan **Belum ada produk.**
- Jalankan `php artisan db:seed --class=KatalogSeeder` dua kali pada database latihan yang belum diubah: jumlah tetap 3 kategori dan 6 produk. `php artisan db:show --counts` dapat memperlihatkan jumlah baris.
- Ubah harga atau stok contoh di database, lalu refresh katalog untuk melihat bahwa sumber datanya database. Menjalankan seeder kembali mengembalikan nilai enam produk contoh.
- Bandingkan `produk.kategori_id` dengan `kategori.id`. `with('kategori')` memuat kategori bersama kumpulan produk, sehingga Blade tidak perlu melakukan query untuk setiap baris.

Untuk mencoba query dan relasi di terminal, jalankan `php artisan tinker`, lalu masukkan satu per satu:

```php
App\Models\Produk::orderBy('nama')->get();
App\Models\Produk::where('stok', '>', 0)->get();
App\Models\Produk::where('sku', 'KOP-001')->first()->kategori->nama; // Minuman
App\Models\Produk::find(1); // Cari berdasarkan primary key; bisa null jika ID 1 tidak ada.
App\Models\Produk::count(); // 6 pada database latihan yang belum diubah.
App\Models\Kategori::where('nama', 'Minuman')->first()->produk()->where('stok', '>', 0)->count(); // 1
```

Contoh relasi di atas mengasumsikan seeder sudah dijalankan dan data contoh belum diubah. Keluar dari Tinker dengan `exit`.

Pengujian otomatis menggunakan SQLite **in-memory** agar tidak menyentuh MariaDB latihan:

```sh
php artisan test
```

Pengujian mencakup katalog, pencarian, data kosong, stok habis, relasi, seeder berulang, perubahan data, serta penolakan foreign key yang tidak valid. Untuk memverifikasi perilaku mesin MariaDB dan koneksinya, tetap jalankan migration dan demo manual di atas.

## Masalah umum

- **Access denied**: periksa username, password, dan hak akses ke `kasir_h8`.
- **Unknown database**: buat database tersebut dan pastikan `DB_DATABASE` sesuai.
- **Connection refused**: pastikan service database aktif dan `DB_HOST=db` saat aplikasi dijalankan di container.
- **Could not find driver**: pastikan ekstensi PHP `pdo_mysql` aktif di container aplikasi.
- **Table not found**: periksa database tujuan dan jalankan `php artisan migrate:status` lalu `php artisan migrate`.
- Setelah mengubah `.env`, jalankan `php artisan config:clear`.
