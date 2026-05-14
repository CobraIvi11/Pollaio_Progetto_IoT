<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore DB: " . $e->getMessage());
}

$ultima = $pdo->query("SELECT * FROM dati_sensori ORDER BY ricevuto_il DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$storiche = $pdo->query("
    SELECT acqua_perc, luce_val, temperatura, umidita,
           DATE_FORMAT(ricevuto_il, '%H:%i') AS ora
    FROM dati_sensori
    ORDER BY ricevuto_il DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
$storiche = array_reverse($storiche);

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
")->fetch(PDO::FETCH_ASSOC);

$temp  = $ultima['temperatura'] ?? null;
$hum   = $ultima['umidita']     ?? null;
$acqua = $ultima['acqua_perc']  ?? null;
$luce  = $ultima['luce_val']    ?? null;
$data_ag = $ultima ? date('d M Y', strtotime($ultima['ricevuto_il'])) : date('d M Y');

function fmt($v, $dec = 1) { return $v !== null ? number_format($v, $dec) : '--'; }

function badgeTemp($v) {
    if ($v === null) return ['neutral', '– N/D'];
    if ($v < 10)    return ['cold',    '❄ Freddo'];
    if ($v > 32)    return ['hot',     '🔥 Caldo'];
    return ['ok', '✓ Ottimale'];
}
function badgeHum($v) {
    if ($v === null) return ['neutral', '– N/D'];
    return ($v >= 40 && $v <= 80) ? ['ok', '✓ Ottimale'] : ['warn', '⚠ Fuori range'];
}
function badgeAcqua($v) {
    if ($v === null) return ['neutral', '– N/D'];
    if ($v > 60) return ['ok', '✓ Alto'];
    if ($v > 20) return ['warn', '~ Medio'];
    return ['hot', '⚠ Basso'];
}

[$tcls, $tlbl] = badgeTemp($temp);
[$hcls, $hlbl] = badgeHum($hum);
[$acls, $albl] = badgeAcqua($acqua);

$labels  = json_encode(array_column($storiche, 'ora'));
$j_temp  = json_encode(array_map('floatval', array_column($storiche, 'temperatura')));
$j_hum   = json_encode(array_map('floatval', array_column($storiche, 'umidita')));
$j_acqua = json_encode(array_map('floatval', array_column($storiche, 'acqua_perc')));
$j_luce  = json_encode(array_map('floatval', array_column($storiche, 'luce_val')));

$nome_utente = htmlspecialchars(($_SESSION['nome'] ?? 'Utente') . ' ' . ($_SESSION['cognome'] ?? ''));
$iniziali    = strtoupper(substr($_SESSION['nome'] ?? 'U', 0, 1) . substr($_SESSION['cognome'] ?? 'T', 0, 1));
$ruolo       = htmlspecialchars($_SESSION['ruolo'] ?? 'Operatore');

$ora_it = (int)date('H');
$saluto = $ora_it < 12 ? 'Buongiorno' : ($ora_it < 18 ? 'Buon pomeriggio' : 'Buonasera');
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Pollaio IoT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/Pollaio_Progetto_Iot_WebApp/style/home.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Sora', Arial, sans-serif; background: #f0f2ee; min-height: 100vh; display: flex; }
.sidebar { font-family: 'Sora', Arial, sans-serif; }
.nav-item { font-family: 'Sora', Arial, sans-serif; font-size: 0.84rem; }
.user-name { font-family: 'Sora', Arial, sans-serif; }
.main { flex: 1; padding: 36px 40px 60px; display: flex; flex-direction: column; gap: 28px; overflow-x: hidden; }

/* TOPBAR */
.topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.topbar-greeting { font-size: 0.78rem; font-weight: 600; color: #aaa; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px; }
.topbar-title { font-size: 2rem; font-weight: 800; color: #1a2e18; letter-spacing: -0.03em; line-height: 1; }
.topbar-title span { color: #4CAF50; }

/* STATUS PILL */
.status-pill {
    display: flex; align-items: center; gap: 10px;
    background: white; border: 2px solid #e8f0e6;
    border-radius: 16px; padding: 10px 18px;
    font-size: 0.78rem; color: #888; font-weight: 500; white-space: nowrap;
}
.status-pill strong {
    color: #2d5a27;
    font-size: 1.05rem;
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    letter-spacing: 0.04em;
    display: block;
    line-height: 1.2;
}
.status-pill small { color: #bbb; font-size: 0.72rem; display: block; font-family: 'JetBrains Mono', monospace; }
.pulse { width: 9px; height: 9px; border-radius: 50%; background: #4CAF50; box-shadow: 0 0 0 0 rgba(76,175,80,0.5); animation: ripple 2s infinite; flex-shrink: 0; }
@keyframes ripple {
    0%   { box-shadow: 0 0 0 0 rgba(76,175,80,0.5); }
    70%  { box-shadow: 0 0 0 8px rgba(76,175,80,0); }
    100% { box-shadow: 0 0 0 0 rgba(76,175,80,0); }
}

/* SECTION LABEL */
.sec-label { font-size: 0.7rem; font-weight: 700; color: #c0c8be; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: -12px; }

/* CARDS */
.cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
@media (max-width: 1100px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .cards-grid { grid-template-columns: 1fr; } }

.scard {
    background: white; border-radius: 22px; padding: 24px 22px 20px;
    display: flex; flex-direction: column; gap: 14px;
    border: 2px solid #eef2ec; position: relative; overflow: hidden;
    transition: transform 0.22s cubic-bezier(.34,1.56,.64,1), box-shadow 0.22s;
    animation: slideUp 0.5s cubic-bezier(.34,1.2,.64,1) both;
}
.scard:hover { transform: translateY(-5px); box-shadow: 0 16px 40px rgba(45,90,39,0.10); }
.scard:nth-child(1) { animation-delay: 0.05s; }
.scard:nth-child(2) { animation-delay: 0.12s; }
.scard:nth-child(3) { animation-delay: 0.19s; }
.scard:nth-child(4) { animation-delay: 0.26s; }
@keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

.scard-temp  { border-top: 4px solid #e2a00a; }
.scard-hum   { border-top: 4px solid #42a5f5; }
.scard-acqua { border-top: 4px solid #26a69a; }
.scard-luce  { border-top: 4px solid #ffb300; }

.card-top { display: flex; align-items: center; justify-content: space-between; }
.card-icon-wrap { width: 46px; height: 46px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.scard-temp  .card-icon-wrap { background: #fff8e6; }
.scard-hum   .card-icon-wrap { background: #e8f4fd; }
.scard-acqua .card-icon-wrap { background: #e0f2f1; }
.scard-luce  .card-icon-wrap { background: #fffde7; }

.card-badge { font-size: 0.68rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; letter-spacing: 0.02em; }
.badge-ok      { background: #e8f5e9; color: #2d5a27; }
.badge-warn    { background: #fff8e6; color: #b14a06; }
.badge-hot     { background: #fbe9e7; color: #c62828; }
.badge-cold    { background: #e8eaf6; color: #283593; }
.badge-neutral { background: #f5f5f5; color: #9e9e9e; }

.card-label-txt { font-size: 0.72rem; font-weight: 600; color: #b0bba8; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
.card-value-big { font-family: 'JetBrains Mono', monospace; font-size: 2.8rem; font-weight: 600; color: #1a2e18; line-height: 1; letter-spacing: -0.02em; }
.card-value-big .unit { font-size: 1.1rem; color: #ccc; font-weight: 400; margin-left: 2px; }

.card-footer { display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; color: #c0c8be; border-top: 1.5px solid #f5f5f3; padding-top: 12px; font-weight: 500; }
.card-footer .avg-val { color: #4CAF50; font-weight: 700; font-family: 'JetBrains Mono', monospace; }

/* CHART */
.chart-section { background: white; border-radius: 22px; padding: 28px 28px 24px; border: 2px solid #eef2ec; animation: slideUp 0.5s 0.3s cubic-bezier(.34,1.2,.64,1) both; }
.chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 12px; }
.chart-title { font-size: 1rem; font-weight: 700; color: #1a2e18; letter-spacing: -0.01em; }
.chart-sub { font-size: 0.72rem; color: #bbb; font-weight: 500; margin-top: 2px; }
.chart-tabs { display: flex; gap: 6px; }
.tab-btn { padding: 6px 16px; border-radius: 20px; border: 2px solid #eef2ec; background: white; font-size: 0.74rem; font-weight: 700; color: #aaa; cursor: pointer; transition: all 0.18s; font-family: 'Sora', Arial, sans-serif; }
.tab-btn:hover { border-color: #4CAF50; color: #2d5a27; background: #f0f7ee; }
.tab-btn.active { background: linear-gradient(135deg, #2d5a27, #4CAF50); color: white; border-color: transparent; }
.chart-wrap { height: 220px; position: relative; }

/* STAT ROW */
.stat-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; }
@media (max-width: 1100px) { .stat-row { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px)  { .stat-row { grid-template-columns: repeat(2, 1fr); } }

.stat-tile { background: white; border-radius: 18px; padding: 18px 16px; border: 2px solid #eef2ec; animation: slideUp 0.5s 0.35s cubic-bezier(.34,1.2,.64,1) both; transition: transform 0.2s; }
.stat-tile:hover { transform: translateY(-3px); }
.stat-tile-label { font-size: 0.68rem; font-weight: 700; color: #c0c8be; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
.stat-tile-val   { font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 600; color: #1a2e18; line-height: 1; }
.stat-tile-sub   { font-size: 0.7rem; color: #d0d8ce; font-weight: 500; margin-top: 4px; }
.stat-tile-accent { width: 28px; height: 3px; border-radius: 4px; margin-top: 10px; }
.accent-green  { background: linear-gradient(90deg, #2d5a27, #4CAF50); }
.accent-blue   { background: linear-gradient(90deg, #1565C0, #42a5f5); }
.accent-teal   { background: linear-gradient(90deg, #00695c, #26a69a); }
.accent-yellow { background: linear-gradient(90deg, #b14a06, #ffb300); }
.accent-gray   { background: linear-gradient(90deg, #555, #aaa); }

/* RANGE BAR */
.range-row { display: flex; align-items: center; gap: 10px; margin-top: 6px; }
.range-track { flex: 1; height: 5px; background: #f0f2ee; border-radius: 10px; position: relative; overflow: visible; }
.range-fill { height: 100%; border-radius: 10px; background: linear-gradient(90deg, #42a5f5, #4CAF50, #e2a00a, #f44336); }
.range-thumb { position: absolute; top: 50%; transform: translate(-50%, -50%); width: 12px; height: 12px; border-radius: 50%; background: #2d5a27; border: 2px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
.range-lbl { font-size: 0.68rem; font-family: 'JetBrains Mono', monospace; color: #bbb; font-weight: 500; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <img src="/Pollaio_Progetto_Iot_WebApp/img/Logo.png" alt="Logo">
    </div>
    <nav class="nav-section">
        <a class="nav-item active" href="#">
            <span class="ni-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
            Dashboard
        </a>
        <a class="nav-item" href="#" style="margin-top:6px">
            <span class="ni-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
            Storico
        </a>
        <a class="nav-item" href="#" style="margin-top:6px">
            <span class="ni-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
            Sensori
        </a>
        <a class="nav-item" href="/Pollaio_Progetto_Iot_WebApp/login" style="margin-top:auto">
            <span class="ni-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
            Esci
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="user-avatar"><?= $iniziali ?></div>
            <div class="user-info">
                <div class="user-name"><?= $nome_utente ?></div>
                <div class="user-role"><?= $ruolo ?></div>
            </div>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-greeting"><?= $saluto ?></div>
            <div class="topbar-title">🐔 Pollaio <span>IoT</span></div>
        </div>
        <!-- STATUS PILL con orologio live -->
        <div class="status-pill">
            <div class="pulse"></div>
            <div>
                <strong id="live-clock"></strong>
                <small><?= $data_ag ?> · <?= $medie['totale'] ?? 0 ?> letture/24h</small>
            </div>
        </div>
    </div>

    <div class="sec-label">Sensori in tempo reale</div>

    <div class="cards-grid">

        <!-- Temperatura -->
        <div class="scard scard-temp">
            <div class="card-top">
                <div class="card-icon-wrap">🌡️</div>
                <span class="card-badge badge-<?= $tcls ?>"><?= $tlbl ?></span>
            </div>
            <div class="card-body">
                <div class="card-label-txt">Temperatura</div>
                <div class="card-value-big"><?= fmt($temp) ?><span class="unit">°C</span></div>
            </div>
            <?php if ($temp !== null && $medie['min_temp'] !== null): ?>
            <div class="range-row">
                <span class="range-lbl"><?= $medie['min_temp'] ?>°</span>
                <div class="range-track">
                    <div class="range-fill" style="width:100%"></div>
                    <?php
                        $min = (float)$medie['min_temp'];
                        $max = (float)$medie['max_temp'];
                        $pos = $max > $min ? round(($temp - $min) / ($max - $min) * 100) : 50;
                        $pos = max(0, min(100, $pos));
                    ?>
                    <div class="range-thumb" style="left:<?= $pos ?>%"></div>
                </div>
                <span class="range-lbl"><?= $medie['max_temp'] ?>°</span>
            </div>
            <?php endif; ?>
            <div class="card-footer">
                <span>Media 24h</span>
                <span class="avg-val"><?= fmt($medie['avg_temp']) ?>°C</span>
            </div>
        </div>

        <!-- Umidità -->
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

        <!-- Acqua -->
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
            <div style="height:6px; background:#e0f2f1; border-radius:10px; overflow:hidden;">
                <div style="height:100%; width:<?= min(100,(float)$acqua) ?>%; background:linear-gradient(90deg,#26a69a,#4CAF50); border-radius:10px;"></div>
            </div>
            <?php endif; ?>
            <div class="card-footer">
                <span>Media 24h</span>
                <span class="avg-val"><?= fmt($medie['avg_acqua']) ?>%</span>
            </div>
        </div>

        <!-- Luce -->
        <div class="scard scard-luce">
            <div class="card-top">
                <div class="card-icon-wrap">☀️</div>
                <span class="card-badge badge-ok">Attivo</span>
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

    <!-- Grafico -->
    <div class="chart-section">
        <div class="chart-header">
            <div>
                <div class="chart-title">Andamento ultime 20 letture</div>
                <div class="chart-sub">Dati ricevuti via MQTT · topic: pollaio/telemetria</div>
            </div>
            <div class="chart-tabs">
                <button class="tab-btn active" onclick="showDs('temperatura',this)">Temp</button>
                <button class="tab-btn" onclick="showDs('umidita',this)">Umidità</button>
                <button class="tab-btn" onclick="showDs('acqua',this)">Acqua</button>
                <button class="tab-btn" onclick="showDs('luce',this)">Luce</button>
                <button class="tab-btn" onclick="showDs('all',this)">Tutto</button>
            </div>
        </div>
        <div class="chart-wrap">
            <canvas id="mainChart"></canvas>
        </div>
    </div>

    <!-- Statistiche -->
    <div class="sec-label">Statistiche ultime 24 ore</div>
    <div class="stat-row">
        <div class="stat-tile">
            <div class="stat-tile-label">Temp media</div>
            <div class="stat-tile-val"><?= fmt($medie['avg_temp']) ?>°</div>
            <div class="stat-tile-sub">Temperatura</div>
            <div class="stat-tile-accent accent-green"></div>
        </div>
        <div class="stat-tile">
            <div class="stat-tile-label">Temp min / max</div>
            <div class="stat-tile-val"><?= fmt($medie['min_temp']) ?> / <?= fmt($medie['max_temp']) ?>°</div>
            <div class="stat-tile-sub">Range 24h</div>
            <div class="stat-tile-accent accent-yellow"></div>
        </div>
        <div class="stat-tile">
            <div class="stat-tile-label">Umidità media</div>
            <div class="stat-tile-val"><?= fmt($medie['avg_hum']) ?>%</div>
            <div class="stat-tile-sub">Umidità relativa</div>
            <div class="stat-tile-accent accent-blue"></div>
        </div>
        <div class="stat-tile">
            <div class="stat-tile-label">Acqua media</div>
            <div class="stat-tile-val"><?= fmt($medie['avg_acqua']) ?>%</div>
            <div class="stat-tile-sub">Livello serbatoio</div>
            <div class="stat-tile-accent accent-teal"></div>
        </div>
        <div class="stat-tile">
            <div class="stat-tile-label">Letture totali</div>
            <div class="stat-tile-val"><?= $medie['totale'] ?? 0 ?></div>
            <div class="stat-tile-sub">Ultime 24 ore</div>
            <div class="stat-tile-accent accent-gray"></div>
        </div>
    </div>

</div>

<script>
/* ── OROLOGIO LIVE ── */
function tickClock() {
    const now  = new Date();
    const hh   = String(now.getHours()).padStart(2, '0');
    const mm   = String(now.getMinutes()).padStart(2, '0');
    const ss   = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('live-clock').textContent = hh + ':' + mm + ':' + ss;
}
tickClock();                        // primo render immediato, niente flickering
setInterval(tickClock, 1000);

/* ── CHART.JS ── */
const labels  = <?= $labels ?>;
const dTemp   = <?= $j_temp ?>;
const dHum    = <?= $j_hum ?>;
const dAcqua  = <?= $j_acqua ?>;
const dLuce   = <?= $j_luce ?>;

const base = { tension: 0.45, fill: true, pointRadius: 4, pointHoverRadius: 7, borderWidth: 2.5, pointBackgroundColor: 'white', pointBorderWidth: 2 };

const datasets = {
    temperatura: { ...base, label: 'Temperatura (°C)', data: dTemp,  borderColor: '#e2a00a', backgroundColor: 'rgba(226,160,10,0.07)',  pointBorderColor: '#e2a00a' },
    umidita:     { ...base, label: 'Umidità (%)',       data: dHum,   borderColor: '#42a5f5', backgroundColor: 'rgba(66,165,245,0.07)', pointBorderColor: '#42a5f5' },
    acqua:       { ...base, label: 'Acqua (%)',          data: dAcqua, borderColor: '#26a69a', backgroundColor: 'rgba(38,166,154,0.07)', pointBorderColor: '#26a69a' },
    luce:        { ...base, label: 'Luminosità (lx)',   data: dLuce,  borderColor: '#ffb300', backgroundColor: 'rgba(255,179,0,0.07)',  pointBorderColor: '#ffb300' },
};

const chart = new Chart(document.getElementById('mainChart').getContext('2d'), {
    type: 'line',
    data: { labels, datasets: [datasets.temperatura] },
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

function showDs(key, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    chart.data.datasets = key === 'all' ? Object.values(datasets) : [datasets[key]];
    chart.update('active');
}

// Auto-refresh pagina ogni 30s per aggiornare i dati dal DB
setTimeout(() => location.reload(), 30000);
</script>
</body>
</html>
