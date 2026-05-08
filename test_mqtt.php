<?php

require('vendor/autoload.php');

use Dotenv\Dotenv;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$server   = $_ENV['MQTT_SERVER'];
$port     = $_ENV['MQTT_PORT'];
$username = $_ENV['MQTT_USER'];
$password = $_ENV['MQTT_PASS'];
$clientId = 'php_client_' . uniqid();

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setUseTls(true);

$mqtt = new MqttClient($server, $port, $clientId);

try {
    $mqtt->connect($connectionSettings, true);
    $mqtt->publish('test/topic', 'Messaggio inviato con .env!', 0);
    $mqtt->disconnect();
    echo "Connessione sicura riuscita!";
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage();
}
?>