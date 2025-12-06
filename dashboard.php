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

// Gerar array de todos os dias do intervalo (para o gráfico não ter buracos)
$periodo = new DatePeriod(
    new DateTime($dateStart),
    new DateInterval('P1D'),
    (new DateTime($dateEnd))->modify('+1 day')
);
$timelineData = [];
foreach ($periodo as $dt) {
    $timelineData[$dt->format("Y-m-d")] = ['qtd' => 0, 'pax' => 0];
}

/* ==========================================================
   2. CONSULTA AO BANCO
========================================================== */
$stmt = $pdo->prepare("
    SELECT id, nome, data, num_pessoas, horario, telefone, 
           tipo_evento, forma_pagamento, observacoes, 
           data_emissao, status, motivo_cancelamento
    FROM clientes
    WHERE data BETWEEN :inicio AND :fim
    ORDER BY data ASC
");
$stmt->execute(['inicio' => $dateStart, 'fim' => $dateEnd]);
$dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   3. PROCESSAMENTO DE DADOS (BI)
========================================================== */

// --- KPI GERAIS ---
$kpis = [
    'total_reservas' => 0, 'total_pax' => 0, 'canceladas' => 0,
    'antecedencia_soma' => 0, 'antecedencia_qtd' => 0
];

// --- GRUPOS (OS 8 CARDS) ---
$grupos = [
    '2_4'   => ['reservas' => 0, 'pax' => 0], // Pequenos (Casais/Famílias)
    '5_10'  => ['reservas' => 0, 'pax' => 0], // Médios
    '11_20' => ['reservas' => 0, 'pax' => 0], // Grandes
    '21_plus' => ['reservas' => 0, 'pax' => 0] // Eventos
];

// --- GRÁFICOS ---
$graficos = [
    'pagamento' => [], 'eventos' => [], 'motivos_cancel' => [],
    'dias_semana' => array_fill_keys(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'], 0)
];

$clientes = []; // Para cálculo de fidelidade

foreach ($dados as $row) {
    $pax = (int)$row['num_pessoas'];
    
    // Totalizadores Gerais
    $kpis['total_reservas']++;
    $kpis['total_pax'] += $pax;

    // 1. Análise de Cancelamento
    if ($row['status'] == 0 || !empty($row['motivo_cancelamento'])) { 
        $kpis['canceladas']++;
        $motivo = $row['motivo_cancelamento'] ?: 'Não informado';
        $graficos['motivos_cancel'][$motivo] = ($graficos['motivos_cancel'][$motivo] ?? 0) + 1;
    } else {
        // --- DADOS DE RESERVAS VÁLIDAS ---

        // 2. Timeline Diária
        if (isset($timelineData[$row['data']])) {
            $timelineData[$row['data']]['qtd']++;
            $timelineData[$row['data']]['pax'] += $pax;
        }

        // 3. Classificação por Grupos (Os 8 Cards)
        if ($pax <= 4) {
            $grupos['2_4']['reservas']++;
            $grupos['2_4']['pax'] += $pax;
        } elseif ($pax <= 10) {
            $grupos['5_10']['reservas']++;
            $grupos['5_10']['pax'] += $pax;
        } elseif ($pax <= 20) {
            $grupos['11_20']['reservas']++;
            $grupos['11_20']['pax'] += $pax;
        } else {
            $grupos['21_plus']['reservas']++;
            $grupos['21_plus']['pax'] += $pax;
        }

        // 4. Pagamento e Eventos
        $pag = $row['forma_pagamento'] ?: 'Não def.';
        $graficos['pagamento'][$pag] = ($graficos['pagamento'][$pag] ?? 0) + 1;

        $evt = $row['tipo_evento'] ?: 'Normal';
        $graficos['eventos'][$evt] = ($graficos['eventos'][$evt] ?? 0) + 1;

        // 5. Dia da Semana
        $diaSemana = ["Sun"=>"Dom","Mon"=>"Seg","Tue"=>"Ter","Wed"=>"Qua","Thu"=>"Qui","Fri"=>"Sex","Sat"=>"Sáb"];
        $d = date('D', strtotime($row['data']));
        $graficos['dias_semana'][$diaSemana[$d]] += $pax;
    }

    // 6. Lead Time (Antecedência)
    if (!empty($row['data_emissao'])) {
        $dtReserva = new DateTime($row['data']);
        $dtEmissao = new DateTime($row['data_emissao']);
        $dtReserva->setTime(0,0); $dtEmissao->setTime(0,0);
        if ($dtReserva >= $dtEmissao) {
            $diff = $dtEmissao->diff($dtReserva);
            $kpis['antecedencia_soma'] += $diff->days;
            $kpis['antecedencia_qtd']++;
        }
    }

    // 7. Fidelidade (Agrupamento por telefone)
    $tel = preg_replace('/\D/', '', $row['telefone']);
    if(!empty($tel)) {
        if(!isset($clientes[$tel])) $clientes[$tel] = ['datas' => []];
        $clientes[$tel]['datas'][] = $row['data'];
    }
}

// --- CÁLCULOS FINAIS ---
$mediaAntecedencia = $kpis['antecedencia_qtd'] > 0 ? round($kpis['antecedencia_soma'] / $kpis['antecedencia_qtd']) : 0;
$taxaCancelamento = $kpis['total_reservas'] > 0 ? round(($kpis['canceladas'] / $kpis['total_reservas']) * 100, 1) : 0;

// Cálculo Média de Retorno (Frequência)
$somaDias = 0; $qtdRetornos = 0;
foreach ($clientes as $cli) {
    if (count($cli['datas']) > 1) {
        sort($cli['datas']);
        for ($i = 1; $i < count($cli['datas']); $i++) {
            $d1 = new DateTime($cli['datas'][$i-1]);
            $d2 = new DateTime($cli['datas'][$i]);
            $somaDias += $d1->diff($d2)->days;
            $qtdRetornos++;
        }
    }
}
$cicloRetorno = $qtdRetornos > 0 ? round($somaDias / $qtdRetornos) : "—";

// Lógica de largura do gráfico de timeline
$numDiasGrafico = count($timelineData);
$larguraCanvas = $numDiasGrafico > 31 ? ($numDiasGrafico * 40) . 'px' : '100%';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { --bg-body: #f3f4f6; --primary: #4361ee; }
        body { background-color: var(--bg-body); font-family: 'Segoe UI', sans-serif; padding-bottom: 50px; }
        
        /* CARD PRINCIPAL */
        .stat-card {
            background: white; border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); border: none; height: 100%;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 10px; }
        .stat-val { font-size: 1.8rem; font-weight: 800; color: #1e293b; line-height: 1; }
        .stat-lbl { color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-desc { font-size: 0.8rem; color: #94a3b8; margin-top: 5px; }

        /* CARDS DE GRUPOS (Os 8 cards) */
        .group-card {
            background: white; border-left: 4px solid #ccc; border-radius: 8px; padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); margin-bottom: 15px;
        }
        .gc-title { font-size: 0.85rem; font-weight: 700; color: #555; text-transform: uppercase; }
        .gc-val { font-size: 1.5rem; font-weight: 800; color: #333; }
        .gc-sub { font-size: 0.8rem; color: #888; }
        
        /* Cores dos Grupos */
        .border-blue { border-left-color: #3b82f6; }
        .border-green { border-left-color: #10b981; }
        .border-orange { border-left-color: #f59e0b; }
        .border-purple { border-left-color: #8b5cf6; }

        /* Gráfico Scroll */
        .scroll-chart-container {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 10px;
        }
        .chart-wrapper-scroll {
            height: 350px;
            display: inline-block;
        }

        /* Títulos de Seção */
        .section-header { font-size: 1.1rem; font-weight: 700; color: #374151; margin: 30px 0 15px 0; border-left: 4px solid var(--primary); padding-left: 10px; }
        
        .filter-bar { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;}
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">
    
    <!-- 1. HEADER E FILTROS -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold m-0"><i class="bi bi-bar-chart-line-fill text-primary"></i> Analytics do Restaurante</h3>
            <small class="text-muted">Análise estratégica de demanda, grupos e fidelidade.</small>
        </div>
        <div class="col-md-6">
            <form method="GET" class="filter-bar justify-content-end">
                <div>
                    <label class="small fw-bold">Data Início</label>
                    <input type="date" name="inicio" class="form-control form-control-sm" value="<?= $dateStart ?>">
                </div>
                <div>
                    <label class="small fw-bold">Data Fim</label>
                    <input type="date" name="fim" class="form-control form-control-sm" value="<?= $dateEnd ?>">
                </div>
                <button class="btn btn-primary btn-sm fw-bold px-4"><i class="bi bi-filter"></i> Filtrar</button>
            </form>
        </div>
    </div>

    <!-- 2. KPI GERAIS (LINHA 1) -->
    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-lbl">Reservas Totais</div>
                        <div class="stat-val"><?= $kpis['total_reservas'] ?></div>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-check"></i></div>
                </div>
                <div class="stat-desc">Total de <?= $kpis['total_pax'] ?> pessoas esperadas.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-lbl">Antecedência</div>
                        <div class="stat-val"><?= $mediaAntecedencia ?> <span class="fs-6 text-muted">dias</span></div>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="stat-desc">Média entre ligar e consumir.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-lbl">Ciclo de Retorno</div>
                        <div class="stat-val"><?= $cicloRetorno ?> <span class="fs-6 text-muted">dias</span></div>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-repeat"></i></div>
                </div>
                <div class="stat-desc">Frequência média do cliente fiel.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-lbl">Taxa Cancelamento</div>
                        <div class="stat-val text-danger"><?= $taxaCancelamento ?>%</div>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
                </div>
                <div class="stat-desc"><?= $kpis['canceladas'] ?> reservas canceladas.</div>
            </div>
        </div>
    </div>

    <!-- 3. ANÁLISE PROFUNDA DE GRUPOS (OS 8 CARDS PEDIDOS) -->
    <div class="section-header">Segmentação por Tamanho de Grupo</div>
    <div class="row g-3">
        <!-- Pequenos (1-4) -->
        <div class="col-md-3">
            <div class="group-card border-blue">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="gc-title text-primary">Pequenos (1-4)</div>
                        <div class="gc-val"><?= $grupos['2_4']['reservas'] ?> <small class="fs-6 text-muted">reservas</small></div>
                    </div>
                    <i class="bi bi-person text-primary fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="group-card border-blue">
                <div class="gc-title text-primary">Pessoas (Vol. 1-4)</div>
                <div class="gc-val"><?= $grupos['2_4']['pax'] ?> <small class="fs-6 text-muted">pax</small></div>
                <div class="gc-sub">Alta rotatividade de mesa.</div>
            </div>
        </div>

        <!-- Médios (5-10) -->
        <div class="col-md-3">
            <div class="group-card border-green">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="gc-title text-success">Médios (5-10)</div>
                        <div class="gc-val"><?= $grupos['5_10']['reservas'] ?> <small class="fs-6 text-muted">reservas</small></div>
                    </div>
                    <i class="bi bi-people text-success fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="group-card border-green">
                <div class="gc-title text-success">Pessoas (Vol. 5-10)</div>
                <div class="gc-val"><?= $grupos['5_10']['pax'] ?> <small class="fs-6 text-muted">pax</small></div>
                <div class="gc-sub">Ideal para junção de 2 mesas.</div>
            </div>
        </div>

        <!-- Grandes (11-20) -->
        <div class="col-md-3">
            <div class="group-card border-orange">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="gc-title text-warning">Grandes (11-20)</div>
                        <div class="gc-val"><?= $grupos['11_20']['reservas'] ?> <small class="fs-6 text-muted">reservas</small></div>
                    </div>
                    <i class="bi bi-people-fill text-warning fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="group-card border-orange">
                <div class="gc-title text-warning">Pessoas (Vol. 11-20)</div>
                <div class="gc-val"><?= $grupos['11_20']['pax'] ?> <small class="fs-6 text-muted">pax</small></div>
                <div class="gc-sub">Exige garçom dedicado.</div>
            </div>
        </div>

        <!-- Eventos (21+) -->
        <div class="col-md-3">
            <div class="group-card border-purple">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="gc-title text-info">Eventos (21+)</div>
                        <div class="gc-val"><?= $grupos['21_plus']['reservas'] ?> <small class="fs-6 text-muted">reservas</small></div>
                    </div>
                    <i class="bi bi-balloon-fill text-info fs-3 opacity-50"></i>
                </div>
            </div>
            <div class="group-card border-purple">
                <div class="gc-title text-info">Pessoas (Vol. 21+)</div>
                <div class="gc-val"><?= $grupos['21_plus']['pax'] ?> <small class="fs-6 text-muted">pax</small></div>
                <div class="gc-sub">Área exclusiva necessária.</div>
            </div>
        </div>
    </div>

    <!-- 4. TIMELINE COM SCROLL (EVOLUÇÃO DIÁRIA) -->
    <div class="section-header">Fluxo Diário (Todos os Dias)</div>
    <div class="card p-3 shadow-sm border-0">
        <div class="scroll-chart-container">
            <!-- A largura é definida dinamicamente no PHP -->
            <div class="chart-wrapper-scroll" style="width: <?= $larguraCanvas ?>;">
                <canvas id="chartTimeline"></canvas>
            </div>
        </div>
        <small class="text-muted mt-2 text-center d-block"><i class="bi bi-arrows-expand"></i> Role horizontalmente para ver todo o período caso ultrapasse 30 dias.</small>
    </div>

    <!-- 5. GRÁFICOS OPERACIONAIS INFERIORES -->
    <div class="row g-3 mt-3">
        <!-- Pagamento -->
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Preferência de Pagamento</h6>
                <div style="height: 250px;"><canvas id="chartPagamento"></canvas></div>
                <div class="mt-2 small text-muted text-center">*Conta Única agiliza o checkout em 40%.</div>
            </div>
        </div>

        <!-- Motivos Cancelamento -->
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="fw-bold mb-3 border-bottom pb-2 text-danger">Top Motivos Cancelamento</h6>
                <?php if(empty($graficos['motivos_cancel'])): ?>
                    <div class="text-center py-5 text-muted">Sem dados de cancelamento</div>
                <?php else: ?>
                    <div class="table-responsive" style="max-height:250px; overflow:auto;">
                        <table class="table table-sm table-hover align-middle">
                            <?php arsort($graficos['motivos_cancel']); foreach($graficos['motivos_cancel'] as $m => $q): ?>
                            <tr><td><?= $m ?></td><td class="text-end fw-bold"><?= $q ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dias da Semana -->
        <div class="col-md-4">
            <div class="stat-card">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Fluxo Semanal (Pessoas)</h6>
                <div style="height: 250px;"><canvas id="chartSemana"></canvas></div>
            </div>
        </div>
    </div>

</div>

<!-- SCRIPTS CHART.JS -->
<script>
    Chart.defaults.font.family = "'Segoe UI', sans-serif";
    Chart.defaults.maintainAspectRatio = false;

    // --- GRÁFICO TIMELINE (SCROLL) ---
    const ctxTimeline = document.getElementById('chartTimeline');
    new Chart(ctxTimeline, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($timelineData)) ?>,
            datasets: [
                {
                    label: 'Reservas',
                    data: <?= json_encode(array_column($timelineData, 'qtd')) ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    yAxisID: 'y',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Pessoas (Pax)',
                    data: <?= json_encode(array_column($timelineData, 'pax')) ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'transparent',
                    yAxisID: 'y1',
                    borderDash: [5, 5],
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { type: 'linear', display: true, position: 'left', title: {display:true, text:'Qtd Reservas'} },
                y1: { type: 'linear', display: true, position: 'right', grid: {drawOnChartArea: false}, title: {display:true, text:'Total Pessoas'} }
            },
            plugins: { legend: { position: 'top' } }
        }
    });

    // --- GRÁFICO PAGAMENTO ---
    new Chart(document.getElementById('chartPagamento'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($graficos['pagamento'])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($graficos['pagamento'])) ?>,
                backgroundColor: ['#4361ee', '#10b981', '#f59e0b', '#ef4444']
            }]
        },
        options: { cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });

    // --- GRÁFICO SEMANA ---
    new Chart(document.getElementById('chartSemana'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($graficos['dias_semana'])) ?>,
            datasets: [{
                label: 'Volume de Pessoas',
                data: <?= json_encode(array_values($graficos['dias_semana'])) ?>,
                backgroundColor: '#4361ee',
                borderRadius: 4
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });
</script>

</body>
</html>