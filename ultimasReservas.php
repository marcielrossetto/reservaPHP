<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';

/* ==========================
   LÓGICA DE FILTROS
========================== */

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Padrão: dia 1 do mês atual
$data_fim    = $_GET['data_fim'] ?? date('Y-m-t');     // Padrão: último dia do mês atual

// Monta Condição
$where = " WHERE 1=1 ";
$params = [];

if ($data_inicio && $data_fim) {
    // Filtra pela DATA DE EMISSÃO (Criação)
    $where .= " AND DATE(data_emissao) BETWEEN :ini AND :fim ";
    $params[':ini'] = $data_inicio;
    $params[':fim'] = $data_fim;
}

/* ==========================
   CONSULTAS AO BANCO
========================== */

// 1. Busca Totais (Reservas e Pessoas)
$sqlTotais = $pdo->prepare("SELECT COUNT(*) AS total_reservas, SUM(num_pessoas) AS total_pax FROM clientes $where");
$sqlTotais->execute($params);
$totais = $sqlTotais->fetch(PDO::FETCH_ASSOC);

$totalReservas = $totais['total_reservas'] ?? 0;
$totalPessoas  = $totais['total_pax'] ?? 0;

// 2. Busca Lista Detalhada
$sql = $pdo->prepare("
    SELECT clientes.*,
           COALESCE((SELECT nome FROM login WHERE id = clientes.usuario_id LIMIT 1), 'Sistema/Site') AS nome_criador
    FROM clientes
    $where
    ORDER BY data_emissao DESC
");
$sql->execute($params);
$reservas = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* VARIÁVEIS E GERAL */
    body { background-color: #f4f6f9; }
    .main-wrapper { max-width: 1000px; margin: 20px auto; padding: 15px; }

    /* BARRA DE ESTATÍSTICAS */
    .stats-container {
        display: flex; gap: 15px; margin-bottom: 20px;
    }
    .stat-card {
        flex: 1; background: #fff; padding: 15px; border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 4px solid #ccc;
        display: flex; align-items: center; gap: 15px;
    }
    .stat-card.blue { border-left-color: #3b82f6; }
    .stat-card.orange { border-left-color: #f59e0b; }
    
    .stat-icon {
        width: 45px; height: 45px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .bg-icon-blue { background: #eff6ff; color: #3b82f6; }
    .bg-icon-orange { background: #fff7ed; color: #f59e0b; }

    .stat-info .label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; font-weight: bold; }
    .stat-info .value { font-size: 1.25rem; font-weight: 800; color: #1f2937; line-height: 1; }

    /* FILTRO */
    .filter-box {
        background: #fff; padding: 15px; border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03); margin-bottom: 20px;
    }
    .filter-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 150px; }
    .form-group label { font-size: 0.8rem; font-weight: bold; color: #555; display: block; margin-bottom: 4px; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; }
    .btn-filtrar {
        background: #3b82f6; color: white; border: none; padding: 9px 20px;
        border-radius: 6px; font-weight: bold; cursor: pointer; flex: 0 0 auto;
    }
    .btn-filtrar:hover { background: #2563eb; }

    /* LISTA DE CARDS */
    .reserva-item {
        background: #fff; border-radius: 10px; padding: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03); margin-bottom: 12px;
        border: 1px solid #e5e7eb; position: relative;
    }

    .item-header {
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px dashed #eee; padding-bottom: 8px; margin-bottom: 8px;
    }
    .client-name { font-size: 1rem; font-weight: 700; color: #111827; }
    .client-phone { font-size: 0.85rem; color: #6b7280; }
    
    .item-body {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;
        font-size: 0.85rem; color: #374151;
    }
    .info-point { display: flex; align-items: center; gap: 6px; }
    .info-point i { color: #9ca3af; width: 16px; text-align: center; }

    /* OBS BOX */
    .obs-container {
        margin-top: 10px; background: #f9fafb; border: 1px solid #f3f4f6;
        border-radius: 6px; padding: 8px; font-size: 0.8rem; color: #4b5563;
        max-height: 60px; overflow-y: auto;
    }
    .obs-container::-webkit-scrollbar { width: 4px; }
    .obs-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* CREATOR TAG */
    .creator-tag {
        font-size: 0.7rem; background: #e0e7ff; color: #4338ca;
        padding: 2px 8px; border-radius: 12px; font-weight: 600;
        display: inline-block;
    }
</style>

<div class="main-wrapper">

    <h3 class="mb-3" style="font-weight: 700; color: #333;">Relatório de Emissões</h3>

    <!-- 1. TOTAIS -->
    <div class="stats-container">
        <div class="stat-card blue">
            <div class="stat-icon bg-icon-blue"><i class="fas fa-clipboard-check"></i></div>
            <div class="stat-info">
                <div class="label">Reservas Criadas</div>
                <div class="value"><?= $totalReservas ?></div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon bg-icon-orange"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="label">Pessoas (Pax)</div>
                <div class="value"><?= $totalPessoas ?></div>
            </div>
        </div>
    </div>

    <!-- 2. FILTRO -->
    <div class="filter-box">
        <form method="GET">
            <div class="filter-row">
                <div class="form-group">
                    <label><i class="far fa-calendar-alt"></i> De (Criação)</label>
                    <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label><i class="far fa-calendar-alt"></i> Até (Criação)</label>
                    <input type="date" name="data_fim" value="<?= $data_fim ?>" class="form-control">
                </div>
                <button type="submit" class="btn-filtrar"><i class="fas fa-search"></i> Filtrar</button>
            </div>
        </form>
    </div>

    <!-- 3. LISTA -->
    <?php if ($totalReservas > 0): ?>
        <?php foreach ($reservas as $r): 
            $dataReserva = date("d/m/Y", strtotime($r['data'])) . " às " . substr($r['horario'], 0, 5);
            $dataCriacao = date("d/m/Y H:i", strtotime($r['data_emissao']));
        ?>
        
        <div class="reserva-item">
            <!-- Cabeçalho do Card -->
            <div class="item-header">
                <div>
                    <div class="client-name"><?= ucwords(strtolower($r['nome'])) ?></div>
                    <div class="client-phone"><i class="fab fa-whatsapp text-success"></i> <?= $r['telefone'] ?></div>
                </div>
                <div class="text-right">
                    <span class="creator-tag"><i class="fas fa-user-edit"></i> <?= $r['nome_criador'] ?></span>
                </div>
            </div>

            <!-- Corpo do Card -->
            <div class="item-body">
                <div class="info-point">
                    <i class="fas fa-calendar-day" title="Data da Reserva"></i> 
                    <strong>Reserva:</strong> <?= $dataReserva ?>
                </div>
                <div class="info-point">
                    <i class="fas fa-users" title="Qtd Pessoas"></i> 
                    <strong>Pax:</strong> <?= $r['num_pessoas'] ?> pessoas
                </div>
                <div class="info-point">
                    <i class="fas fa-history" title="Data de Criação"></i> 
                    <strong>Criado em:</strong> <?= $dataCriacao ?>
                </div>
            </div>

            <!-- Observações (se houver) -->
            <?php if (!empty($r['observacoes'])): ?>
            <div class="obs-container">
                <strong><i class="far fa-comment-dots"></i> Obs:</strong> <?= nl2br(htmlspecialchars($r['observacoes'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #999;">
            <i class="far fa-folder-open" style="font-size: 3rem; margin-bottom: 10px;"></i>
            <p>Nenhuma reserva encontrada neste período.</p>
        </div>
    <?php endif; ?>

</div>

