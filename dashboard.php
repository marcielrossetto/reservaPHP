<?php
session_start();
require 'config.php';
require 'cabecalho.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

/* ==========================================================
   1. FILTROS E PREPARAÇÃO
========================================================== */
$dateStart = $_GET['inicio'] ?? date('Y-m-01');
$dateEnd   = $_GET['fim'] ?? date('Y-m-t');

$periodo = new DatePeriod(
    new DateTime($dateStart),
    new DateInterval('P1D'),
    (new DateTime($dateEnd))->modify('+1 day')
);
$timelineData = [];
foreach ($periodo as $dt) {
    $timelineData[$dt->format("Y-m-d")] = ['pax' => 0];
}

/* ==========================================================
   2. CONSULTA AO BANCO
========================================================== */
$stmt = $pdo->prepare("
    SELECT nome, data, num_pessoas, telefone, status, forma_pagamento
    FROM clientes
    WHERE data BETWEEN :inicio AND :fim
    ORDER BY data ASC
");
$stmt->execute(['inicio' => $dateStart, 'fim' => $dateEnd]);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   3. PROCESSAMENTO (BI + CRM)
========================================================== */
$kpis = ['res' => 0, 'pax' => 0, 'canc' => 0];
$grupos = [
    'pequeno' => ['res' => 0, 'pax' => 0], // 1-4
    'medio'   => ['res' => 0, 'pax' => 0], // 5-10
    'grande'  => ['res' => 0, 'pax' => 0], // 11-20
    'evento'  => ['res' => 0, 'pax' => 0], // 21+
];
$graficos = [
    'pagamento' => [],
    'dias_semana' => array_fill_keys(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'], 0)
];
$clientes = [];

foreach ($dados as $row) {
    $pax = (int)$row['num_pessoas'];
    $kpis['res']++;
    $kpis['pax'] += $pax;

    if ($row['status'] == 0) {
        $kpis['canc']++;
    } else {
        // Timeline (Gráfico de Palitos)
        if (isset($timelineData[$row['data']])) {
            $timelineData[$row['data']]['pax'] += $pax;
        }

        // Unificação dos Grupos nos Cards
        if ($pax <= 4) { $grupos['pequeno']['res']++; $grupos['pequeno']['pax'] += $pax; }
        elseif ($pax <= 10) { $grupos['medio']['res']++; $grupos['medio']['pax'] += $pax; }
        elseif ($pax <= 20) { $grupos['grande']['res']++; $grupos['grande']['pax'] += $pax; }
        else { $grupos['evento']['res']++; $grupos['evento']['pax'] += $pax; }

        $pag = $row['forma_pagamento'] ?: 'Outros';
        $graficos['pagamento'][$pag] = ($graficos['pagamento'][$pag] ?? 0) + 1;

        $d = date('D', strtotime($row['data']));
        $diaMap = ["Sun"=>"Dom","Mon"=>"Seg","Tue"=>"Ter","Wed"=>"Qua","Thu"=>"Qui","Fri"=>"Sex","Sat"=>"Sáb"];
        $graficos['dias_semana'][$diaMap[$d]] += $pax;
    }

    // CRM Detalhado
    $tel = preg_replace('/\D/', '', $row['telefone']);
    if(!empty($tel)) {
        if(!isset($clientes[$tel])) {
            $clientes[$tel] = ['nome' => $row['nome'], 'visitas' => 0, 'pax_total' => 0, 'ultima' => $row['data']];
        }
        $clientes[$tel]['visitas']++;
        $clientes[$tel]['pax_total'] += $pax;
        if($row['data'] > $clientes[$tel]['ultima']) $clientes[$tel]['ultima'] = $row['data'];
    }
}

uasort($clientes, function($a, $b) { return $b['visitas'] <=> $a['visitas']; });
$topClientes = array_slice($clientes, 0, 6);

$larguraCanvas = count($timelineData) > 31 ? (count($timelineData) * 40) . 'px' : '100%';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Business Intelligence | ReservaPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --primary: #4f46e5; --slate-100: #f1f5f9; --slate-800: #1e293b; }
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: var(--slate-800); }
        
        /* Estilo dos Cards Unificados */
        .card-combined {
            background: white; border-radius: 16px; padding: 20px;
            border: 1px solid var(--slate-100); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: 0.3s; height: 100%;
        }
        .card-combined:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .card-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; }
        .val-main { font-size: 1.75rem; font-weight: 800; display: block; line-height: 1.2; }
        .val-sub { font-size: 0.875rem; color: #64748b; display: flex; align-items: center; gap: 5px; margin-top: 5px; }

        .chart-container { background: white; border-radius: 20px; padding: 25px; border: 1px solid var(--slate-100); }
        .table-crm { font-size: 0.85rem; }
        .table-crm th { font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; border: none; }
        .badge-status { padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.7rem; }
        
        .border-pequeno { border-top: 4px solid #6366f1; }
        .border-medio { border-top: 4px solid #10b981; }
        .border-grande { border-top: 4px solid #f59e0b; }
        .border-evento { border-top: 4px solid #ec4899; }
        
        .filter-bar { background: white; padding: 15px 25px; border-radius: 100px; border: 1px solid var(--slate-100); margin-bottom: 30px; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">
    
    <!-- HEADER & FILTROS -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-800 mb-1">Dashboard Estratégico</h1>
            <p class="text-muted small">Análise de fluxo diário e comportamento do cliente.</p>
        </div>
        <form class="filter-bar d-flex gap-3 align-items-center">
            <input type="date" name="inicio" class="form-control form-control-sm border-0" value="<?= $dateStart ?>">
            <span class="text-muted">até</span>
            <input type="date" name="fim" class="form-control form-control-sm border-0" value="<?= $dateEnd ?>">
            <button class="btn btn-primary btn-sm px-4 rounded-pill fw-bold">Atualizar</button>
        </form>
    </div>

    <!-- 1. CARDS UNIFICADOS (RESERVAS + PESSOAS) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-combined border-pequeno">
                <span class="card-label">Grupos Pequenos (1-4)</span>
                <span class="val-main"><?= $grupos['pequeno']['res'] ?> <small class="fs-6 fw-normal text-muted">res.</small></span>
                <span class="val-sub"><i class="bi bi-people-fill text-primary"></i> <?= $grupos['pequeno']['pax'] ?> pessoas totais</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-combined border-medio">
                <span class="card-label">Grupos Médios (5-10)</span>
                <span class="val-main"><?= $grupos['medio']['res'] ?> <small class="fs-6 fw-normal text-muted">res.</small></span>
                <span class="val-sub"><i class="bi bi-people-fill text-success"></i> <?= $grupos['medio']['pax'] ?> pessoas totais</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-combined border-grande">
                <span class="card-label">Grupos Grandes (11-20)</span>
                <span class="val-main"><?= $grupos['grande']['res'] ?> <small class="fs-6 fw-normal text-muted">res.</small></span>
                <span class="val-sub"><i class="bi bi-people-fill text-warning"></i> <?= $grupos['grande']['pax'] ?> pessoas totais</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-combined border-evento">
                <span class="card-label">Eventos (21+)</span>
                <span class="val-main"><?= $grupos['evento']['res'] ?> <small class="fs-6 fw-normal text-muted">res.</small></span>
                <span class="val-sub"><i class="bi bi-people-fill text-danger"></i> <?= $grupos['evento']['pax'] ?> pessoas totais</span>
            </div>
        </div>
    </div>

    <!-- 2. GRÁFICO DE PALITOS (EVOLUÇÃO DIÁRIA PAX) -->
    <div class="chart-container mb-4">
        <h6 class="fw-800 mb-4">Evolução Diária de Pessoas (Pax)</h6>
        <div class="overflow-x-auto">
            <div style="height: 300px; width: <?= $larguraCanvas ?>;">
                <canvas id="paxChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- 3. CRM DETALHADO DO CLIENTE -->
        <div class="col-lg-8">
            <div class="chart-container h-100">
                <h6 class="fw-800 mb-4">Top Embaixadores & Comportamento</h6>
                <div class="table-responsive">
                    <table class="table table-crm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="text-center">Frequência</th>
                                <th class="text-center">Total Pax</th>
                                <th>Última Visita</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($topClientes as $tel => $c): 
                                $status = $c['visitas'] > 3 ? ['VIP', 'bg-primary text-white'] : ['Recorrente', 'bg-light text-dark border'];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($c['nome']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;"><?= $tel ?></div>
                                </td>
                                <td class="text-center"><span class="fw-bold"><?= $c['visitas'] ?>x</span></td>
                                <td class="text-center fw-bold text-primary"><?= $c['pax_total'] ?></td>
                                <td><?= date('d/m/Y', strtotime($c['ultima'])) ?></td>
                                <td class="text-end">
                                    <span class="badge-status <?= $status[1] ?>"><?= $status[0] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. DISTRIBUIÇÃO SEMANAL -->
        <div class="col-lg-4">
            <div class="chart-container h-100">
                <h6 class="fw-800 mb-4">Volume por Dia da Semana</h6>
                <div style="height: 300px;">
                    <canvas id="weekChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Gráfico de Palitos (Evolução Diária)
    new Chart(document.getElementById('paxChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($timelineData)) ?>,
            datasets: [{
                label: 'Total de Pessoas',
                data: <?= json_encode(array_column($timelineData, 'pax')) ?>,
                backgroundColor: '#4f46e5',
                borderRadius: 4,
                hoverBackgroundColor: '#4338ca'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { borderDash: [2, 2] } }
            }
        }
    });

    // Gráfico Semanal
    new Chart(document.getElementById('weekChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($graficos['dias_semana'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($graficos['dias_semana'])) ?>,
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { display: false } },
                y: { grid: { display: false } }
            }
        }
    });
</script>

</body>
</html>