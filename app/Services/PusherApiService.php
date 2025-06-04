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

        // Log para verificar que las credenciales se están cargando
        // NO loguear el App Secret en producción. Solo para depuración.
        Log::info('PusherApiService initialized with:');
        Log::info('  App ID: ' . $this->appId);
        Log::info('  App Key: ' . $this->appKey);
        Log::info('  Cluster: ' . $this->cluster);

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

        // --- CORRECCIÓN 2: json_encode($data) para el payload ---
        $payload = json_encode([
            'name' => 'test-event',
            'data' => json_encode($data), // ¡Importante! 'data' debe ser una cadena JSON
            'channels' => $channels,
        ]);

        if ($payload === false) {
            Log::error('PusherApiService: Fallo al codificar el payload JSON. ' . json_last_error_msg());
            return false;
        }

        // --- CAMBIO AQUÍ: Definimos dos variables de ruta ---
        // Path para la petición Guzzle. Se mantiene como "events" porque la baseUri ya incluye "/apps/{appId}/"
        $requestApiPath = "events";

        // Path COMPLETO requerido para la firma (incluye /apps/{appId}/)
        $signingPath = "/apps/{$this->appId}/events";
        // --- FIN CAMBIO ---

        $auth_timestamp = time();

        // Parámetros de la query para la autenticación
        $query = [
            'auth_key' => $this->appKey,
            'auth_timestamp' => $auth_timestamp,
            'auth_version' => '1.0', // Versión de la API de Pusher (corrección anterior)
            'body_md5' => md5($payload), // Hash MD5 del cuerpo de la petición
        ];

        // Ordenar los parámetros alfabéticamente por clave (requerido por Pusher)
        ksort($query);

        // --- CORRECCIÓN 1: Construir la cadena para firmar con RFC 3986 ---
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986); // Codificación RFC 3986

        // --- CAMBIO CLAVE AQUÍ: Usamos $signingPath en la cadena a firmar ---
        $string_to_sign = "POST\n{$signingPath}\n{$queryString}"; // Cadena a firmar

        // Calcular la firma
        $auth_signature = hash_hmac('sha256', $string_to_sign, $this->appSecret, false);

        // Añadir la firma a los parámetros de la query
        $query['auth_signature'] = $auth_signature;

        // Logs para depuración antes de enviar la petición (actualizados con las nuevas variables)
        Log::info('PusherApiService: Attempting to send event.');
        Log::info('  Request Path (Guzzle): ' . $requestApiPath); // Log para Guzzle
        Log::info('  Signing Path: ' . $signingPath); // Log para la firma
        Log::info('  Query Params: ' . json_encode($query));
        Log::info('  Request Body (Payload): ' . $payload);
        Log::info('  String to Sign: ' . $string_to_sign); // Verifica que esta sea la correcta
        Log::info('  Generated Signature: ' . $auth_signature); // Y esta tambien

        try {
            // Usamos $requestApiPath para la petición Guzzle
            $response = $this->client->post($requestApiPath, [
                'query' => $query,
                'body' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents(); // Capturar el cuerpo siempre

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("PusherApiService: Evento enviado con éxito. Status: {$statusCode}. Response: {$body}");
                return true;
            } else {
                Log::error("PusherApiService: Error del servidor Pusher. Status: {$statusCode}. Response: {$body}");
                return false;
            }
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("PusherApiService: Error de CONEXIÓN a Pusher. " . $e->getMessage() . " - Verifica PUSHER_APP_CLUSTER y conectividad.");
            return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) { // Errores 4xx (ej: 401 Unauthorized)
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            Log::error("PusherApiService: Error de cliente (4xx) al enviar evento. Status: " . $e->getCode() . ". Response: " . $responseBody);
            return false;
        } catch (\GuzzleHttp\Exception\ServerException $e) { // Errores 5xx
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body';
            Log::error("PusherApiService: Error de servidor (5xx) al enviar evento. Status: " . $e->getCode() . ". Response: " . $responseBody);
            return false;
        } catch (\Exception $e) {
            Log::error("PusherApiService: Error inesperado al enviar evento: " . $e->getMessage());
            return false;
        }
    }
}