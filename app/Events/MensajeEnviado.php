<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensajeEnviado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mensaje;
    public $de;
    public $para;
    public $producto_id;

    public function __construct($mensaje, $de, $para, $producto_id)
    {
        $this->mensaje = $mensaje;
        $this->de = $de;
        $this->para = $para;
        $this->producto_id = $producto_id;
    }

    public function broadcastOn()
    {
        // Emitir al canal público basado en el producto
        return new Channel('chat.' . $this->producto_id);
    }

    public function broadcastAs()
    {
        return 'mensaje.enviado';
    }
}
