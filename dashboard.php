<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

/* ============================================================
   1. KPIs GERAIS
============================================================ */
$sqlKpi = $pdo->prepare("
    SELECT 
        COUNT(*) as total_res,
        SUM(CASE WHEN status != 0 THEN num_pessoas ELSE 0 END) as pax_ativo,
        COUNT(CASE WHEN status = 0 THEN 1 END) as qtd_cancel,
        SUM(CASE WHEN status = 0 THEN num_pessoas ELSE 0 END) as pax_cancel,
        AVG(DATEDIFF(data, data_emissao)) as lead_time
    FROM clientes 
    WHERE empresa_id = :emp AND data BETWEEN :ini AND :fim
");
$sqlKpi->execute(['emp' => $empresa_id, 'ini' => $data_inicio, 'fim' => $data_fim]);
$kpis = $sqlKpi->fetch();

/* ============================================================
   2. FILA DE ESPERA (SENTADOS)
============================================================ */
$sqlFila = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 1 THEN 1 END) as total_sentados,
        SUM(CASE WHEN status = 1 THEN num_pessoas ELSE 0 END) as pax_sentados
    FROM fila_espera 
    WHERE empresa_id = :emp AND DATE(data_criacao) BETWEEN :ini AND :fim
");
$sqlFila->execute(['emp' => $empresa_id, 'ini' => $data_inicio, 'fim' => $data_fim]);
$fila = $sqlFila->fetch();

