@extends('produk.layout')

@section('title', ($produk->exists ? 'Edit Produk' : 'Tambah Produk').' | Kasir Toko')

@section('produk-content')
    <a class="back-link" href="{{ route('produk.index') }}">← Kembali ke produk</a>
    <p class="eyebrow">Manajemen toko</p>
    <h1>{{ $produk->exists ? 'Edit produk' : 'Tambah produk' }}</h1>
    <p class="intro">Isi informasi produk agar dapat ditampilkan di katalog.</p>

    <section class="panel form-panel" aria-label="Formulir produk">
        @if ($kategori->isEmpty())
            <div class="flash flash-error" role="alert">Belum ada kategori. Tambahkan kategori sebelum menyimpan produk.</div>
        @endif
        <form method="POST" action="{{ $produk->exists ? route('produk.update', $produk) : route('produk.store') }}">
            @csrf
            @if ($produk->exists)
                @method('PUT')
            @endif

            <div class="field-grid">
                <div class="field">
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" type="text" value="{{ old('sku', $produk->sku) }}" required maxlength="50" @error('sku') aria-invalid="true" aria-describedby="sku-error" @enderror>
                    <span class="field-hint">Kode unik untuk mengenali produk.</span>
                    @error('sku') <small class="field-error" id="sku-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="nama">Nama produk</label>
                    <input id="nama" name="nama" type="text" value="{{ old('nama', $produk->nama) }}" required maxlength="255" @error('nama') aria-invalid="true" aria-describedby="nama-error" @enderror>
                    @error('nama') <small class="field-error" id="nama-error">{{ $message }}</small> @enderror
                </div>
                <div class="field field-wide">
                    <label for="kategori_id">Kategori</label>
                    <select id="kategori_id" name="kategori_id" required @error('kategori_id') aria-invalid="true" aria-describedby="kategori_id-error" @enderror>
                        <option value="">Pilih kategori</option>
                        @foreach ($kategori as $item)
                            <option value="{{ $item->id }}" @selected((string) old('kategori_id', $produk->kategori_id) === (string) $item->id)>{{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @error('kategori_id') <small class="field-error" id="kategori_id-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="harga">Harga (Rp)</label>
                    <input id="harga" name="harga" type="number" value="{{ old('harga', $produk->harga) }}" required min="0" max="4294967295" step="1" @error('harga') aria-invalid="true" aria-describedby="harga-error" @enderror>
                    <span class="field-hint">Rupiah utuh, contoh: 15000.</span>
                    @error('harga') <small class="field-error" id="harga-error">{{ $message }}</small> @enderror
                </div>
                <div class="field">
                    <label for="stok">Stok</label>
                    <input id="stok" name="stok" type="number" value="{{ old('stok', $produk->stok) }}" required min="0" max="4294967295" step="1" @error('stok') aria-invalid="true" aria-describedby="stok-error" @enderror>
                    @error('stok') <small class="field-error" id="stok-error">{{ $message }}</small> @enderror
                </div>
            </div>
            <div class="form-actions">
                <button class="button button-primary" type="submit" @disabled($kategori->isEmpty())>Simpan produk</button>
                <a class="button button-secondary" href="{{ route('produk.index') }}">Batal</a>
            </div>
        </form>
    </section>
@endsection
