<?php
session_start();

// ── PROTEZIONE ACCESSO (Controllo Sessione) ──
if (!isset($_SESSION['user_nome'])) {
    header("Location: /Pollaio_Progetto_IoT_WebApp/login");
    exit;
}

// ── GESTIONE LOGOUT (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httly"]
        );
    }
    session_destroy();
    header("Location: /Pollaio_Progetto_IoT_WebApp/login");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'db.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("Errore configurazione applicazione: " . $e->getMessage());
}

// Medie aggregate ultime 24 ore
$medie = $pdo->query("
    SELECT
        ROUND(AVG(temperatura), 1) AS avg_temp,
        ROUND(MIN(temperatura), 1) AS min_temp,
        ROUND(MAX(temperatura), 1) AS max_temp,
        ROUND(AVG(umidita), 1)     AS avg_hum,
        ROUND(AVG(acqua_perc), 1)  AS avg_acqua,
        ROUND(AVG(luce_val), 1)    AS avg_luce,
        COUNT(*)                   AS totale
    FROM dati_sensori
    WHERE ricevuto_il >= NOW() - INTERVAL 24 HOUR
")->fetch(PDO::FETCH_ASSOC) ?: [];

$data_ag = date('d M Y');
$ora_it  = (int)date('H');
$saluto  = $ora_it < 12 ? 'Buongiorno' : ($ora_it < 18 ? 'Buon pomeriggio' : 'Buonasera');

$nome_utente = htmlspecialchars(trim(($_SESSION['user_nome'] ?? 'Utente') . ' ' . ($_SESSION['user_cognome'] ?? '')));

function fmt($v, $dec = 1) {
    return $v !== null ? number_format($v, $dec) : '--';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche — Pollaio IoT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- CSS Separati: Struttura Comune + Componenti di questa pagina -->
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/struttura.css">
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/statistiche.css">

    <style>
        /* Stile locale per la nota di avviso nella sidebar */
        .ctrl-notice {
            font-size: 0.78rem;
            color: #a0aaa0;
            line-height: 1.3;
            margin: -4px 0 12px 0;
            padding: 0 4px;
            opacity: 0.85;
        }
        .nav-btn-ctrl:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="/Pollaio_Progetto_IoT_WebApp/img/Logo.png" alt="Logo" class="brand-logo">
    </div>
    <nav class="nav-section">
        <a class="nav-item" href="/Pollaio_Progetto_IoT_WebApp/home">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </span>
            Dashboard
        </a>

        <a class="nav-item active" href="/Pollaio_Progetto_IoT_WebApp/statistiche">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </span>
            Statistiche 24h
        </a>

        <a class="nav-item" href="/Pollaio_Progetto_IoT_WebApp/orari_mangime">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
             </span>
            Orari Mangime
        </a>

        <div class="nav-divider">Controllo Manuale</div>
        <p class="ctrl-notice">Vai alla pagina <strong>Dashboard</strong> per attivare i comandi hardware.</p>

        <!-- Pulsanti Hardware Disabilitati -->
        <button class="nav-btn-ctrl" id="btn-luce" disabled>
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </span>
            <span class="ctrl-label">Accendi Luce</span>
            <span class="ctrl-state" id="state-luce">OFF</span>
        </button>

        <button class="nav-btn-ctrl nav-btn-feed" id="btn-mangime" disabled style="margin-top:8px;">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </span>
            <span class="ctrl-label">Eroga Mangime</span>
            <span class="ctrl-state" id="state-mangime">OFF</span>
        </button>

        <form action="" method="POST" style="margin-top:auto; width: 100%;">
            <input type="hidden" name="azione_logout" value="1">
            <button type="submit" class="nav-item" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; font-family: inherit;">
                <span class="ni-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </span>
                Esci
            </button>
        </form>
    </nav>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-greeting"><?= $saluto ?>, <strong><?= $nome_utente ?></strong></div>
            <div class="topbar-title">📊 Analisi <span>Statistiche</span></div>
        </div>
        <div class="status-pill">
            <div class="pulse"></div>
            <div>
                <strong id="live-clock">00:00:00</strong>
                <small><?= $data_ag ?> · Report 24 Ore</small>
            </div>
        </div>
    </div>

    <div class="sec-label">Resoconto aggregato delle ultime 24 ore di telemetria</div>

    <div class="stat-row">
        <?php
        $tiles = [
                ['lbl' => 'Temperatura media',  'val' => fmt($medie['avg_temp'] ?? null) . '°C', 'sub' => 'Media termica rilevata', 'acc' => 'green'],
                ['lbl' => 'Escursione termica',  'val' => fmt($medie['min_temp'] ?? null) . ' / ' . fmt($medie['max_temp'] ?? null) . '°C', 'sub' => 'Valori minimi e massimi', 'acc' => 'yellow'],
                ['lbl' => 'Umidità media',      'val' => fmt($medie['avg_hum'] ?? null) . '%', 'sub' => 'Tasso igrometrico medio', 'acc' => 'blue'],
                ['lbl' => 'Livello acqua medio','val' => fmt($medie['avg_acqua'] ?? null) . '%', 'sub' => 'Volume medio nel serbatoio', 'acc' => 'teal'],
                ['lbl' => 'Luminosità media',   'val' => fmt($medie['avg_luce'] ?? null, 0) . ' lx', 'sub' => 'Radiazione luminosa media', 'acc' => 'yellow'],
                ['lbl' => 'Letture salvate',    'val' => $medie['totale'] ?? 0, 'sub' => 'Pacchetti MQTT ricevuti', 'acc' => 'gray']
        ];
        foreach ($tiles as $tile): ?>
            <div class="stat-tile" style="padding: 26px 22px; display: flex; flex-direction: column; gap: 8px;">
                <div class="stat-tile-label"><?= $tile['lbl'] ?></div>
                <div class="stat-tile-val" style="font-size: 1.9rem; letter-spacing: -0.02em; color: #1a2e18;"><?= $tile['val'] ?></div>
                <div class="stat-tile-sub" style="margin-top: auto; color: #a0aaa0;"><?= $tile['sub'] ?></div>
                <div class="stat-tile-accent <?= 'accent-' . $tile['acc'] ?>" style="width: 40px; height: 4px; margin-top: 6px;"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Gestione Orologio Real-time
    function tickClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('live-clock').textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }
    tickClock();
    setInterval(tickClock, 1000);

    // Auto-refresh della pagina ogni minuto
    setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>