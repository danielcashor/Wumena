<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $data = ["mensaje1" => "Bienvenido a la API de Laravel 10", "mensaje2" => "Espero que esto aparezca"];
    return response()->json($data, 200);
});

Route::fallback(function () {
    return response()->json(['error' => 'No encontrado'], 404);
});