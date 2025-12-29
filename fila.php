<?php
// 1. CONFIGURAÇÃO DE FUSO HORÁRIO E ERROS
date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Em produção, mude para 0

session_status() === PHP_SESSION_NONE ? session_start() : null;
require_once 'config.php';

// Verifica login
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->prepare("SET time_zone = '-03:00'")->execute();
} catch (Exception $e) {
}

// PEGA O ID DA EMPRESA DA SESSÃO
$empresa_id = $_SESSION['empresa_id'];
$agora_data = date('Y-m-d H:i:s');

function formatarTempoPHP($segundos)
{
    if ($segundos >= 3600) {
        $h = (int) ($segundos / 3600);
        $m = (int) (($segundos % 3600) / 60);
        return "{$h}h " . str_pad($m, 2, "0", STR_PAD_LEFT) . "m";
    }
    $m = (int) ($segundos / 60);
    $s = (int) ($segundos % 60);
    return sprintf('%02d:%02d', $m, $s);
}

// --- AÇÕES VIA AJAX (MULTI-EMPRESA) ---
if (isset($_GET['ajax_acao'])) {
    try {
        $id = $_GET['id'];
        if ($_GET['ajax_acao'] === 'sentar') {
            $mesa = !empty($_GET['mesa']) ? $_GET['mesa'] : 'NAO DEFINIDO';

            // LÓGICA DE POSIÇÃO: Filtrado por empresa_id
            $stmtPos = $pdo->prepare("SELECT COUNT(*) as posicao FROM fila_espera WHERE status = 0 AND empresa_id = ? AND data_criacao <= (SELECT data_criacao FROM fila_espera WHERE id = ?) AND DATE(data_criacao) = CURDATE()");
            $stmtPos->execute([$empresa_id, $id]);
            $posicao_atual = $stmtPos->fetch(PDO::FETCH_ASSOC)['posicao'];

            $pdo->prepare("UPDATE fila_espera SET status = 1, num_mesa = ?, hora_sentado = ?, posicao_fila = ? WHERE id = ? AND empresa_id = ?")
                ->execute([$mesa, $agora_data, $posicao_atual, $id, $empresa_id]);

        } elseif ($_GET['ajax_acao'] === 'cancelar') {
            $pdo->prepare("UPDATE fila_espera SET status = 2, hora_sentado = ? WHERE id = ? AND empresa_id = ?")
                ->execute([$agora_data, $id, $empresa_id]);
        } elseif ($_GET['ajax_acao'] === 'voltar') {
            $pdo->prepare("UPDATE fila_espera SET status = 0, num_mesa = NULL, hora_sentado = NULL, posicao_fila = NULL WHERE id = ? AND empresa_id = ?")
                ->execute([$id, $empresa_id]);
        } elseif ($_GET['ajax_acao'] === 'editar') {
            $nome = $_GET['nome'];
            $pax = (int) $_GET['pax'];
            $tel = preg_replace('/\D/', '', $_GET['telefone']);
            $pdo->prepare("UPDATE fila_espera SET nome = ?, num_pessoas = ?, telefone = ? WHERE id = ? AND empresa_id = ?")
                ->execute([$nome, $pax, $tel, $id, $empresa_id]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- INSERIR (MULTI-EMPRESA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'inserir') {
    try {
        $motivo = $_POST['prio_motivo'] ?? "";
        $prio = !empty($motivo) ? 1 : 0;
        $tel = preg_replace('/\D/', '', $_POST['telefone']);
        $sql = $pdo->prepare("INSERT INTO fila_espera (empresa_id, nome, telefone, num_pessoas, prioridade, prio_motivo, status, data_criacao) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
        $sql->execute([$empresa_id, $_POST['nome'], $tel, $_POST['qtd_pessoas'], $prio, $motivo, $agora_data]);
        header("Location: fila.php");
        exit;
    } catch (PDOException $e) {
        die("ERRO: " . $e->getMessage());
    }
}

// --- BUSCA DADOS FILTRADOS ---
$filtro_data = $_GET['data'] ?? date('Y-m-d');
$filtro_hora = $_GET['hora'] ?? '';
$sql_cond = " WHERE empresa_id = ? AND DATE(data_criacao) = ? ";
$params = [$empresa_id, $filtro_data];
if (!empty($filtro_hora)) {
    $sql_cond .= " AND HOUR(data_criacao) = ? ";
    $params[] = $filtro_hora;
}

// 1. ESPERANDO
$espera = $pdo->prepare("SELECT *, UNIX_TIMESTAMP(data_criacao) as ts_criacao FROM fila_espera $sql_cond AND status = 0 ORDER BY data_criacao ASC");
$espera->execute($params);
$espera = $espera->fetchAll(PDO::FETCH_ASSOC);

// Contagem de mesas (cards coloridos)
$mesas_cont = ['ate2' => 0, '3a4' => 0, '5a6' => 0, '7a8' => 0, '9mais' => 0];
foreach ($espera as $e) {
    $p = (int) $e['num_pessoas'];
    if ($p <= 2)
        $mesas_cont['ate2']++;
    elseif ($p <= 4)
        $mesas_cont['3a4']++;
    elseif ($p <= 6)
        $mesas_cont['5a6']++;
    elseif ($p <= 8)
        $mesas_cont['7a8']++;
    else
        $mesas_cont['9mais']++;
}

// 2. HISTÓRICO
$historico_sql = $pdo->prepare("SELECT * FROM fila_espera $sql_cond AND status IN (1,2) ORDER BY hora_sentado DESC");
$historico_sql->execute($params);
$historico_raw = $historico_sql->fetchAll(PDO::FETCH_ASSOC);

$total_sentados_pax = 0;
$total_desistencias = 0;
$soma_segundos = 0;
$cont_media = 0;
$historico_final = [];
foreach ($historico_raw as $row) {
    $isC = ($row['status'] == 2);
    $inicio = strtotime($row['data_criacao']);
    $fim = strtotime($row['hora_sentado']);
    $diff = ($fim > $inicio) ? ($fim - $inicio) : 0;
    if (!$isC) {
        $total_sentados_pax += (int) $row['num_pessoas'];
        $soma_segundos += $diff;
        $cont_media++;
    } else {
        $total_desistencias++;
    }
    $row['esperou_formatado'] = formatarTempoPHP($diff);
    $historico_final[] = $row;
}
$total_espera_pax = array_sum(array_column($espera, 'num_pessoas'));
$tempo_medio_texto = ($cont_media > 0) ? formatarTempoPHP((int) ($soma_segundos / $cont_media)) : "00:00";

require 'cabecalho.php';
?>

<!-- O RESTANTE DO HTML, CSS E JS PERMANECE EXATAMENTE IGUAL -->
<!-- (O layout não foi alterado conforme solicitado) -->
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: #f2f2f7;
            font-family: -apple-system, sans-serif;
            font-size: 0.8rem;
            color: #1c1c1e;
            overflow-x: hidden;
        }

        .page-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px;
        }

        .toolbar-min {
            background: #fff;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin: 10px 0 15px;
        }

        .card-ios {
            background: #fff;
            border-radius: 15px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            border: none;
            margin-bottom: 20px;
        }

        .fila-item {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 12px;
            margin-bottom: 6px;
            background: #fff;
            border: 1px solid #e5e5ea;
            position: relative;
            gap: 4px;
        }

        .prio-style {
            background: #fff9e6 !important;
            border-color: #ffcc00 !important;
        }

        .hist-style {
            background: #f8fafc !important;
            border-color: #d1d5db !important;
        }

        .pos-num {
            font-weight: 800;
            color: #8e8e93;
            width: 35px;
            font-size: 0.6rem;
            border-right: 1px solid #eee;
            text-align: center;
            flex-shrink: 0;
        }

        .pos-num b {
            display: block;
            color: #1c1c1e;
            font-size: 0.8rem;
            line-height: 1;
        }

        .pos-num .id-raw {
            font-size: 0.5rem;
            color: #aeaeb2;
        }

        .nome-container {
            flex: 1;
            min-width: 0;
            padding: 0 4px;
        }

        .res-nome {
            font-weight: 800;
            text-transform: uppercase;
            color: #1c1c1e;
            font-size: 0.85rem;
            line-height: 1.1;
            word-wrap: break-word;
        }

        .pax-focus {
            flex-shrink: 0;
            text-align: center;
            padding: 0 6px;
            min-width: 40px;
            background: #f2f2f7;
            border-radius: 10px;
            border: none;
            margin: 0 5px;
        }

        .pax-focus strong {
            display: block;
            font-size: 1.1rem;
            font-weight: 900;
            color: #1c1c1e;
            line-height: 1;
        }

        .pax-focus small {
            font-size: 0.5rem;
            font-weight: 700;
            color: #8e8e93;
            text-transform: uppercase;
        }

        .timer {
            font-weight: 800;
            color: #ff3b30;
            font-size: 0.75rem;
            min-width: 40px;
            text-align: center;
            flex-shrink: 0;
        }

        .btn-action-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: 0.2s;
        }

        .btn-sentar-icon {
            background: #34c759;
            color: white;
        }

        .btn-cancel-icon {
            background: #f2f2f7;
            color: #ff3b30;
            border: 1px solid #e5e5ea;
        }

        /* BOTÕES DE EDITAR E WPP SEM BORDA */
        .btn-ios-action {
            padding: 2px;
            color: #aeaeb2;
            border: none !important;
            background: none !important;
            box-shadow: none !important;
        }

        .btn-ios-action:hover {
            color: #007aff;
        }

        .badge-prio {
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 0.6rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .prio-idoso {
            background: #E5F1FF;
            color: #007AFF;
        }

        .prio-colo {
            background: #FFE5F1;
            color: #FF2D55;
        }

        .prio-gestante {
            background: #FFF9E6;
            color: #FF9500;
        }

        .prio-deficiente {
            background: #F2F2F7;
            color: #5856D6;
        }

        .mesa-cont-group {
            display: flex;
            gap: 3px;
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .mesa-badge {
            background: #fff;
            padding: 2px 4px;
            border-radius: 6px;
            text-align: center;
            min-width: 42px;
            border: 2.3px solid #d1d1d6;
            flex-shrink: 0;
        }

        .mesa-badge span {
            display: block;
            font-size: 1rem;
            font-weight: 900;
            line-height: 1;
        }

        .mesa-badge small {
            font-size: 6px;
            text-transform: uppercase;
            font-weight: 800;
            display: block;
        }

        .m-ate2 {
            border-color: #007aff;
            color: #007aff;
        }

        .m-3a4 {
            border-color: #34c759;
            color: #34c759;
        }

        .m-5a6 {
            border-color: #ff9500;
            color: #ff9500;
        }

        .m-7a8 {
            border-color: #ff3b30;
            color: #ff3b30;
        }

        .m-9mais {
            border-color: #5856d6;
            color: #5856d6;
        }
    </style>
</head>

<body>

    <div class="page-wrapper" style="margin-top: 65px;">

        <div class="toolbar-min no-print">
            <form method="POST" class="row g-2 align-items-end">
                <input type="hidden" name="acao" value="inserir">
                <div class="col-md-2 col-6"><input type="text" name="nome" class="form-control form-control-sm"
                        placeholder="Nome" required></div>
                <div class="col-md-2 col-6"><input type="text" name="telefone" class="form-control form-control-sm"
                        placeholder="WhatsApp" onkeyup="maskPhone(this)"></div>
                <div class="col-md-1 col-4"><select name="qtd_pessoas" class="form-select form-select-sm"><?php for ($i = 1; $i <= 30; $i++)
                    echo "<option value='$i'>$i Pax</option>"; ?></select>
                </div>
                <div class="col-md-2 col-8"><select name="prio_motivo"
                        class="form-select form-select-sm text-warning fw-bold">
                        <option value="">Prioridade?</option>
                        <option value="Idoso">Idoso</option>
                        <option value="Colo">Colo</option>
                        <option value="Gestante">Gestante</option>
                        <option value="Deficiente">Deficiente</option>
                    </select></div>
                <div class="col-md-1 col-12"><button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">OK</button>
                </div>
                <div class="col-md-4 d-flex gap-1 align-items-center justify-content-end border-start ps-3">
                    <input type="date" class="form-control form-control-sm w-auto" value="<?= $filtro_data ?>"
                        onchange="location.href='?data='+this.value">
                    <button type="button" onclick="location.reload()" class="btn btn-light btn-sm border"><i
                            class="material-icons v-middle" style="font-size:18px">sync</i></button>
                </div>
            </form>
        </div>

        <div class="row g-3">
            <!-- COLUNA: ESPERANDO -->
            <div class="col-md-6 col-12">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1 flex-wrap gap-2">
                    <h6 class="m-0 fw-bold"><i class="material-icons v-middle text-danger">groups</i> ESPERANDO
                        (<?= $total_espera_pax ?> PAX)</h6>
                    <div class="mesa-cont-group">
                        <div class="mesa-badge m-ate2"><small>Até 2</small><span><?= $mesas_cont['ate2'] ?></span></div>
                        <div class="mesa-badge m-3a4"><small>3-4</small><span><?= $mesas_cont['3a4'] ?></span></div>
                        <div class="mesa-badge m-5a6"><small>5-6</small><span><?= $mesas_cont['5a6'] ?></span></div>
                        <div class="mesa-badge m-7a8"><small>7-8</small><span><?= $mesas_cont['7a8'] ?></span></div>
                        <div class="mesa-badge m-9mais"><small>9+</small><span><?= $mesas_cont['9mais'] ?></span></div>
                    </div>
                </div>
                <div class="card-ios">
                    <?php $pos = 1;
                    foreach ($espera as $res):
                        $motivo = $res['prio_motivo'];
                        $prioClass = '';
                        if ($motivo == 'Idoso')
                            $prioClass = 'prio-idoso';
                        elseif ($motivo == 'Colo')
                            $prioClass = 'prio-colo';
                        elseif ($motivo == 'Gestante')
                            $prioClass = 'prio-gestante';
                        elseif ($motivo == 'Deficiente')
                            $prioClass = 'prio-deficiente';
                        ?>
                        <div class="fila-item <?= $res['prioridade'] ? 'prio-style' : '' ?>">
                            <div class="pos-num"><b><?= $pos++ ?>º</b>
                                <div class="id-raw"><?= $res['id'] ?></div>
                            </div>
                            <div class="nome-container">
                                <div class="res-nome"><?= htmlspecialchars($res['nome']) ?></div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($res['prioridade']): ?>
                                        <span class="badge-prio <?= $prioClass ?>"><?= $motivo ?></span>
                                    <?php else: ?>
                                        <small class="text-muted" style="font-size: 0.6rem;">Espera</small>
                                    <?php endif; ?>
                                    <button class="btn-ios-action"
                                        onclick="abrirModalEditar(<?= $res['id'] ?>, '<?= addslashes($res['nome']) ?>', <?= $res['num_pessoas'] ?>, '<?= $res['telefone'] ?>')"><i
                                            class="material-icons" style="font-size: 12px;">edit</i></button>
                                    <?php if (!empty($res['telefone'])): ?>
                                        <button class="btn-ios-action text-success"
                                            onclick="enviarMensagemProxima(<?= $res['id'] ?>, '<?= addslashes($res['nome']) ?>', '<?= $res['telefone'] ?>')"><i
                                                class="fab fa-whatsapp" style="font-size: 14px;"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="pax-focus"><strong><?= $res['num_pessoas'] ?></strong><small>Pax</small></div>
                            <div class="timer" data-start="<?= $res['ts_criacao'] ?>">00:00</div>
                            <div class="d-flex gap-1">
                                <button class="btn-action-circle btn-sentar-icon"
                                    onclick="abrirModalMesa(<?= $res['id'] ?>)"><i class="material-icons"
                                        style="font-size: 18px;">event_seat</i></button>
                                <button class="btn-action-circle btn-cancel-icon"
                                    onclick="cancelarFila(<?= $res['id'] ?>)"><i class="material-icons"
                                        style="font-size: 18px;">close</i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- COLUNA: HISTÓRICO -->
            <div class="col-md-6 col-12">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <h6 class="m-0 fw-bold text-success"><i class="material-icons v-middle">history</i> HISTÓRICO</h6>
                    <div><span class="badge bg-success"><?= $total_sentados_pax ?> PAX</span> <span
                            class="badge bg-dark">Méd: <?= $tempo_medio_texto ?></span></div>
                </div>
                <div class="card-ios">
                    <?php foreach ($historico_final as $res):
                        $isC = ($res['status'] == 2); ?>
                        <div class="fila-item hist-style <?= $isC ? 'opacity-50' : '' ?>">
                            <div class="pos-num">
                                <!-- MOSTRA A POSIÇÃO QUE ELE ESTAVA QUANDO SENTOU -->
                                <b><?= $isC ? 'X' : ($res['posicao_fila'] ? $res['posicao_fila'] . 'º' : '?') ?></b>
                                <div class="id-raw"><?= $res['id'] ?></div>
                            </div>
                            <div class="nome-container">
                                <div class="res-nome <?= $isC ? 'text-decoration-line-through text-muted' : '' ?>">
                                    <?= htmlspecialchars($res['nome']) ?>
                                </div>
                                <small class="text-muted fw-bold"
                                    style="font-size: 0.65rem;"><?= $isC ? 'DESISTIU' : 'Mesa: ' . $res['num_mesa'] ?></small>
                            </div>
                            <div class="pax-focus"><strong><?= $res['num_pessoas'] ?></strong><small>Pax</small></div>
                            <div class="text-end" style="min-width: 85px;">
                                <small class="d-block text-muted"
                                    style="font-size: 0.5rem;"><?= $isC ? 'Hora' : 'Sentou às | Fila' ?></small>
                                <div class="fw-bold <?= $isC ? 'text-muted' : 'text-success' ?>"
                                    style="font-size: 0.65rem;">
                                    <?= $isC ? date('H:i', strtotime($res['hora_sentado'])) : date('H:i', strtotime($res['hora_sentado'])) . ' | ' . $res['esperou_formatado'] ?>
                                </div>
                            </div>
                            <button class="btn btn-link p-0 text-muted" onclick="voltarFila(<?= $res['id'] ?>)"><i
                                    class="material-icons" style="font-size: 16px;">undo</i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-body p-4">
                    <h6 class="fw-bold mb-3 text-center">Editar Registro</h6>
                    <label class="small text-muted">Nome</label><input type="text" id="editNome"
                        class="form-control mb-2">
                    <label class="small text-muted">WhatsApp</label><input type="text" id="editTelefone"
                        class="form-control mb-2" onkeyup="maskPhone(this)">
                    <label class="small text-muted">Pessoas</label><select id="editPax" class="form-select mb-3"><?php for ($i = 1; $i <= 30; $i++)
                        echo "<option value='$i'>$i Pax</option>"; ?></select>
                    <div class="d-flex gap-2"><button class="btn btn-light border w-100"
                            data-bs-dismiss="modal">Sair</button><button class="btn btn-primary w-100 fw-bold"
                            onclick="confirmarEdicao()">SALVAR</button></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMesa" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius: 20px;">
                <div class="modal-body text-center p-4">
                    <h6 class="fw-bold mb-3 text-center">Mesa do Cliente?</h6><input type="text" id="inputMesa"
                        class="form-control form-control-lg text-center mb-3" placeholder="Opcional" autofocus>
                    <div class="d-flex gap-2"><button class="btn btn-light w-100"
                            data-bs-dismiss="modal">Sair</button><button class="btn btn-success w-100 fw-bold"
                            onclick="confirmarSentar()">OK</button></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let tempId = null;
        const serverTime = <?= time() ?>;
        const clientTime = Math.floor(Date.now() / 1000);
        const diffTime = serverTime - clientTime;

        function maskPhone(input) {
            let v = input.value.replace(/\D/g, "");
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
            v = v.replace(/(\d{5})(\d)/, "$1-$2");
            input.value = v;
        }

        function updateTimers() {
            const now = Math.floor(Date.now() / 1000) + diffTime;
            document.querySelectorAll('.timer').forEach(timer => {
                const start = parseInt(timer.getAttribute('data-start'));
                const diff = Math.max(0, now - start);
                if (diff >= 3600) {
                    const hours = Math.floor(diff / 3600);
                    const minutes = Math.floor((diff % 3600) / 60);
                    timer.innerText = `${hours}h ${minutes.toString().padStart(2, '0')}m`;
                    timer.style.color = "#5856d6";
                } else {
                    const minutes = Math.floor(diff / 60);
                    const seconds = diff % 60;
                    timer.innerText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    timer.style.color = "#ff3b30";
                }
            });
        }
        setInterval(updateTimers, 1000); updateTimers();

        const myModalMesa = new bootstrap.Modal(document.getElementById('modalMesa'));
        const myModalEdit = new bootstrap.Modal(document.getElementById('modalEditar'));

        function abrirModalMesa(id) { tempId = id; document.getElementById('inputMesa').value = ''; myModalMesa.show(); }
        function abrirModalEditar(id, nome, pax, telefone) { tempId = id; document.getElementById('editNome').value = nome; document.getElementById('editPax').value = pax; document.getElementById('editTelefone').value = telefone; maskPhone(document.getElementById('editTelefone')); myModalEdit.show(); }
        function enviarMensagemProxima(id, nome, telefone) { if (!telefone) return; const msg = `Olá ${nome}, sua mesa é a próxima! Por favor, dirija-se ao responsável para ser acomodado na mesa.`; window.open(`https://wa.me/55${telefone}?text=${encodeURIComponent(msg)}`, '_blank'); }
        function confirmarSentar() { fetch(`fila.php?ajax_acao=sentar&id=${tempId}&mesa=${document.getElementById('inputMesa').value}`).then(() => location.reload()); }
        function confirmarEdicao() { fetch(`fila.php?ajax_acao=editar&id=${tempId}&nome=${encodeURIComponent(document.getElementById('editNome').value)}&pax=${document.getElementById('editPax').value}&telefone=${encodeURIComponent(document.getElementById('editTelefone').value)}`).then(() => location.reload()); }
        function cancelarFila(id) { if (confirm("Confirmar desistência?")) fetch(`fila.php?ajax_acao=cancelar&id=${id}`).then(() => location.reload()); }
        function voltarFila(id) { if (confirm("Mover de volta para a fila?")) fetch(`fila.php?ajax_acao=voltar&id=${id}`).then(() => location.reload()); }
    </script>
</body>

</html>