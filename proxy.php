<?php
/**
 * proxy.php
 * Proxy de consulta para https://app.upsjb.edu.pe/pv/
 * Endpoint real: POST https://app.upsjb.edu.pe/api-fotocheck/api/Fotocheck/AgregarRegistroAcceso
 * Body: {"codigo":"<numero>"}
 *
 * Uso:  proxy.php?numero=76481753
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$numero = $_GET['numero'] ?? '';
if (!preg_match('/^\d+$/', $numero)) {
    http_response_code(400);
    echo json_encode(['error' => 'Número inválido']);
    exit;
}

$url = 'https://app.upsjb.edu.pe/api-fotocheck/api/Fotocheck/AgregarRegistroAcceso';

$payload = json_encode(['codigo' => $numero]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_ENCODING       => '',
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: es-ES,es;q=0.9',
        'Content-Type: application/json',
        'Origin: https://app.upsjb.edu.pe',
        'Referer: https://app.upsjb.edu.pe/pv/',
        'sec-ch-ua: "Chromium";v="154", "Google Chrome";v="154", "Not A(Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
    ],
]);

$respuesta = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$curlErrno   = curl_errno($ch);
$curlError   = curl_error($ch);
curl_close($ch);

if ($respuesta === false || $httpCode >= 400) {
    http_response_code(502);
    echo json_encode([
        'error'         => 'Fallo la petición al API',
        'url'           => $url,
        'httpCode'      => $httpCode,
        'contentType'   => $contentType,
        'curlErrno'     => $curlErrno,
        'curlError'     => $curlError,
        'preview'       => substr((string)$respuesta, 0, 500),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($respuesta, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(502);
    echo json_encode([
        'error'   => 'La respuesta no es JSON válido',
        'preview' => substr((string)$respuesta, 0, 500),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Extraer solo lo que nos interesa
echo json_encode([
    'imagen'             => $data['imagen']             ?? null,
    'situacion'          => $data['situacion']          ?? 'SIN SITUACIÓN',
    'nombre'             => trim(($data['apellidoPaterno'] ?? '') . ' ' . ($data['apellidoMaterno'] ?? '') . ' ' . ($data['nombre'] ?? '')),
    'numeroDocumento'    => $data['numeroDocumento']    ?? $numero,
    'carrera'            => $data['carrera']            ?? '',
    'cicloAcademico'     => $data['cicloAcademico']     ?? '',
    'semestre'           => $data['semestre']           ?? '',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);