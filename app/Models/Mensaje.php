<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Usuario;


class Mensaje extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'emisor_id',
        'mensaje',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function emisor()
    {
        return $this->belongsTo(Usuario::class, 'emisor_id');
    }
}
