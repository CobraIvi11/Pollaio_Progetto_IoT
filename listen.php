<?php

require __DIR__ . '/vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// --- CARICAMENTO FILE .ENV ---
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// ==========================================
// 1. CONFIGURAZIONE DATABASE (Presa dal .env)
// ==========================================
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione DB: " . $e->getMessage() . "\n");
}

// ==========================================
// 2. CONFIGURAZIONE HIVEMQ CLOUD (Presa dal .env)
// ==========================================
$server   = $_ENV['MQTT_SERVER'];
$port     = $_ENV['MQTT_PORT'];
$clientId = 'PHP_Server_' . uniqid();
$user     = $_ENV['MQTT_USER'];
$password = $_ENV['MQTT_PASS'];
$topic    = 'pollaio/telemetria';

$mqtt = new MqttClient($server, $port, $clientId);

// Impostazioni di connessione con TLS e Autenticazione
$connectionSettings = (new ConnectionSettings)
    ->setUsername($user)
    ->setPassword($password)
    ->setUseTls(true)
    ->setTlsVerifyPeer(false)
    ->setKeepAliveInterval(60);

try {
    $mqtt->connect($connectionSettings, true);
    echo "Connesso a MQTT Broker HiveMQ Cloud...\n";
} catch (Exception $e) {
    die("Errore di connessione MQTT: " . $e->getMessage() . "\n");
}

$mqtt->subscribe($topic, function (string $topic, string $message) use ($pdo) {

    echo "Messaggio ricevuto da [$topic]: $message\n";

    // -------------------------
    // DECODE JSON
    // -------------------------
    $data = json_decode($message, true);

    if (!$data) {
        echo "Errore: JSON non valido\n";
        return;
    }

    // -------------------------
    // VALIDAZIONE NUOVI DATI ESP32
    // -------------------------
    // Usiamo array_key_exists per evitare errori se temp/umidità sono null
    if (
        !array_key_exists('acqua', $data) ||
        !array_key_exists('luce', $data) ||
        !array_key_exists('temperatura', $data) ||
        !array_key_exists('umidita', $data)
    ) {
        echo "Errore: Dati mancanti nel JSON del pollaio\n";
        return;
    }

    // -------------------------
    // INSERT DATABASE (Nuova Tabella)
    // -------------------------
    try {
        $stmt = $pdo->prepare("
            INSERT INTO dati_sensori (acqua_perc, luce_val, temperatura, umidita)
            VALUES (:acqua, :luce, :temperatura, :umidita)
        ");

        $stmt->execute([
            ':acqua'       => $data['acqua'],
            ':luce'        => $data['luce'],
            ':temperatura' => $data['temperatura'],
            ':umidita'     => $data['umidita']
        ]);

        echo "=> Dati salvati con successo nel database!\n";

    } catch (PDOException $e) {
        echo "Errore DB: " . $e->getMessage() . "\n";
    }
}, 0);

// -------------------------
// LOOP PRINCIPALE
// -------------------------
$mqtt->loop(true);