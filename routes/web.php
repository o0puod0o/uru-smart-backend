<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SsoCallbackController;
use App\Http\Controllers\SsoRedirectController;
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

Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->name('admin.login');
});

Route::middleware(['auth:admin', 'admin.account'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

    Route::resource('users', AdminUserController::class)->except('create', 'store');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');

    Route::get('/notifications/create', [AdminNotificationController::class, 'create'])->name('notifications.create');
    Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');

    Route::get('/approvals', [AdminApprovalController::class, 'index'])->name('approvals.index');
    Route::put('/approvals/proposals/{proposal}', [AdminApprovalController::class, 'updateProposal'])->name('approvals.proposals.update');
    Route::put('/approvals/reports/{report}', [AdminApprovalController::class, 'updateReport'])->name('approvals.reports.update');

    Route::middleware('admin.super')->group(function () {
        Route::resource('accounts', AdminAccountController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);
    });
});
