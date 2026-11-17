<?php
// Gabungkan ke routes/web.php; jangan timpa route katalog hari 8.
use App\Http\Controllers\ProdukController;
Route::resource('produk', ProdukController::class)
    ->parameters(['produk' => 'produk'])->except(['show']);
