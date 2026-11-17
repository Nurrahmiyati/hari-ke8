<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoRiwayatSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $kategori = Kategori::firstOrCreate(['nama' => 'Minuman']);
            $produk = Produk::firstOrCreate(
                ['sku' => 'DEMO-H9-RIWAYAT', 'nama' => 'Kopi Demo Bertransaksi'],
                ['kategori_id' => $kategori->id, 'harga' => 15000, 'stok' => 10],
            );

            $transaksi = DB::table('transaksi')->where('nomor', 'DEMO-H9-RIWAYAT')->first();

            if ($transaksi !== null) {
                $memilikiDetailDemo = DB::table('detail_transaksi')
                    ->where('transaksi_id', $transaksi->id)
                    ->where('produk_id', $produk->id)
                    ->exists();

                if (! $memilikiDetailDemo) {
                    throw new RuntimeException('Nomor transaksi demo sudah digunakan oleh data lain.');
                }

                return;
            }

            $waktu = now();
            $transaksiId = DB::table('transaksi')->insertGetId([
                'nomor' => 'DEMO-H9-RIWAYAT',
                'waktu' => $waktu,
                'total' => 15000,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);

            DB::table('detail_transaksi')->insert([
                'transaksi_id' => $transaksiId,
                'produk_id' => $produk->id,
                'jumlah' => 1,
                'harga_satuan' => 15000,
                'subtotal' => 15000,
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
        });
    }
}
