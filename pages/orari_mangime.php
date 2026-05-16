<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── PROTEZIONE ACCESSO (Controllo Sessione) ──
// Aggiunto il controllo anche su 'user_id' per sicurezza
if (!isset($_SESSION['user_nome']) || !isset($_SESSION['user_id'])) {
    header("Location: /Pollaio_Progetto_IoT_WebApp/login");
    exit;
}

$current_user_id = intval($_SESSION['user_id']); // ID dell'utente correntemente loggato

// ── GESTIONE LOGOUT (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
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

// ── GESTIONE AZIONI (POST) ──
$messaggio = '';
$tipo_messaggio = ''; // 'success' o 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Aggiungi Orario
    if (isset($_POST['azione']) && $_POST['azione'] === 'aggiungi') {
        $orario = $_POST['orario'] ?? '';

        if (!empty($orario)) {
            try {
                // Modificato: Ora inserisce anche l'utente_id dell'utente corrente
                $stmt = $pdo->prepare("INSERT INTO orari_mangime (orario, utente_id) VALUES (?, ?)");
                $stmt->execute([$orario, $current_user_id]);
                $messaggio = "Orario programmato con successo!";
                $tipo_messaggio = "success";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Errore Unique Key (chiave composta orario + utente)
                    $messaggio = "Questo orario è già stato impostato da te.";
                } else {
                    $messaggio = "Errore durante il salvataggio: " . $e->getMessage();
                }
                $tipo_messaggio = "error";
            }
        }
    }

    // 2. Erase Orario
    if (isset($_POST['azione']) && $_POST['azione'] === 'elimina') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Modificato: Controllo di sicurezza, l'orario viene eliminato solo se appartiene all'utente loggato
            $stmt = $pdo->prepare("DELETE FROM orari_mangime WHERE id = ? AND utente_id = ?");
            $stmt->execute([$id, $current_user_id]);
            $messaggio = "Programmazione rimossa.";
            $tipo_messaggio = "success";
        }
    }
}

// ── RECUPERO ORARI PROGRAMMATI (Filtrati per Utente) ──
// Modificato: Recuperiamo solo gli orari associati all'utente corrente
$stmt = $pdo->prepare("SELECT *, TIME_FORMAT(orario, '%H:%i') AS orario_fmt FROM orari_mangime WHERE utente_id = ? ORDER BY orario ASC");
$stmt->execute([$current_user_id]);
$orari = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data_ag = date('d M Y');
$nome_utente = htmlspecialchars(trim(($_SESSION['user_nome'] ?? 'Utente') . ' ' . ($_SESSION['user_cognome'] ?? '')));

// Definizione della rotta corretta per i form compatibile con il router
$action_url = "/Pollaio_Progetto_IoT_WebApp/orari_mangime";
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmazione Mangime — Pollaio IoT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/struttura.css">
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/orari_mangime.css">

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

        <a class="nav-item" href="/Pollaio_Progetto_IoT_WebApp/statistiche">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </span>
            Statistiche 24h
        </a>

        <a class="nav-item active" href="/Pollaio_Progetto_IoT_WebApp/orari_mangime">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
             </span>
            Orari Mangime
        </a>

        <div class="nav-divider">Controllo Manuale</div>
        <p class="ctrl-notice">Vai alla pagina <strong>Dashboard</strong> per attivare i comandi hardware.</p>

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
            <div class="topbar-greeting">Pianificazione · <strong><?= $nome_utente ?></strong></div>
            <div class="topbar-title">Alimentazione <span>Automatica</span></div>
        </div>
        <div class="status-pill">
            <div class="pulse"></div>
            <div>
                <strong id="live-clock">00:00:00</strong>
                <small><?= $data_ag ?> · Server Live</small>
            </div>
        </div>
    </div>

    <div class="sec-label">Pianificazione dei pasti e configurazione oraria del servo-motore.</div>

    <?php if (!empty($messaggio)): ?>
        <div class="alert alert-<?= $tipo_messaggio ?>"><?= $messaggio ?></div>
    <?php endif; ?>

    <div class="grid-container">
        <div class="panel-card">
            <div class="panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Aggiungi un nuovo orario
            </div>
            <form action="<?= $action_url ?>" method="POST">
                <input type="hidden" name="azione" value="aggiungi">

                <div class="form-group">
                    <label for="orario">Seleziona l'orario di erogazione</label>
                    <input type="time" id="orario" name="orario" class="form-control" required>
                </div>

                <button type="submit" class="btn-submit">Salva Programmazione</button>
            </form>
        </div>

        <div class="panel-card">
            <div class="panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Orari attivi
            </div>

            <?php if (empty($orari)): ?>
                <div style="color: #b0bba8; font-size: 0.84rem; font-weight: 500; text-align: center; padding: 40px 0;">
                    Nessun orario impostato. Il pollaio verrà alimentato solo manualmente.
                </div>
            <?php else: ?>
                <ul class="orari-list">
                    <?php foreach ($orari as $o): ?>
                        <li class="orario-item">
                            <div>
                                <span class="orario-time"><?= $o['orario_fmt'] ?></span>
                            </div>
                            <form action="<?= $action_url ?>" method="POST" onsubmit="return confirm('Vuoi davvero eliminare questo orario?');">
                                <input type="hidden" name="azione" value="elimina">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <button type="submit" class="btn-delete" title="Elimina orario">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Orologio Real-time in alto a destra
    function tickClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('live-clock').textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }
    tickClock();
    setInterval(tickClock, 1000);
</script>
</body>
</html>