/* ============================================================
   3. LÓGICA DO GRÁFICO (PREENCHIMENTO DE DATAS)
============================================================ */
$sqlEvo = $pdo->prepare("
    SELECT 
        c.data, 
        SUM(c.num_pessoas) as res_pax,
        (SELECT SUM(f.num_pessoas) FROM fila_espera f WHERE f.empresa_id = c.empresa_id AND DATE(f.data_criacao) = c.data AND f.status = 1) as fila_pax
    FROM clientes c
    WHERE c.empresa_id = :emp AND c.status != 0 AND c.data BETWEEN :ini AND :fim
    GROUP BY c.data
");
$sqlEvo->execute(['emp' => $empresa_id, 'ini' => $data_inicio, 'fim' => $data_fim]);
$dados_banco = $sqlEvo->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

$labels = [];
$dataset_res = [];
$dataset_fila = [];
$periodo = new DatePeriod(new DateTime($data_inicio), new DateInterval('P1D'), (new DateTime($data_fim))->modify('+1 day'));

foreach ($periodo as $d) {
    $dateStr = $d->format('Y-m-d');
    $labels[] = $d->format('d/m');
    $dataset_res[] = (int) ($dados_banco[$dateStr]['res_pax'] ?? 0);
    $dataset_fila[] = (int) ($dados_banco[$dateStr]['fila_pax'] ?? 0);
}

/* ============================================================
   4. RANKINGS E FAIXAS
============================================================ */
$sqlTop = $pdo->prepare("SELECT nome, telefone, COUNT(*) as visitas, SUM(num_pessoas) as pax FROM clientes WHERE empresa_id = :emp AND status != 0 GROUP BY telefone ORDER BY visitas DESC LIMIT 10");
$sqlTop->execute(['emp' => $empresa_id]);
$topClientes = $sqlTop->fetchAll();

$sqlUser = $pdo->prepare("SELECT l.nome, COUNT(c.id) as qtd FROM login l LEFT JOIN clientes c ON l.id = c.usuario_id AND c.data BETWEEN :ini AND :fim WHERE l.empresa_id = :emp AND l.status = 1 GROUP BY l.id ORDER BY qtd DESC");
$sqlUser->execute(['emp' => $empresa_id, 'ini' => $data_inicio, 'fim' => $data_fim]);
$equipe = $sqlUser->fetchAll();

$sqlFaixas = $pdo->prepare("SELECT SUM(CASE WHEN num_pessoas BETWEEN 5 AND 6 THEN 1 ELSE 0 END) as f5_6, SUM(CASE WHEN num_pessoas BETWEEN 7 AND 8 THEN 1 ELSE 0 END) as f7_8, SUM(CASE WHEN num_pessoas BETWEEN 10 AND 15 THEN 1 ELSE 0 END) as f10_15, SUM(CASE WHEN num_pessoas BETWEEN 15 AND 20 THEN 1 ELSE 0 END) as f15_20, SUM(CASE WHEN num_pessoas > 20 THEN 1 ELSE 0 END) as f20_mais FROM clientes WHERE empresa_id = :emp AND status != 0 AND data BETWEEN :ini AND :fim");
$sqlFaixas->execute(['emp' => $empresa_id, 'ini' => $data_inicio, 'fim' => $data_fim]);
$faixas = $sqlFaixas->fetch();

require 'cabecalho.php';
?>

<div class="container-fluid py-4" style="background: #f4f7fa;">

    <!-- FILTROS -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h4 class="fw-bold"><i class="fas fa-chart-pie text-primary me-2"></i>Dashboard de Performance</h4>
        </div>
        <div class="col-md-6 text-end">
            <form class="d-flex justify-content-end gap-2">
                <input type="date" name="data_inicio" class="form-control w-auto shadow-sm" value="<?= $data_inicio ?>">
                <input type="date" name="data_fim" class="form-control w-auto shadow-sm" value="<?= $data_fim ?>">
                <button class="btn btn-primary shadow-sm px-4">Atualizar Dados</button>
            </form>
        </div>
    </div>

    <!-- 1. KPIs (TOPO) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-primary border-5">
                <small class="text-muted fw-bold">PÚBLICO RESERVADO</small>
                <h2 class="fw-900 m-0"><?= $kpis['pax_ativo'] ?? 0 ?></h2>
                <span class="badge bg-light text-primary border mt-1 w-fit"><?= $kpis['total_res'] ?>
                    Agendamentos</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-danger border-5">
                <small class="text-muted fw-bold">CANCELAMENTOS</small>
                <h2 class="fw-900 m-0 text-danger"><?= $kpis['qtd_cancel'] ?? 0 ?></h2>
                <small class="text-danger">Perda de <?= $kpis['pax_cancel'] ?? 0 ?> pessoas</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-success border-5">
                <small class="text-muted fw-bold">FILA (SENTADOS)</small>
                <h2 class="fw-900 m-0 text-success"><?= $fila['total_sentados'] ?? 0 ?></h2>
                <small class="text-muted"><?= $fila['pax_sentados'] ?? 0 ?> Pax convertidos</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-warning border-5">
                <small class="text-muted fw-bold">ANTECEDÊNCIA MÉDIA</small>
                <h2 class="fw-900 m-0 text-warning"><?= round($kpis['lead_time'], 1) ?> Dias</h2>
                <small class="text-muted">Lead time de reservas</small>
            </div>
        </div>
    </div>

    <!-- 2. RANKINGS (MEIO) -->
    <div class="row g-4 mb-4">
        <!-- RANKING CLIENTES -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-3 text-dark border-bottom pb-2"><i class="fas fa-award text-warning me-2"></i>Top
                    10 Clientes Fiéis (Histórico)</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="small">NOME DO CLIENTE</th>
                                <th class="text-center small">VISITAS</th>
                                <th class="text-center small">TOTAL PAX</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topClientes as $tc): ?>
                                <tr>
                                    <td><span
                                            class="fw-bold small text-uppercase"><?= htmlspecialchars($tc['nome']) ?></span>
                                    </td>
                                    <td class="text-center"><span
                                            class="badge bg-green rounded-pill px-3"><?= $tc['visitas'] ?></span></td>
                                    <td class="text-center fw-bold text-primary"><?= $tc['pax'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RANKING EQUIPE (EM CARDS) -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4 h-100">
                <h6 class="fw-bold mb-4 text-dark border-bottom pb-2"><i
                        class="fas fa-user-tie text-primary me-2"></i>Produtividade da Equipe</h6>
                <div class="row g-2">
                    <?php foreach ($equipe as $u): ?>
                        <div class="col-12">
                            <div
                                class="p-3 bg-white rounded border-start border-primary border-3 shadow-xs d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <div class="fw-bold small text-dark"><?= strtoupper($u['nome']) ?></div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Reserva Efetuada</small>
                                </div>
                                <div class="text-end">
                                    <h4 class="m-0 fw-900 text-primary"><?= $u['qtd'] ?></h4>
                                    <small class="fw-bold text-muted" style="font-size: 0.5rem;">TOTAL</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. GRÁFICOS (FUNDO - POWER BI STYLE) -->
    <div class="row g-4">
        <!-- GRÁFICO GRANDE DE BARRAS -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar text-primary me-2"></i>Tendência Diária: Reservas
                    vs. Fila</h6>
                <div style="position: relative; height: 350px;">
                    <canvas id="chartEvolucao"></canvas>
                </div>
            </div>
        </div>

        <!-- GRÁFICO DONUT -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold mb-3 text-center">Perfil de Grupos (Pax)</h6>
                <div style="position: relative; height: 350px;">
                    <canvas id="chartFaixas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-900 {
        font-weight: 900;
    }

    .card {
        border-radius: 15px;
        transition: 0.3s;
    }

    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08) !important;
    }

    .w-fit {
        width: fit-content;
    }

    .shadow-xs {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // GRÁFICO DE PALITOS (BARRA)
    new Chart(document.getElementById('chartEvolucao'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Reservas',
                    data: <?= json_encode($dataset_res) ?>,
                    backgroundColor: '#0d6efd',
                    borderRadius: 5
                },
                {
                    label: 'Fila (Sentados)',
                    data: <?= json_encode($dataset_fila) ?>,
                    backgroundColor: '#34c759',
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } }
            }
        }
    });

    // GRÁFICO PERFIL GRUPOS (DONUT)
    new Chart(document.getElementById('chartFaixas'), {
        type: 'doughnut',
        data: {
            labels: ['5-6', '7-8', '10-15', '15-20', '20+'],
            datasets: [{
                data: [<?= (int) $faixas['f5_6'] ?>, <?= (int) $faixas['f7_8'] ?>, <?= (int) $faixas['f10_15'] ?>, <?= (int) $faixas['f15_20'] ?>, <?= (int) $faixas['f20_mais'] ?>],
                backgroundColor: ['#0d6efd', '#6610f2', '#fd7e14', '#dc3545', '#198754'],
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>