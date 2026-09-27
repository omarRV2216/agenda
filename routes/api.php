<?php

use App\Http\Controllers\Empleado_Controller;
use App\Http\Controllers\Login_Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::post('/Register_user', [Empleado_Controller::class, 'Create']);
Route::post('/Login', [Login_Controller::class, 'Login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/me',      [Login_Controller::class, 'Me']);     
    Route::post('/logout', [Login_Controller::class, 'Logout']);  
});
