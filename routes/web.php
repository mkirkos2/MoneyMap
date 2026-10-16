<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\SavingsGoalEntryController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return redirect()->to(route('login', absolute: false));
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('transactions', TransactionController::class)->except(['show']);
    Route::resource('budgets', BudgetController::class)->except(['show']);
    Route::resource('savings-goals', SavingsGoalController::class);
    Route::post('savings-goals/{savings_goal}/entries', [SavingsGoalEntryController::class, 'store'])
        ->name('savings-goals.entries.store');
});

require __DIR__.'/settings.php';
