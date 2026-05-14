<?php
/**
 * comando.php — Riceve comandi manuali dalla dashboard e li pubblica via MQTT
 * POST JSON: { "dispositivo": "luce"|"mangime", "stato": "ON"|"OFF"|"APRI" }
 */

session_start();
header('Content-Type: application/json');

// Solo utenti autenticati - TOLTO FINCHE LOGIN NON E FUNZIOANNTE
/*if (empty($_SESSION['nome'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'errore' => 'Non autenticato']);
    exit;
}
*/

// Leggi il body JSON
$body = json_decode(file_get_contents('php://input'), true);
$dispositivo = $body['dispositivo'] ?? '';
$stato       = $body['stato']       ?? '';

$dispositivi_validi = ['luce', 'mangime'];
$stati_validi = ['ON', 'OFF', 'APRI'];

if (!in_array($dispositivo, $dispositivi_validi) || !in_array($stato, $stati_validi)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errore' => 'Parametri non validi']);
    exit;
}

// Mappa dispositivo → topic MQTT
$topics = [
    'luce'    => 'pollaio/comando/luce',
    'mangime' => 'pollaio/comando/mangime',
];
$topic   = $topics[$dispositivo];
$payload = $stato;


require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    $server   = $_ENV['MQTT_SERVER']   ?? 'tuo-cluster-id.hivemq.cloud';
    $port     = (int)($_ENV['MQTT_PORT'] ?? 8883);
    $username = $_ENV['MQTT_USER']       ?? '';
    $password = $_ENV['MQTT_PASS']       ?? '';
    $clientId = 'php_cmd_' . uniqid();

    $settings = (new ConnectionSettings)
        ->setUsername($username)
        ->setPassword($password)
        ->setKeepAliveInterval(30)
        ->setUseTls(false);

    $mqtt = new MqttClient($server, $port, $clientId);
    $mqtt->connect($settings, true);
    $mqtt->publish($topic, $payload, 1);   // QoS 1 per garantire la consegna
    $mqtt->disconnect();

    echo json_encode([
        'ok'          => true,
        'topic'       => $topic,
        'payload'     => $payload,
        'utente'      => $_SESSION['nome'] . ' ' . ($_SESSION['cognome'] ?? ''),
        'timestamp'   => date('Y-m-d H:i:s'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'errore' => $e->getMessage()]);
}
