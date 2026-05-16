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
                $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: /Pollaio_Progetto_IoT_WebApp/login");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'db.php';

use Dotenv\Dotenv;

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    die("Errore configurazione applicazione: " . $e->getMessage());
}

// Ultima lettura in tempo reale
$ultima = $pdo->query("
    SELECT * FROM dati_sensori 
    ORDER BY ricevuto_il DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC) ?: [];

// Ultime 20 letture per il grafico
$storiche = $pdo->query("
    SELECT acqua_perc, luce_val, temperatura, umidita,
           DATE_FORMAT(ricevuto_il, '%H:%i') AS ora
    FROM dati_sensori
    ORDER BY ricevuto_il DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
$storiche = array_reverse($storiche);

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

$temp  = $ultima['temperatura'] ?? null;
$hum   = $ultima['umidita']     ?? null;
$acqua = $ultima['acqua_perc']  ?? null;
$luce  = $ultima['luce_val']    ?? null;

$data_ag = !empty($ultima) ? date('d M Y', strtotime($ultima['ricevuto_il'])) : date('d M Y');
$ora_it  = (int)date('H');
$saluto  = $ora_it < 12 ? 'Buongiorno' : ($ora_it < 18 ? 'Buon pomeriggio' : 'Buonasera');

$nome_utente = htmlspecialchars(trim(($_SESSION['user_nome'] ?? 'Utente') . ' ' . ($_SESSION['user_cognome'] ?? '')));

function fmt($v, $dec = 1) {
    return $v !== null ? number_format($v, $dec) : '--';
}

function getBadgeProps($type, $v) {
    if ($v === null) return ['neutral', '– N/D'];
    switch ($type) {
        case 'temp':
            if ($v < 10) return ['cold', '❄ Freddo'];
            if ($v > 32) return ['hot',  '🔥 Caldo'];
            return ['ok', '✓ Ottimale'];
        case 'hum':
            return ($v >= 40 && $v <= 80) ? ['ok', '✓ Ottimale'] : ['warn', '⚠ Fuori range'];
        case 'acqua':
            if ($v > 60) return ['ok',   '✓ Alto'];
            if ($v > 20) return ['warn', '~ Medio'];
            return ['hot',  '⚠ Basso'];
        case 'luce':
            if ($v < 50) return ['warn', '🌙 Buio'];
            return ['ok', '☀️ Attivo'];
        default:
            return ['neutral', '–'];
    }
}

[$tcls, $tlbl] = getBadgeProps('temp', $temp);
[$hcls, $hlbl] = getBadgeProps('hum', $hum);
[$acls, $albl] = getBadgeProps('acqua', $acqua);
[$lcls, $llbl] = getBadgeProps('luce', $luce);

$pos_thumb = 50;
if ($temp !== null && isset($medie['min_temp'], $medie['max_temp'])) {
    $min = (float)$medie['min_temp'];
    $max = (float)$medie['max_temp'];
    $pos_thumb = $max > $min ? round(($temp - $min) / ($max - $min) * 100) : 50;
    $pos_thumb = max(0, min(100, $pos_thumb));
}

$chartData = [
        'labels' => array_column($storiche, 'ora'),
        'temp'   => array_map('floatval', array_column($storiche, 'temperatura')),
        'hum'    => array_map('floatval', array_column($storiche, 'umidita')),
        'acqua'  => array_map('floatval', array_column($storiche, 'acqua_perc')),
        'luce'   => array_map('floatval', array_column($storiche, 'luce_val')),
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Pollaio IoT</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- I due CSS Caricati insieme -->
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/struttura.css">
    <link rel="stylesheet" href="/Pollaio_Progetto_IoT_WebApp/style/home.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <img src="/Pollaio_Progetto_IoT_WebApp/img/Logo.png" alt="Logo" class="brand-logo">
    </div>
    <nav class="nav-section">
        <a class="nav-item active" href="/Pollaio_Progetto_IoT_WebApp/home">
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

        <a class="nav-item" href="/Pollaio_Progetto_IoT_WebApp/orari_mangime">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
             </span>
            Orari Mangime
        </a>

        <div class="nav-divider">Controllo Manuale</div>

        <button class="nav-btn-ctrl" id="btn-luce" onclick="toggleLuce(this)">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </span>
            <span class="ctrl-label">Accendi Luce</span>
            <span class="ctrl-state" id="state-luce">OFF</span>
        </button>

        <button class="nav-btn-ctrl nav-btn-feed" id="btn-mangime" onclick="erogaMangime(this)" style="margin-top:8px">
            <span class="ni-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
            </span>
            <span class="ctrl-label">Eroga Mangime</span>
            <span class="ctrl-state" id="state-mangime">PRONTO</span>
        </button>

        <!-- MODIFICATO: Sostituito margin-top: auto con un margine fisso controllato per compattare lo spazio -->
        <form action="" method="POST" style="margin-top: 32px; width: 100%;">
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
            <div class="topbar-title">🐔 Pollaio <span>IoT</span></div>
        </div>
        <div class="status-pill">
            <div class="pulse"></div>
            <div>
                <strong id="live-clock">00:00:00</strong>
                <small><?= $data_ag ?> · <?= $medie['totale'] ?? 0 ?> letture/24h</small>
            </div>
        </div>
    </div>

    <div class="sec-label">Sensori in tempo reale</div>

    <div class="cards-grid">
        <div class="scard scard-temp">
            <div class="card-top">
                <div class="card-icon-wrap">🌡️</div>
                <span class="card-badge badge-<?= $tcls ?>"><?= $tlbl ?></span>
            </div>
            <div class="card-body">
                <div class="card-label-txt">Temperatura</div>
                <div class="card-value-big"><?= fmt($temp) ?><span class="unit">°C</span></div>
            </div>
            <?php if ($temp !== null && isset($medie['min_temp'])): ?>
                <div class="range-row">
                    <span class="range-lbl"><?= $medie['min_temp'] ?>°</span>
                    <div class="range-track">
                        <div class="range-fill"></div>
                        <div class="range-thumb" style="left:<?= $pos_thumb ?>%"></div>
                    </div>
                    <span class="range-lbl"><?= $medie['max_temp'] ?>°</span>
                </div>
            <?php endif; ?>
            <div class="card-footer">
                <span>Media 24h</span>
                <span class="avg-val"><?= fmt($medie['avg_temp']) ?>°C</span>
            </div>
        </div>

        <div class="scard scard-hum">
            <div class="card-top">
                <div class="card-icon-wrap">💧</div>
                <span class="card-badge badge-<?= $hcls ?>"><?= $hlbl ?></span>
            </div>
            <div class="card-body">
                <div class="card-label-txt">Umidità relativa</div>
                <div class="card-value-big"><?= fmt($hum) ?><span class="unit">%</span></div>
            </div>
            <div class="card-footer">
                <span>Media 24h</span>
                <span class="avg-val"><?= fmt($medie['avg_hum']) ?>%</span>
            </div>
        </div>

        <div class="scard scard-acqua">
            <div class="card-top">
                <div class="card-icon-wrap">🪣</div>
                <span class="card-badge badge-<?= $acls ?>"><?= $albl ?></span>
            </div>
            <div class="card-body">
                <div class="card-label-txt">Livello acqua</div>
                <div class="card-value-big"><?= fmt($acqua) ?><span class="unit">%</span></div>
            </div>
            <?php if ($acqua !== null): ?>
                <div class="water-progress-wrap">
                    <div class="water-progress-bar" style="width:<?= min(100, (float)$acqua) ?>%;"></div>
                </div>
            <?php endif; ?>
            <div class="card-footer">
                <span>Media 24h</span>
                <span class="avg-val"><?= fmt($medie['avg_acqua']) ?>%</span>
            </div>
        </div>

        <div class="scard scard-luce">
            <div class="card-top">
                <div class="card-icon-wrap">☀️</div>
                <span class="card-badge badge-<?= $lcls ?>"><?= $llbl ?></span>
            </div>
            <div class="card-body">
                <div class="card-label-txt">Luminosità</div>
                <div class="card-value-big"><?= fmt($luce, 0) ?><span class="unit">lx</span></div>
            </div>
            <div class="card-footer">
                <span>Media 24h</span>
                <span class="avg-val"><?= fmt($medie['avg_luce'], 0) ?> lx</span>
            </div>
        </div>
    </div>

    <div class="chart-section">
        <div class="chart-header">
            <div>
                <div class="chart-title">Andamento ultime 20 letture</div>
                <div class="chart-sub">Dati ricevuti via MQTT · topic: pollaio/telemetria</div>
            </div>
            <div class="chart-tabs">
                <button class="tab-btn active" onclick="showDs('temperatura', this)">Temp</button>
                <button class="tab-btn" onclick="showDs('umidita', this)">Umidità</button>
                <button class="tab-btn" onclick="showDs('acqua', this)">Acqua</button>
                <button class="tab-btn" onclick="showDs('luce', this)">Luce</button>
                <button class="tab-btn" onclick="showDs('all', this)">Tutto</button>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="mainChart"></canvas>
        </div>
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

    const phpData = <?= json_encode($chartData) ?>;
    const baseConfig = { tension: 0.45, fill: true, pointRadius: 4, pointHoverRadius: 7, borderWidth: 2.5, pointBackgroundColor: 'white', pointBorderWidth: 2 };

    const datasets = {
        temperatura: { ...baseConfig, label: 'Temperatura (°C)', data: phpData.temp,  borderColor: '#e2a00a', backgroundColor: 'rgba(226,160,10,0.07)',  pointBorderColor: '#e2a00a' },
        umidita:     { ...baseConfig, label: 'Umidità (%)',       data: phpData.hum,   borderColor: '#42a5f5', backgroundColor: 'rgba(66,165,245,0.07)', pointBorderColor: '#42a5f5' },
        acqua:       { ...baseConfig, label: 'Acqua (%)',          data: phpData.acqua, borderColor: '#26a69a', backgroundColor: 'rgba(38,166,154,0.07)', pointBorderColor: '#26a69a' },
        luce:        { ...baseConfig, label: 'Luminosità (lx)',   data: phpData.luce,  borderColor: '#ffb300', backgroundColor: 'rgba(255,179,0,0.07)',  pointBorderColor: '#ffb300' },
    };

    const chart = new Chart(document.getElementById('mainChart').getContext('2d'), {
        type: 'line',
        data: { labels: phpData.labels, datasets: [datasets.temperatura] },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a2e18',
                    titleFont: { family: 'Sora', size: 11, weight: '600' },
                    bodyFont:  { family: 'JetBrains Mono', size: 12 },
                    padding: 12, cornerRadius: 12, displayColors: true, boxRadius: 4,
                }
            },
            scales: {
                x: { grid: { color: '#f0f2ee', drawBorder: false }, ticks: { font: { family: 'JetBrains Mono', size: 10 }, color: '#c0c8be', maxRotation: 0 } },
                y: { grid: { color: '#f0f2ee', drawBorder: false }, ticks: { font: { family: 'JetBrains Mono', size: 10 }, color: '#c0c8be' } }
            }
        }
    });

    function showDs(type, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        chart.data.datasets = type === 'all' ? Object.values(datasets) : [datasets[type]];
        chart.update();
    }

    setTimeout(() => location.reload(), 60000);

    async function sendCommand(dispositivo, stato) {
        try {
            await fetch('/src/comando.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ dispositivo, stato })
            });
        } catch (e) {
            console.error("Errore nell'invio del comando:", e);
        }
    }

    let luceOn = false;
    function toggleLuce(btn) {
        luceOn = !luceOn;
        const statoTxt = document.getElementById('state-luce');
        const labelTxt = btn.querySelector('.ctrl-label');

        btn.classList.toggle('active-luce', luceOn);
        statoTxt.textContent = luceOn ? 'ON' : 'OFF';
        labelTxt.textContent = luceOn ? 'Spegni Luce' : 'Accendi Luce';

        sendCommand('luce', luceOn ? 'ON' : 'OFF');
    }

    function erogaMangime(btn) {
        if (btn.classList.contains('feeding')) return;
        const statoTxt = document.getElementById('state-mangime');
        const labelTxt = btn.querySelector('.ctrl-label');

        btn.classList.add('feeding');
        statoTxt.textContent = 'IN CORSO';
        labelTxt.textContent = 'Erogazione...';
        btn.disabled = true;

        sendCommand('mangime', 'APRI');

        setTimeout(() => {
            btn.classList.remove('feeding');
            statoTxt.textContent = 'PRONTO';
            labelTxt.textContent = 'Eroga Mangime';
            btn.disabled = false;
        }, 3000);
    }
</script>
</body>
</html>