<?php
session_start();



require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =================== MULTI-EMPRESA ================== */
$empresa_id = (int) ($_SESSION['empresa_id'] ?? 0);

/* ============================================================
   BACKEND – AJAX HANDLERS
============================================================ */

/* 1 – BUSCAR DADOS PARA MODAL DE EDIÇÃO */
if (isset($_GET['acao']) && $_GET['acao'] === 'get_reserva') {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND empresa_id = :emp");
    $stmt->execute([':id' => $id, ':emp' => $empresa_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

/* 2 – SALVAR EDIÇÃO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_edicao') {
    try {
        $id = (int) $_POST['id_reserva'];
        $telefone = preg_replace('/\D/', '', $_POST['telefone']);
        $telefone2 = !empty($_POST['telefone2']) ? preg_replace('/\D/', '', $_POST['telefone2']) : null;

        if (!preg_match('/^(\d{2})9\d{8}$/', $telefone)) {
            echo json_encode(['erro' => 'Telefone principal inválido.']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE clientes 
            SET nome=:nome, data=:data, num_pessoas=:num_pessoas, horario=:horario, telefone=:telefone,
                telefone2=:telefone2, tipo_evento=:tipo_evento, forma_pagamento=:forma_pagamento,
                valor_rodizio=:valor_rodizio, num_mesa=:num_mesa, observacoes=:observacoes
            WHERE id=:id AND empresa_id = :emp
        ");

        $stmt->execute([
            ":id" => $id,
            ":emp" => $empresa_id,
            ":nome" => trim($_POST['nome']),
            ":data" => $_POST['data'],
            ":num_pessoas" => $_POST['num_pessoas'],
            ":horario" => $_POST['horario'],
            ":telefone" => $telefone,
            ":telefone2" => $telefone2,
            ":tipo_evento" => $_POST['tipo_evento'],
            ":forma_pagamento" => $_POST['forma_pagamento'],
            ":valor_rodizio" => $_POST['valor_rodizio'],
            ":num_mesa" => $_POST['num_mesa'],
            ":observacoes" => trim($_POST['observacoes'])
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['erro' => $e->getMessage()]);
    }
    exit;
}

/* 3 – LISTAGEM AJAX */
if (isset($_GET['acao']) && $_GET['acao'] === 'listar_ajax') {
    echo renderizarListaReservas(
        $pdo,
        $_GET['filtro_data'] ?? date('Y-m-d'),
        $_GET['busca_texto'] ?? '',
        $_GET['periodo'] ?? 'todos',
        $_GET['ver_cancelados'] ?? 'false',
        $empresa_id
    );
    exit;
}

/* 4 – CONFIRMAR / ATIVAR / CANCELAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($acao === 'confirmar_reserva') {
        $ok = $pdo->prepare("UPDATE clientes SET confirmado = 1 WHERE id = :id AND empresa_id = :emp")
            ->execute([':id' => $id, ':emp' => $empresa_id]);
        echo json_encode(['success' => $ok]);
        exit;
    }

    if ($acao === 'ativar_reserva') {
        $ok = $pdo->prepare("UPDATE clientes SET status = 1 WHERE id = :id AND empresa_id = :emp")
            ->execute([':id' => $id, ':emp' => $empresa_id]);
        echo json_encode(['success' => $ok]);
        exit;
    }

    if ($acao === 'cancelar_reserva') {
        $ok = $pdo->prepare("UPDATE clientes SET status = 0 WHERE id = :id AND empresa_id = :emp")
            ->execute([':id' => $id, ':emp' => $empresa_id]);
        echo json_encode(['success' => $ok]);
        exit;
    }
}

/* 5 – PERFIL */
if (isset($_GET['acao']) && $_GET['acao'] === 'ver_perfil') {
    $tel = preg_replace('/\D/', '', $_GET['telefone'] ?? '');

    $stats = $pdo->prepare("
        SELECT COUNT(*) as total_reservas,
               SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as total_canceladas,
               SUM(num_pessoas) as total_pessoas_trazidas
        FROM clientes 
        WHERE telefone LIKE :tel AND empresa_id = :emp
    ");
    $stats->execute([':tel' => "%$tel%", ':emp' => $empresa_id]);

    $hist = $pdo->prepare("
        SELECT data, num_pessoas, observacoes, status
        FROM clientes
        WHERE telefone LIKE :tel AND empresa_id = :emp
        ORDER BY data DESC LIMIT 5
    ");
    $hist->execute([':tel' => "%$tel%", ':emp' => $empresa_id]);

    echo json_encode([
        'stats' => $stats->fetch(PDO::FETCH_ASSOC),
        'historico' => $hist->fetchAll(PDO::FETCH_ASSOC)
    ]);
    exit;
}

/* 6 – PREÇOS */
$sqlPreco = $pdo->prepare("SELECT * FROM preco_rodizio WHERE empresa_id = :emp ORDER BY id DESC LIMIT 1");
$sqlPreco->execute([":emp" => $empresa_id]);
$ultimo_preco = $sqlPreco->fetch(PDO::FETCH_ASSOC);

/* ============================================================
   FUNÇÃO DE RENDERIZAÇÃO
============================================================ */
function renderizarListaReservas(PDO $pdo, $filtroData, $buscaTexto, $periodo, $verCancelados = 'false', $empresa_id = 0)
{
    $sqlWhereTotal = " WHERE status != 0 AND empresa_id = :emp ";
    $paramsTotal = [':emp' => $empresa_id];

    if (!empty($filtroData)) {
        $sqlWhereTotal .= " AND data = :data ";
        $paramsTotal[':data'] = $filtroData;
    }
    if (!empty($buscaTexto)) {
        $sqlWhereTotal .= " AND (nome LIKE :texto OR telefone LIKE :texto OR id LIKE :texto) ";
        $paramsTotal[':texto'] = "%$buscaTexto%";
    }

    $tituloPeriodo = "Dia Completo";
    if ($periodo === 'almoco') {
        $sqlWhereTotal .= " AND horario < '18:00:00'";
        $tituloPeriodo = "Almoço";
    } elseif ($periodo === 'jantar') {
        $sqlWhereTotal .= " AND horario >= '18:00:00'";
        $tituloPeriodo = "Jantar";
    }

    $sqlTotal = $pdo->prepare("SELECT SUM(num_pessoas) as total FROM clientes $sqlWhereTotal");
    $sqlTotal->execute($paramsTotal);
    $total_pessoas = $sqlTotal->fetch()['total'] ?? 0;

    $sqlWhereLista = " WHERE empresa_id = :emp ";
    $paramsLista = [':emp' => $empresa_id];

    if ($verCancelados !== 'true') {
        $sqlWhereLista .= " AND status != 0 ";
    }
    if (!empty($filtroData)) {
        $sqlWhereLista .= " AND data = :data ";
        $paramsLista[':data'] = $filtroData;
    }
    if (!empty($buscaTexto)) {
        $sqlWhereLista .= " AND (nome LIKE :texto OR telefone LIKE :texto OR id LIKE :texto) ";
        $paramsLista[':texto'] = "%$buscaTexto%";
    }
    if ($periodo === 'almoco') {
        $sqlWhereLista .= " AND horario < '18:00:00'";
    } elseif ($periodo === 'jantar') {
        $sqlWhereLista .= " AND horario >= '18:00:00'";
    }

    $sql = $pdo->prepare("SELECT * FROM clientes $sqlWhereLista ORDER BY horario ASC");
    $sql->execute($paramsLista);
    $reservas = $sql->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    ?>
    <div class="alert alert-secondary py-2 px-3 d-flex justify-content-between align-items-center mb-3 shadow-sm"
        style="font-size:0.9rem; border-radius:8px; border:none; background:#e9ecef;">
        <span><i class="fas fa-calendar-day text-primary"></i>
            <strong><?= date('d/m', strtotime($filtroData)) ?></strong> - <?= $tituloPeriodo ?></span>
        <span class="badge bg-dark p-2">Total Ativos: <?= $total_pessoas ?></span>
    </div>
    <div class="lista-reservas-conteudo">
        <?php if (count($reservas) > 0): ?>
            <?php foreach ($reservas as $r):
                $isCancelado = ($r['status'] == 0);
                $isConfirmado = ($r['confirmado'] == 1);

                if ($isCancelado) {
                    $classeBorda = 'status-cancelado';
                    $textoBadge = 'Cancelado';
                    $classBadge = 'badge-cancel';
                } else {
                    $classeBorda = $isConfirmado ? 'status-confirmado' : 'status-pendente';
                    $textoBadge = $isConfirmado ? 'Confirmado' : 'Pendente';
                    $classBadge = $isConfirmado ? 'badge-ok' : 'badge-wait';
                }

                $horaShort = date("H:i", strtotime($r['horario']));
                $nome = ucwords(strtolower($r['nome']));
                $nomeSafe = htmlspecialchars($nome, ENT_QUOTES);
                $telLimpo = preg_replace('/[^0-9]/', '', $r['telefone']);
                $linkZapDireto = "https://wa.me/55$telLimpo";
                $msgZap = "Olá $nome! Confirmando reserva para dia " .
                    date('d/m', strtotime($r['data'])) . " às $horaShort para {$r['num_pessoas']} pessoas.";
                $linkZapComMsg = "https://wa.me/55$telLimpo?text=" . urlencode($msgZap);
                $obsTexto = empty($r['observacoes'])
                    ? '<span class="text-muted small" style="font-style:italic">...</span>'
                    : htmlspecialchars($r['observacoes']);
                ?>
                <div class="reserva-card <?= $classeBorda ?>" id="card-<?= $r['id'] ?>">
                    <span class="badge-id-corner"><?= $r['id'] ?></span>
                    <div class="card-content-wrapper">
                        <span class="badge-status <?= $classBadge ?>"><?= $textoBadge ?></span>

                        <div class="sec-info">
                            <div class="client-name"
                                style="<?= $isCancelado ? 'text-decoration: line-through; color: #999;' : '' ?>">
                                <?= htmlspecialchars($nome) ?>
                            </div>
                            <span class="btn-perfil" onclick="abrirModalPerfil('<?= $telLimpo ?>')">
                                <i class="fas fa-history"></i>
                                <span class="d-none d-md-inline">Histórico</span>
                            </span>
                        </div>

                        <div class="sec-meta-group">
                            <div class="meta-item meta-pax">
                                <span class="pax-val" style="<?= $isCancelado ? 'color: #999;' : '' ?>">
                                    <?= (int) $r['num_pessoas'] ?>
                                </span>
                                <span class="pax-lbl">Pax</span>
                            </div>
                            <div class="meta-item meta-time">
                                <span class="time-val" style="<?= $isCancelado ? 'color: #999;' : '' ?>">
                                    <?= $horaShort ?>
                                </span>
                                <?php if (!empty($r['num_mesa'])): ?>
                                    <span class="mesa-val">M:<?= $r['num_mesa'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="sec-obs-container">
                            <div class="obs-box"><?= $obsTexto ?></div>
                        </div>
                    </div>

                    <button class="btn-actions-ios" onclick="toggleIOSMenu(<?= $r['id'] ?>)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="ios-menu" id="ios-menu-<?= $r['id'] ?>">
                        <button class="ios-action text-success"
                            onclick="abrirModalZap(<?= $r['id'] ?>, '<?= addslashes($linkZapComMsg) ?>', '<?= addslashes($linkZapDireto) ?>')"
                            title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button type="button" class="ios-action text-primary" onclick="abrirModalEditar(<?= $r['id'] ?>)"
                            title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>

                        <?php if ($isCancelado): ?>
                            <button class="ios-action text-success" onclick="abrirModalAtivar(<?= $r['id'] ?>, '<?= $nomeSafe ?>')"
                                title="Reativar">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        <?php else: ?>
                            <button class="ios-action text-danger" onclick="abrirModalCancelar(<?= $r['id'] ?>, '<?= $nomeSafe ?>')"
                                title="Cancelar">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted">Nenhuma reserva encontrada.</div>
        <?php endif; ?>
    </div>

    <!-- TABELA IMPRESSÃO -->
    <div id="print-data-hidden" style="display:none;">
        <div class="print-header">
            <h3>Lista de Reservas</h3>
            <p><?= date('d/m/Y', strtotime($filtroData)) ?> | <?= $tituloPeriodo ?></p>
        </div>
        <table class="print-table" style="width:100%; border-collapse:collapse; font-size:10pt; font-family:Arial;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Qtd</th>
                    <th>Hora</th>
                    <th>Obs</th>
                    <th>Mesa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $r):
                    if ($r['status'] == 0)
                        continue; ?>
                    <tr>
                        <td style="border:1px solid #000;padding:4px;"><?= htmlspecialchars($r['id']) ?></td>
                        <td style="border:1px solid #000;padding:4px;"><?= htmlspecialchars($r['nome']) ?></td>
                        <td style="border:1px solid #000;padding:4px; text-align:center;"><?= (int) $r['num_pessoas'] ?></td>
                        <td style="border:1px solid #000;padding:4px; text-align:center;">
                            <?= date("H:i", strtotime($r['horario'])) ?>
                        </td>
                        <td style="border:1px solid #000;padding:4px;"><?= htmlspecialchars($r['observacoes']) ?></td>
                        <td style="border:1px solid #000;padding:4px; text-align:center;">
                            <?= htmlspecialchars($r['num_mesa'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

/* ====================== CALENDÁRIO ========================== */
function generateCalendar(PDO $pdo, int $month, int $year, int $empresa_id): string
{
    global $filtroData, $buscaTexto, $periodo;

    $stmt = $pdo->prepare("
        SELECT data,
               SUM(CASE WHEN horario BETWEEN '11:00:00' AND '17:59:00' THEN IF(status!=0, num_pessoas, 0) ELSE 0 END) AS almoco,
               SUM(CASE WHEN horario BETWEEN '18:00:00' AND '23:59:00' THEN IF(status!=0, num_pessoas, 0) ELSE 0 END) AS jantar
        FROM clientes
        WHERE MONTH(data) = :m AND YEAR(data) = :y AND empresa_id = :emp
        GROUP BY data
    ");
    $stmt->execute(['m' => $month, 'y' => $year, ':emp' => $empresa_id]);

    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $map[$row['data']] = $row;
    }

    $stmtTotal = $pdo->prepare("
        SELECT COUNT(*) as total_res, SUM(num_pessoas) as total_pax
        FROM clientes
        WHERE MONTH(data) = :m AND YEAR(data) = :y AND status != 0 AND empresa_id = :emp
    ");
    $stmtTotal->execute(['m' => $month, 'y' => $year, ':emp' => $empresa_id]);
    $totaisMes = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalPaxMes = $totaisMes['total_pax'] ?? 0;
    $totalResMes = $totaisMes['total_res'] ?? 0;

    $firstDayTs = mktime(0, 0, 0, $month, 1, $year);
    $numDays = (int) date('t', $firstDayTs);
    $dayOfWeek = (int) date('w', $firstDayTs);

    $monthName = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"][$month - 1];

    $prevM = $month - 1;
    $prevY = $year;
    if ($prevM < 1) {
        $prevM = 12;
        $prevY--;
    }
    $nextM = $month + 1;
    $nextY = $year;
    if ($nextM > 12) {
        $nextM = 1;
        $nextY++;
    }

    $q = http_build_query(['filtro_data' => $filtroData, 'busca_texto' => $buscaTexto, 'periodo' => $periodo]);

    $html = "<div class='calendar-container'>
        <div class='cal-header-modern'>
            <a href='?month=$prevM&year=$prevY&$q' class='btn-nav-cal'><i class='fas fa-chevron-left'></i></a>
            <div class='cal-title-group'>
                <span class='cal-month-year'>{$monthName} {$year}</span>
                <div class='cal-stats-badges'>
                    <span title=\"Total\"><i class='fas fa-users'></i> {$totalPaxMes}</span>
                    <span class='divider'>•</span>
                    <span title=\"Reservas\"><i class='fas fa-file-alt'></i> {$totalResMes}</span>
                </div>
            </div>
            <a href='?month=$nextM&year=$nextY&$q' class='btn-nav-cal'><i class='fas fa-chevron-right'></i></a>
        </div>
        <div class='table-responsive'>
        <table class='cal-table'>
            <thead>
                <tr>
                    <th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>
                </tr>
            </thead>
            <tbody><tr>";

    if ($dayOfWeek > 0) {
        for ($i = 0; $i < $dayOfWeek; $i++) {
            $html .= "<td class='empty'></td>";
        }
    }

    $d = 1;
    while ($d <= $numDays) {
        if ($dayOfWeek == 7) {
            $dayOfWeek = 0;
            $html .= "</tr><tr>";
        }
        $currDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $alm = $map[$currDate]['almoco'] ?? 0;
        $jan = $map[$currDate]['jantar'] ?? 0;
        $cls = ($currDate === date('Y-m-d')) ? 'today' : '';
        if ($currDate === $filtroData)
            $cls .= ' selected';

        $html .= "<td class='day-cell $cls' onclick=\"mudarData('$currDate', this)\">
                    <div class='d-flex justify-content-between align-items-start'>
                        <span class='day-num'>$d</span>
                        <button class='btn-eye-sm' onclick=\"verReservasDia('$currDate', event)\">
                            <i class='fas fa-eye'></i>
                        </button>
                    </div>
                    <div class='pills-container'>";
        if ($alm > 0)
            $html .= "<span class='pill pill-a'>A: $alm</span>";
        if ($jan > 0)
            $html .= "<span class='pill pill-j'>J: $jan</span>";
        $html .= "</div></td>";

        $d++;
        $dayOfWeek++;
    }

    if ($dayOfWeek != 7) {
        for ($i = 0; $i < (7 - $dayOfWeek); $i++) {
            $html .= "<td class='empty'></td>";
        }
    }
    $html .= "</tr></tbody></table></div></div>";
    return $html;
}

require 'cabecalho.php';

$filtroData = $_REQUEST['filtro_data'] ?? date('Y-m-d');
$buscaTexto = $_REQUEST['busca_texto'] ?? '';
$periodo = $_REQUEST['periodo'] ?? 'todos';

$calendarHtml = generateCalendar(
    $pdo,
    isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m', strtotime($filtroData)),
    isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y', strtotime($filtroData)),
    $empresa_id
);
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
        body {
            background-color: #f0f2f5;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            padding-bottom: 60px;
        }

        .btn-actions-ios {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #f8f9fa;
            color: #555;
            position: absolute;
            top: 10px;
            right: 8px;
            z-index: 20;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-actions-ios:hover {
            background: #e2e2e2;
        }

        .ios-menu {
            position: absolute;
            right: 45px;
            top: 10px;
            background: white;
            border-radius: 12px;
            padding: 5px;
            display: none;
            flex-direction: column;
            gap: 5px;
            width: 45px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 100;
        }

        .ios-menu.show {
            display: flex;
            animation: iosAppear 0.2s ease-out;
        }

        .ios-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            color: #383838ff;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s;
        }

        .ios-action:hover {
            background: #f0f0f0;
        }

        @keyframes iosAppear {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .sec-actions {
            display: none !important;
        }

        /* CALENDAR */
        :root {
            --cal-bg: #212529;
            --cal-header: #2c3034;
            --cal-cell: #2c3034;
            --cal-hover: #343a40;
            --cal-text: #e9ecef;
            --cal-muted: #adb5bd;
            --accent: #0d6efd;
            --pill-a: #ffc107;
            --pill-j: #0dcaf0;
        }

        #cal-wrapper {
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transition: max-height 0.5s ease-in-out, opacity 0.4s ease-in-out;
            margin-bottom: 0;
        }

        #cal-wrapper.collapsed {
            max-height: 0;
            opacity: 0;
        }

        .calendar-container {
            background: var(--cal-bg);
            color: var(--cal-text);
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 0;
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .cal-header-modern {
            background: var(--cal-header);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #3d4146;
        }

        .cal-title-group {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cal-month-year {
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: capitalize;
            color: #fff;
            margin-bottom: 2px;
        }

        .cal-stats-badges {
            font-size: 0.85rem;
            color: var(--cal-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nav-cal {
            color: var(--cal-muted);
            font-size: 1.1rem;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.2s;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-nav-cal:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .cal-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .cal-table th {
            text-align: center;
            color: var(--cal-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 8px 0;
            background: var(--cal-bg);
        }

        .cal-table td {
            background: var(--cal-cell);
            border: 1px solid #3d4146;
            height: 60px;
            vertical-align: top;
            padding: 4px;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .cal-table td:not(.empty):hover {
            background: var(--cal-hover);
        }

        .cal-table td.today {
            background: #3c4149;
            border: 1px solid var(--accent);
        }

        .cal-table td.selected {
            background: #495057;
            box-shadow: inset 0 0 0 1px #fff;
        }

        .day-num {
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            margin-left: 2px;
        }

        .btn-eye-sm {
            background: none;
            border: none;
            padding: 0;
            color: var(--cal-muted);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: 0.2s;
            cursor: pointer;
            z-index: 10;
            position: relative;
        }

        .btn-eye-sm:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }

        .pills-container {
            display: flex;
            justify-content: flex-start;
            gap: 3px;
            margin-top: 2px;
            flex-wrap: wrap;
        }

        .pill {
            font-size: 0.65rem;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 700;
            color: #000;
            line-height: 1;
            display: inline-block;
        }

        .pill-a {
            background: var(--pill-a);
        }

        .pill-j {
            background: var(--pill-j);
        }

        .toggle-cal-container {
            text-align: center;
            margin-top: -3px;
            margin-bottom: 20px;
            position: relative;
            z-index: 10;
        }

        .btn-toggle-cal {
            background: var(--cal-header);
            color: var(--cal-muted);
            border: 1px solid #3d4146;
            border-top: none;
            padding: 5px 20px;
            font-size: 0.8rem;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: 0.2s;
        }

        .btn-toggle-cal:hover {
            background: var(--cal-hover);
            color: #fff;
        }

        .filter-bar {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            max-width: 900px;
            margin: 0 auto 20px auto;
        }

        .alert-secondary {
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        #area-lista-reservas {
            max-width: 900px;
            margin: 0 auto;
        }

        .reserva-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 12px;
            border-left: 5px solid #ccc;
            position: relative;
            padding: 5px 10px;
            padding-right: 50px;
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            transition: 0.2s;
            overflow: visible !important;
        }

        .status-confirmado {
            border-left-color: #198754 !important;
        }

        .status-pendente {
            border-left-color: #fd7e14 !important;
        }

        .status-cancelado {
            border-left-color: #dc3545 !important;
            background-color: #fff5f5;
        }

        .card-content-wrapper {
            display: flex;
            width: 100%;
            align-items: center;
        }

        .sec-info {
            flex: 0 0 220px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .client-name {
            font-weight: 700;
            color: #333;
            font-size: clamp(12px, 4vw, 16px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            width: 100%;
        }

        .btn-perfil {
            font-size: 0.75rem;
            color: var(--accent);
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            margin-top: 2px;
        }

        .sec-meta-group {
            flex: 0 0 110px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 1px solid #f0f0f0;
            padding: 0 10px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 45px;
        }

        .pax-val {
            font-size: 1rem;
            font-weight: 800;
            color: #fd7e14;
            line-height: 1;
        }

        .pax-lbl {
            font-size: 0.6rem;
            color: #888;
            text-transform: uppercase;
        }

        .time-val {
            font-size: 0.9rem;
            font-weight: 700;
            color: #333;
        }

        .mesa-val {
            font-size: 0.55rem;
            background: #eee;
            padding: 1px 6px;
            border-radius: 4px;
            color: #555;
            margin-top: 2px;
        }

        .sec-obs-container {
            flex: 1;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
        }

        .obs-box {
            background-color: #fcfcfc;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.8rem;
            color: #666;
            width: 100%;
            height: 60px;
            overflow-y: auto;
            white-space: normal;
        }

        @media (max-width: 768px) {
            .sec-info {
                flex: 0 0 150px;
            }

            .sec-meta-group {
                flex: 0 0 95px;
            }
        }

        .badge-status {
            position: absolute;
            top: 1px;
            right: 50px;
            font-size: 0.35rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            z-index: 5;
        }

        .badge-id-corner {
            position: absolute;
            top: 4px;
            left: 16px;
            font-size: 0.5rem;
            font-weight: 800;
            color: #adb5bd;
            z-index: 5;
        }

        .badge-ok {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-wait {
            background: #fff3cd;
            color: #664d03;
        }

        .badge-cancel {
            background: #dc3545;
            color: #fff;
        }

        .modal-overlay-dia {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(2px);
        }

        .modal-overlay-dia.show {
            display: flex;
        }

        .modal-box-dia {
            background: #fff;
            width: 90%;
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 85vh;
        }

        .modal-header-dia {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .modal-body-dia {
            padding: 0;
            overflow-y: auto;
        }

        .reserva-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-switch .form-check-input {
            width: 2em;
            height: 1em;
            cursor: pointer;
        }

        .toggle-cancel-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 2px;
            display: block;
            text-align: center;
        }

        .btn-period {
            width: 44px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .btn-period.active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .btn-icon {
            width: 42px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon i {
            font-size: 14px;
        }

        .modal-ios-label {
            font-weight: 600;
            margin-top: 10px;
            color: #333;
            font-size: 0.9rem;
        }

        .modal-ios-input {
            margin-top: 5px;
            border-radius: 12px !important;
            border: 1px solid #d1d1d6 !important;
            padding: 10px 12px !important;
            font-size: 1rem !important;
            width: 100%;
            background: #fafafa;
            transition: .2s;
        }

        .modal-ios-input:focus {
            border-color: #007AFF !important;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.25) !important;
            background: #fff;
        }

        @media (max-width: 480px) {

            .client-name {
                max-width: 100px;
                display: inline-block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            .sec-info {
                flex: 0 0 110px !important;
                padding-right: 5px;
            }

            .sec-meta-group {
                flex: 0 0 85px !important;
                padding: 0 5px !important;
                border-left: 1px solid #eee;
            }

            .sec-obs-container {
                flex: 1 !important;
                padding: 4px 5px !important;
                justify-content: flex-start !important;
            }

            .obs-box {
                height: 50px !important;
                font-size: 0.75rem !important;
                padding: 4px 6px !important;
                max-width: none !important;
            }

            .reserva-card {
                padding-right: 40px !important;
            }
        }

        @media (min-width: 769px) {
            .sec-info {
                flex: 0 0 220px;
            }

            .sec-meta-group {
                flex: 0 0 110px;
            }

            .sec-obs-container {
                flex: 1;
            }

            .obs-box {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-3 no-print">
        <div id="cal-wrapper"><?= $calendarHtml ?></div>
        <div class="toggle-cal-container">
            <button class="btn-toggle-cal" onclick="toggleCal()" id="btnToggleText">
                Esconder Calendário <i class="fas fa-chevron-up"></i>
            </button>
        </div>

        <div class="filter-bar d-flex align-items-end gap-2 p-2 bg-white rounded shadow-sm">
            <div style="min-width: 125px; max-width: 140px;">
                <label class="form-label fw-bold small mb-0" style="font-size: 0.7rem;">Data</label>
                <input type="date" name="filtro_data" id="filtro_data" class="form-control form-control-sm"
                    value="<?= htmlspecialchars($filtroData) ?>" onchange="carregarListaAjax()">
            </div>
            <div style="min-width: 100px; flex: 1;">
                <label class="form-label fw-bold small mb-0" style="font-size: 0.7rem;">Busca</label>
                <input type="text" name="busca_texto" id="busca_texto" class="form-control form-control-sm"
                    placeholder="Nome/Tel" value="<?= htmlspecialchars($buscaTexto) ?>" onkeyup="carregarListaAjax()">
            </div>
            <div>
                <label class="form-label fw-bold small mb-0 d-block text-center"
                    style="font-size: 0.7rem;">Período</label>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" onclick="setPeriodo('todos')" class="btn btn-outline-secondary btn-period"
                        id="btn-todos"><i class="fas fa-list-ul small"></i></button>
                    <button type="button" onclick="setPeriodo('almoco')" class="btn btn-outline-secondary btn-period"
                        id="btn-almoco"><i class="fas fa-sun small"></i></button>
                    <button type="button" onclick="setPeriodo('jantar')" class="btn btn-outline-secondary btn-period"
                        id="btn-jantar"><i class="fas fa-moon small"></i></button>
                </div>
                <input type="hidden" name="periodo" id="periodoInput" value="<?= $periodo ?>">
            </div>
            <div class="d-flex flex-column align-items-center justify-content-end" style="min-width: 50px;">
                <span class="toggle-cancel-label">Canc.</span>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" id="checkCancelados" onchange="carregarListaAjax()">
                </div>
            </div>
            <div class="d-flex gap-1" style="min-width: 70px;">
                <button type="button" onclick="imprimirReservas()" class="btn btn-secondary btn-sm btn-icon"
                    style="width: 32px; height: 31px;"><i class="fas fa-print small"></i></button>
                <a href="adicionar_reserva.php" class="btn btn-primary btn-sm btn-icon"
                    style="width: 32px; height: 31px;"><i class="fas fa-plus small"></i></a>
            </div>
        </div>

        <div id="area-lista-reservas">
            <?= renderizarListaReservas($pdo, $filtroData, $buscaTexto, $periodo, 'false', $empresa_id) ?>
        </div>
    </div>

    <!-- MODAL - Reservas do Dia -->
    <div class="modal-overlay-dia" id="modalDia" onclick="if(event.target === this) fecharModalDia()">
        <div class="modal-box-dia">
            <div class="modal-header-dia">
                <h5 id="modalTitle">Reservas do Dia</h5>
                <button type="button" class="btn-close" onclick="fecharModalDia()"></button>
            </div>
            <div class="modal-body-dia" id="modalContent">
                <div class="text-center p-3">Carregando...</div>
            </div>
        </div>
    </div>

    <!-- MODAL - WhatsApp -->
    <div class="modal fade" id="modalZap" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title">WhatsApp</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-grid gap-2">
                    <button class="btn btn-outline-success" id="btnZapConfirmar">Confirmar & Enviar</button>
                    <button class="btn btn-outline-secondary" id="btnZapDireto">Apenas Abrir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL - Perfil do Cliente -->
    <div class="modal fade" id="modalPerfil" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Perfil do Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="p-2 border rounded">
                                <div class="fw-bold" id="pTotal">0</div>
                                <small class="text-muted">Reservas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded">
                                <div class="fw-bold text-danger" id="pCancel">0</div>
                                <small class="text-muted">Canceladas</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 border rounded">
                                <div class="fw-bold text-success" id="pPessoas">0</div>
                                <small class="text-muted">Pessoas</small>
                            </div>
                        </div>
                    </div>
                    <h6>Histórico Recente</h6>
                    <ul class="list-group" id="listaHistorico"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL - Ativar Reserva -->
    <div class="modal fade" id="modalAtivar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reativar Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja reativar a reserva de <strong id="nomeAtivar"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarAtivar">Reativar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL - Cancelar Reserva -->
    <div class="modal fade" id="modalCancelar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja cancelar a reserva de <strong id="nomeCancelar"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarCancelar">Sim, Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL - Editar Reserva -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title"><i class="fas fa-pen"></i> Editar Reserva</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <form id="formEditar">
                        <input type="hidden" id="edit_id" name="id_reserva">

                        <label class="modal-ios-label">Telefone:</label>
                        <input type="text" id="edit_telefone" name="telefone" class="modal-ios-input" maxlength="15"
                            oninput="maskPhone(this)" required>

                        <label class="modal-ios-label">Nome:</label>
                        <input type="text" id="edit_nome" name="nome" class="modal-ios-input" required>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="modal-ios-label">Data:</label>
                                <input type="date" id="edit_data" name="data" class="modal-ios-input" required>
                            </div>
                            <div class="col-6">
                                <label class="modal-ios-label">Horário:</label>
                                <select id="edit_horario" name="horario" class="modal-ios-input" required>
                                    <optgroup label="Almoço">
                                        <option value="11:30:00">11:30</option>
                                        <option value="12:00:00">12:00</option>
                                        <option value="12:30:00">12:30</option>
                                        <option value="13:00:00">13:00</option>
                                        <option value="13:30:00">13:30</option>
                                        <option value="14:00:00">14:00</option>
                                        <option value="14:30:00">14:30</option>
                                        <option value="15:00:00">15:00</option>
                                    </optgroup>
                                    <optgroup label="Jantar">
                                        <option value="17:00:00">17:00</option>
                                        <option value="17:30:00">17:30</option>
                                        <option value="18:00:00">18:00</option>
                                        <option value="18:30:00">18:30</option>
                                        <option value="19:00:00">19:00</option>
                                        <option value="19:30:00">19:30</option>
                                        <option value="20:00:00">20:00</option>
                                        <option value="20:30:00">20:30</option>
                                        <option value="21:00:00">21:00</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <label class="modal-ios-label">Nº Pessoas:</label>
                        <input type="number" id="edit_num_pessoas" name="num_pessoas" class="modal-ios-input" required>

                        <label class="modal-ios-label">Tel. Alternativo:</label>
                        <input type="text" id="edit_telefone2" name="telefone2" class="modal-ios-input" maxlength="15"
                            oninput="maskPhone(this)">

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="modal-ios-label">Forma Pagto:</label>
                                <select id="edit_forma_pagamento" name="forma_pagamento" class="modal-ios-input">
                                    <option value="">Selecione</option>
                                    <option value="unica">Única</option>
                                    <option value="individual">Individual</option>
                                    <option value="U (rod) I (beb)">Única (rod) / Individual (beb)</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="modal-ios-label">Evento:</label>
                                <select id="edit_tipo_evento" name="tipo_evento" class="modal-ios-input">
                                    <option value="">Selecione</option>
                                    <option value="Aniversario">Aniversário</option>
                                    <option value="Conf. fim de ano">Conf. Fim de Ano</option>
                                    <option value="Formatura">Formatura</option>
                                    <option value="Casamento">Casamento</option>
                                    <option value="Conf. Familia">Conf. Família</option>
                                </select>
                            </div>
                        </div>

                        <label class="modal-ios-label">Valor Rodízio:</label>
                        <select id="edit_valor_rodizio" name="valor_rodizio" class="modal-ios-input">
                            <option value="">Selecione</option>
                            <?php if ($ultimo_preco): ?>
                                <option value="<?= $ultimo_preco['almoco'] ?>">Almoço - R$ <?= $ultimo_preco['almoco'] ?>
                                </option>
                                <option value="<?= $ultimo_preco['jantar'] ?>">Jantar - R$ <?= $ultimo_preco['jantar'] ?>
                                </option>
                                <option value="<?= $ultimo_preco['outros'] ?>">Sábado - R$ <?= $ultimo_preco['outros'] ?>
                                </option>
                                <option value="<?= $ultimo_preco['domingo_almoco'] ?>">Domingo Almoço - R$
                                    <?= $ultimo_preco['domingo_almoco'] ?></option>
                            <?php endif; ?>
                        </select>

                        <label class="modal-ios-label">Mesa:</label>
                        <select id="edit_num_mesa" name="num_mesa" class="modal-ios-input">
                            <option value="">Selecione</option>
                            <option value="Salão 1">Salão 1</option>
                            <option value="Salão 2">Salão 2</option>
                            <option value="Salão 3">Salão 3</option>
                            <?php for ($i = 1; $i <= 99; $i++): ?>
                                <option value="<?= $i ?>">Mesa <?= $i ?></option>
                            <?php endfor; ?>
                        </select>

                        <label class="modal-ios-label">Observações:</label>
                        <textarea id="edit_observacoes" name="observacoes" class="modal-ios-input" rows="2"></textarea>
                    </form>
                    <div class="d-grid mt-3">
                        <button class="btn btn-primary" onclick="salvarEdicao()">Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const URL_ATUAL = window.location.pathname;
        let curId, curLink, curLinkD, ativId, cancId;
        let modalAtivar, modalCancelar, modalEditar;

        document.addEventListener("DOMContentLoaded", () => {
            modalAtivar = new bootstrap.Modal(document.getElementById('modalAtivar'));
            modalCancelar = new bootstrap.Modal(document.getElementById('modalCancelar'));
            modalEditar = new bootstrap.Modal(document.getElementById('modalEditar'));

            // Fecha o menu iOS ao clicar fora
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.btn-actions-ios') && !e.target.closest('.ios-menu')) {
                    document.querySelectorAll('.ios-menu').forEach(m => m.classList.remove('show'));
                }
            });
        });

        /* === MENU iOS === */
        function toggleIOSMenu(id) {
            event.stopPropagation();
            document.querySelectorAll(".ios-menu").forEach(m => {
                if (m.id !== "ios-menu-" + id) m.classList.remove("show");
            });
            const menu = document.getElementById("ios-menu-" + id);
            if (menu) menu.classList.toggle("show");
        }

        /* === CALENDÁRIO – MOSTRAR/ESCONDER === */
        function toggleCal() {
            const el = document.getElementById('cal-wrapper');
            const btn = document.getElementById('btnToggleText');
            const ativo = el.classList.toggle('collapsed');
            btn.innerHTML = ativo
                ? 'Mostrar Calendário <i class="fas fa-chevron-down"></i>'
                : 'Esconder Calendário <i class="fas fa-chevron-up"></i>';
        }

        /* === PERÍODO === */
        function setPeriodo(val) {
            document.getElementById('periodoInput').value = val;
            document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
            const btn = document.getElementById('btn-' + val);
            if (btn) btn.classList.add('active');
            carregarListaAjax();
        }

        /* === CARREGAR LISTAGEM PRINCIPAL === */
        function carregarListaAjax(dataISO = null) {
            if (dataISO) document.getElementById('filtro_data').value = dataISO;

            const d = document.getElementById('filtro_data').value;
            const b = document.getElementById('busca_texto').value;
            const p = document.getElementById('periodoInput').value;
            const verCancelados = document.getElementById('checkCancelados').checked;
            const container = document.getElementById('area-lista-reservas');

            container.style.opacity = '0.6';

            fetch(URL_ATUAL + '?' + new URLSearchParams({
                acao: 'listar_ajax',
                filtro_data: d,
                busca_texto: b,
                periodo: p,
                ver_cancelados: verCancelados
            }))
                .then(r => r.text())
                .then(html => {
                    container.innerHTML = html;
                    container.style.opacity = '1';
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('filtro_data', d);
                    window.history.pushState({}, '', newUrl);
                });
        }

        /* === CALENDÁRIO – MUDAR DIA === */
        function mudarData(dataISO, el) {
            document.querySelectorAll('.cal-table td').forEach(c => c.classList.remove('selected'));
            if (el) el.classList.add('selected');
            carregarListaAjax(dataISO);
        }

        /* === MODAL – RESERVAS DO DIA === */
        function verReservasDia(data, e) {
            if (e) {
                e.stopPropagation();
                e.preventDefault();
            }

            const modal = document.getElementById('modalDia');
            modal.classList.add('show');

            document.getElementById('modalTitle').innerText = 'Reservas - ' + data.split('-').reverse().join('/');
            document.getElementById('modalContent').innerHTML =
                '<div class="text-center p-3 text-muted">Buscando...</div>';

            fetch('ajax_reservas_dia.php?data=' + data)
                .then(r => r.json())
                .then(json => {
                    if (!json.lista || json.lista.length === 0) {
                        document.getElementById('modalContent').innerHTML =
                            '<div class="p-3 text-center text-muted">Nenhuma reserva encontrada.</div>';
                        return;
                    }
                    let h = '';
                    json.lista.forEach(r => {
                        h += `<div class="reserva-item">
                    <div>
                        <strong>${r.nome}</strong>
                        <br><small class="text-muted">${r.num_pessoas} pax • ${r.horario.substring(0, 5)}</small>
                    </div>
                    <button type="button" onclick="fecharModalDia(); setTimeout(() => abrirModalEditar(${r.id}), 300)" 
                            class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-pen"></i> Editar
                    </button>
                </div>`;
                    });
                    h += `<div class="p-2 text-center bg-light fw-bold">Total: ${json.resumo.total} pessoas</div>`;
                    document.getElementById('modalContent').innerHTML = h;
                })
                .catch(err => {
                    document.getElementById('modalContent').innerHTML =
                        '<div class="p-3 text-center text-danger">Erro ao carregar dados.</div>';
                    console.error(err);
                });
        }

        function fecharModalDia() {
            document.getElementById('modalDia').classList.remove('show');
        }

        /* === MODAL – PERFIL === */
        function abrirModalPerfil(tel) {
            new bootstrap.Modal(document.getElementById('modalPerfil')).show();
            fetch(URL_ATUAL + '?acao=ver_perfil&telefone=' + tel)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('pTotal').innerText = d.stats.total_reservas || 0;
                    document.getElementById('pCancel').innerText = d.stats.total_canceladas || 0;
                    document.getElementById('pPessoas').innerText = d.stats.total_pessoas_trazidas || 0;

                    let h = '';
                    d.historico.forEach(x => {
                        h += `<li class="list-group-item d-flex justify-content-between">
                    <span>${new Date(x.data).toLocaleDateString('pt-BR')}</span>
                    <span>${x.num_pessoas}p <i class="fas ${x.status == 0 ? 'fa-times text-danger' : 'fa-check text-success'}"></i></span>
                </li>`;
                    });
                    document.getElementById('listaHistorico').innerHTML =
                        h || '<li class="list-group-item text-center">Sem histórico.</li>';
                });
        }

        /* === ATIVAR / CANCELAR === */
        function abrirModalAtivar(id, nome) {
            document.querySelectorAll('.ios-menu').forEach(m => m.classList.remove('show'));
            ativId = id;
            document.getElementById('nomeAtivar').innerText = nome;
            modalAtivar.show();
        }

        document.getElementById('btnConfirmarAtivar').onclick = () => {
            fetch(URL_ATUAL, { method: 'POST', body: new URLSearchParams({ acao: 'ativar_reserva', id: ativId }) })
                .then(() => { modalAtivar.hide(); carregarListaAjax(); });
        };

        function abrirModalCancelar(id, nome) {
            document.querySelectorAll('.ios-menu').forEach(m => m.classList.remove('show'));
            cancId = id;
            document.getElementById('nomeCancelar').innerText = nome;
            modalCancelar.show();
        }

        document.getElementById('btnConfirmarCancelar').onclick = () => {
            fetch(URL_ATUAL, { method: 'POST', body: new URLSearchParams({ acao: 'cancelar_reserva', id: cancId }) })
                .then(() => { modalCancelar.hide(); carregarListaAjax(); });
        };

        function confirmarReserva(id) {
            fetch(URL_ATUAL, { method: 'POST', body: new URLSearchParams({ acao: 'confirmar_reserva', id }) })
                .then(() => carregarListaAjax());
        }

        /* === WHATSAPP === */
        function abrirModalZap(id, linkMsg, linkDireto) {
            document.querySelectorAll('.ios-menu').forEach(m => m.classList.remove('show'));
            curId = id;
            curLink = linkMsg;
            curLinkD = linkDireto;
            new bootstrap.Modal(document.getElementById('modalZap')).show();
        }

        document.getElementById('btnZapConfirmar').onclick = () => {
            window.open(curLink, '_blank');
            const fd = new FormData();
            fd.append('acao', 'confirmar_reserva');
            fd.append('id', curId);
            fetch(URL_ATUAL, { method: 'POST', body: fd })
                .then(() => setTimeout(() => carregarListaAjax(), 1000));
            bootstrap.Modal.getInstance(document.getElementById('modalZap')).hide();
        };

        document.getElementById('btnZapDireto').onclick = () => {
            window.open(curLinkD, '_blank');
            bootstrap.Modal.getInstance(document.getElementById('modalZap')).hide();
        };

        /* === EDITAR === */
        function abrirModalEditar(id) {
            document.querySelectorAll(".ios-menu").forEach(m => m.classList.remove("show"));
            fetch(URL_ATUAL + '?acao=get_reserva&id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (!data) return alert("Erro ao carregar dados!");
                    for (let k in data) {
                        const el = document.getElementById('edit_' + k);
                        if (el) el.value = data[k] ?? '';
                    }
                    modalEditar.show();
                });
        }

        function salvarEdicao() {
            const form = document.getElementById('formEditar');
            if (!form.reportValidity()) return;
            const fd = new FormData(form);
            fd.append('acao', 'salvar_edicao');
            fetch(URL_ATUAL, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { modalEditar.hide(); carregarListaAjax(); }
                    else alert('Erro: ' + (d.erro || 'Desconhecido'));
                });
        }

        /* === IMPRESSÃO === */
        function imprimirReservas() {
            const conteudo = document.getElementById('print-data-hidden')?.innerHTML
                || document.getElementById('area-lista-reservas')?.innerHTML;
            const w = window.open('', '', 'height=900,width=1100');
            w.document.write(`<html><head><title>Imprimir</title></head><body>${conteudo}</body></html>`);
            w.document.close();
            w.print();
        }

        /* === MASK DE TELEFONE === */
        function maskPhone(input) {
            let v = input.value.replace(/\D/g, "");
            if (v.length > 11) v = v.slice(0, 11);
            input.value = v.length <= 10
                ? v.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{4})(\d)/, "$1-$2")
                : v.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2");
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>