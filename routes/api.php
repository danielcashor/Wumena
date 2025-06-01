<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ChatController;


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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Productos

Route::controller(ProductoController::class)->group(function() {
    Route::get('/productos/buscar',  'search');
    Route::get('/productos', 'index');
    Route::post('/productos', 'store');
    Route::put('/productos/reservar/{id}', 'reservar');
    Route::put('/productos/vender/{id}', 'vender');
    Route::put('/productos/{id}',  'update');
Route::delete('/productos/{id}',  'destroy');
    Route::get('/productos/{id}', 'show');
    Route::get('/productos/filtrarUser/{idUsuario}', 'verProductosUsuario');
    Route::get('/productos/filtrar/{categoria}', 'verProductosCategoria');


    Route::delete('/productos/borrar/{id}', 'destroy');
});

Route::post('/chat/enviar', [ChatController::class, 'enviarMensaje']);
Route::get('/chat/{chatId}/mensajes', [ChatController::class, 'obtenerMensajes']);
Route::get('/chat/{usuarioId}', [ChatController::class, 'getChatsUsuario']);

// Direcciones

Route::controller(DireccionController::class)->group(function() {
    Route::get('/direcciones', 'index');
    Route::post('/direcciones/crear', 'store');
    Route::put('/direcciones/editar/{id}', 'update');
    Route::get('/direcciones/{id}', 'show');
    Route::get('/direcciones/filtrar/{idUsuario}', 'verDireccionesUsuario');
    Route::delete('/direcciones/borrar/{id}', 'destroy');
});

// Usuarios

Route::controller(UsuarioController::class)->group(function() {
    Route::get('/usuarios', 'index');
    Route::post('/usuarios', 'store');
    Route::post('/login', [UsuarioController::class, 'login']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::get('/usuarios/{id}', 'show');
    Route::delete('/usuarios/borrar/{id}', 'destroy');
});
