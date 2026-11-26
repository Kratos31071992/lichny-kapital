<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Маршруты платформы «Личный капитал» (Спринт 1)
|--------------------------------------------------------------------------
*/

// Посадочная страница (п. 3 ТЗ): неавторизованный пользователь её видит,
// авторизованный — сразу перенаправляется на дашборд (логика в Welcome.vue).
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

// Дашборд доступен только после подтверждения email (middleware verified).
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Профиль пользователя (п. 4 ТЗ): только для авторизованных.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // «Скачать мои данные» — экспорт по 152-ФЗ (п. 4 ТЗ).
    Route::get('/profile/export', [ProfileController::class, 'export'])->name('profile.export');
    // «Удалить аккаунт» — полное удаление данных (п. 4 ТЗ).
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Админка (п. 5 ТЗ): доступ только для роли admin.
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    // Массовая блокировка и удаление спама (п. 5 ТЗ).
    Route::post('/users/bulk', [AdminUserController::class, 'bulk'])->name('users.bulk');
    // Генерация и смена паролей (п. 5 ТЗ).
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');

    // Журнал действий (п. 5 ТЗ: кто, когда и что менял у пользователей).
    Route::get('/logs', [AuditLogController::class, 'index'])->name('logs.index');

    // Настройки интеграций и тестовая отправка (п. 6 ТЗ).
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('/integrations/test', [IntegrationController::class, 'test'])->name('integrations.test');
    Route::post('/integrations/backup', [IntegrationController::class, 'backupNow'])->name('integrations.backup');
});

require __DIR__.'/auth.php';
