<?php
session_start();
require 'config.php';

// Segurança
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';

/* ================== CONFIGURAÇÕES E FILTROS ================== */

// Paginação
$porPagina   = 15;
$paginaAtual = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset      = ($paginaAtual - 1) * $porPagina;

// Parâmetros de Filtro
$data_inicio = $_GET['data_inicio'] ?? "";
$data_fim    = $_GET['data_fim'] ?? "";
$hora_inicio = $_GET['hora_inicio'] ?? "";
$hora_fim    = $_GET['hora_fim'] ?? "";
$busca       = $_GET['busca'] ?? "";
$verCanceladas = isset($_GET['canceladas']) && $_GET['canceladas'] == '1';

/* ================== CONSTRUÇÃO DA QUERY ================== */

$where = " WHERE 1=1 ";
$params = [];

// Filtros de Data/Hora/Busca
if ($data_inicio && $data_fim) {
    $where .= " AND data BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $data_inicio;
    $params[':data_fim'] = $data_fim;
}
if ($hora_inicio && $hora_fim) {
    $where .= " AND horario BETWEEN :hora_inicio AND :hora_fim";
    $params[':hora_inicio'] = $hora_inicio;
    $params[':hora_fim'] = $hora_fim;
}
if ($busca) {
    $where .= " AND (nome LIKE :busca OR telefone LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

// Filtro de Status (Ativo ou Cancelado)
if ($verCanceladas) {
    $where .= " AND status = 0"; // Apenas canceladas
    $tituloPagina = "Reservas Canceladas";
    $corBadge = "#fee2e2";
    $corTextoBadge = "#991b1b";
} else {
    $where .= " AND status <> 0"; // Apenas ativas
    $tituloPagina = "Reservas Ativas";
    $corBadge = "#dbeafe";
    $corTextoBadge = "#1e40af";
}

/* ================== CONSULTAS AO BANCO ================== */

// 1. Total de registros (para paginação)
$sqlCount = $pdo->prepare("SELECT COUNT(*) as total FROM clientes {$where}");
$sqlCount->execute($params);
$totalRegistros = $sqlCount->fetch()['total'];
$totalPaginas   = ceil($totalRegistros / $porPagina);

// 2. Soma de pessoas
$sqlSoma = $pdo->prepare("SELECT SUM(num_pessoas) as soma FROM clientes {$where}");
$sqlSoma->execute($params);
$total_pessoas = $sqlSoma->fetch()['soma'] ?? 0;

// 3. Lista Principal (SEM LIMIT/OFFSET - Traz tudo)
$sqlLista = $pdo->prepare("
    SELECT 
        clientes.*,
        (SELECT nome FROM login WHERE id = clientes.usuario_id LIMIT 1) as nome_criador
    FROM clientes 
    {$where} 
    ORDER BY id DESC 
");

foreach ($params as $k => $v) $sqlLista->bindValue($k, $v);
// As linhas bindValue de limit e offset foram removidas
$sqlLista->execute();
$reservas = $sqlLista->fetchAll(PDO::FETCH_ASSOC);

// Função para manter filtros na URL da paginação
function urlFiltros($page) {
    $query = $_GET;
    $query['page'] = $page;
    return http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservas</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    :root { --primary: #2563eb; --bg-body: #f3f4f6; --bg-card: #ffffff; --text-main: #1f2937; --border: #e5e7eb; }
    body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; padding-bottom: 50px; font-size: 0.9rem; }
    .main-wrapper { max-width: 1400px; margin: 20px auto; padding: 0 15px; }

    /* FILTROS */
    .filter-card { background: var(--bg-card); padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end; }
    .form-label { font-size: 0.75rem; font-weight: 600; color: #6b7280; display: block; margin-bottom: 4px; text-transform: uppercase; }
    .form-input { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem; box-sizing: border-box; }
    .btn-submit { background: var(--primary); color: white; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; width: 100%; }
    
    /* BARRA DE AÇÃO */
    .info-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
    .badge-total { background: <?= $corBadge ?>; color: <?= $corTextoBadge ?>; padding: 6px 12px; border-radius: 20px; font-weight: 600; }
    
    .btn-group-actions { display: flex; gap: 10px; }
    
    .btn-new { background: #10b981; color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }
    .btn-cancel { background: #ef4444; color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }
    .btn-back { background: #6b7280; color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }

    /* TABELA */
    .table-responsive { background: var(--bg-card); border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow-x: auto; border: 1px solid var(--border); }
    .data-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
    .data-table th { background: #f9fafb; padding: 12px 15px; text-align: left; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid var(--border); }
    .data-table td { padding: 12px 15px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .data-table tr:hover { background: #f9fafb; }
/* LINHA CANCELADA (Sem transparência, fundo levemente vermelho) */
    .row-cancelled td { 
        background: #fff5f5; 
        color: #555; /* Cor do texto normal para leitura fácil */
    }

    /* ETIQUETA CANCELADO (Vermelho vivo com texto branco) */
    .st-cancel { 
        background: #ff0000; 
        color: #ffffff; 
        padding: 4px 10px; /* Um pouco mais gordinho para destacar */
    }
    /* SCROLL BOX */
    .scroll-box { max-width: 220px; max-height: 85px; overflow-y: auto; font-size: 0.8rem; background: #fff; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; line-height: 1.4; white-space: normal; }
    .scroll-box::-webkit-scrollbar { width: 4px; }
    .scroll-box::-webkit-scrollbar-thumb { background: #ccc; border-radius: 2px; }

    /* OUTROS */
    .user-name { 
        font-weight: 600; 
        color: #333333 !important; /* Cor Cinza Escuro/Preto */
        display: block; 
        margin-bottom: 2px; 
    }
    .status-badge { padding: 3px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
    .st-active { background: #d1fae5; color: #065f46; }
    .st-cancel { background: #fee2e2; color: #991b1b; }
    .action-select { padding: 5px; border-radius: 4px; border: 1px solid #d1d5db; background: white; font-size: 0.8rem; cursor: pointer; }
    
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
    .page-btn { padding: 6px 12px; border: 1px solid var(--border); background: white; color: var(--text-main); text-decoration: none; border-radius: 4px; font-size: 0.9rem; }
    .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

    @media (max-width: 768px) { .filter-grid { grid-template-columns: 1fr 1fr; } .btn-submit-div { grid-column: span 2; } }
</style>
</head>
<body>

<div class="main-wrapper">

    <!-- FILTROS -->
    <div class="filter-card">
        <form method="GET" class="filter-grid">
            
            <?php if($verCanceladas): ?>
                <input type="hidden" name="canceladas" value="1">
            <?php endif; ?>

            <div><label class="form-label">Início</label><input type="date" name="data_inicio" class="form-input" value="<?= $data_inicio ?>"></div>
            <div><label class="form-label">Fim</label><input type="date" name="data_fim" class="form-input" value="<?= $data_fim ?>"></div>
            <div><label class="form-label">Hora De</label><input type="time" name="hora_inicio" class="form-input" value="<?= $hora_inicio ?>"></div>
            <div><label class="form-label">Hora Até</label><input type="time" name="hora_fim" class="form-input" value="<?= $hora_fim ?>"></div>
            <div style="grid-column: span 2;"><label class="form-label">Buscar</label><input type="text" name="busca" class="form-input" placeholder="Nome ou Telefone..." value="<?= htmlspecialchars($busca) ?>"></div>
            <div class="btn-submit-div"><label class="form-label">&nbsp;</label><button type="submit" class="btn-submit">Filtrar</button></div>
        </form>
    </div>

    <!-- BARRA DE AÇÃO -->
    <div class="info-bar">
        <span class="badge-total">
            <i class="fas fa-chart-pie"></i> <?= $tituloPagina ?>: <?= $totalRegistros ?> (Pax: <?= $total_pessoas ?>)
        </span>
        
        <div class="btn-group-actions">
            <?php if ($verCanceladas): ?>
                <a href="pesquisar.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar p/ Ativas</a>
            <?php else: ?>
                <a href="?canceladas=1" class="btn-cancel"><i class="fas fa-ban"></i> Ver Canceladas</a>
                <a href="adicionar_reserva.php" class="btn-new"><i class="fas fa-plus"></i> Nova Reserva</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABELA -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="50">ID</th>
                    <th width="200">Cliente</th>
                    <th width="140">Data / Hora</th>
                    <th width="60">Pax</th>
                    <th width="80">Mesa</th>
                    <th>Obs. Reserva</th>
                    <th>Obs. Cliente</th>
                    <th width="80">Status</th>
                    <th width="100">Criado Por</th>
                    <th width="100">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($reservas) > 0): ?>
                    <?php foreach($reservas as $r): 
                        $status = (int)$r['status'];
                        $nome = ucwords(strtolower($r['nome']));
                        $telLimpo = preg_replace('/[^0-9]/', '', $r['telefone']);
                        $dataShow = date('d/m/Y H:i', strtotime($r['data'] . ' ' . $r['horario']));
                        $nomeCriador = !empty($r['nome_criador']) ? htmlspecialchars(ucwords(strtolower($r['nome_criador']))) : '---';
                        $zapLink = "https://wa.me/55$telLimpo";
                    ?>
                    <tr class="<?= $status === 0 ? 'row-cancelled' : '' ?>">
                        <td><strong>#<?= $r['id'] ?></strong></td>
                        <td>
                            <span class="user-name"><?= $nome ?></span>
                            <span style="font-size:0.75rem; color:#666"><i class="fab fa-whatsapp"></i> <?= $r['telefone'] ?></span>
                        </td>
                        <td><?= $dataShow ?></td>
                        <td><strong><?= $r['num_pessoas'] ?></strong></td>
                        <td><?= $r['num_mesa'] ?: '-' ?></td>
                        
                        <td>
                            <?php if($r['observacoes']): ?>
                                <div class="scroll-box"><?= nl2br(htmlspecialchars($r['observacoes'])) ?></div>
                            <?php else: ?> - <?php endif; ?>
                        </td>

                        <td>
                            <?php if($r['obsCliente']): ?>
                                <div class="scroll-box" style="border-left: 2px solid var(--primary);"><?= nl2br(htmlspecialchars($r['obsCliente'])) ?></div>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        
                        <td>
                            <span class="status-badge <?= $status === 0 ? 'st-cancel' : 'st-active' ?>">
                                <?= $status === 0 ? 'Cancelado' : 'Ativo' ?>
                            </span>
                        </td>
                        
                        <td style="font-size:0.85rem;"><?= $nomeCriador ?></td>
                        
                        <td>
                            <select class="action-select" onchange="if(this.value) window.location.href=this.value">
                                <option value="" disabled selected>Opções</option>
                                <option value="<?= $zapLink ?>">📲 WhatsApp</option>
                                <option value="obsCliente.php?id=<?= $r['id'] ?>">📝 Perfil</option>
                                
                                <?php if($status === 0): ?>
                                    <option value="ativar_reserva.php?id=<?= $r['id'] ?>">✅ Reativar</option>
                                <?php else: ?>
                                    <option value="editar_reserva.php?id=<?= $r['id'] ?>">✏️ Editar</option>
                                    <option value="excluir_reserva.php?id=<?= $r['id'] ?>">❌ Cancelar</option>
                                <?php endif; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" style="text-align:center; padding:30px; color:#999;">Nenhum registro encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

 

</div>
</body>
</html>