<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload di Composer per caricare la libreria php-mqtt
require_once __DIR__ . '/../vendor/autoload.php';

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

// ── PROTEZIONE ACCESSO ──
if (!isset($_SESSION['user_id'])) {
    header("Location: /Pollaio_Progetto_IoT_WebApp/login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utente = $_SESSION['user_id'];

    // Recuperiamo i dati dal form (con fallback se vuoti)
    $pasto_mattina = $_POST['pasto_mattina'] ?? '08:00';
    $pasto_pomeriggio = $_POST['pasto_pomeriggio'] ?? '17:00';

    try {
        // 1. ── SALVATAGGIO NEL DATABASE (LOGICA SINGLE-ROW) ──
        require_once 'db.php';
        $db = Database::getInstance()->getConnection();

        // Verifichiamo se esiste già una riga per questo utente
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM orari_mangime WHERE id_utente = :id_utente");
        $checkStmt->execute([':id_utente' => $id_utente]);
        $utente_esiste = $checkStmt->fetchColumn();

        if ($utente_esiste > 0) {
            // Se l'utente esiste già, aggiorniamo la sua unica riga
            $sql = "UPDATE orari_mangime 
                    SET pasto_mattina = :pasto_mattina, 
                        pasto_pomeriggio = :pasto_pomeriggio 
                    WHERE id_utente = :id_utente";
        } else {
            // Se è la prima volta in assoluto, facciamo una INSERT
            $sql = "INSERT INTO orari_mangime (id_utente, pasto_mattina, pasto_pomeriggio) 
                    VALUES (:id_utente, :pasto_mattina, :pasto_pomeriggio)";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_utente'       => $id_utente,
            ':pasto_mattina'   => $pasto_mattina,
            ':pasto_pomeriggio'=> $pasto_pomeriggio
        ]);


        // 2. ── CONFIGURAZIONE E INVIO MQTT ──

        // Parametri di configurazione del Broker (Modificali con i tuoi dati reali)
        $server   = 'broker.hivemq.com'; // O l'IP del tuo server (es. '192.168.1.X')
        $port     = 1883;
        $clientId = 'pollaio_webapp_client_' . uniqid();
        $username = null; // Inserisci la stringa se il tuo broker richiede autenticazione
        $password = null; // Inserisci la stringa se il tuo broker richiede autenticazione

        // Creazione del client MQTT
        $mqtt = new MqttClient($server, $port, $clientId);

        // Impostazioni di connessione (timeout, credenziali)
        $settings = (new ConnectionSettings())
            ->setKeepAliveInterval(60)
            ->setConnectTimeout(5);

        if ($username !== null && $password !== null) {
            $settings->textCredentials($username, $password);
        }

        // Ci colleghiamo al Broker
        $mqtt->connect($settings, false);

        // Prepariamo il payload JSON strutturato per l'ESP32
        $payloadData = [
            'id_utente' => $id_utente,
            'pasto_mattina' => $pasto_mattina,
            'pasto_pomeriggio' => $pasto_pomeriggio,
            'timestamp' => time()
        ];
        $payload = json_encode($payloadData);

        // Pubblichiamo sul topic dedicato al tuo pollaio
        // NOTA: Sostituisci "mio_pollaio_id" con un identificativo unico per non incrociare i dati con altri
        $topic = "pollaio/config/orari";
        $mqtt->publish($topic, $payload, 0); // QoS 0 (o 1 se vuoi più certezza)

        // Chiudiamo la connessione MQTT in modo pulito
        $mqtt->disconnect();

        // Messaggio di conferma finale per l'utente
        $_SESSION['mqtt_successo'] = "Orari salvati sul Database e inviati con successo all'ESP32 via MQTT!";

    } catch (\Exception $e) {
        // Cattura sia gli errori del DB che i fallimenti di connessione del Broker MQTT
        $_SESSION['mqtt_errore'] = "Errore durante l'operazione: " . $e->getMessage();
    }

    // Ritorniamo alla pagina di pianificazione orari
    header("Location: /Pollaio_Progetto_IoT_WebApp/orari_mangime");
    exit;
} else {
    // Se qualcuno prova ad accedere alla pagina direttamente via GET, lo rispediamo indietro
    header("Location: /Pollaio_Progetto_IoT_WebApp/orari_mangime");
    exit;
}