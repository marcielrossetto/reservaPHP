<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

// ====================================================================
// 1. AJAX: ATUALIZAR STATUS
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_reserva') {
    $id = $_POST['id'];
    $sql = $pdo->prepare("UPDATE clientes SET confirmado = 1 WHERE id = :id");
    echo json_encode(['success' => $sql->execute([':id' => $id])]);
    exit;
}

// ====================================================================
// 2. AJAX: BUSCAR PERFIL E HISTÓRICO COMPLETO
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'ver_perfil') {
    $telefoneRaw = $_GET['telefone'];
    $telefone = preg_replace('/\D/', '', $telefoneRaw);
    
    // 1. Estatísticas Gerais
    $sqlStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reservas,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as total_canceladas,
            SUM(num_pessoas) as total_pessoas_trazidas,
            MAX(nome) as nome_cliente,
            MAX(telefone) as tel_cliente
        FROM clientes 
        WHERE telefone LIKE :tel
    ");
    $sqlStats->execute([':tel' => "%$telefone%"]);
    $stats = $sqlStats->fetch(PDO::FETCH_ASSOC);

    // 2. Lista das Últimas 5 Reservas (Histórico + Obs)
    $sqlList = $pdo->prepare("
        SELECT data, num_pessoas, observacoes, status 
        FROM clientes 
        WHERE telefone LIKE :tel 
        ORDER BY data DESC 
        LIMIT 5
    ");
    $sqlList->execute([':tel' => "%$telefone%"]);
    $historico = $sqlList->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['stats' => $stats, 'historico' => $historico]);
    exit;
}

require 'cabecalho.php';

// ====================================================================
// 3. FILTROS
// ====================================================================
$filtroData = $_REQUEST['filtro_data'] ?? date('Y-m-d');
$buscaTexto = $_REQUEST['busca_texto'] ?? '';
$periodo = $_REQUEST['periodo'] ?? 'todos';

$sqlWhere = " WHERE status != 0 "; 
$params = [];

if (!empty($filtroData)) { $sqlWhere .= " AND data = :data "; $params[':data'] = $filtroData; }
if (!empty($buscaTexto)) { $sqlWhere .= " AND (nome LIKE :texto OR telefone LIKE :texto OR id LIKE :texto) "; $params[':texto'] = "%$buscaTexto%"; }

$tituloPeriodo = "Dia Completo";
if ($periodo == 'almoco') { $sqlWhere .= " AND horario < '18:00:00'"; $tituloPeriodo = "Almoço"; }
elseif ($periodo == 'jantar') { $sqlWhere .= " AND horario >= '18:00:00'"; $tituloPeriodo = "Jantar"; }

// Query Totais
$sqlTotal = $pdo->prepare("SELECT SUM(num_pessoas) as total FROM clientes $sqlWhere");
$sqlTotal->execute($params);
$total_pessoas = $sqlTotal->fetch()['total'] ?? 0;

