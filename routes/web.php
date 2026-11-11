<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CajeroController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClienteDashboardController;
use App\Http\Controllers\ContenidoController;
use App\Http\Controllers\CocinaController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RecuperarPasswordController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/catalogo', [PublicController::class, 'catalogo'])->name('catalogo');
Route::get('/promociones', [PublicController::class, 'promociones'])->name('promociones');
Route::get('/delivery', [DeliveryController::class, 'index'])->name('delivery.index');
Route::get('/login-cliente', [AuthController::class, 'showClienteLogin'])->name('login.cliente');
Route::post('/login-cliente', [AuthController::class, 'loginCliente'])
    ->middleware('throttle:6,1')
    ->name('login.cliente.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::get('/login-admin', [AuthController::class, 'showAdminLogin'])->name('login.admin');
Route::post('/login-admin', [AuthController::class, 'loginAdmin'])
    ->middleware('throttle:6,1')
    ->name('login.admin.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/recuperar-password', [RecuperarPasswordController::class, 'formulario'])->name('password.formulario');
Route::post('/recuperar-password', [RecuperarPasswordController::class, 'generarCodigo'])->name('password.codigo');
Route::post('/recuperar-password/cambiar', [RecuperarPasswordController::class, 'cambiar'])->name('password.cambiar');
Route::get('/recuperar-password/limpiar', [RecuperarPasswordController::class, 'limpiar'])->name('password.limpiar');
Route::middleware(['auth', 'role:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard', [ClienteDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito');
    Route::post('/carrito/agregar/{producto}', [CarritoController::class, 'add'])->name('carrito.add');
    Route::put('/carrito/{producto}', [CarritoController::class, 'update'])->name('carrito.update');
    Route::delete('/carrito/{producto}', [CarritoController::class, 'remove'])->name('carrito.remove');
    Route::post('/checkout', [CarritoController::class, 'checkout'])->name('checkout');
    Route::get('/pedidos', [ClienteDashboardController::class, 'pedidos'])->name('pedidos');
    Route::get('/pedidos/{pedido}', [ClienteDashboardController::class, 'showPedido'])->name('pedidos.show');
    Route::get('/perfil', [ClienteDashboardController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [ClienteDashboardController::class, 'updatePerfil'])->name('perfil.update');
});
Route::middleware(['auth', 'role:cajero'])->prefix('cajero')->name('cajero.')->group(function () {
    Route::get('/dashboard', [CajeroController::class, 'dashboard'])->name('dashboard');

    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');

    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::get('/caja/create', [CajaController::class, 'create'])->name('caja.create');
    Route::post('/caja', [CajaController::class, 'store'])->name('caja.store');
    Route::get('/caja/{caja}', [CajaController::class, 'show'])->name('caja.show');
    Route::post('/caja/cobros/{pedido}', [CajaController::class, 'cobrar'])->name('caja.cobrar');
    Route::get('/caja/{caja}/movimiento', [CajaController::class, 'crearMovimiento'])->name('caja.movimiento.create');
    Route::post('/caja/{caja}/movimiento', [CajaController::class, 'storeMovimiento'])->name('caja.movimiento.store');
    Route::get('/caja/{caja}/cierre', [CajaController::class, 'cerrar'])->name('caja.cierre');
    Route::post('/caja/{caja}/cierre', [CajaController::class, 'storeCierre'])->name('caja.cierre.store');
});
Route::middleware(['auth', 'role:cocina'])->prefix('cocina')->name('cocina.')->group(function () {
    Route::get('/dashboard', [CocinaController::class, 'dashboard'])->name('dashboard');

    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'updateEstado'])->name('pedidos.estado');

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::put('/stock/{producto}', [StockController::class, 'update'])->name('stock.update');
});
Route::middleware(['auth', 'role:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/contenido', [ContenidoController::class, 'index'])->name('contenidos.index');
    Route::put('/contenido/horarios', [ContenidoController::class, 'updateHorarios'])->name('contenidos.horarios');
    Route::put('/contenido/contacto', [ContenidoController::class, 'updateContacto'])->name('contenidos.contacto');
    Route::resource('productos', ProductoController::class)->except(['show']);
    Route::resource('promociones', PromocionController::class)->except(['show']);
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/create', [ClienteController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{cliente}/edit', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
    Route::patch('/pedidos/{pedido}/estado', [PedidoController::class, 'updateEstado'])->name('pedidos.estado');
    Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::put('/stock/{producto}', [StockController::class, 'update'])->name('stock.update');
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
    Route::get('/caja/create', [CajaController::class, 'create'])->name('caja.create');
    Route::post('/caja', [CajaController::class, 'store'])->name('caja.store');
    Route::get('/caja/{caja}', [CajaController::class, 'show'])->name('caja.show');
    Route::post('/caja/cobros/{pedido}', [CajaController::class, 'cobrar'])->name('caja.cobrar');
    Route::get('/caja/{caja}/movimiento', [CajaController::class, 'crearMovimiento'])->name('caja.movimiento.create');
    Route::post('/caja/{caja}/movimiento', [CajaController::class, 'storeMovimiento'])->name('caja.movimiento.store');
    Route::get('/caja/{caja}/cierre', [CajaController::class, 'cerrar'])->name('caja.cierre');
    Route::post('/caja/{caja}/cierre', [CajaController::class, 'storeCierre'])->name('caja.cierre.store');
});
Route::redirect('/admin', '/admin/dashboard');