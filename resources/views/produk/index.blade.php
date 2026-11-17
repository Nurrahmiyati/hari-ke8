@extends('produk.layout')

@section('title', 'Kelola Produk | Kasir Toko')

@section('produk-content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Manajemen toko</p>
            <h1>Kelola produk</h1>
            <p class="intro">Tambah, ubah, atau hapus produk yang tersedia di katalog.</p>
        </div>
        <a class="button button-primary" href="{{ route('produk.create') }}">+ Tambah produk</a>
    </div>

    <section class="panel" aria-labelledby="judul-produk">
        <div class="panel-header">
            <div>
                <h2 class="panel-title" id="judul-produk">Data produk</h2>
                <p class="panel-subtitle">Kelola informasi produk dan stok toko</p>
            </div>
            <span class="product-count">{{ $daftarProduk->count() }} produk</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th scope="col">Produk</th><th scope="col">Kategori</th><th scope="col">Harga</th><th scope="col">Stok</th><th scope="col">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($daftarProduk as $produk)
                        <tr>
                            <td><span class="product-name">{{ $produk->nama }}</span><span class="sku">{{ $produk->sku }}</span></td>
                            <td><span class="category">{{ $produk->kategori->nama }}</span></td>
                            <td class="price">Rp {{ number_format($produk->harga, 0, ',', '.') }}</td>
                            <td>{{ $produk->stok }}</td>
                            <td>
                                <div class="actions">
                                    <a class="action-link" href="{{ route('produk.edit', $produk) }}">Edit <span class="sr-only">{{ $produk->nama }}</span></a>
                                    <form action="{{ route('produk.destroy', $produk) }}" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="action-delete" type="submit">Hapus <span class="sr-only">{{ $produk->nama }}</span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="5"><strong>Belum ada produk.</strong>Tambahkan produk pertama untuk mengisi katalog.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-footer"><span>Menampilkan {{ $daftarProduk->count() }} produk</span><span>Harga dalam Rupiah</span></div>
    </section>
@endsection
