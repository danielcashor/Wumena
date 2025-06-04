<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Mensaje;
use App\Services\PusherApiService; // <--- ¡Asegúrate de que esta línea esté aquí!

class ChatController extends Controller
{
    public function enviarMensaje(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string',
            'de' => 'required|integer',
            'para' => 'required|integer',
            'producto_id' => 'required|integer', // Asegúrate de que 'producto_id' esté en la validación
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
        dd([
            'PUSHER_APP_ID' => env('PUSHER_APP_ID'),
            'PUSHER_APP_KEY' => env('PUSHER_APP_KEY'),
            'PUSHER_APP_SECRET' => env('PUSHER_APP_SECRET'),
            'PUSHER_APP_CLUSTER' => env('PUSHER_APP_CLUSTER'),
            'Chat ID' => $chat->id,
            'Canal' => 'chat.' . $chat->id,
            'Evento' => 'mensaje-enviado',
            'Mensaje Data' => $mensaje->toArray(),
        ]);

        // 4. Emitir el evento a través de Pusher.com usando tu nuevo servicio
        $pusherService = new PusherApiService();

        // --- CAMBIO AQUÍ: Canal PÚBLICO ---
        // El nombre del canal es simplemente 'chat.<chat_id>'
        $channelName = 'chat.' . $chat->id;

        $pusherService->trigger(
            $channelName, // El canal al que enviar (ej: 'chat.123')
            'mensaje-enviado', // El nombre del evento (en kebab-case es buena práctica)
            [
                'mensaje' => $mensaje->toArray(),
                'de_usuario_id' => $request->de,
                'para_usuario_id' => $request->para,
                'producto_id' => $request->producto_id,
            ]
        );

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