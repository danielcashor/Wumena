<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;


use App\Models\Usuario;
use Illuminate\Http\Request;

class UsuarioController extends Controller {

    public function index() {
        $usuarios = Usuario::all();

        return response()->json($usuarios);
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'email' => 'required|string|email|max:100|unique:usuarios',
            'clave' => 'required|string|min:6',
            'valoracion' => 'nullable|numeric|between:0,5',
            'rol' => 'required|string|max:10'
        ]);

        // Encriptar la clave
        $validated['clave'] = bcrypt($validated['clave']);
        $validated['valoracion'] = $validated['valoracion'] ?? 0.0;
        $validated['rol'] = 'usuario';

        $usuario = Usuario::create($validated);

        return response()->json($usuario, 201);
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'clave' => 'required'
    ]);

    $usuario = Usuario::where('email', $request->email)->first();

    if (!$usuario || !Hash::check($request->clave, $usuario->clave)) {
        return response()->json(['mensaje' => 'Credenciales incorrectas'], 401);
    }

    return response()->json($usuario); // En el futuro aquí podrías devolver un token JWT
}


    public function show(string $id) {
        $usuario = Usuario::find($id);
        if($usuario){
            return response()->json($usuario);
        }else {
            return response(['mensaje' => 'Error, no hemos podido encontrar al usuario'], 500);
        }
    }

    public function update(Request $request, $id){
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['mensaje' => 'Usuario no encontrado'], 404);
        }

        $usuario->nombre = $request->input('nombre', $usuario->nombre);
        $usuario->email = $request->input('email', $usuario->email);
        $usuario->save();

        return response()->json($usuario);
    }

    public function destroy(string $id) {

    }
}
