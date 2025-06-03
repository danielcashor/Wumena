<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log; // Para registrar errores

class PusherApiService
{
    protected $client;
    protected $appId;
    protected $appKey;
    protected $appSecret;
    protected $cluster;

    public function __construct()
    {
        // Asegúrate de que estas variables de entorno estén configuradas en tu .env (y en Render)
        $this->appId = env('PUSHER_APP_ID');
        $this->appKey = env('PUSHER_APP_KEY');
        $this->appSecret = env('PUSHER_APP_SECRET');
        $this->cluster = env('PUSHER_APP_CLUSTER');

        $baseUri = "https://api-{$this->cluster}.pusher.com/apps/{$this->appId}/";

        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 5, // Tiempo de espera para la conexión en segundos
        ]);
    }

    /**
     * Envía un evento a Pusher.com.
     *
     * @param string|array $channels  Nombre del canal o array de nombres de canales.
     * @param string       $event     Nombre del evento.
     * @param array        $data      Datos a enviar con el evento.
     * @return bool
     */
    public function trigger($channels, $event, array $data = [])
    {
        // Pusher espera un array de canales
        $channels = (array) $channels;

        $payload = json_encode([
            'name' => $event,
            'data' => $data,
            'channels' => $channels,
        ]);

        if ($payload === false) {
            Log::error('PusherApiService: Fallo al codificar el payload JSON. ' . json_last_error_msg());
            return false;
        }

        $path = "events";
        $auth_timestamp = time();

        // Parámetros de la query para la autenticación
        $query = [
            'auth_key' => $this->appKey,
            'auth_timestamp' => $auth_timestamp,
            'auth_version' => '1.2', // Versión de la API de Pusher
            'body_md5' => md5($payload), // Hash MD5 del cuerpo de la petición
        ];

        // Ordenar los parámetros alfabéticamente por clave (requerido por Pusher)
        ksort($query);

        // Construir la cadena para firmar
        $string_to_sign = "POST\n{$path}\n" . http_build_query($query) . "\n";

        // ¡IMPORTANTE! Quitar el hash MD5 de la string para firmar si no es parte de la query.
        // La documentación de Pusher 1.2 indica que body_md5 va en la query y el payload en el cuerpo.
        // La firma incluye el body_md5 en la query string.
        // La línea correcta para la firma es esta, sin el payload al final de la string_to_sign:
        $string_to_sign = "POST\n{$path}\n" . http_build_query($query); // String sin el payload para la firma

        // Calcular la firma
        $auth_signature = hash_hmac('sha256', $string_to_sign, $this->appSecret, false);

        // Añadir la firma a los parámetros de la query
        $query['auth_signature'] = $auth_signature;

        try {
            $response = $this->client->post($path, [
                'query' => $query,
                'body' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("Evento Pusher enviado con éxito al canal: " . implode(', ', $channels) . ", Evento: " . $event);
                return true;
            } else {
                $body = $response->getBody()->getContents();
                Log::error("Error Pusher: Código de estado {$statusCode}. Respuesta: {$body}");
                return false;
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("Error de conexión a Pusher: " . $e->getMessage() . " - Verifica PUSHER_APP_CLUSTER y conectividad.");
            return false;
        } catch (\Exception $e) {
            Log::error("Error al enviar evento Pusher: " . $e->getMessage());
            return false;
        }
    }
}