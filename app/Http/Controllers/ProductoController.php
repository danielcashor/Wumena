<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductoController extends Controller {

    public function index() {
        $productos = Producto::with('usuarios')->get(); // con relaciones

        return response()->json($productos);
    }

    public function store(Request $request)
    {
        $validador = $request->validate([
            'id_usuario' => 'required|exists:usuarios,id',
            'nombre' => 'required|string|max:150',
            'precio' => 'required|numeric',
            'descripcion' => 'required|string',
            'categoria' => 'required|string|max:50',
            'imagen'     => 'nullable|image|max:2048'
        ]);

        $validador['estado'] = 'Disponible';

        if ($request->hasFile('imagen')) {
            // Sube la imagen a Cloudinary
            $uploadedFile = Cloudinary::upload($request->file('imagen')->getRealPath());

            // Obtén la URL segura de la imagen en Cloudinary
            // Esta URL es la que guardarás en tu base de datos
            $imageUrl = $uploadedFile->getSecurePath();

            // Asigna la URL de Cloudinary al validador para guardarla en la base de datos
            $validador['imagen'] = $imageUrl;
        }

        $producto = Producto::create($validador);

        return response()->json($producto, 201); // 201 Created
    }
    public function search(Request $request){
        $q = $request->query('q');

        if (!$q) {
            return response()->json(['mensaje' => 'Falta el parámetro de búsqueda'], 400);
        }

        $results = Producto::whereRaw('LOWER(nombre) LIKE ?', ['%' . strtolower($q) . '%'])
                           ->with('usuarios')
                           ->get();

        return response()->json($results);
    }









    public function reservar(string $id) {
        $producto = Producto::find($id);

        if ($producto) {
            $producto->update("estado", "Reservado");

            return response()->json($producto);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar el producto'], 404);
        }
    }

    public function vender(string $id) {
        $producto = Producto::find($id);

        if ($producto) {
            $producto->update("estado", "Vendido");

            return response()->json($producto);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar el producto'], 404);
        }
    }

    public function show(string $id) {
        $producto = Producto::with('usuarios')->find($id);

        if ($producto) {
            return response()->json($producto);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar el producto'], 404);
        }
    }


    public function verProductosUsuario(string $idUsuario) {
        $listaProductos = Producto::where("id_usuario", $idUsuario)
                                  ->with('usuarios')
                                  ->get();

        if ($listaProductos) {
            return response()->json($listaProductos);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar los productos a la venta del usuario'], 404);
        }
    }

    public function verProductosCategoria(string $categoria){
        $categoria = strtolower(trim($categoria));

        $productos = Producto::with('usuarios')
            ->whereRaw('LOWER(TRIM(categoria)) = ?', [$categoria])
            ->get();

        if ($productos->isNotEmpty()) {
            return response()->json($productos);
        } else {
            return response(['mensaje' => 'No se encontraron productos en esa categoría'], 404);
        }
    }



    public function update(Request $request, $id) {
        $producto = Producto::find($id);

        if($producto){
            $datosEditados = $request->validate([
                'nombre' => ['required'],
                'precio' => ['required'],
                'descripcion' => ['required'],
                'categoria' => ['required'],
            ]);
            $producto->nombre = $datosEditados['nombre'];
            $producto->precio = $datosEditados['precio'];
            $producto->categoria = $datosEditados['categoria'];
            $producto->descripcion = $datosEditados['descripcion'];

            if($producto->save()){
                return response()->json([
                    'mensaje' => 'Producto actualizado con exito',
                    'Producto: ' => $producto
                ], 200);
            } else {
                return response(['mensaje' => 'Error al actualizar el producto'], 500);
            }
        } else {
            return response(['mensaje' => 'Error, no se ha podido encontrar el producto'], 404);
        }
    }

    public function destroy(string $id) {
        $producto = Producto::find($id);

        if ($producto) {
            $producto->delete();

            return response(['mensaje' => 'Producto borrado con exito'], 200);
        } else {
            return response(['mensaje' => 'Error, no hemos podido encontrar el producto'], 404);
        }
    }
}
