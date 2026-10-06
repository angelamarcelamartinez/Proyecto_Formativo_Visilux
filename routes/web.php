<?php

use App\Http\Controllers\Admin\ControlController;
use App\Http\Controllers\Admin\CrudController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LicenciaController as AdminLicenciaController;
use App\Http\Controllers\Admin\PaginaController;
use App\Http\Controllers\Superadmin\DashboardController as SuperDashboardController;
use App\Http\Controllers\Superadmin\EmpresaController as SuperEmpresaController;
use App\Http\Controllers\Superadmin\LicenciaController as SuperLicenciaController;
use App\Http\Controllers\Superadmin\PlanController as SuperPlanController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SuperadminLoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\ContratarController;
use Illuminate\Support\Facades\Route;

// Página principal: muestra la óptica principal (config/visioptica.php)
Route::get('/', [SiteController::class, 'inicio'])->name('home');

// Página pública de cada óptica: /optica/visilux, /optica/vision-plus...
Route::get('/optica/{slug}', [SiteController::class, 'optica'])->name('optica.show');

// --- Páginas públicas del sitio ---
Route::get('/terapia-visual', [SiteController::class, 'terapiaVisual'])->name('terapia-visual');
Route::get('/conocenos', [SiteController::class, 'conocenos'])->name('conocenos');
Route::post('/conocenos', [SiteController::class, 'enviarContacto'])->name('conocenos.contacto');
Route::get('/planes', [SiteController::class, 'planes'])->name('planes');

// Renovar el plan de una óptica que ya existe (sirve aunque no pueda entrar al panel)
Route::get('/renovar', [\App\Http\Controllers\RenovarController::class, 'create'])->name('renovar.create');
Route::post('/renovar', [\App\Http\Controllers\RenovarController::class, 'store'])->name('renovar.store');

// Contratar un plan: registro de la óptica + pago simulado
Route::get('/planes/{plan}/contratar', [ContratarController::class, 'create'])->name('contratar.create');
Route::post('/planes/{plan}/contratar', [ContratarController::class, 'store'])->name('contratar.store');
Route::get('/planes/solicitud-enviada', [ContratarController::class, 'enviado'])->name('contratar.enviado');
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

// --- Login del superadmin: URL secreta, sin ningún botón ni enlace en el sitio ---
// La dirección sale de SUPERADMIN_LOGIN_PATH en el .env (config/visioptica.php).
Route::get('/' . config('visioptica.superadmin_login_path'), [SuperadminLoginController::class, 'showLoginForm'])
    ->name('superadmin.login');
Route::post('/' . config('visioptica.superadmin_login_path'), [SuperadminLoginController::class, 'login'])
    ->name('superadmin.login.attempt');

// --- Panel del superadministrador (dueño de VisiOptica) ---
// Sin sesión de superadmin estas rutas responden 404 (no redirigen al login).
Route::prefix('superadmin')->middleware(['superadmin'])->name('superadmin.')->group(function () {
    Route::get('/', [SuperDashboardController::class, 'index'])->name('dashboard');
    Route::post('/avisos', [SuperDashboardController::class, 'enviarAvisos'])->name('avisos');

    // Ópticas
    Route::get('/opticas', [SuperEmpresaController::class, 'index'])->name('empresas.index');
    Route::get('/opticas/nueva', [SuperEmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/opticas', [SuperEmpresaController::class, 'store'])->name('empresas.store');
    Route::get('/opticas/{nit}', [SuperEmpresaController::class, 'show'])->name('empresas.show');
    Route::get('/opticas/{nit}/editar', [SuperEmpresaController::class, 'edit'])->name('empresas.edit');
    Route::put('/opticas/{nit}', [SuperEmpresaController::class, 'update'])->name('empresas.update');
    Route::post('/opticas/{nit}/estado', [SuperEmpresaController::class, 'cambiarEstado'])->name('empresas.estado');
    Route::post('/opticas/{nit}/licencia', [SuperEmpresaController::class, 'asignarLicencia'])->name('empresas.licencia');

    // Solicitudes y licencias
    Route::get('/licencias', [SuperLicenciaController::class, 'index'])->name('licencias.index');
    Route::post('/licencias/{id}/aprobar', [SuperLicenciaController::class, 'aprobar'])->name('licencias.aprobar');
    Route::post('/licencias/{id}/rechazar', [SuperLicenciaController::class, 'rechazar'])->name('licencias.rechazar');

    // Planes
    Route::get('/planes', [SuperPlanController::class, 'index'])->name('planes.index');
    Route::put('/planes/{id}', [SuperPlanController::class, 'update'])->name('planes.update');
});

// --- Panel de administración de cada óptica ---
// 'licencia' saca al admin si su óptica está suspendida o sin licencia vigente.
Route::prefix('admin')->middleware(['auth', 'admin', 'licencia'])->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Mi licencia: ver el plan y renovar antes de que venza
    Route::get('/mi-licencia', [AdminLicenciaController::class, 'index'])->name('licencia.index');
    Route::post('/mi-licencia', [AdminLicenciaController::class, 'solicitar'])->name('licencia.solicitar');
    Route::delete('/mi-licencia/{id}', [AdminLicenciaController::class, 'cancelar'])->name('licencia.cancelar');

    // Página pública de la óptica (va antes del CRUD genérico para que /{table} no la atrape)
    Route::get('/mi-pagina', [PaginaController::class, 'edit'])->name('pagina.edit');
    Route::put('/mi-pagina', [PaginaController::class, 'update'])->name('pagina.update');

    // Recordatorios de cita de control
    Route::get('/citas-control', [ControlController::class, 'index'])->name('control.index');
    Route::post('/citas-control/enviar-todos', [ControlController::class, 'enviarTodos'])->name('control.enviarTodos');
    Route::post('/citas-control/{documento}/enviar', [ControlController::class, 'enviar'])->name('control.enviar');

    // CRUD genérico
    // Autocompletar datos del paciente por documento (citas, fórmulas médicas)
    Route::get('/buscar-usuario', [CrudController::class, 'buscarUsuario'])->name('buscar-usuario');

    Route::get('/{table}/exportar', [CrudController::class, 'export'])->name('crud.export');
    Route::get('/{table}', [CrudController::class, 'index'])->name('crud.index');
    Route::get('/{table}/crear', [CrudController::class, 'create'])->name('crud.create');
    Route::post('/{table}', [CrudController::class, 'store'])->name('crud.store');
    Route::get('/{table}/{id}/editar', [CrudController::class, 'edit'])->name('crud.edit');
    Route::put('/{table}/{id}', [CrudController::class, 'update'])->name('crud.update');
    Route::delete('/{table}/{id}', [CrudController::class, 'destroy'])->name('crud.destroy');
});