<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KenoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('/test', [KenoController::class, 'test']);

Route::prefix('keno')->group(function () {
    Route::get('/', [KenoController::class, 'index'])->name('api.index');
    Route::get('/kenocount', [KenoController::class, 'kenocount'])->name('api.kenocount');
    Route::get('/pull', [KenoController::class, 'kenopull'])->name('api.kenopull');
});