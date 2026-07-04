<?php

use App\Http\Controllers\Central\MonitorController;
use App\Http\Controllers\Central\TenantController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

/*
|--------------------------------------------------------------------------
| Central Admin — يعمل على أي دومين
|--------------------------------------------------------------------------
| https://wa-queue.test/admin
| https://central.wa-queue.test/admin
| http://localhost/admin
*/

Route::prefix('central/api/v1')->group(function () {
    Route::get('/monitor', [MonitorController::class, 'index']);
    Route::post('/monitor/check-all', [MonitorController::class, 'checkAll']);
    Route::post('/monitor/{tenantId}/check', [MonitorController::class, 'checkTenant']);

    Route::apiResource('tenants', TenantController::class);
    Route::post('/tenants/{id}/domains', [TenantController::class, 'addDomain']);
});

Route::get('/admin/{any?}', function () {
    return view('central');
})->where('any', '.*')->name('central.admin');

/*
|--------------------------------------------------------------------------
| Central welcome (localhost فقط)
|--------------------------------------------------------------------------
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::get('/', fn () => redirect('/admin'));
    });
}

/*
|--------------------------------------------------------------------------
| Tenant routes — مسار على دومين النظام (الطريقة الافتراضية)
|--------------------------------------------------------------------------
| https://wa-queue.test/kaml-kamal/
| https://intellij-app.com/kaml-kamal/api/v1/queue
*/

$reservedTenantSegments = implode('|', config('tenancy.reserved_path_segments', []));

Route::prefix('{tenant}')
    ->where(['tenant' => "(?!{$reservedTenantSegments})[a-zA-Z0-9_-]+"])
    ->middleware([InitializeTenancyByPath::class])
    ->group(base_path('routes/tenant_routes.php'));
