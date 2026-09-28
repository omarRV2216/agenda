<?php

use App\Http\Controllers\Empleado_Controller;
use App\Http\Controllers\Login_Controller;
use App\Http\Controllers\Service_Controller;
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

Route::post('/Login', [Login_Controller::class, 'Login']);
Route::get('/servicios', [Service_Controller::class, 'Get']);
Route::post('/Register_user', [Empleado_Controller::class, 'Create']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/me',      [Login_Controller::class, 'Me']);     
    Route::post('/logout', [Login_Controller::class, 'Logout']);

    

    Route::get('/empleados', [Empleado_Controller::class, 'Get']);
    Route::post('/empleados/create', [Empleado_Controller::class, 'Create']);
    Route::post('/empleados/{id}',           [Empleado_Controller::class, 'Update']);
    Route::delete('/empleados/{id}',        [Empleado_Controller::class, 'Delete']);
    Route::post('/Group-By-Campo',     [Empleado_Controller::class, 'GroupByCampo']);


    Route::post('/servicios/create',                    [Service_Controller::class, 'Create']);
    Route::post('/servicios/{id}',                [Service_Controller::class, 'Update']);
    Route::patch('/servicios/{id}/desactivar',   [Service_Controller::class, 'Desactivar']);
    Route::delete('/servicios/{id}',             [Service_Controller::class, 'Delete']);
});

Route::get('/imagen/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');
