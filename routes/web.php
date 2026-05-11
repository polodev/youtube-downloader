<?php

use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DownloadController::class, 'index'])->name('downloads.index');
Route::post('/downloads', [DownloadController::class, 'store'])->name('downloads.store');
Route::post('/downloads/{download}/download', [DownloadController::class, 'download'])->name('downloads.download');
Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('downloads.destroy');

Route::get('/video-gallery', fn() => view('video-gallery'))->name('video-gallery');
