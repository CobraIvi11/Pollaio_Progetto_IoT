<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

// ── PROTEZIONE ACCESSO ──
if (!isset($_SESSION['user_nome'])) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

// ── GESTIONE LOGOUT (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione_logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: " . BASE_URL . "/login");
    exit;
}

// ── LETTURA ALERT MQTT DA SESSIONE ──
$mqtt_successo = $_SESSION['mqtt_successo'] ?? '';
$mqtt_errore   = $_SESSION['mqtt_errore']   ?? '';
unset($_SESSION['mqtt_successo'], $_SESSION['mqtt_errore']);

$data_ag     = date('d M Y');
$nome_utente = htmlspecialchars(trim(($_SESSION['user_nome'] ?? 'Utente') . ' ' . ($_SESSION['user_cognome'] ?? '')));
$id_utente   = $_SESSION['user_id'] ?? null;

// ── INIZIALIZZAZIONE DEFAULT VIA HARDWARE/MEMORIA ──
$pasto_mattina_salvato = "08:00";
$pasto_pomeriggio_salvato = "17:00";

if ($id_utente) {
    try {
        require_once 'db.php';
        $db = Database::getInstance()->getConnection();

        // Cerchiamo se l'utente loggato ha già un record salvato
        $stmt = $db->prepare("SELECT pasto_mattina, pasto_pomeriggio FROM orari_mangime WHERE id_utente = :id_utente LIMIT 1");
        $stmt->execute([':id_utente' => $id_utente]);
        $orari_db = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se esiste nel DB, sovrascriviamo le variabili con i dati reali dell'utente
        if ($orari_db && !empty($orari_db['pasto_mattina']) && !empty($orari_db['pasto_pomeriggio'])) {
            $pasto_mattina_salvato = substr($orari_db['pasto_mattina'], 0, 5);
            $pasto_pomeriggio_salvato = substr($orari_db['pasto_pomeriggio'], 0, 5);
        }
    } catch (\Exception $e) {
        // In caso di errore nel DB (es. tabella non pronta), restano i default (08:00 e 17:00)
        $pasto_mattina_salvato = "08:00";
        $pasto_pomeriggio_salvato = "17:00";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orari Mangime — Pollaio IoT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?=BASE_URL?>/style/struttura.css">
    <link rel="stylesheet" href="<?=BASE_URL?>/style/orari_mangime.css">

    <style>
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
        <img src="<?=BASE_URL?>/img/Logo.png" alt="Logo" class="brand-logo">
    </div>
    <nav class="nav-section">
        <a class="nav-item" href="<?=BASE_URL?>/home">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </span>
            Dashboard
        </a>

        <a class="nav-item" href="<?=BASE_URL?>/statistiche">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </span>
            Statistiche 24h
        </a>

        <a class="nav-item active" href="<?=BASE_URL?>/orari_mangime">
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
            <div class="topbar-title">Orari <span>Mangime</span></div>
        </div>
        <div class="status-pill">
            <div class="pulse"></div>
            <div>
                <strong id="live-clock">00:00:00</strong>
                <small><?= $data_ag ?> · Server Live</small>
            </div>
        </div>
    </div>

    <div class="sec-label">Impostazione pasti giornalieri per l'ESP32 (via MQTT)</div>

    <?php if (!empty($mqtt_successo)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mqtt_successo) ?></div>
    <?php endif; ?>
    <?php if (!empty($mqtt_errore)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($mqtt_errore) ?></div>
    <?php endif; ?>

    <div class="panel-card">
        <div class="panel-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Invia orari pasti all'ESP32
        </div>

        <form action="<?=BASE_URL?>/imposta_orari" method="POST">
            <div class="grid-container" style="gap: 16px; margin-top: 0;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="pasto_mattina">🌅 Pasto Mattina</label>
                    <input type="time" id="pasto_mattina" name="pasto_mattina" class="form-control" value="<?= htmlspecialchars($pasto_mattina_salvato) ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="pasto_pomeriggio">🌆 Pasto Pomeriggio</label>
                    <input type="time" id="pasto_pomeriggio" name="pasto_pomeriggio" class="form-control" value="<?= htmlspecialchars($pasto_pomeriggio_salvato) ?>" required>
                </div>
            </div>
            <div style="margin-top: 22px;">
                <button type="submit" class="btn-submit">📡 Invia al Pollaio via MQTT</button>
            </div>
        </form>
    </div>

</div>

<script>
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