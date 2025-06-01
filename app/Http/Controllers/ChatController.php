<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Mensaje;
use App\Events\MensajeEnviado;

class ChatController extends Controller
{
    public function enviarMensaje(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string',
            'de' => 'required|integer',
            'para' => 'required|integer',
        ]);

        // 1. Buscar si ya existe un chat entre ambos usuarios
        $chat = Chat::where('producto_id', $request->producto_id)
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('usuario_1_id', $request->de)
                        ->where('usuario_2_id', $request->para);
                })->orWhere(function ($q) use ($request) {
                    $q->where('usuario_1_id', $request->para)
                        ->where('usuario_2_id', $request->de);
                });
            })
            ->first();


        if (!$chat) {
            $chat = Chat::create([
                'producto_id' => $request->producto_id,
                'usuario_1_id' => $request->de,
                'usuario_2_id' => $request->para,
            ]);
        }


        // 3. Guardar el mensaje en la base de datos
        $mensaje = Mensaje::create([
            'chat_id' => $chat->id,
            'emisor_id' => $request->de,
            'mensaje' => $request->mensaje,
        ]);

        // 4. Emitir el evento a través de websockets
        broadcast(new MensajeEnviado(
            $mensaje->mensaje,
            $mensaje->emisor_id,
            $request->para,
            $request->producto_id
        ))->toOthers();

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'mensaje' => $mensaje,
        ]);
    }
    public function getChatsUsuario($usuarioId){
        $chats = Chat::where('usuario_1_id', $usuarioId)
                    ->orWhere('usuario_2_id', $usuarioId)
                    ->with(['usuario1', 'usuario2', 'producto'])
                    ->get();

        return response()->json($chats);
    }


    public function obtenerMensajes($chatId)
    {
        $mensajes = Mensaje::where('chat_id', $chatId)
            ->with('emisor:id,nombre,email')
            ->orderBy('created_at')
            ->get();

        return response()->json($mensajes);
    }
}
