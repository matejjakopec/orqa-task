<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\Files\FileDownloadController;
use App\Http\Controllers\Files\FileStatusController;
use App\Http\Controllers\Superadmin\OrganisationController;
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;
use App\Http\Controllers\Users\ProfileController;
use App\Models\StoredFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function (Request $request): RedirectResponse {
    return redirect()->route($request->user()->can('viewAny', StoredFile::class) ? 'files.index' : 'admin.users.index');
})->middleware(['auth', 'role:user,admin,superadmin'])->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('role:user,superadmin')->group(function (): void {
        Route::get('/files', [FileController::class, 'index'])->name('files.index');
        Route::get('/files/status', FileStatusController::class)->middleware('throttle:file-status')->name('files.status');
        Route::post('/files', [FileController::class, 'store'])->middleware('throttle:file-uploads')->name('files.store');
        Route::get('/files/{publicId}/download', FileDownloadController::class)->whereUuid('publicId')->middleware('throttle:file-downloads')->name('files.download');
        Route::get('/files/{publicId}/delete', [FileController::class, 'show'])->whereUuid('publicId')->name('files.delete');
        Route::delete('/files/{publicId}', [FileController::class, 'destroy'])->whereUuid('publicId')->middleware('throttle:file-deletions')->name('files.destroy');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{publicId}', [AdminUserController::class, 'show'])->whereUuid('publicId')->name('users.show');
    });

    Route::prefix('superadmin')->name('superadmin.')->middleware('role:superadmin')->group(function (): void {
        Route::get('/users', [SuperadminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [SuperadminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [SuperadminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{publicId}', [SuperadminUserController::class, 'show'])->whereUuid('publicId')->name('users.show');
        Route::get('/organisations', [OrganisationController::class, 'index'])->name('organisations.index');
        Route::get('/organisations/create', [OrganisationController::class, 'create'])->name('organisations.create');
        Route::post('/organisations', [OrganisationController::class, 'store'])->name('organisations.store');
    });
});
