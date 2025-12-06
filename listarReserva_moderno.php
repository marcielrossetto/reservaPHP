<?php
session_start();
require 'config.php';
require 'cabecalho.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

/* ===========================
   FILTROS & PARÂMETROS
=========================== */

$data_inicio = $_GET['data_inicio'] ?? "";
$data_fim    = $_GET['data_fim'] ?? "";
$busca       = $_GET['busca'] ?? "";

/* ===========================
   MONTA QUERY
=========================== */

$where = " WHERE 1=1 ";
$params = [];

if ($data_inicio && $data_fim) {
    $where .= " AND data BETWEEN :inicio AND :fim";
    $params[':inicio'] = $data_inicio;
    $params[':fim'] = $data_fim;
}

if ($busca) {
    $where .= " AND (nome LIKE :busca OR telefone LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

/* ===========================
   CONSULTA BD
=========================== */

// Usando prepare para segurança
$stmt = $pdo->prepare("SELECT * FROM clientes $where ORDER BY data DESC, id DESC");
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   LÓGICA DE INTELEGÊNCIA (PROCESSAMENTO)
=========================== */

// Variáveis para dashboard
$totalReservas = count($reservas);
$totalPessoas  = 0;
$ativos        = 0;
$cancelados    = 0;

// Agrupamento para cálculo de frequência
$historicoClientes = [];

foreach ($reservas as $r) {
    // Somatórias
    $totalPessoas += (int)$r['num_pessoas'];
    if ($r['status'] == 1) $ativos++; else $cancelados++;

    // Agrupar datas por telefone (identificador único)
    $tel = $r['telefone'];
    if (!isset($historicoClientes[$tel])) {
        $historicoClientes[$tel] = [];
    }
    $historicoClientes[$tel][] = $r['data'];
}

// Cálculo da Média de Retorno (Frequency)
$frequenciaCliente = [];

foreach ($historicoClientes as $tel => $datas) {
    $qtd = count($datas);
    
    if ($qtd <= 1) {
        $frequenciaCliente[$tel] = [
            'tipo' => 'Novo / Único',
            'media_dias' => 0,
            'badge_class' => 'bg-info'
        ];
    } else {
        // Ordena datas para calcular intervalo
        rsort($datas); 
        $intervalos = [];
        
        for ($i = 0; $i < count($datas) - 1; $i++) {
            $d1 = new DateTime($datas[$i]);
            $d2 = new DateTime($datas[$i+1]);
            $diff = $d1->diff($d2);
            $intervalos[] = $diff->days;
        }

        // Média de dias entre visitas
        $media = count($intervalos) > 0 ? array_sum($intervalos) / count($intervalos) : 0;
        $media = round($media);

        $frequenciaCliente[$tel] = [
            'tipo' => "Recorrente (Volta a cada ~$media dias)",
            'media_dias' => $media,
            'badge_class' => 'bg-purple' // Classe CSS personalizada
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestão de Reservas Inteligente</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    :root {
        --bg-color: #f0f2f5;
        --card-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --primary-color: #3b82f6;
    }

    body {
        background-color: var(--bg-color);
        font-family: 'Inter', system-ui, sans-serif;
        color: var(--text-primary);
        padding-bottom: 50px;
    }

    /* KPI DASHBOARD */
    .kpi-card {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border-left: 5px solid var(--primary-color);
        transition: transform 0.2s;
    }
    .kpi-card:hover { transform: translateY(-3px); }
    .kpi-title { font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; }
    .kpi-value { font-size: 1.75rem; font-weight: 700; margin-top: 5px; }

    /* FILTROS */
    .filter-section {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        margin-bottom: 30px;
    }

    /* GRID DE RESERVAS */
    .reserva-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 20px;
    }

    /* CARD DA RESERVA */
    .card-reserva {
        background: var(--card-bg);
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .card-reserva:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .card-header-custom {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #475569;
        margin-right: 12px;
    }

    .card-body-custom {
        padding: 20px;
    }

    .info-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    .info-row i { width: 25px; color: var(--primary-color); }

    /* BADGES */
    .badge-status-1 { background-color: #dcfce7; color: #166534; } /* Ativo */
    .badge-status-0 { background-color: #fee2e2; color: #991b1b; } /* Cancelado */
    .bg-purple { background-color: #f3e8ff; color: #6b21a8; } /* Recorrente */
    
    .frequency-tag {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 10px;
        font-weight: 600;
    }

    .action-bar {
        background: #f8fafc;
        padding: 12px 20px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
    }
    
    .btn-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        transition: 0.2s;
        color: white;
    }
    .btn-whats { background: #25D366; }
    .btn-whats:hover { background: #1da851; }
    .btn-edit { background: #3b82f6; }
    .btn-del { background: #ef4444; }
    .btn-view { background: #64748b; }

</style>
</head>
<body>

<div class="container mt-5">

    <!-- CABEÇALHO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Gestão de Reservas</h2>
            <p class="text-muted small">Acompanhamento e frequência de clientes</p>
        </div>
        <a href="nova_reserva.php" class="btn btn-primary rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i> Nova Reserva
        </a>
    </div>

    <!-- KPI DASHBOARD -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="kpi-card" style="border-color: #3b82f6;">
                <div class="kpi-title">Total Reservas</div>
                <div class="kpi-value"><?= $totalReservas ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="border-color: #166534;">
                <div class="kpi-title">Confirmadas</div>
                <div class="kpi-value text-success"><?= $ativos ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="border-color: #ef4444;">
                <div class="kpi-title">Canceladas</div>
                <div class="kpi-value text-danger"><?= $cancelados ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="border-color: #8b5cf6;">
                <div class="kpi-title">Pessoas (Total)</div>
                <div class="kpi-value" style="color:#6b21a8"><?= $totalPessoas ?></div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Buscar Cliente</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="busca" class="form-control border-start-0" 
                           placeholder="Nome ou telefone..." value="<?= htmlspecialchars($busca) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="<?= $data_inicio ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?= $data_fim ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100 fw-bold">Filtrar</button>
            </div>
        </form>
    </div>

    <!-- LISTAGEM (GRID) -->
    <div class="reserva-grid">
    <?php foreach ($reservas as $r): 
        $id = $r['id'];
        $nome = htmlspecialchars($r['nome']);
        $tel = $r['telefone'];
        $status = $r['status']; // 1 = ativo, 0 = cancelado
        $statusClass = $status == 1 ? 'badge-status-1' : 'badge-status-0';
        $statusLabel = $status == 1 ? 'Confirmado' : 'Cancelado';
        
        // Dados de Frequência calculados anteriormente
        $freqData = $frequenciaCliente[$tel] ?? ['tipo'=>'Novo', 'badge_class'=>'bg-secondary'];

        // Link Whats
        $cleanTel = preg_replace('/\D/', '', $tel);
        $whatsMsg = urlencode("Olá $nome, confirmando sua reserva para " . date('d/m', strtotime($r['data'])));
        $whatsLink = "https://wa.me/55$cleanTel?text=$whatsMsg";
    ?>
    
    <div class="card-reserva">
        <div class="card-header-custom">
            <div class="d-flex align-items-center">
                <div class="user-avatar">
                    <?= strtoupper(substr($nome, 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-bold text-dark"><?= $nome ?></div>
                    <span class="badge rounded-pill <?= $statusClass ?>"><?= $statusLabel ?></span>
                </div>
            </div>
        </div>

        <div class="card-body-custom">
            <div class="info-row">
                <i class="fa-regular fa-calendar"></i> 
                <strong><?= date('d/m/Y', strtotime($r['data'])) ?></strong> 
                <span class="ms-2 text-muted">às <?= substr($r['horario'], 0, 5) ?></span>
            </div>
            <div class="info-row">
                <i class="fa-solid fa-users"></i> 
                <span><?= $r['num_pessoas'] ?> pessoas</span>
            </div>
            <div class="info-row">
                <i class="fa-solid fa-phone"></i> 
                <span><?= $tel ?></span>
            </div>

            <!-- FREQUÊNCIA DE RESERVA -->
            <div class="frequency-tag <?= $freqData['badge_class'] ?>">
                <i class="fa-solid fa-chart-line me-1"></i> <?= $freqData['tipo'] ?>
            </div>
        </div>

        <div class="action-bar">
            <a href="<?= $whatsLink ?>" target="_blank" class="btn-icon btn-whats" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="editar_reserva.php?id=<?= $id ?>" class="btn-icon btn-edit" title="Editar"><i class="fa-solid fa-pen"></i></a>
            <button class="btn-icon btn-view" onclick="openDetails(<?= htmlspecialchars(json_encode($r)) ?>)" title="Ver Detalhes"><i class="fa-solid fa-eye"></i></button>
            <a href="excluir_reserva.php?id=<?= $id ?>" class="btn-icon btn-del" onclick="return confirm('Tem certeza?')" title="Excluir"><i class="fa-solid fa-trash"></i></a>
        </div>
    </div>

    <?php endforeach; ?>
    </div>
    
    <?php if(empty($reservas)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-folder-open fa-3x mb-3"></i>
            <p>Nenhuma reserva encontrada para este filtro.</p>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL DE DETALHES -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold" id="modalNome">Detalhes da Reserva</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Mesa:</span>
                <strong id="modalMesa"></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Evento:</span>
                <strong id="modalEvento"></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Valor Rodízio:</span>
                <strong class="text-success" id="modalValor"></strong>
            </li>
            <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Forma Pagto:</span>
                <strong id="modalPagto"></strong>
            </li>
            <li class="list-group-item">
                <span class="text-muted d-block mb-1">Observações:</span>
                <div class="bg-light p-2 rounded small" id="modalObs"></div>
            </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openDetails(data) {
        document.getElementById('modalNome').innerText = data.nome;
        document.getElementById('modalMesa').innerText = data.num_mesa || 'N/A';
        document.getElementById('modalEvento').innerText = data.tipo_evento || 'Padrão';
        document.getElementById('modalValor').innerText = 'R$ ' + data.valor_rodizio;
        document.getElementById('modalPagto').innerText = data.forma_pagamento;
        document.getElementById('modalObs').innerText = data.observacoes || 'Sem observações.';
        
        var myModal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
        myModal.show();
    }
</script>

</body>
</html>