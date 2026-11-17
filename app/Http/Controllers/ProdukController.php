<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdukRequest;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProdukController extends Controller
{
    public function index(): View
    {
        return view('produk.index', [
            'daftarProduk' => Produk::with('kategori')->orderBy('nama')->get(),
        ]);
    }

    public function create(): View
    {
        return view('produk.form', [
            'produk' => new Produk,
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function store(ProdukRequest $request): RedirectResponse
    {
        Produk::create($request->validated());

        return to_route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk): View
    {
        return view('produk.form', [
            'produk' => $produk,
            'kategori' => Kategori::orderBy('nama')->get(),
        ]);
    }

    public function update(ProdukRequest $request, Produk $produk): RedirectResponse
    {
        $produk->update($request->validated());

        return to_route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        try {
            $produk->delete();
        } catch (QueryException $exception) {
            $driver = $produk->getConnection()->getDriverName();
            $errorCode = (int) ($exception->errorInfo[1] ?? 0);
            $isReferencedProduct = ($driver === 'mysql' && $errorCode === 1451)
                || ($driver === 'sqlite' && $errorCode === 19 && str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed'));

            if (! $isReferencedProduct) {
                throw $exception;
            }

            return to_route('produk.index')->with('error', 'Produk dipakai pada transaksi dan tidak dapat dihapus.');
        }

        return to_route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }
}
