<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Organizations;
use App\Http\Controllers\Districts;
use App\Http\Controllers\Projects;


Route::get('/', [Organizations::class, 'root'])->name('root');

Route::get('/about', [Organizations::class, 'about'])->name('about');

Route::get('/agencies', [Organizations::class, 'list'])->name('orgs');

Route::get('/agency/{id}', [Organizations::class, 'orgAbout'])->name('orgProfile');

Route::get('/agency/{id}/capitalprojects', [Organizations::class, 'orgProjectSection'])->name('orgProjectSection');

Route::get('/agency/{id}/{section}', [Organizations::class, 'orgSection'])->name('orgSection');

//Route::get('/agency/{id}/capitalprojects/{prjId}', [Organizations::class, 'orgProject'])->name('orgProject');
Route::get('/agency/{id}/capitalprojects/{prjId}', function ($id, $prjId) {
    return redirect(route('project', ['prjId' => $prjId]));
})->name('orgProject');
Route::get('/capitalprojects/{prjId}', [Organizations::class, 'project'])->name('project');



Route::get('/districts', [Districts::class, 'main'])->name('districts');

Route::get('/districts/{type}/{id}/{section}', [Districts::class, 'main'])->name('districtsPreset');

Route::get('/districtXHR/{type}/{id}/{section}', [Districts::class, 'section'])->name('distSection');



Route::get('/capitalprojects', [Projects::class, 'main'])->name('projects');

