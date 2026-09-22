<?php

use App\Http\Controllers\Auth\SessionTokenController;
use Illuminate\Support\Facades\Route;

// W1 loads this file inside the web middleware group through bootstrap/app.php.
Route::get('/csrf-token', [SessionTokenController::class, 'token'])->name('session.csrf-token');
