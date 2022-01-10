<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Organizations;
use App\Http\Controllers\Districts;
use App\Http\Controllers\Projects;
use App\Http\Controllers\Titles;
use App\Http\Controllers\Notices;


Route::get('/', [Organizations::class, 'root'])->name('root');

Route::get('/about', [Organizations::class, 'about'])->name('about');

Route::get('/organizations', function () {
    return redirect(route('orgs'));
});
Route::get('/organizations/directory', [Organizations::class, 'orgsDirectory'])->name('orgs');
Route::get('/organizations/chart', [Organizations::class, 'orgsChart'])->name('orgsChart');
Route::get('/organizations/chart/{id}', [Organizations::class, 'orgsChart'])->name('orgsChartFocus');
Route::get('/organizations/all', [Organizations::class, 'orgsAll'])->name('orgsAll');

Route::get('/organization/{id}', [Organizations::class, 'orgAbout'])->name('orgProfile');

Route::get('/organization/{id}/capitalprojects', [Organizations::class, 'orgProjectSection'])->name('orgProjectSection');

Route::get('/organization/{id}/notices/{subsection}', [Organizations::class, 'orgNoticesSection'])->name('orgNoticeSection');

Route::get('/organization/{id}/{section}', [Organizations::class, 'orgSection'])->name('orgSection');

//Route::get('/organization/{id}/capitalprojects/{prjId}', [Organizations::class, 'orgProject'])->name('orgProject');
Route::get('/organization/{id}/capitalprojects/{prjId}', function ($id, $prjId) {
    return redirect(route('project', ['prjId' => $prjId]));
})->name('orgProject');
Route::get('/capitalprojects/{prjId}', [Organizations::class, 'project'])->name('project');



Route::get('/districts', [Districts::class, 'main'])->name('districts');

Route::get('/districts/{type}/{id}/{section}', [Districts::class, 'main'])->name('districtsPreset');

Route::get('/districtXHR/{type}/{id}/capitalprojects', [Districts::class, 'projectSection'])->name('distProjectSection');

Route::get('/districtXHR/{type}/{id}/{section}', [Districts::class, 'section'])->name('distSection');



Route::get('/capitalprojects', [Projects::class, 'main'])->name('projects');



Route::get('/titles', [Titles::class, 'main'])->name('titles');

Route::get('/titles/{id}', function ($id) {
    return redirect(route('titleSection', ['id' => $id, 'section' => 'schedule']));
})->name('title');

Route::get('/titles/{id}/{section}', [Titles::class, 'section'])->name('titleSection');



Route::get('/notices', [Notices::class, 'main'])->name('notices');

Route::get('/notices/events.ics', [Notices::class, 'ical'])->name('noticesIcalEvents');

Route::get('/notices/{section}', [Notices::class, 'section'])->name('noticesSection');



