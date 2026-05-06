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

        Route::get("/create", [FtwController::class, 'create'])->name('create');
        Route::get("/{id}", [FtwController::class, 'show'])
            ->whereNumber('id')
            ->name('show');

        Route::get("/employees", [FtwController::class, 'searchEmployees'])->name('employees');
        Route::get("/employees/{id}/work", [FtwController::class, 'employeeWorkDetails'])->name('employee.work');

        Route::post("/", [FtwController::class, 'store'])->name('store');
    });
