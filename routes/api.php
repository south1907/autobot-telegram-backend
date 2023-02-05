<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|----------------------------------updateEmail----------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('bot')->group(function () {
    Route::post('setWebhook', [BotController::class, 'setWebhook'])->name('bot.setWebhook');
    Route::post('deleteWebhook', [BotController::class, 'deleteWebhook'])->name('bot.deleteWebhook');
    Route::post('{botToken}/webhook', [BotController::class, 'webhook'])->name('bot.webhook');
});

Route::prefix('group')->group(function () {
    Route::get('', [GroupController::class, 'index'])->name('group.index');
    Route::post('{groupId}/edit', [GroupController::class, 'update'])->name('group.update');
});

Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('verify-token', [AuthController::class, 'verify'])->name('verify');
