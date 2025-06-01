<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use Illuminate\Http\Request;

class DireccionController extends Controller {

    public function index() {
        $direcciones = Direccion::all();

        return response()->json($direcciones);
    }

    public function store(Request $request)
{
    $validador = $request->validate([
        'id_usuario' => 'required|exists:usuarios,id',
        'nombreCalle' => 'required|string|max:150',
        'ciudad' => 'required|string|max:50',
        'provincia' => 'required|string|max:50',
        'codPostal' => 'required|string|digits:5',
    ]);

    $direccion = Direccion::create($validador);

    return response()->json($direccion, 200);
}


    public function show(string $id) {
        $direccion = Direccion::find($id);
        if ($direccion) {
            return response()->json($direccion);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar la direccion'], 404);
        }
    }

    public function verDireccionesUsuario(string $idUsuario)
{
    $listaDirecciones = Direccion::where("id_usuario", $idUsuario)->get();

    if ($listaDirecciones->isNotEmpty()) {
        return response()->json($listaDirecciones, 200);
    } else {
        return response()->json(['mensaje' => 'No se encontraron direcciones'], 404);
    }
}


    public function update(Request $request, $id) {
        $direccion = Direccion::find($id);

        if($direccion){
            $datosEditados = $request->validate([
                'nombreCalle' => 'required|string|max:150',
                'ciudad' => 'required|string|max:50',
                'provincia' => 'required|string|max:50',
                'codPostal' => 'required|int|max:11',
            ]);
            $direccion->nombreCalle = $datosEditados['nombreCalle'];
            $direccion->ciudad = $datosEditados['ciudad'];
            $direccion->provincia = $datosEditados['provincia'];
            $direccion->codPostal = $datosEditados['codPostal'];

            if($direccion->save()){
                return response()->json([
                    'mensaje' => 'Producto actualizado con exito',
                    'Direccion: ' => $direccion
                ], 200);
            } else {
                return response(['mensaje' => 'Error al actualizar el producto'], 500);
            }
        } else {
            return response(['mensaje' => 'Error, no se ha podido encontrar el producto'], 404);
        }
    }
    public function destroy(string $id) {
        $producto = Productos::find($id);

        if ($producto) {
            $producto->delete();

            return response(['mensaje' => 'Producto borrado con exito'], 200);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar el producto'], 404);
        }
    }
}
