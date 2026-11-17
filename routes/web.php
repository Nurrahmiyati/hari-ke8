<?php

use App\Http\Controllers\KatalogController;
use App\Http\Controllers\ProdukController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/katalog');
Route::get('/katalog', [KatalogController::class, 'index'])->name('katalog.index');
Route::resource('produk', ProdukController::class)
    ->parameters(['produk' => 'produk'])
    ->except(['show']);
