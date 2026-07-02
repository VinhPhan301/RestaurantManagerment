<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffPosController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:manager,staff'])->group(function () {
    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
    Route::patch('/kitchen/items/{orderItem}/status', [KitchenController::class, 'updateItemStatus'])->name('kitchen.items.status');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store']);
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('dashboard');

        Route::resource('branches', BranchController::class);
        Route::resource('users', UserController::class);
        Route::resource('menus', MenuController::class);
        Route::resource('orders', OrderController::class);
        Route::resource('tables', TableController::class);

        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    });
});

// Staff Routes
Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [StaffAuthController::class, 'create'])->name('login');
        Route::post('/login', [StaffAuthController::class, 'store']);
    });

    Route::middleware(['auth', 'role:manager,staff'])->group(function () {
        Route::get('/dashboard', [StaffPosController::class, 'dashboard'])->name('dashboard');
        Route::post('/orders', [StaffPosController::class, 'storeOrder'])->name('orders.store');
        Route::post('/tables/move', [StaffPosController::class, 'moveTable'])->name('tables.move');
        Route::post('/checkout', [StaffPosController::class, 'checkout'])->name('checkout');

        Route::post('/logout', [StaffAuthController::class, 'destroy'])->name('logout');
    });
});

require __DIR__.'/auth.php';
