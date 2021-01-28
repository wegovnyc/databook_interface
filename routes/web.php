<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Organizations;

Route::get('/', function () {
    return view('index');
})->name('index');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/organizations', [Organizations::class, 'list'])->name('orgs');

Route::get('/organization/{id}', [Organizations::class, 'about'])->name('orgProfile');

Route::get('/organization/{id}/{section}', [Organizations::class, 'section'])->name('orgSection');
