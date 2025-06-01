<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Usuario;

class Producto extends Model {

    use HasFactory;

    protected $table = "productos";

    protected $fillable = [
        'id_usuario',
        'nombre',
        'categoria',
        'precio',
        'descripcion',
        'estado',
        'imagen'
    ];

    public $timestamps = false;

    public function usuarios() {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
