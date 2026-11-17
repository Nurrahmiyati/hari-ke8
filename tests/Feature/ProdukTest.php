<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Produk;
use Database\Seeders\DemoRiwayatSeeder;
use Database\Seeders\KatalogSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ProdukTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daftar_produk_menampilkan_data_dan_navigasi_katalog(): void
    {
        $this->seed(KatalogSeeder::class);

        $this->get(route('produk.index'))
            ->assertOk()
            ->assertSeeInOrder(['Buku Tulis', 'Kopi Ende', 'Nasi Bungkus'])
            ->assertSee('KOP-001')
            ->assertSee('Minuman')
            ->assertSee('Rp 15.000')
            ->assertSee('Tambah produk')
            ->assertSee(route('katalog.index'));
    }

    public function test_form_produk_menampilkan_kategori_dan_nilai_saat_edit(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $produk = Produk::create($this->dataProduk($kategori));

        $this->get(route('produk.edit', $produk))
            ->assertOk()
            ->assertSee('Edit produk')
            ->assertSee('H9-001')
            ->assertSee('Kopi Praktik')
            ->assertSee('Minuman')
            ->assertSee('value="PUT"', false);
    }

    public function test_form_tambah_menjelaskan_jika_belum_ada_kategori(): void
    {
        $this->get(route('produk.create'))
            ->assertOk()
            ->assertSee('Belum ada kategori.')
            ->assertSee('type="submit" disabled', false);
    }

    #[DataProvider('batasAngkaValid')]
    public function test_produk_dapat_disimpan_pada_batas_harga_dan_stok(int $nilai): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $data = $this->dataProduk($kategori, [
            'sku' => str_repeat('S', 50),
            'nama' => str_repeat('N', 255),
            'harga' => $nilai,
            'stok' => $nilai,
            'id' => 9999,
        ]);

        $this->post(route('produk.store'), $data)
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('success', 'Produk berhasil ditambahkan.');

        unset($data['id']);
        $this->assertDatabaseHas('produk', $data);
        $this->assertDatabaseMissing('produk', ['id' => 9999]);
        $this->assertDatabaseCount('produk', 1);
    }

    /** @return array<string, array{int}> */
    public static function batasAngkaValid(): array
    {
        return ['nol' => [0], 'maksimum unsigned integer' => [4294967295]];
    }

    public function test_pesan_sukses_tampil_di_daftar_setelah_produk_disimpan(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);

        $this->followingRedirects()
            ->post(route('produk.store'), $this->dataProduk($kategori))
            ->assertOk()
            ->assertSee('Produk berhasil ditambahkan.')
            ->assertSee('Kopi Praktik');

        $this->assertDatabaseHas('produk', ['sku' => 'H9-001']);
    }

    public function test_data_wajib_yang_kosong_ditolak(): void
    {
        $this->from(route('produk.create'))
            ->post(route('produk.store'), [])
            ->assertRedirect(route('produk.create'))
            ->assertSessionHasErrors([
                'sku' => 'SKU wajib diisi.',
                'nama' => 'Nama produk wajib diisi.',
                'kategori_id' => 'Kategori wajib diisi.',
                'harga' => 'Harga wajib diisi.',
                'stok' => 'Stok wajib diisi.',
            ]);

        $this->assertDatabaseCount('produk', 0);
    }

    /** @param array<string, mixed> $perubahan */
    #[DataProvider('inputProdukTidakValid')]
    public function test_input_tidak_valid_ditolak(array $perubahan, string $kolom, string $pesan): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);

        $this->from(route('produk.create'))
            ->post(route('produk.store'), $this->dataProduk($kategori, $perubahan))
            ->assertRedirect(route('produk.create'))
            ->assertSessionHasErrors([$kolom => $pesan]);

        $this->assertDatabaseCount('produk', 0);
    }

    /** @return array<string, array{array<string, mixed>, string, string}> */
    public static function inputProdukTidakValid(): array
    {
        return [
            'sku terlalu panjang' => [['sku' => str_repeat('S', 51)], 'sku', 'SKU maksimal 50 karakter.'],
            'sku bukan teks' => [['sku' => ['H9-001']], 'sku', 'SKU harus berupa teks.'],
            'nama terlalu panjang' => [['nama' => str_repeat('N', 256)], 'nama', 'Nama produk maksimal 255 karakter.'],
            'nama bukan teks' => [['nama' => ['Kopi']], 'nama', 'Nama produk harus berupa teks.'],
            'kategori tidak ada' => [['kategori_id' => 999999], 'kategori_id', 'Kategori tidak ditemukan.'],
            'kategori bukan bilangan bulat' => [['kategori_id' => 'abc'], 'kategori_id', 'Kategori harus bilangan bulat.'],
            'harga negatif' => [['harga' => -1], 'harga', 'Harga minimal 0.'],
            'harga pecahan' => [['harga' => '1.5'], 'harga', 'Harga harus bilangan bulat.'],
            'harga bukan angka' => [['harga' => 'abc'], 'harga', 'Harga harus bilangan bulat.'],
            'harga melebihi batas' => [['harga' => 4294967296], 'harga', 'Harga maksimal 4294967295.'],
            'stok negatif' => [['stok' => -1], 'stok', 'Stok minimal 0.'],
            'stok pecahan' => [['stok' => '1.5'], 'stok', 'Stok harus bilangan bulat.'],
            'stok bukan angka' => [['stok' => 'abc'], 'stok', 'Stok harus bilangan bulat.'],
            'stok melebihi batas' => [['stok' => 4294967296], 'stok', 'Stok maksimal 4294967295.'],
        ];
    }

    public function test_sku_duplikat_ditolak_dan_nilai_lain_tetap_di_form(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        Produk::create($this->dataProduk($kategori));

        $this->followingRedirects()->from(route('produk.create'))
            ->post(route('produk.store'), $this->dataProduk($kategori, ['nama' => 'Kopi Lain']))
            ->assertOk()
            ->assertSee('SKU sudah digunakan produk lain.')
            ->assertSee('value="Kopi Lain"', false);
        $this->assertDatabaseCount('produk', 1);
    }

    public function test_produk_dapat_diubah_dengan_sku_miliknya_sendiri(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $kategoriBaru = Kategori::create(['nama' => 'Makanan']);
        $produk = Produk::create($this->dataProduk($kategori));

        $this->put(route('produk.update', $produk), $this->dataProduk($kategoriBaru, [
            'nama' => 'Kopi Praktik Baru',
            'harga' => 17000,
            'stok' => 0,
        ]))->assertRedirect(route('produk.index'))
            ->assertSessionHas('success', 'Produk berhasil diperbarui.');

        $this->assertDatabaseHas('produk', [
            'id' => $produk->id,
            'sku' => 'H9-001',
            'nama' => 'Kopi Praktik Baru',
            'kategori_id' => $kategoriBaru->id,
            'harga' => 17000,
            'stok' => 0,
        ]);
        $this->assertDatabaseCount('produk', 1);
    }

    public function test_sku_produk_lain_ditolak_saat_edit_tanpa_mengubah_data(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $produk = Produk::create($this->dataProduk($kategori));
        Produk::create($this->dataProduk($kategori, ['sku' => 'H9-002', 'nama' => 'Teh Praktik']));

        $this->from(route('produk.edit', $produk))
            ->put(route('produk.update', $produk), $this->dataProduk($kategori, ['sku' => 'H9-002', 'harga' => 17000]))
            ->assertRedirect(route('produk.edit', $produk))
            ->assertSessionHasErrors(['sku' => 'SKU sudah digunakan produk lain.']);

        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'sku' => 'H9-001', 'harga' => 15000]);
        $this->assertDatabaseCount('produk', 2);
    }

    public function test_produk_tanpa_riwayat_dapat_dihapus(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $produk = Produk::create($this->dataProduk($kategori));

        $this->delete(route('produk.destroy', $produk))
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('success', 'Produk berhasil dihapus.');

        $this->assertModelMissing($produk);
    }

    public function test_produk_dengan_riwayat_transaksi_tidak_dapat_dihapus(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $produk = Produk::create($this->dataProduk($kategori));
        $transaksiId = DB::table('transaksi')->insertGetId([
            'nomor' => 'TRX-TEST', 'waktu' => now(), 'total' => 15000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('detail_transaksi')->insert([
            'transaksi_id' => $transaksiId, 'produk_id' => $produk->id,
            'jumlah' => 1, 'harga_satuan' => 15000, 'subtotal' => 15000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->delete(route('produk.destroy', $produk))
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('error', 'Produk dipakai pada transaksi dan tidak dapat dihapus.');

        $this->assertModelExists($produk);
        $this->assertDatabaseHas('detail_transaksi', ['transaksi_id' => $transaksiId, 'produk_id' => $produk->id]);
    }

    public function test_seeder_riwayat_membuat_data_demo_tanpa_menimpa_perubahan_saat_diulang(): void
    {
        $this->seed(DemoRiwayatSeeder::class);
        Produk::where('sku', 'DEMO-H9-RIWAYAT')->update(['harga' => 17000]);

        $this->seed(DemoRiwayatSeeder::class);

        $produk = Produk::where('sku', 'DEMO-H9-RIWAYAT')->firstOrFail();
        $transaksi = DB::table('transaksi')->where('nomor', 'DEMO-H9-RIWAYAT')->first();
        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'harga' => 17000]);
        $this->assertDatabaseHas('detail_transaksi', ['transaksi_id' => $transaksi->id, 'produk_id' => $produk->id, 'harga_satuan' => 15000]);
        $this->assertDatabaseCount('produk', 1);
        $this->assertDatabaseCount('transaksi', 1);
        $this->assertDatabaseCount('detail_transaksi', 1);
    }

    public function test_seeder_riwayat_tidak_menggunakan_sku_milik_produk_lain(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $produk = Produk::create($this->dataProduk($kategori, ['sku' => 'DEMO-H9-RIWAYAT', 'nama' => 'Produk Lain']));

        try {
            $this->seed(DemoRiwayatSeeder::class);
            $this->fail('Seeder harus menolak SKU yang sudah digunakan produk lain.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertDatabaseHas('produk', ['id' => $produk->id, 'nama' => 'Produk Lain']);
            $this->assertDatabaseCount('transaksi', 0);
            $this->assertDatabaseCount('detail_transaksi', 0);
        }
    }

    public function test_seeder_riwayat_membatalkan_perubahan_jika_nomor_transaksi_sudah_dipakai(): void
    {
        DB::table('transaksi')->insert([
            'nomor' => 'DEMO-H9-RIWAYAT', 'waktu' => now(), 'total' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        try {
            $this->seed(DemoRiwayatSeeder::class);
            $this->fail('Seeder harus menolak nomor transaksi yang sudah digunakan data lain.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Nomor transaksi demo sudah digunakan oleh data lain.', $exception->getMessage());
            $this->assertDatabaseCount('produk', 0);
            $this->assertDatabaseCount('kategori', 0);
            $this->assertDatabaseCount('transaksi', 1);
        }
    }

    public function test_produk_yang_tidak_ada_menghasilkan_404(): void
    {
        $this->get(route('produk.edit', 999999))->assertNotFound();
    }

    public function test_daftar_produk_melakukan_escaping_pada_input_pengguna(): void
    {
        $kategori = Kategori::create(['nama' => 'Minuman']);
        $nama = '<script>alert(1)</script>';
        $sku = '<svg onload=alert(1)>';
        Produk::create($this->dataProduk($kategori, ['nama' => $nama, 'sku' => $sku]));

        $this->get(route('produk.index'))
            ->assertOk()
            ->assertSee($nama)
            ->assertSee($sku)
            ->assertDontSee($nama, false)
            ->assertDontSee($sku, false);
    }

    /**
     * @param  array<string, mixed>  $perubahan
     * @return array<string, mixed>
     */
    private function dataProduk(Kategori $kategori, array $perubahan = []): array
    {
        return array_replace([
            'kategori_id' => $kategori->id,
            'sku' => 'H9-001',
            'nama' => 'Kopi Praktik',
            'harga' => 15000,
            'stok' => 10,
        ], $perubahan);
    }
}
