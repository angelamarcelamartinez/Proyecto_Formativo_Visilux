<?php

use App\Http\Controllers\Admin\ControlController;
use App\Http\Controllers\Admin\CrudController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CarritoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('home');

// --- Páginas públicas del sitio ---
Route::get('/terapia-visual', [SiteController::class, 'terapiaVisual'])->name('terapia-visual');
Route::get('/conocenos', [SiteController::class, 'conocenos'])->name('conocenos');
Route::post('/conocenos', [SiteController::class, 'enviarContacto'])->name('conocenos.contacto');
Route::get('/planes', [SiteController::class, 'planes'])->name('planes');
Route::get('/agendar-cita', [SiteController::class, 'agendarCita'])->name('citas.create');
Route::get('/agendar-cita/horarios', [SiteController::class, 'horariosDisponibles'])->name('citas.horarios');
Route::post('/agendar-cita', [SiteController::class, 'guardarCita'])->name('citas.store');

// --- Catálogo público de productos ---
Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');

// --- Carrito de compras (el controlador exige sesión iniciada por su cuenta,
//     con un mensaje propio -- por eso no llevan el middleware 'auth' aquí) ---
Route::post('/carrito/agregar/{id_producto}', [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
Route::put('/carrito/{id}', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
Route::delete('/carrito/{id}', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
Route::post('/carrito/finalizar', [CarritoController::class, 'finalizar'])->name('carrito.finalizar');

// --- Rutas de Autenticación Personalizadas ---
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    // Registro
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Recuperación de Contraseña
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/update', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Logout (requiere estar autenticado)
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// --- Panel de administración ---
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Recordatorios de cita de control
    Route::get('/citas-control', [ControlController::class, 'index'])->name('control.index');
    Route::post('/citas-control/enviar-todos', [ControlController::class, 'enviarTodos'])->name('control.enviarTodos');
    Route::post('/citas-control/{documento}/enviar', [ControlController::class, 'enviar'])->name('control.enviar');

    // Autocompletar datos del paciente por documento (citas, fórmulas médicas)
    Route::get('/buscar-usuario', [CrudController::class, 'buscarUsuario'])->name('buscar-usuario');

    // CRUD genérico
    Route::get('/{table}', [CrudController::class, 'index'])->name('crud.index');
    Route::get('/{table}/crear', [CrudController::class, 'create'])->name('crud.create');
    Route::get('/{table}/exportar', [CrudController::class, 'export'])->name('crud.export');
    Route::post('/{table}', [CrudController::class, 'store'])->name('crud.store');
    Route::get('/{table}/{id}/editar', [CrudController::class, 'edit'])->name('crud.edit');
    Route::put('/{table}/{id}', [CrudController::class, 'update'])->name('crud.update');
    Route::delete('/{table}/{id}', [CrudController::class, 'destroy'])->name('crud.destroy');
});