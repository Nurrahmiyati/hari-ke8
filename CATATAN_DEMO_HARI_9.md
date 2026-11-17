# Catatan demo Hari 9 — CRUD produk dan validasi

Pegangan untuk menunjukkan contoh pada [slide Hari 9](https://docs.google.com/presentation/d/1bhfFjoQSVV1Mlk0mRQGmLl_p5QwywurywKvGOAc6Bjw/edit?usp=sharing) langsung dari kode proyek ini. Nomor slide mengikuti urutan pada presentasi.

Di VS Code, tekan **Ctrl+P** lalu ketik `nama/file:nomor_baris` untuk langsung membuka bagian kode, misalnya `routes/web.php:9`. Tautan nama file di bawah juga bisa dibuka dari pratinjau Markdown.

## Persiapan sebelum menjelaskan

1. Buka proyek di **VS Code dev container**, kemudian buka terminal di dalam container aplikasi:

   ```sh
   cd /workspace/Praktik_Hari_9/kasir-h8
   php -v
   php artisan --version --no-interaction
   php artisan migrate:status --no-interaction
   php artisan route:list --path=produk --no-interaction
   ```

2. Pastikan MariaDB dan tabel sudah tersedia serta daftar route menampilkan enam route produk. Siapkan database **latihan** yang memiliki kategori; bila belum, lihat petunjuk database di [README](README.md). Gunakan `php artisan db:show --counts --no-interaction` untuk melihat tabel dan jumlah baris.
3. Jalankan aplikasi dari terminal yang sama:

   ```sh
   php artisan serve --host=0.0.0.0 --port=8000 --no-interaction
   ```

   Buka port **8000** melalui tab **Ports** di VS Code, lalu buka path `/produk`. Katalog hari sebelumnya ada di `/katalog`. Hentikan server dengan **Ctrl+C** setelah selesai.
4. Buka terminal kedua di container untuk menjalankan pengujian saat demo:

   ```sh
   cd /workspace/Praktik_Hari_9/kasir-h8
   vendor/bin/phpunit tests/Feature/ProdukTest.php
   ```

   Konfigurasi [phpunit.xml](phpunit.xml) memakai SQLite in-memory untuk pengujian. Pastikan variabel lingkungan pengujian tersebut tidak ditimpa konfigurasi lain. Pengujian ini bukan pengganti pemeriksaan manual di browser dan MariaDB.

Saat diperiksa pada 17 November 2026, container menggunakan PHP 8.4.27 dan Laravel 13.42.0; slide merujuk dokumentasi Laravel 12. Migration sudah berjalan, terdapat tiga kategori dan enam produk, enam route produk terdaftar, dan tes produk lulus **31 tes / 153 assertion**. Kondisi data dapat berubah setelah demo.

**Data latihan:** `KatalogSeeder` mengembalikan nilai nama, kategori, harga, dan stok untuk enam SKU contohnya. Jika memang ingin menyiapkan atau memulihkan data contoh pada database latihan, jalankan `php artisan db:seed --class=KatalogSeeder --no-interaction`. Tidak perlu menjalankan `migrate:fresh`; perintah itu menghapus tabel dan datanya.

## Peta cepat slide ke kode

| Slide | Topik | Buka file dan baris | Yang ditunjukkan |
| --- | --- | --- | --- |
| 4–5 | Prasyarat dan CRUD | [Produk.php](app/Models/Produk.php), baris 5; [ProdukController.php](app/Http/Controllers/ProdukController.php), baris 14 | Nama tabel, `fillable`, relasi, dan metode CRUD. |
| 6, 11–12 | Aturan FSD dan Form Request | [ProdukRequest.php](app/Http/Requests/ProdukRequest.php), baris 11 dan 25 | `authorize()`, aturan wajib, tipe, batas, kategori, dan pesan. |
| 7–8 | Resource route | [web.php](routes/web.php), baris 9 | Enam route; nama parameter `{produk}` cocok dengan controller. |
| 9–10 | Alur dan form tambah | [form.blade.php](resources/views/produk/form.blade.php), baris 15; [ProdukController.php](app/Http/Controllers/ProdukController.php), baris 29 | POST, CSRF, validasi, simpan, redirect. |
| 13 | SKU unik saat edit | [ProdukRequest.php](app/Http/Requests/ProdukRequest.php), baris 18 | `unique` dan `ignore` untuk produk yang sedang diedit. |
| 14–16 | Store, error, checkpoint create | [ProdukController.php](app/Http/Controllers/ProdukController.php), baris 29; [form.blade.php](resources/views/produk/form.blade.php), baris 23 | Data tervalidasi, `old()`, `@error`, demo tambah. |
| 17 | Read | [ProdukController.php](app/Http/Controllers/ProdukController.php), baris 14; [index.blade.php](resources/views/produk/index.blade.php), baris 29 | Ambil produk bersama kategori dan tampilkan daftar. |
| 18–20 | Edit dan update | [ProdukController.php](app/Http/Controllers/ProdukController.php), baris 36; [form.blade.php](resources/views/produk/form.blade.php), baris 15 | Route model binding, nilai awal, PUT, dan update baris yang sama. |
| 21–22 | Delete dan riwayat | [index.blade.php](resources/views/produk/index.blade.php), baris 38; [ProdukController.php](app/Http/Controllers/ProdukController.php), baris 51; [migration detail](database/migrations/2026_11_18_000004_create_detail_transaksi_table.php), baris 10 | Konfirmasi, DELETE, dan penolakan foreign key. |
| 23–24 | Flash dan checkpoint update/delete | [layout.blade.php](resources/views/produk/layout.blade.php), baris 4 | Pesan sukses atau error setelah redirect. |
| 26–27 | QA dan diagnosis | [ProdukTest.php](tests/Feature/ProdukTest.php), baris 97, 148, 161, 185, 200, dan 212 | Contoh pengujian input gagal, update, delete, dan riwayat. |
| 25, 28–29 | Kontribusi dan persiapan demo | [LKPD Hari 9](../LKPD_Hari_9.md) dan [Panduan Mentor](../Panduan_Mentor_Hari_9.md) | Isi bukti QA dan kontribusi sesuai proyek kelompok. |

## Urutan penjelasan kode

### 1. Route dan alur request — slide 7–10

Buka `routes/web.php:9`:

```php
Route::resource('produk', ProdukController::class)
    ->parameters(['produk' => 'produk'])
    ->except(['show']);
```

Katakan: “Satu resource mendaftarkan route daftar, form tambah, simpan, form edit, update, dan hapus. `show` dikecualikan; parameter `{produk}` dipakai untuk mengambil produk yang akan diedit atau dihapus.” Tunjukkan hasil `route:list` dan cocokkan method GET, POST, PUT/PATCH, dan DELETE.

Buka `resources/views/produk/form.blade.php:15`. Satu form dipakai untuk tambah dan edit. `$produk->exists` menentukan route; form mengirim POST dan `@method('PUT')` menandai permintaan update. `@csrf` menyertakan token perlindungan CSRF.

Alur tambah: **form → POST → `ProdukRequest` → `store()` → database → redirect → pesan sukses**. Jika validasi gagal, pengguna kembali ke form dengan pesan dan nilai input sebelumnya.

### 2. Validasi server dan SKU unik — slide 6, 11–13

Buka `app/Http/Requests/ProdukRequest.php:11`, lalu baris 18–30:

```php
$skuUnik = Rule::unique('produk', 'sku');
$produk = $this->route('produk');

if ($produk instanceof Produk) {
    $skuUnik->ignore($produk);
}

return [
    'sku' => ['bail', 'required', 'string', 'max:50', $skuUnik],
    'nama' => ['required', 'string', 'max:255'],
    'kategori_id' => ['required', 'integer', 'exists:kategori,id'],
    'harga' => ['required', 'integer', 'min:0', 'max:4294967295'],
    'stok' => ['required', 'integer', 'min:0', 'max:4294967295'],
];
```

Katakan: “Server memeriksa input sebelum controller menyimpan. SKU harus unik; saat edit, `ignore($produk)` mengecualikan produk itu sendiri, tetapi SKU milik produk lain tetap ditolak. Kategori harus ada, sedangkan harga dan stok berupa bilangan bulat dari 0 sampai 4294967295.” `bail` menghentikan aturan SKU berikutnya setelah salah satu aturan SKU gagal.

`authorize()` mengembalikan `true` untuk contoh latihan ini; autentikasi dan pembatasan hak akses belum diterapkan di fitur ini. Buka juga `Produk.php:7`: `$fillable` membatasi kolom yang boleh diisi melalui mass assignment, sementara aturan validasi tetap berada di `ProdukRequest`.

### 3. Simpan dan tampilkan error — slide 14–16

Buka `app/Http/Controllers/ProdukController.php:29`:

```php
public function store(ProdukRequest $request): RedirectResponse
{
    Produk::create($request->validated());

    return to_route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
}
```

Katakan: “`validated()` mengambil data yang lolos pemeriksaan. Setelah dibuat, aplikasi kembali ke daftar dan membawa pesan sementara.” Lihat `form.blade.php` baris 24–31: `old()` mengisi kembali nilai sebelumnya; `@error` menampilkan pesan pada kolom terkait. Nilai default kedua di `old()` membantu menampilkan nilai produk saat edit.

### 4. Daftar dan edit — slide 17–20

Buka `app/Http/Controllers/ProdukController.php:14`:

```php
return view('produk.index', [
    'daftarProduk' => Produk::with('kategori')->orderBy('nama')->get(),
]);
```

Katakan: “Produk diambil berdasarkan nama dan kategori dimuat bersama produk.” Di `resources/views/produk/index.blade.php:29`, tunjukkan `@forelse` dan `$produk->kategori->nama`. Ekspresi Blade `{{ ... }}` melakukan escaping terhadap teks yang ditampilkan; contoh pengujiannya ada di `ProdukTest.php:288`. Di `ProdukController.php:36`, parameter `Produk $produk` menerima model dari `{produk}` melalui route model binding; ID yang tidak ditemukan menghasilkan 404.

Buka `ProdukController.php:44` untuk update:

```php
public function update(ProdukRequest $request, Produk $produk): RedirectResponse
{
    $produk->update($request->validated());

    return to_route('produk.index')->with('success', 'Produk berhasil diperbarui.');
}
```

Tunjukkan bahwa data produk yang dipilih diperbarui; jumlah baris produk tidak bertambah.

### 5. Hapus, riwayat, dan pesan — slide 21–24

Buka `resources/views/produk/index.blade.php:38`: tombol hapus berada dalam form POST yang menyertakan `@csrf`, `@method('DELETE')`, dan dialog konfirmasi. **Cancel** membatalkan pengiriman; **OK** melanjutkan permintaan hapus.

Di `database/migrations/2026_11_18_000004_create_detail_transaksi_table.php:10`, tunjukkan `restrictOnDelete()` pada `produk_id`. Di `ProdukController.php:51`, jelaskan bahwa controller mencoba menghapus, lalu menangani error foreign key MySQL atau SQLite sebagai pesan bahwa produk sudah dipakai transaksi; error database lain tetap dilempar. Ini menjaga detail transaksi yang merujuk produk tersebut.

Buka `resources/views/produk/layout.blade.php:4`: `session('success')` menampilkan pesan berhasil, dan `session('error')` menampilkan alasan penolakan.

## Skenario demo di browser

Gunakan database latihan. Jika `H9-001` sudah ada, pilih SKU lain yang belum digunakan dan gunakan SKU yang sama secara konsisten pada langkah-langkah berikut. Periksa daftar produk atau tabel `produk` di phpMyAdmin sebelum memulai.

| Langkah | Tindakan | Hasil yang diharapkan |
| --- | --- | --- |
| Create | Di `/produk`, pilih **Tambah produk**. Isi SKU `H9-001`, nama `Kopi Praktik`, kategori `Minuman`, harga `15000`, stok `10`, lalu simpan. | Pesan berhasil tampil; satu produk baru ada di daftar dan tabel `produk`. |
| Read | Lihat produk baru di daftar dan di `/katalog`. | Nama, kategori, harga Rp 15.000, dan stok sesuai data yang disimpan. |
| Validasi SKU | Tambah lagi dengan SKU `H9-001`, nama `Kopi Duplikat`, dan kolom lain valid. | Pesan **SKU sudah digunakan produk lain.** muncul; input lain tetap terisi dan tidak ada produk baru. |
| Update | Edit `Kopi Praktik`, pertahankan SKU, ubah harga menjadi `17000`, lalu simpan. | Berhasil; produk yang sama berharga Rp 17.000, ID dan jumlah baris tetap. |
| SKU saat edit | Edit produk tersebut dan ganti SKU dengan SKU produk lain yang terlihat di daftar. | Ditolak; SKU dan harga yang sudah tersimpan tidak berubah. |
| Cancel delete | Klik **Hapus** pada `Kopi Praktik`, lalu pilih **Cancel/Batal** pada dialog browser. | Produk tetap ada. |
| Delete | Klik **Hapus** lagi dan pilih **OK**. | Produk uji hilang dari daftar dan tabel; pesan berhasil tampil. |

Untuk membuktikan perubahan di database, buka database latihan pada phpMyAdmin dan periksa isi tabel `produk` berdasarkan SKU serta ID sebelum dan sesudah tindakan. Pada template yang sedang berjalan, phpMyAdmin tersedia melalui port host **8081**. `db:show --counts` membantu melihat jumlah baris; jangan mengandalkan pesan sukses saja.

**Catatan validasi:** `required`, `min`, `max`, dan `step` pada HTML dapat menghentikan input kosong, negatif, atau pecahan di browser sebelum request dikirim. Untuk menunjukkan validasi **server** dengan mudah, gunakan contoh SKU duplikat di atas atau jalankan tes input tidak valid di `ProdukTest.php:115`. Tes tersebut juga memeriksa bahwa data gagal tidak tersimpan.

### Opsional: produk yang memiliki riwayat transaksi

Untuk menunjukkan slide 22 dengan data sungguhan, jalankan hanya pada database latihan:

```sh
php artisan db:seed --class=DemoRiwayatSeeder --no-interaction
```

Seeder ini membuat produk `DEMO-H9-RIWAYAT`, satu transaksi, dan detailnya. Di `/produk`, coba hapus **Kopi Demo Bertransaksi**: hasilnya **Produk dipakai pada transaksi dan tidak dapat dihapus.** Periksa bahwa produk dan detail transaksinya masih ada. Seeder menggunakan SKU dan nomor transaksi khusus; bila keduanya sudah dipakai data lain, selesaikan bentroknya di database latihan sebelum mengulang. Data demo ini tetap tersimpan setelah percobaan.

Alternatif tanpa menambahkan data demo ke MariaDB: tunjukkan `test_produk_dengan_riwayat_transaksi_tidak_dapat_dihapus` pada `tests/Feature/ProdukTest.php:212`, lalu jalankan tes produk di terminal. Tes memakai database pengujian SQLite; bukti perilaku MariaDB tetap perlu demo manual.

## Tanya jawab dan diagnosis singkat

- **Mengapa validasi server tetap perlu?** Pemeriksaan HTML dapat dilewati; server harus memeriksa setiap request sebelum menyimpan.
- **Mengapa SKU sendiri boleh tetap saat edit?** `ignore($produk)` mengecualikan baris yang sedang diperbarui dari pemeriksaan unik.
- **Apa beda `@csrf` dan `@method`?** `@csrf` menyertakan token perlindungan; `@method` menandai method seperti PUT atau DELETE pada form POST.
- **403:** periksa `authorize()`. **419:** periksa token CSRF, cookie, dan session. **405:** cocokkan method form dengan route. **404:** periksa ID dan parameter `{produk}`.
- **Kategori kosong:** siapkan kategori di database latihan; form menonaktifkan tombol simpan jika belum ada kategori. **SKU sendiri ditolak saat edit:** periksa binding `{produk}` dan `ignore($produk)`.

Untuk QA kelompok, catat langkah, hasil yang diharapkan, hasil aktual, dan bukti. Sertakan setidaknya kasus valid, SKU duplikat, update, delete, serta satu kasus input tidak valid sesuai PRD/FSD kelompok.
