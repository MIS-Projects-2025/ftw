<?php


use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\General\FtwController;

$app_name = env('APP_NAME', '');

Route::redirect('/', "/$app_name");

Route::prefix($app_name)
    ->middleware(AuthMiddleware::class)
    ->name('ftw.')
    ->group(function () {

        Route::get("/records",      [FtwController::class, 'index'])->name('index');
        Route::get("/create", [FtwController::class, 'create'])->name('create');

        Route::get("/data/history", [FtwController::class, 'historyData'])->name('data.history');
        Route::get("/data/pending", [FtwController::class, 'pendingData'])->name('data.pending');

        Route::get("/employees", [FtwController::class, 'searchEmployees'])->name('employees');
        Route::get("/employees/{id}/work", [FtwController::class, 'employeeWorkDetails'])->name('employee.work');

        Route::post("/",            [FtwController::class, 'store'])->name('store');
        Route::post("/bulk-action", [FtwController::class, 'bulkAction'])->name('bulk-action');
        Route::post("/{id}/action", [FtwController::class, 'handleAction'])
            ->whereNumber('id')
            ->name('action');

        Route::get("/{id}", [FtwController::class, 'show'])
            ->whereNumber('id')
            ->name('show');
    });
