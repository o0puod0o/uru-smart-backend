<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SsoCallbackController;
use App\Http\Controllers\SsoRedirectController;
use App\Http\Controllers\WebAdminAuthController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
    ]);
});

Route::get('/db-connections-demo', function () {
    $primary = DB::connection('mysql')->selectOne('select database() as name');
    $second = DB::connection('mysql_second')->selectOne('select database() as name');

    DB::connection('mysql_second')->statement(
        'create table if not exists external_training_courses (id int auto_increment primary key, course_code varchar(30) not null, course_name varchar(150) not null, owner_system varchar(80) not null, created_at timestamp default current_timestamp)'
    );

    $seedRows = [
        ['course_code' => 'EXT-101', 'course_name' => 'อบรมการใช้งานระบบผู้เชี่ยวชาญ', 'owner_system' => 'Expert Database'],
        ['course_code' => 'EXT-202', 'course_name' => 'อบรมงานวิจัยและผลงานวิชาการ', 'owner_system' => 'Expert Database'],
        ['course_code' => 'EXT-303', 'course_name' => 'อบรมฐานข้อมูลภายนอก', 'owner_system' => 'Expert Database'],
    ];

    foreach ($seedRows as $row) {
        DB::connection('mysql_second')
            ->table('external_training_courses')
            ->updateOrInsert(['course_code' => $row['course_code']], $row);
    }

    $primaryUsers = DB::connection('mysql')
        ->table('users')
        ->select('id', 'username', 'email')
        ->orderBy('id')
        ->limit(5)
        ->get();

    $secondCourses = DB::connection('mysql_second')
        ->table('external_training_courses')
        ->orderBy('id')
        ->get();

    return view('db-connections-demo', [
        'primaryDatabase' => $primary->name,
        'secondDatabase' => $second->name,
        'primaryUsers' => $primaryUsers,
        'secondCourses' => $secondCourses,
    ]);
});

Route::get('/frontend-sso-demo', function () {
    return view('frontend-sso-demo');
});

Route::get('/auth/callback', SsoCallbackController::class)->name('auth.callback');
Route::get('/auth/sso-url', [SsoRedirectController::class, 'url'])->name('auth.sso-url');
Route::get('/auth/redirect', [SsoRedirectController::class, 'redirect'])->name('auth.redirect');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [WebAdminAuthController::class, 'create'])->name('login');
    Route::get('/admin/sso/redirect', [WebAdminAuthController::class, 'redirectToSso'])->name('admin.sso.redirect');
    Route::post('/admin/login', [WebAdminAuthController::class, 'store'])->name('admin.login');
});

Route::middleware(['auth', 'web.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.users.index'))->name('dashboard');
    Route::post('/logout', [WebAdminAuthController::class, 'destroy'])->name('logout');
    Route::resource('users', AdminUserController::class)->except('create', 'store');
});
