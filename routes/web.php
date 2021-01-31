<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Organizations;


Route::get('/', [Organizations::class, 'root'])->name('root');

Route::get('/about', [Organizations::class, 'about'])->name('about');

Route::get('/organizations', [Organizations::class, 'list'])->name('orgs');

Route::get('/organization/{id}', [Organizations::class, 'orgAbout'])->name('orgProfile');

Route::get('/organization/{id}/{section}', [Organizations::class, 'orgSection'])->name('orgSection');
