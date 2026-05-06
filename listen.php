<?php

require('vendor/autoload.php');

use Dotenv\Dotenv;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server   = $_ENV['MQTT_SERVER'];
$port     = (int)$_ENV['MQTT_PORT'];
$username = $_ENV['MQTT_USER'];
$password = $_ENV['MQTT_PASS'];
$clientId = 'pollaio_subscriber_' . uniqid();

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setUseTls(true)
    ->setTlsVerifyPeer(false)
    ->setTlsVerifyPeerName(false);

$mqtt = new MqttClient($server, $port, $clientId);

try {
    echo "Connessione al cluster HiveMQ in corso...\n";

    $mqtt->connect($connectionSettings, true);

    echo "Connesso con successo! In ascolto sul topic 'sensori/pollaio'...\n";
    echo "Premi CTRL+C per fermare lo script.\n";
    echo "------------------------------------------------------------\n";

    $mqtt->subscribe('sensori/pollaio', function ($topic, $message) {
        echo sprintf("Ricevuto messaggio su [%s]: %s\n", $topic, $message);

    }, 0);

    $mqtt->loop(true);

} catch (Exception $e) {
    echo "Errore fatale: " . $e->getMessage();
}