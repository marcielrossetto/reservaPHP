<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

/* ======================================================
   BACKEND: AJAX HANDLERS (MULTI-EMPRESA)
====================================================== */

// 1. Buscar dados para os Modais
if (isset($_GET['acao']) && $_GET['acao'] === 'get_reserva') {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND empresa_id = :emp");
    $stmt->execute([':id' => $id, ':emp' => $empresa_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

// 2. Salvar Edição Geral
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_edicao') {
    try {
        $id = (int) $_POST['id_reserva'];
        $sql = $pdo->prepare("UPDATE clientes SET nome=:nome, data=:data, num_pessoas=:num_pessoas, horario=:horario, telefone=:telefone, num_mesa=:num_mesa, observacoes=:observacoes, obsCliente=:obsC WHERE id=:id AND empresa_id = :emp");
        $sql->execute([
            ":id" => $id,
            ":emp" => $empresa_id,
            ":nome" => trim($_POST['nome'] ?? ''),
            ":data" => $_POST['data'] ?? '',
            ":num_pessoas" => $_POST['num_pessoas'] ?? 0,
            ":horario" => $_POST['horario'] ?? '',
            ":telefone" => preg_replace('/\D/', '', $_POST['telefone'] ?? ''),
            ":num_mesa" => $_POST['num_mesa'] ?? '',
            ":observacoes" => trim($_POST['observacoes'] ?? ''),
            ":obsC" => trim($_POST['obsCliente'] ?? '')
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
    exit;
}

// 3. Salvar Apenas Obs Cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_obs_cliente') {
    $id = (int) $_POST['id_reserva_obs'];
    $obs = $_POST['obsCliente'] ?? '';
    $stmt = $pdo->prepare("UPDATE clientes SET obsCliente = :obs WHERE id = :id AND empresa_id = :emp");
    $stmt->execute([':obs' => $obs, ':id' => $id, ':emp' => $empresa_id]);
    echo json_encode(['success' => true]);
    exit;
}

// 4. Cancelar Reserva com Motivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'processar_cancelamento') {
    $id = (int) $_POST['id'];
    $motivo = isset($_POST['motivo_cancelamento']) ? implode(", ", $_POST['motivo_cancelamento']) : 'Não informado';
    $stmt = $pdo->prepare("UPDATE clientes SET status = 0, motivo_cancelamento = :motivo WHERE id = :id AND empresa_id = :emp");
    $stmt->execute([':motivo' => $motivo, ':id' => $id, ':emp' => $empresa_id]);
    echo json_encode(['success' => true]);
    exit;
}

// 5. Reativar Reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'reativar_reserva') {
    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare("UPDATE clientes SET status = 1 WHERE id = :id AND empresa_id = :emp");
    $stmt->execute([':id' => $id, ':emp' => $empresa_id]);
    echo json_encode(['success' => true]);
    exit;
}

/* ======================================================
   FILTROS E CONSULTAS
====================================================== */
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$busca = $_GET['busca'] ?? '';
$verCanceladas = isset($_GET['canceladas']) && $_GET['canceladas'] === '1';

$where = " WHERE c.empresa_id = :emp_id ";
$params = [':emp_id' => $empresa_id];

if ($data_inicio && $data_fim) {
    $where .= " AND c.data BETWEEN :data_inicio AND :data_fim";
    $params[':data_inicio'] = $data_inicio;
    $params[':data_fim'] = $data_fim;
}
if ($busca) {
    $where .= " AND (c.nome LIKE :busca OR c.telefone LIKE :busca OR c.id LIKE :busca)";
    $params[':busca'] = "%{$busca}%";
}

if ($verCanceladas) {
    $where .= " AND c.status = 0";
    $tituloPagina = "Canceladas";
    $corBadge = "#fee2e2";
    $corTexto = "#991b1b";
} else {
    $where .= " AND c.status <> 0";
    $tituloPagina = "Ativas";
    $corBadge = "#dbeafe";
    $corTexto = "#1e40af";
}

$sqlSoma = $pdo->prepare("SELECT SUM(num_pessoas) as total FROM clientes c $where");
$sqlSoma->execute($params);
$totalPessoasFiltro = $sqlSoma->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$sqlLista = $pdo->prepare("SELECT c.*, u.nome as criador_nome, e.nome_empresa FROM clientes c LEFT JOIN login u ON c.usuario_id = u.id LEFT JOIN empresas e ON c.empresa_id = e.id $where ORDER BY c.id DESC");
$sqlLista->execute($params);
$reservas = $sqlLista->fetchAll(PDO::FETCH_ASSOC);

require 'cabecalho.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisar Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --bg-body: #f3f4f6; --bg-card: #ffffff; --border: #e5e7eb; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-body); font-size: 0.85rem; }
        .main-wrapper { max-width: 1500px; margin: 20px auto; padding: 0 15px; }
        .filter-card { background: var(--bg-card); padding: 15px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; align-items: end; }
        .form-input { width: 100%; padding: 6px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; }
        .table-responsive { background: var(--bg-card); border-radius: 10px; overflow-x: auto; border: 1px solid var(--border); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f9fafb; padding: 10px; font-size: 0.7rem; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid var(--border); }
        .data-table td { padding: 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .row-cancelled td { background: #fff5f5; }
        .scroll-box { max-width: 180px; max-height: 55px; overflow-y: auto; font-size: 0.75rem; background: #fff; padding: 4px; border: 1px solid #e5e7eb; border-radius: 4px; white-space: normal; line-height: 1.2; }
        .obs-cliente-box { border-left: 3px solid #2563eb; background: #f0f7ff; }
        .motivo-box { border-left: 3px solid #dc3545; color: #dc3545; font-weight: 500; }
        .action-select { padding: 4px; border-radius: 4px; border: 1px solid #d1d5db; font-size: 0.75rem; width: 100%; cursor: pointer; }
        .modal-ios-label { font-weight: 600; margin-top: 8px; font-size: 0.8rem; display: block; }
        .modal-ios-input { margin-top: 4px; border-radius: 8px !important; border: 1px solid #d1d1d6 !important; padding: 8px !important; width: 100%; }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <!-- FILTROS -->
        <div class="filter-card">
            <form method="GET" class="filter-grid">
                <?php if ($verCanceladas): ?><input type="hidden" name="canceladas" value="1"><?php endif; ?>
                <div><label class="small fw-bold">Início</label><input type="date" name="data_inicio" class="form-input" value="<?= $data_inicio ?>"></div>
                <div><label class="small fw-bold">Fim</label><input type="date" name="data_fim" class="form-input" value="<?= $data_fim ?>"></div>
                <div style="grid-column: span 2;"><label class="small fw-bold">Busca</label><input type="text" name="busca" class="form-input" placeholder="Nome, Tel ou ID" value="<?= htmlspecialchars($busca) ?>"></div>
                <div><button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button></div>
            </form>
        </div>

        <!-- RESUMO -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                <span class="badge rounded-pill p-2 px-3 shadow-sm" style="background:<?= $corBadge ?>; color:<?= $corTexto ?>;">
                    <?= $tituloPagina ?>: <?= count($reservas) ?>
                </span>
                <span class="badge rounded-pill p-2 px-3 bg-dark text-white">
                    Pax Total: <?= $totalPessoasFiltro ?>
                </span>
            </div>
            <div class="gap-2 d-flex">
                <?php if ($verCanceladas): ?>
                    <a href="pesquisar.php" class="btn btn-secondary btn-sm">Ativas</a>
                <?php else: ?>
                    <a href="?canceladas=1" class="btn btn-outline-danger btn-sm">Canceladas</a>
                    <a href="adicionar_reserva.php" class="btn btn-primary btn-sm">Nova</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABELA -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Data/Hora</th>
                        <th>Pax</th>
                        <th>Mesa</th>
                        <th>Obs Reserva</th>
                        <th>Obs Cliente</th>
                        <th>Motivo Cancel.</th>
                        <th>Registro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $r):
                        $status = (int) $r['status'];
                        $tel = preg_replace('/\D/', '', $r['telefone']);
                        $dataBr = date('d/m/Y', strtotime($r['data']));
                        $hora = date('H:i', strtotime($r['horario']));
                        $dataEmissao = !empty($r['data_emissao']) ? date('d/m/y H:i', strtotime($r['data_emissao'])) : 'N/D';
                        $paramsZap = "{$tel}|{$r['nome']}|{$dataBr}|{$hora}|{$r['num_pessoas']}";
                        ?>
                        <tr class="<?= $status === 0 ? 'row-cancelled' : '' ?>">
                            <td>#<?= $r['id'] ?></td>
                            <td><span class="fw-bold"><?= ucwords(strtolower($r['nome'])) ?></span><br><small><?= $r['telefone'] ?></small></td>
                            <td><?= $dataBr ?><br><b><?= $hora ?></b></td>
                            <td class="text-center"><b><?= $r['num_pessoas'] ?></b></td>
                            <td><?= $r['num_mesa'] ?: '-' ?></td>
                            <td><div class="scroll-box"><?= htmlspecialchars($r['observacoes'] ?? '') ?></div></td>
                            <td><div class="scroll-box obs-cliente-box"><?= htmlspecialchars($r['obsCliente'] ?? '') ?></div></td>
                            <td><?php if ($status === 0): ?><div class="scroll-box motivo-box"><?= htmlspecialchars($r['motivo_cancelamento'] ?? 'N/I') ?></div><?php else: ?> - <?php endif; ?></td>
                            <td style="font-size: 0.65rem;">
                                <i class="fas fa-building text-primary"></i> <?= htmlspecialchars($r['nome_empresa']) ?><br>
                                <i class="fas fa-user"></i> <?= ucwords(strtolower($r['criador_nome'] ?? 'Sist.')) ?><br>
                                <i class="fas fa-clock"></i> <?= $dataEmissao ?>
                            </td>
                            <td>
                                <select class="action-select" onchange="handleAction(this)">
                                    <option value="">Opções</option>
                                    <option value="whatsapp:<?= $paramsZap ?>">📲 WhatsApp</option>
                                    <option value="obs:<?= $r['id'] ?>">📝 Obs Cliente</option>
                                    <?php if ($status !== 0): ?>
                                        <option value="editar:<?= $r['id'] ?>">✏️ Editar</option>
                                        <option value="cancelar:<?= $r['id'] ?>">❌ Cancelar</option>
                                    <?php else: ?>
                                        <option value="reativar:<?= $r['id'] ?>">✅ Reativar</option>
                                    <?php endif; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAIS -->
    <!-- 1. Editar -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6>Editar Reserva</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditar">
                        <input type="hidden" name="id_reserva" id="edit_id">
                        <label class="modal-ios-label">Nome:</label><input type="text" name="nome" id="edit_nome" class="modal-ios-input">
                        <div class="row g-2">
                            <div class="col-6"><label class="modal-ios-label">Data:</label><input type="date" name="data" id="edit_data" class="modal-ios-input"></div>
                            <div class="col-6"><label class="modal-ios-label">Hora:</label><input type="time" name="horario" id="edit_horario" class="modal-ios-input"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><label class="modal-ios-label">Telefone:</label><input type="text" name="telefone" id="edit_telefone" class="modal-ios-input"></div>
                            <div class="col-6"><label class="modal-ios-label">Pax:</label><input type="number" name="num_pessoas" id="edit_num_pessoas" class="modal-ios-input"></div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12"><label class="modal-ios-label">Mesa:</label><input type="text" name="num_mesa" id="edit_num_mesa" class="modal-ios-input"></div>
                        </div>
                        <label class="modal-ios-label">Obs Cliente (Fixo):</label><textarea name="obsCliente" id="edit_obsCliente_geral" class="modal-ios-input" rows="2"></textarea>
                        <label class="modal-ios-label">Obs Reserva (Hoje):</label><textarea name="observacoes" id="edit_observacoes" class="modal-ios-input" rows="2"></textarea>
                    </form>
                    <button class="btn btn-primary w-100 mt-3" onclick="salvarEdicao()">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Obs Cliente Rápido -->
    <div class="modal fade" id="modalObsCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h6>Observação do Cliente</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formObsCliente">
                        <input type="hidden" name="id_reserva_obs" id="obs_id">
                        <textarea name="obsCliente" id="edit_obsCliente" class="modal-ios-input" rows="6" placeholder="Notas sobre o cliente..."></textarea>
                    </form>
                    <button class="btn btn-dark w-100 mt-3" onclick="salvarObsCliente()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Cancelar -->
    <div class="modal fade" id="modalCancelar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white"><h6>Cancelar Reserva</h6></div>
                <div class="modal-body">
                    <input type="hidden" id="cancelar_id_input">
                    <label class="small fw-bold mb-2">Motivo:</label>
                    <select id="motivo_cancel_select" class="form-select" multiple>
                        <option>Mudança de planos</option>
                        <option>Problemas de saúde</option>
                        <option>Erro na reserva</option>
                        <option>Não informou</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger btn-sm w-100" onclick="confirmarCancelamento()">Confirmar Cancelamento</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const mEditar = new bootstrap.Modal(document.getElementById('modalEditar'));
        const mObs = new bootstrap.Modal(document.getElementById('modalObsCliente'));
        const mCancel = new bootstrap.Modal(document.getElementById('modalCancelar'));

        function handleAction(select) {
            const val = select.value; if (!val) return;
            const action = val.split(':')[0];
            const data = val.split(':')[1];

            if (action === 'whatsapp') {
                const p = data.split('|');
                const msg = encodeURIComponent(`Olá *${p[1]}*! Gostaríamos de confirmar sua reserva para o dia *${p[2]}* às *${p[3]}* para *${p[4]}* pessoas. Podemos confirmar?`);
                window.open(`https://wa.me/55${p[0]}?text=${msg}`, '_blank');
            }
            if (action === 'editar') {
                fetch(`pesquisar.php?acao=get_reserva&id=${data}`).then(r => r.json()).then(res => {
                    document.getElementById('edit_id').value = res.id;
                    document.getElementById('edit_nome').value = res.nome;
                    document.getElementById('edit_data').value = res.data;
                    document.getElementById('edit_horario').value = res.horario;
                    document.getElementById('edit_telefone').value = res.telefone;
                    document.getElementById('edit_num_pessoas').value = res.num_pessoas;
                    document.getElementById('edit_num_mesa').value = res.num_mesa;
                    document.getElementById('edit_obsCliente_geral').value = res.obsCliente || '';
                    document.getElementById('edit_observacoes').value = res.observacoes;
                    mEditar.show();
                });
            }
            if (action === 'obs') {
                fetch(`pesquisar.php?acao=get_reserva&id=${data}`).then(r => r.json()).then(res => {
                    document.getElementById('obs_id').value = res.id;
                    document.getElementById('edit_obsCliente').value = res.obsCliente || '';
                    mObs.show();
                });
            }
            if (action === 'cancelar') {
                document.getElementById('cancelar_id_input').value = data;
                mCancel.show();
            }
            if (action === 'reativar') {
                if (confirm("Deseja reativar esta reserva?")) {
                    const fd = new FormData(); fd.append('acao', 'reativar_reserva'); fd.append('id', data);
                    fetch('pesquisar.php', { method: 'POST', body: fd }).then(() => location.reload());
                }
            }
            select.selectedIndex = 0;
        }

        function salvarEdicao() {
            const fd = new FormData(document.getElementById('formEditar')); 
            fd.append('acao', 'salvar_edicao');
            fetch('pesquisar.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => { if (res.success) location.reload(); });
        }

        function salvarObsCliente() {
            const fd = new FormData(document.getElementById('formObsCliente')); 
            fd.append('acao', 'salvar_obs_cliente');
            fetch('pesquisar.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => { if (res.success) location.reload(); });
        }

        function confirmarCancelamento() {
            const id = document.getElementById('cancelar_id_input').value;
            const select = document.getElementById('motivo_cancel_select');
            const motivos = Array.from(select.selectedOptions).map(option => option.value);
            
            const fd = new FormData();
            fd.append('acao', 'processar_cancelamento');
            fd.append('id', id);
            motivos.forEach(m => fd.append('motivo_cancelamento[]', m));

            fetch('pesquisar.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { if (res.success) location.reload(); });
        }
    </script>
</body>
</html>