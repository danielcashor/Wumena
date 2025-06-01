<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Producto;
use App\Models\Direccion;

class Usuario extends Model {

    use HasFactory;

    protected $table = "usuarios";

    protected $fillable = [
        'nombre',
        'email',
        'clave',
        'valoracion',
        'rol'
    ];

    public $timestamps = false;

    public function productos() {
        return $this->hasMany(Producto::class, 'id_usuario');
    }
    public function direcciones() {
        return $this->hasMany(Direccion::class, 'id_usuario');
    }
    public function mensajes(){
        return $this->hasMany(Mensaje::class, 'emisor_id');
    }

    public function chatsComoUser1(){
        return $this->hasMany(Chat::class, 'usuario_1_id');
    }

    public function chatsComoUser2(){
        return $this->hasMany(Chat::class, 'usuario_2_id');
    }

    public function todosLosChats(){
        return Chat::where('usuario_1_id', $this->id)->orWhere('usuario_2_id', $this->id);
    }

}
