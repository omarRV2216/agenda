<?php

use App\Http\Controllers\Empleado_Controller;
use App\Http\Controllers\EmployeeService_Controller;
use App\Http\Controllers\Login_Controller;
use App\Http\Controllers\Service_Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment_Controller;


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
Route::post('/appointments/create',       [Appointment_Controller::class, 'Create']);
Route::post('/employee-services/assign',   [EmployeeService_Controller::class, 'Assign']);

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

    Route::get('/appointments',               [Appointment_Controller::class, 'Get']);
    Route::get('/appointments/availability',  [Appointment_Controller::class, 'Availability']);
    Route::get('/appointments/upcoming',      [Appointment_Controller::class, 'Upcoming']);
    Route::post('/appointments/{id}',         [Appointment_Controller::class, 'Update']);
    Route::patch('/appointments/{id}/status', [Appointment_Controller::class, 'ChangeStatus']);
    Route::delete('/appointments/{id}',       [Appointment_Controller::class, 'Delete']);


    Route::get('/services/{id}/employees',   [EmployeeService_Controller::class, 'EmpleadosPorServicio']);

    // Servicios que hace un empleado
    Route::get('/employees/{id}/services',   [EmployeeService_Controller::class, 'ServiciosPorEmpleado']);

    // Asignaciones
    
    Route::post('/employee-services/remove',   [EmployeeService_Controller::class, 'Remove']);
    Route::post('/employee-services/replace',  [EmployeeService_Controller::class, 'Replace']);
    Route::post('/employee-services/available',[EmployeeService_Controller::class, 'Available']);
});

Route::get('/imagen/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');
