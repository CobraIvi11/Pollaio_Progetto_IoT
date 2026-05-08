<?php

require('vendor/autoload.php');

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;


$server   = 'tuo-cluster-id.hivemq.cloud';
$port     = 8883;
$clientId = 'php_client_' . uniqid();
$username = 'tuo_user';
$password = 'tuo_password';

$connectionSettings = (new ConnectionSettings)
    ->setUsername($username)
    ->setPassword($password)
    ->setKeepAliveInterval(60)
    ->setLastWillTopic('last/will/topic')
    ->setLastWillMessage('Client disconnesso inaspettatamente')
    ->setUseTls(true);

$mqtt = new MqttClient($server, $port, $clientId);

try {
    $mqtt->connect($connectionSettings, true);

    $mqtt->publish('test/topic', 'Ciao da PHP!', 0);

    echo "Messaggio inviato con successo!";

    $mqtt->disconnect();
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage();
}
?>