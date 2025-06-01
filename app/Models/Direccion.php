<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Usuario;

class Direccion extends Model {
    
    use HasFactory;

    protected $table = "direccion";

    protected $fillable = [
        'id_usuario', 
        'nombreCalle', 
        'ciudad',
        'provincia',
        'codPostal'
    ];

    public $timestamps = false;

    public function usuarios() {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
