<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Usuario;


class Chat extends Model
{
    protected $fillable = ['producto_id', 'usuario_1_id', 'usuario_2_id'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario1(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_1_id');
    }

    public function usuario2(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_2_id');
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class);
    }
}