<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationRequestController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffPosController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use App\Models\Branch;
use App\Models\Menu;
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
        'branches' => Branch::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get(['id', 'name', 'address', 'phone']),
        'menus' => Menu::query()
            ->with('category:id,name')
            ->where('is_available', true)
            ->orderByDesc('is_best_seller')
            ->orderByDesc('is_must_try')
            ->orderBy('category_id')
            ->orderBy('id')
            ->get(['id', 'category_id', 'name', 'description', 'price', 'is_best_seller', 'is_must_try']),
    ]);
})->name('home');

Route::post('/reservations', [ReservationRequestController::class, 'store'])
    ->name('reservations.store');

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

    Route::middleware(['auth', 'role:admin,manager'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', UserController::class);
        Route::resource('menus', MenuController::class);
        Route::resource('orders', OrderController::class);
        Route::resource('tables', TableController::class);
        Route::get('/reservations', [ReservationRequestController::class, 'index'])->name('reservations.index');
        Route::patch('/reservations/{id}/status', [ReservationRequestController::class, 'updateStatus'])->name('reservations.status');

        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::resource('branches', BranchController::class);
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
        Route::post('/tables/reserve', [StaffPosController::class, 'reserveTable'])->name('tables.reserve');
        Route::post('/tables/cancel-reservation', [StaffPosController::class, 'cancelReservation'])->name('tables.cancel-reservation');
        Route::post('/tables/move', [StaffPosController::class, 'moveTable'])->name('tables.move');
        Route::post('/checkout', [StaffPosController::class, 'checkout'])->name('checkout');

        Route::post('/logout', [StaffAuthController::class, 'destroy'])->name('logout');
    });
});

require __DIR__.'/auth.php';