// Query Lista
$sql = $pdo->prepare("SELECT * FROM clientes $sqlWhere ORDER BY horario ASC");
$sql->execute($params);
$reservas = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestão de Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; padding-bottom: 60px; }

        /* --- CARD PADRÃO (DESKTOP) --- */
        .reserva-card {
            background: #fff; border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 10px; 
            display: flex; flex-wrap: wrap; border-left: 5px solid #ccc; 
            position: relative; overflow: hidden; min-height: 100px;
        }
        .status-confirmado { border-left-color: #28a745 !important; }
        .status-pendente { border-left-color: #fd7e14 !important; }

        .sec-info { flex: 2; padding: 12px; border-right: 1px solid #f0f0f0; }
        .client-name { font-weight: 700; color: #333; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .id-reserva { font-weight: 900; color: #777; margin-right: 5px; font-size: 0.9rem; }
        .btn-perfil { font-size: 0.75rem; color: #007bff; text-decoration: none; cursor: pointer; display: block; margin-top: 2px;}

        .sec-pax { flex: 1; padding: 12px; text-align: center; border-right: 1px solid #f0f0f0; display: flex; flex-direction: column; justify-content: center; }
        .pax-count { font-size: 1.4rem; font-weight: 800; color: #ff9f43; line-height: 1; }
        .pax-label { font-size: 0.65rem; color: #888; text-transform: uppercase; }

        .sec-time { flex: 1; padding: 12px; text-align: center; border-right: 1px solid #f0f0f0; display: flex; flex-direction: column; justify-content: center; }
        .time-display { font-size: 1.2rem; font-weight: 700; color: #333; }
        .mesa-display { font-size: 0.75rem; color: #666; background: #eee; padding: 1px 4px; border-radius: 4px; margin-top: 2px;}

        .sec-obs { flex: 3; padding: 12px; background: #fafafa; font-size: 0.8rem; color: #555; overflow: hidden; max-height: 100px; }

        .sec-actions { flex: 1; padding: 8px; display: flex; flex-direction: column; gap: 5px; justify-content: center; min-width: 110px; }
        
        .btn-action {
            width: 100%; border: 1px solid #ddd; background: #fff; color: #555;
            border-radius: 4px; padding: 5px; font-size: 0.8rem; cursor: pointer;
            text-align: center; text-decoration: none; transition: 0.2s;
        }
        .btn-whatsapp { color: #25D366; border-color: #25D366; }
        .btn-whatsapp:hover { background: #25D366; color: #fff; }

        /* Badge Status */
        .badge-status { position: absolute; top: 5px; right: 5px; font-size: 0.6rem; padding: 2px 5px; border-radius: 3px; font-weight: bold; text-transform: uppercase; }
        .badge-ok { background: #d4edda; color: #155724; }
        .badge-wait { background: #fff3cd; color: #856404; }

        /* --- ESTILOS MOBILE (TABLET E CELULAR) - ALTURA REDUZIDA --- */
        @media (max-width: 991px) {
            .reserva-card {
                height: 80px; /* Altura fixa solicitada */
                flex-wrap: nowrap; /* Força linha única */
                align-items: center;
                padding-right: 5px;
            }
            
            .sec-info { 
                flex: 3; border: none; padding: 8px 8px 8px 12px; 
                display: flex; flex-direction: column; justify-content: center;
                overflow: hidden;
            }
            .client-name { font-size: 0.95rem; }
            .btn-perfil { margin-top: 0; }

            .sec-pax { 
                flex: 1; border: none; padding: 5px; min-width: 50px; 
            }
            .pax-count { font-size: 1.2rem; }

            .sec-time { 
                flex: 1; border: none; padding: 5px; min-width: 60px; 
            }
            .time-display { font-size: 1.1rem; }

            /* ESCONDE OBS NO MOBILE PARA CABER EM 80PX */
            .sec-obs { display: none; }

            .sec-actions { 
                flex: 2; padding: 5px; min-width: 90px;
                flex-direction: row; gap: 4px; /* Botões lado a lado */
            }
            .btn-action { 
                padding: 0; height: 35px; width: 35px; /* Botões quadrados */
                display: flex; align-items: center; justify-content: center; font-size: 1rem;
            }
            /* Esconde texto dos botões no mobile, deixa só ícone */
            .btn-text { display: none; }
            
            .badge-status { display: none; } /* Remove badge para limpar visual */
            
            /* Ajuste dos botões do topo */
            .mobile-btn-row {
                display: flex; gap: 8px; width: 100%; margin-top: 10px;
            }
            .mobile-btn-row .btn {
                flex: 1; height: 38px; padding: 5px; font-size: 0.9rem;
                display: flex; align-items: center; justify-content: center; gap: 5px;
            }
        }

        /* Desktop Only */
        @media (min-width: 992px) {
            .mobile-only-icon { display: none; }
        }

        /* Filter Bar */
        .filter-bar { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 15px; }

        /* PRINT */
        #print-area { display: none; }
        @media print {
            body * { visibility: hidden; }
            .no-print, header, nav { display: none !important; }
            #print-area, #print-area * { visibility: visible; }
            #print-area { position: absolute; left: 0; top: 0; width: 100%; padding: 20px; background: white; }
            .print-table { width: 100%; border-collapse: collapse; font-size: 10pt; font-family: Arial; }
            .print-table th, .print-table td { border: 1px solid #000; padding: 4px; text-align: left; }
            .col-obs { white-space: pre-wrap; } /* Quebra linha auto */
        }
    </style>
</head>
<body>

<div class="container-fluid mt-3">
    
    <!-- BARRA DE FILTROS -->
    <div class="filter-bar no-print">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label fw-bold small mb-1">Data</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-calendar"></i></span>
                    <input type="date" name="filtro_data" class="form-control" value="<?= $filtroData ?>" onchange="this.form.submit()">
                </div>
            </div>

            <div class="col-md-3 col-6">
                <label class="form-label fw-bold small mb-1">Busca</label>
                <input type="text" name="busca_texto" class="form-control form-control-sm" placeholder="Nome/Tel" value="<?= htmlspecialchars($buscaTexto) ?>">
            </div>

            <div class="col-md-3 col-12">
                <div class="btn-group w-100 btn-group-sm" role="group">
                    <button type="submit" name="periodo" value="todos" class="btn btn-outline-secondary <?= $periodo == 'todos' ? 'active' : '' ?>">Todos</button>
                    <button type="submit" name="periodo" value="almoco" class="btn btn-outline-secondary <?= $periodo == 'almoco' ? 'active' : '' ?>">Almoço</button>
                    <button type="submit" name="periodo" value="jantar" class="btn btn-outline-secondary <?= $periodo == 'jantar' ? 'active' : '' ?>">Jantar</button>
                </div>
            </div>

            <!-- Botões de Ação (Responsivos) -->
            <div class="col-md-3 col-12">
                <div class="mobile-btn-row">
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm w-100"><i class="fas fa-print"></i> Imprimir</button>
                    <a href="adicionar_reserva.php" class="btn btn-success btn-sm w-100"><i class="fas fa-plus"></i> Nova</a>
                </div>
            </div>
        </form>
    </div>

    <!-- RESUMO -->
    <div class="alert alert-secondary py-1 px-3 d-flex justify-content-between align-items-center no-print mb-3" style="font-size:0.9rem;">
        <span><strong><?= date('d/m', strtotime($filtroData)) ?></strong> - <?= $tituloPeriodo ?></span>
        <span class="badge bg-dark">Total: <?= $total_pessoas ?></span>
    </div>

    <!-- LISTA DE RESERVAS -->
    <div class="lista-reservas no-print">
        <?php if(count($reservas) > 0): ?>
            <?php foreach($reservas as $r): 
                $isConfirmado = ($r['confirmado'] == 1);
                $classeBorda = $isConfirmado ? 'status-confirmado' : 'status-pendente';
                $horaShort = date("H:i", strtotime($r['horario']));
                $nome = ucwords(strtolower($r['nome']));
                $telLimpo = preg_replace('/[^0-9]/', '', $r['telefone']);
                $linkZapDireto = "https://wa.me/55$telLimpo";
                $msgZap = "Olá $nome! Confirmando reserva para dia " . date('d/m', strtotime($r['data'])) . " às $horaShort para {$r['num_pessoas']} pessoas.";
                $linkZapComMsg = "https://wa.me/55$telLimpo?text=" . urlencode($msgZap);
            ?>
            
            <div class="reserva-card <?= $classeBorda ?>">
                <!-- Badge Desktop -->
                <span class="badge-status <?= $isConfirmado ? 'badge-ok' : 'badge-wait' ?>"><?= $isConfirmado ? 'Confirmado' : 'Pendente' ?></span>

                <!-- 1. Info (Nome e Link Perfil) -->
                <div class="sec-info">
                    <div class="client-name">
                        <span class="id-reserva">#<?= $r['id'] ?></span> <?= $nome ?>
                    </div>
                    <span class="btn-perfil" onclick="abrirModalPerfil('<?= $telLimpo ?>')">
                        <i class="fas fa-history"></i> Histórico
                    </span>
                </div>

                <!-- 2. Pessoas -->
                <div class="sec-pax">
                    <span class="pax-count"><?= $r['num_pessoas'] ?></span>
                    <span class="pax-label">Pax</span>
                </div>

                <!-- 3. Hora/Mesa -->
                <div class="sec-time">
                    <div class="time-display"><?= $horaShort ?></div>
                    <?php if(!empty($r['num_mesa'])): ?>
                        <div class="mesa-display">M: <?= $r['num_mesa'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- 4. Obs (Some no Mobile) -->
                <div class="sec-obs">
                    <?= empty($r['observacoes']) ? '<span class="text-muted">...</span>' : htmlspecialchars($r['observacoes']) ?>
                </div>

                <!-- 5. Ações (Ícones no Mobile) -->
                <div class="sec-actions">
                    <button class="btn-action btn-whatsapp" onclick="abrirModalZap(<?= $r['id'] ?>, '<?= $linkZapComMsg ?>', '<?= $linkZapDireto ?>')">
                        <i class="fab fa-whatsapp"></i> <span class="btn-text"> Zap</span>
                    </button>
                    
                    <a href="editar_reserva.php?id=<?= $r['id'] ?>" class="btn-action">
                        <i class="fas fa-pen text-primary"></i> <span class="btn-text"> Editar</span>
                    </a>
                    
                    <a href="excluir_reserva.php?id=<?= $r['id'] ?>" class="btn-action" onclick="return confirm('Excluir?')">
                        <i class="fas fa-trash text-danger"></i> <span class="btn-text"> Excluir</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted">Nenhuma reserva encontrada.</div>
        <?php endif; ?>
    </div>

    <!-- IMPRESSÃO (TABELA LIMPA) -->
    <div id="print-area">
        <div class="print-header">
            <h3>Lista de Reservas</h3>
            <p><?= date('d/m/Y', strtotime($filtroData)) ?> | <?= $tituloPeriodo ?></p>
        </div>
        <table class="print-table">
            <thead>
                <tr>
                    <th style="width:5%">ID</th>
                    <th style="width:25%">Nome</th>
                    <th style="width:5%">Pax</th>
                    <th style="width:10%">Hora</th>
                    <th style="width:45%">Observações</th>
                    <th style="width:10%">Mesa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reservas as $r): ?>
                <tr class="print-row">
                    <td><?= $r['id'] ?></td>
                    <td><?= $r['nome'] ?></td>
                    <td class="text-center font-bold"><?= $r['num_pessoas'] ?></td>
                    <td class="text-center"><?= date("H:i", strtotime($r['horario'])) ?></td>
                    <td class="col-obs"><?= $r['observacoes'] ?></td>
                    <td class="text-center"><?= $r['num_mesa'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- MODAL PERFIL E HISTÓRICO -->
<div class="modal fade" id="modalPerfil" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title m-0"><i class="fas fa-user-clock"></i> Histórico do Cliente</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Resumo -->
                <div class="bg-light p-3 border-bottom d-flex justify-content-around text-center">
                    <div><h5 id="pTotal" class="m-0 fw-bold">0</h5><small class="text-muted">Reservas</small></div>
                    <div><h5 id="pCancel" class="m-0 fw-bold text-danger">0</h5><small class="text-muted">Cancelou</small></div>
                    <div><h5 id="pPessoas" class="m-0 fw-bold text-success">0</h5><small class="text-muted">Pessoas</small></div>
                </div>
                
                <!-- Lista de Reservas Passadas -->
                <div class="p-3">
                    <h6 class="text-muted small fw-bold text-uppercase">Últimas 5 Visitas:</h6>
                    <ul class="list-group list-group-flush small" id="listaHistorico">
                        <li class="list-group-item text-center text-muted">Carregando...</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL WHATSAPP -->
<div class="modal fade" id="modalZap" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title">WhatsApp</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-grid gap-2">
                <button class="btn btn-outline-success" id="btnZapConfirmar">
                    <i class="fas fa-check"></i> Confirmar & Enviar
                </button>
                <button class="btn btn-outline-secondary" id="btnZapDireto">
                    <i class="fas fa-comment"></i> Apenas Abrir
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Lógica WhatsApp ---
    let currentReservaId = null, currentLinkMsg = '', currentLinkDireto = '';

    function abrirModalZap(id, linkComMsg, linkDireto) {
        currentReservaId = id;
        currentLinkMsg = linkComMsg;
        currentLinkDireto = linkDireto;
        new bootstrap.Modal(document.getElementById('modalZap')).show();
    }

    document.getElementById('btnZapConfirmar').onclick = function() {
        window.location.href = currentLinkMsg; // Abre imediatamente (fix iPhone)
        let fd = new FormData();
        fd.append('acao', 'confirmar_reserva');
        fd.append('id', currentReservaId);
        fetch('', { method: 'POST', body: fd }).then(() => {
            setTimeout(() => { location.reload(); }, 2000);
        });
    };

    document.getElementById('btnZapDireto').onclick = function() {
        window.location.href = currentLinkDireto;
    };

    // --- Lógica Perfil ---
    function abrirModalPerfil(telefone) {
        let lista = document.getElementById('listaHistorico');
        lista.innerHTML = '<li class="list-group-item text-center">Carregando...</li>';
        new bootstrap.Modal(document.getElementById('modalPerfil')).show();

        fetch('?acao=ver_perfil&telefone=' + telefone)
        .then(res => res.json())
        .then(data => {
            // Preenche Resumo
            document.getElementById('pTotal').innerText = data.stats.total_reservas;
            document.getElementById('pCancel').innerText = data.stats.total_canceladas;
            document.getElementById('pPessoas').innerText = data.stats.total_pessoas_trazidas || 0;

            // Preenche Lista Histórico com Observações
            let html = '';
            if(data.historico.length > 0) {
                data.historico.forEach(h => {
                    // Formata data BR
                    let dataObj = new Date(h.data);
                    let dataFormatada = dataObj.toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit', year:'2-digit'});
                    
                    let statusIcon = (h.status == 0) ? '<span class="text-danger">✖</span>' : '<span class="text-success">✔</span>';
                    let obs = h.observacoes ? `<br><em class="text-muted" style="font-size:0.8em">Obs: ${h.observacoes}</em>` : '';
                    
                    html += `<li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong>${dataFormatada}</strong>
                            <span>${h.num_pessoas} pax ${statusIcon}</span>
                        </div>
                        ${obs}
                    </li>`;
                });
            } else {
                html = '<li class="list-group-item text-center text-muted">Sem histórico anterior.</li>';
            }
            lista.innerHTML = html;
        });
    }
</script>
</body>
</html>