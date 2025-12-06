<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

/* ============================================================
   FUNÇÃO AUXILIAR: RENDERIZAR LISTA
============================================================ */
function renderizarListaReservas($pdo, $filtroData, $buscaTexto, $periodo)
{
    $sqlWhere = " WHERE status != 0 ";
    $params = [];

    if (!empty($filtroData)) {
        $sqlWhere .= " AND data = :data ";
        $params[':data'] = $filtroData;
    }
    if (!empty($buscaTexto)) {
        $sqlWhere .= " AND (nome LIKE :texto OR telefone LIKE :texto OR id LIKE :texto) ";
        $params[':texto'] = "%$buscaTexto%";
    }

    $tituloPeriodo = "Dia Completo";
    if ($periodo === 'almoco') {
        $sqlWhere .= " AND horario < '18:00:00'";
        $tituloPeriodo = "Almoço";
    } elseif ($periodo === 'jantar') {
        $sqlWhere .= " AND horario >= '18:00:00'";
        $tituloPeriodo = "Jantar";
    }

    // Totais
    $sqlTotal = $pdo->prepare("SELECT SUM(num_pessoas) as total FROM clientes $sqlWhere");
    $sqlTotal->execute($params);
    $total_pessoas = $sqlTotal->fetch()['total'] ?? 0;

    // Lista
    $sql = $pdo->prepare("SELECT * FROM clientes $sqlWhere ORDER BY horario ASC");
    $sql->execute($params);
    $reservas = $sql->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    ?>
    <!-- RESUMO DIA -->
    <div class="alert alert-secondary py-2 px-3 d-flex justify-content-between align-items-center mb-3 shadow-sm"
        style="font-size:0.9rem; border-radius:8px; border:none; background:#e9ecef;">
        <span>
            <i class="fas fa-calendar-day text-primary"></i> <strong><?= date('d/m', strtotime($filtroData)) ?></strong> -
            <?= $tituloPeriodo ?>
        </span>
        <span class="badge bg-dark p-2">Total: <?= $total_pessoas ?></span>
    </div>

    <div class="lista-reservas-conteudo">
        <?php if (count($reservas) > 0): ?>
            <?php foreach ($reservas as $r):
                $isConfirmado = ($r['confirmado'] == 1);
                $classeBorda = $isConfirmado ? 'status-confirmado' : 'status-pendente';
                $horaShort = date("H:i", strtotime($r['horario']));
                $nome = ucwords(strtolower($r['nome']));
                $telLimpo = preg_replace('/[^0-9]/', '', $r['telefone']);
                $linkZapDireto = "https://wa.me/55$telLimpo";
                $msgZap = "Olá $nome! Confirmando reserva para dia " . date('d/m', strtotime($r['data'])) . " às $horaShort para {$r['num_pessoas']} pessoas.";
                $linkZapComMsg = "https://wa.me/55$telLimpo?text=" . urlencode($msgZap);

                $obsTexto = empty($r['observacoes']) ? '<span class="text-muted small" style="font-style:italic">...</span>' : htmlspecialchars($r['observacoes']);
                ?>
                <!-- CARD -->
                <div class="reserva-card <?= $classeBorda ?>" id="card-<?= $r['id'] ?>">

                    <div class="card-content-wrapper">

                        <!-- Badge -->
                        <span class="badge-status <?= $isConfirmado ? 'badge-ok' : 'badge-wait' ?>">
                            <?= $isConfirmado ? 'Confirmado' : 'Pendente' ?>
                        </span>

                        <!-- 1. NOME -->
                        <div class="sec-info">
                            <div class="client-name">
                                <span class="id-reserva">#<?= $r['id'] ?></span> <?= htmlspecialchars($nome) ?>
                            </div>
                            <span class="btn-perfil" onclick="abrirModalPerfil('<?= $telLimpo ?>')">
                                <i class="fas fa-history"></i> <span class="d-none d-md-inline">Histórico</span>
                            </span>
                        </div>

                        <!-- 2. META DADOS (PAX + HORA) -->
                        <div class="sec-meta-group">
                            <div class="meta-item meta-pax">
                                <span class="pax-val"><?= (int) $r['num_pessoas'] ?></span>
                                <span class="pax-lbl">Pax</span>
                            </div>
                            <div class="meta-item meta-time">
                                <span class="time-val"><?= $horaShort ?></span>
                                <?php if (!empty($r['num_mesa'])): ?>
                                    <span class="mesa-val">M:<?= $r['num_mesa'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 3. OBS (BOX QUADRADO) -->
                        <div class="sec-obs-container">
                            <div class="obs-box">
                                <?= $obsTexto ?>
                            </div>
                        </div>

                    </div>

                    <!-- BOTÃO TOGGLE (Mobile) -->
                    <button class="btn-mobile-toggle" onclick="toggleActions(<?= $r['id'] ?>)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>

                    <!-- AÇÕES (MENU LATERAL) -->
                    <div class="sec-actions">
                        <button class="btn-close-actions d-lg-none" onclick="toggleActions(<?= $r['id'] ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="actions-scroll-wrapper">
                            <button class="btn-action btn-whatsapp"
                                onclick="abrirModalZap(<?= $r['id'] ?>, '<?= $linkZapComMsg ?>', '<?= $linkZapDireto ?>')">
                                <i class="fab fa-whatsapp"></i> <span class="d-none d-lg-inline">Zap</span>
                            </button>
                            <a href="editar_reserva.php?id=<?= $r['id'] ?>" class="btn-action">
                                <i class="fas fa-pen text-primary"></i> <span class="d-none d-lg-inline">Editar</span>
                            </a>
                            <a href="excluir_reserva.php?id=<?= $r['id'] ?>" class="btn-action" onclick="return confirm('Excluir?')">
                                <i class="fas fa-trash text-danger"></i> <span class="d-none d-lg-inline">Excluir</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted">Nenhuma reserva encontrada.</div>
        <?php endif; ?>
    </div>

    <div id="print-data-hidden" style="display:none;">
        <div class="print-header">
            <h3>Lista de Reservas</h3>
            <p><?= date('d/m/Y', strtotime($filtroData)) ?> | <?= $tituloPeriodo ?></p>
        </div>
        <table class="print-table" style="width:100%; border-collapse:collapse; font-size:10pt; font-family:Arial;">
            <thead>
                <tr><th>ID</th><th>Nome</th><th>Qtd</th><th>Hora</th><th>Obs</th><th>Mesa</th></tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $r): ?>
                    <tr>
                        <td style="border:1px solid #000;padding:4px;"><?= htmlspecialchars($r['id']) ?></td>
                        <td style="border:1px solid #000;padding:4px;"><?= htmlspecialchars($r['nome']) ?></td>
                        <td style="border:1px solid #000;padding:4px; text-align:center;"><?= (int) $r['num_pessoas'] ?></td>
                        <td style="border:1px solid #000;padding:4px; text-align:center;"><?= date("H:i", strtotime($r['horario'])) ?></td>
                        <td style="border:1px solid #000;padding:4px;"><?= htmlspecialchars($r['observacoes']) ?></td>
                        <td style="border:1px solid #000;padding:4px; text-align:center;"><?= htmlspecialchars($r['num_mesa'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX HANDLERS
if (isset($_GET['acao']) && $_GET['acao'] === 'listar_ajax') {
    echo renderizarListaReservas($pdo, $_GET['filtro_data'] ?? date('Y-m-d'), $_GET['busca_texto'] ?? '', $_GET['periodo'] ?? 'todos');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_reserva') {
    $id = $_POST['id'] ?? null;
    echo json_encode(['success' => ($id && $pdo->prepare("UPDATE clientes SET confirmado = 1 WHERE id = :id")->execute([':id' => $id]))]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'ver_perfil') {
    $tel = preg_replace('/\D/', '', $_GET['telefone'] ?? '');
    $stats = $pdo->prepare("SELECT COUNT(*) as total_reservas, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as total_canceladas, SUM(num_pessoas) as total_pessoas_trazidas FROM clientes WHERE telefone LIKE :tel");
    $stats->execute([':tel' => "%$tel%"]);
    $hist = $pdo->prepare("SELECT data, num_pessoas, observacoes, status FROM clientes WHERE telefone LIKE :tel ORDER BY data DESC LIMIT 5");
    $hist->execute([':tel' => "%$tel%"]);
    echo json_encode(['stats' => $stats->fetch(PDO::FETCH_ASSOC), 'historico' => $hist->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

require 'cabecalho.php';

$filtroData = $_REQUEST['filtro_data'] ?? date('Y-m-d');
$buscaTexto = $_REQUEST['busca_texto'] ?? '';
$periodo = $_REQUEST['periodo'] ?? 'todos';

// CALENDÁRIO
function generateCalendar(PDO $pdo, int $month, int $year): string
{
    global $filtroData, $buscaTexto, $periodo;
    $stmt = $pdo->prepare("SELECT data, SUM(CASE WHEN horario BETWEEN '11:00:00' AND '17:59:00' THEN IF(status!=0, num_pessoas, 0) ELSE 0 END) AS almoco, SUM(CASE WHEN horario BETWEEN '18:00:00' AND '23:59:00' THEN IF(status!=0, num_pessoas, 0) ELSE 0 END) AS jantar FROM clientes WHERE MONTH(data) = :m AND YEAR(data) = :y GROUP BY data");
    $stmt->execute(['m' => $month, 'y' => $year]);
    $map = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $map[$row['data']] = $row;
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total_res, SUM(num_pessoas) as total_pax FROM clientes WHERE MONTH(data) = :m AND YEAR(data) = :y AND status != 0");
    $stmtTotal->execute(['m' => $month, 'y' => $year]);
    $totaisMes = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalPaxMes = $totaisMes['total_pax'] ?? 0;
    $totalResMes = $totaisMes['total_res'] ?? 0;
    
    $firstDayTs = mktime(0, 0, 0, $month, 1, $year);
    $numDays = (int) date('t', $firstDayTs);
    $dayOfWeek = (int) date('w', $firstDayTs);
    $today = date('Y-m-d');
    $prevM = $month - 1; $prevY = $year; if ($prevM < 1) { $prevM = 12; $prevY--; }
    $nextM = $month + 1; $nextY = $year; if ($nextM > 12) { $nextM = 1; $nextY++; }
    $q = http_build_query(['filtro_data' => $filtroData, 'busca_texto' => $buscaTexto, 'periodo' => $periodo]);
    $monthName = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"][$month - 1] ?? $month;
    
    $html = "<div class='calendar-container'><div class='cal-header-modern'><a href='?month=$prevM&year=$prevY&$q' class='btn-nav-cal'><i class='fas fa-chevron-left'></i></a><div class='cal-title-group'><span class='cal-month-year'>{$monthName} {$year}</span><div class='cal-stats-badges'><span title='Total de Pessoas'><i class='fas fa-users'></i> {$totalPaxMes}</span><span class='divider'>•</span><span title='Total de Reservas'><i class='fas fa-file-alt'></i> {$totalResMes}</span></div></div><a href='?month=$nextM&year=$nextY&$q' class='btn-nav-cal'><i class='fas fa-chevron-right'></i></a></div><div class='table-responsive'><table class='cal-table'><thead><tr><th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th></tr></thead><tbody><tr>";
    if ($dayOfWeek > 0) { for ($i = 0; $i < $dayOfWeek; $i++) $html .= "<td class='empty'></td>"; }
    $d = 1;
    while ($d <= $numDays) {
        if ($dayOfWeek == 7) { $dayOfWeek = 0; $html .= "</tr><tr>"; }
        $currDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $alm = $map[$currDate]['almoco'] ?? 0;
        $jan = $map[$currDate]['jantar'] ?? 0;
        $cls = ($currDate === $today) ? 'today' : '';
        if ($currDate === $filtroData) $cls .= ' selected';
        $html .= "<td class='day-cell $cls' onclick=\"mudarData('$currDate', this)\"><div class='d-flex justify-content-between align-items-start'><span class='day-num'>$d</span><button class='btn-eye-sm' onclick=\"verReservasDia('$currDate', event)\"><i class='fas fa-eye'></i></button></div><div class='pills-container'>";
        if ($alm > 0) $html .= "<span class='pill pill-a'>A: $alm</span>";
        if ($jan > 0) $html .= "<span class='pill pill-j'>J: $jan</span>";
        $html .= "  </div></td>";
        $d++; $dayOfWeek++;
    }
    if ($dayOfWeek != 7) { for ($i = 0; $i < (7 - $dayOfWeek); $i++) $html .= "<td class='empty'></td>"; }
    $html .= "</tr></tbody></table></div></div>";
    return $html;
}

$refMes = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m', strtotime($filtroData));
$refAno = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y', strtotime($filtroData));
$calendarHtml = generateCalendar($pdo, $refMes, $refAno);
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
        body { background-color: #f0f2f5; font-family: 'Inter', 'Segoe UI', sans-serif; padding-bottom: 60px; }

        /* CALENDÁRIO */
        :root { --cal-bg: #212529; --cal-header: #2c3034; --cal-cell: #2c3034; --cal-hover: #343a40; --cal-text: #e9ecef; --cal-muted: #adb5bd; --accent: #0d6efd; --pill-a: #ffc107; --pill-j: #0dcaf0; }
        #cal-wrapper { overflow: hidden; max-height: 1200px; opacity: 1; transition: max-height 0.5s ease-in-out, opacity 0.4s ease-in-out; margin-bottom: 0; }
        #cal-wrapper.collapsed { max-height: 0; opacity: 0; }
        .calendar-container { background: var(--cal-bg); color: var(--cal-text); border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); padding: 0; overflow: hidden; max-width: 900px; margin: 0 auto; }
        .cal-header-modern { background: var(--cal-header); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #3d4146; }
        .cal-title-group { text-align: center; display: flex; flex-direction: column; align-items: center; }
        .cal-month-year { font-size: 1.25rem; font-weight: 700; text-transform: capitalize; color: #fff; margin-bottom: 2px; }
        .cal-stats-badges { font-size: 0.85rem; color: var(--cal-muted); display: flex; align-items: center; gap: 8px; }
        .cal-stats-badges i { color: var(--accent); margin-right: 3px; }
        .btn-nav-cal { color: var(--cal-muted); font-size: 1.1rem; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: 0.2s; text-decoration: none; background: rgba(255,255,255,0.05); }
        .btn-nav-cal:hover { background: rgba(255,255,255,0.15); color: #fff; }
        .cal-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .cal-table th { text-align: center; color: var(--cal-muted); font-size: 0.75rem; text-transform: uppercase; padding: 8px 0; background: var(--cal-bg); }
        .cal-table td { background: var(--cal-cell); border: 1px solid #3d4146; height: 60px; vertical-align: top; padding: 4px; cursor: pointer; transition: background 0.2s; position: relative; }
        .cal-table td:not(.empty):hover { background: var(--cal-hover); }
        .cal-table td.today { background: #3c4149; border: 1px solid var(--accent); }
        .cal-table td.selected { background: #495057; box-shadow: inset 0 0 0 1px #fff; }
        .day-num { font-size: 0.85rem; font-weight: 600; color: #fff; margin-left: 2px; }
        .btn-eye-sm { background: none; border: none; padding: 0; color: var(--cal-muted); width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; transition: 0.2s; }
        .btn-eye-sm:hover { color: #fff; background: rgba(255,255,255,0.2); }
        .pills-container { display: flex; justify-content: flex-start; gap: 3px; margin-top: 2px; flex-wrap: wrap; }
        .pill { font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; font-weight: 700; color: #000; line-height: 1; display: inline-block; }
        .pill-a { background: var(--pill-a); } .pill-j { background: var(--pill-j); }
        .toggle-cal-container { text-align: center; margin-top: -10px; margin-bottom: 20px; position: relative; z-index: 10; }
        .btn-toggle-cal { background: var(--cal-header); color: var(--cal-muted); border: 1px solid #3d4146; border-top: none; padding: 5px 20px; font-size: 0.8rem; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.2s; }
        .btn-toggle-cal:hover { background: var(--cal-hover); color: #fff; }

        .filter-bar { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }

        /* LISTA E CARDS */
        .reserva-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin-bottom: 12px; border-left: 5px solid #ccc; position: relative; padding: 5px 10px; display: flex; align-items: center; flex-wrap: nowrap; transition: 0.2s; }
        .status-confirmado { border-left-color: #198754 !important; }
        .status-pendente { border-left-color: #fd7e14 !important; }
        .card-content-wrapper { display: contents; width: 100%; }

        .sec-info { flex: 2; padding: 8px; display: flex; flex-direction: column; justify-content: center; }
        .client-name { font-weight: 700; color: #333; font-size: 1rem; }
        .id-reserva { color: #999; font-weight: 800; font-size: 0.85rem; }
        .btn-perfil { font-size: 0.75rem; color: var(--accent); cursor: pointer; text-decoration: none; font-weight: 600; margin-top: 2px; }

        .sec-meta-group { flex: 2; display: flex; align-items: center; justify-content: space-evenly; }
        .meta-item { display: flex; flex-direction: column; align-items: center; justify-content: center; border-left: 1px solid #f0f0f0; padding: 0 10px; min-width: 70px; }
        .pax-val { font-size: 1.4rem; font-weight: 800; color: #fd7e14; line-height: 1; }
        .pax-lbl { font-size: 0.7rem; color: #888; text-transform: uppercase; }
        .time-val { font-size: 1.2rem; font-weight: 700; color: #333; }
        .mesa-val { font-size: 0.75rem; background: #eee; padding: 1px 6px; border-radius: 4px; color: #555; margin-top: 2px; }

        .sec-obs-container { flex: 3; padding: 8px 15px; display: flex; align-items: center; justify-content: center; }
        .obs-box { background-color: #fcfcfc; border: 1px solid #dee2e6; border-radius: 6px; padding: 6px 10px; font-size: 0.8rem; color: #666; width: 100%; max-width: 320px; height: 60px; overflow-y: auto; white-space: normal; }
        .obs-box::-webkit-scrollbar { width: 4px; }
        .obs-box::-webkit-scrollbar-track { background: #f1f1f1; }
        .obs-box::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

        .badge-status { position: absolute; top: 8px; right: 10px; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: bold; text-transform: uppercase; z-index: 5; }
        .badge-ok { background: #d1e7dd; color: #0f5132; }
        .badge-wait { background: #fff3cd; color: #664d03; }

        .sec-actions { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 8px; min-width: 110px; }
        .btn-action { width: 100%; border: 1px solid #dee2e6; background: #fff; color: #555; border-radius: 4px; padding: 4px; font-size: 0.8rem; cursor: pointer; text-align: center; text-decoration: none; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-action:hover { background: #f8f9fa; }
        .btn-whatsapp { color: #198754; border-color: #198754; }
        .btn-whatsapp:hover { background: #198754; color: #fff; }
        .btn-mobile-toggle, .btn-close-actions { display: none; }

        /* ========= CORREÇÃO DO MODAL FANTASMA ========== */
        .modal-overlay-dia { 
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(0,0,0,0.6); z-index: 2000; 
            display: none; /* Garante que comece escondido */
            justify-content: center; align-items: center; backdrop-filter: blur(2px); 
        }
        .modal-box-dia { background: #fff; width: 90%; max-width: 450px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden; display: flex; flex-direction: column; max-height: 85vh; }
        .modal-header-dia { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; }
        .modal-body-dia { padding: 0; overflow-y: auto; }
        .reserva-item { padding: 12px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }

        /* MOBILE (Max 991px) */
        @media (max-width: 991px) {
            .reserva-card { height: 110px; padding: 4px; padding-right: 35px; display: block; border-left-width: 4px; }
            .card-content-wrapper { display: grid; grid-template-rows: 25px 1fr; grid-template-columns: 90px 1fr; height: 100%; gap: 0; }
            
            .badge-status { top: 3px; right: 40px; font-size: 0.55rem; padding: 1px 3px; }
            
            .sec-info { grid-row: 1 / 2; grid-column: 1 / 3; padding: 0 4px; border: none; border-bottom: 1px dashed #f0f0f0; justify-content: flex-end; }
            .client-name { font-size: 0.75rem !important; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .btn-perfil { display: none; }

            .sec-meta-group { grid-row: 2 / 3; grid-column: 1 / 2; display: flex; flex-direction: column; alignItems: flex-start; justify-content: center; border-right: 1px solid #eee; padding: 0 5px; gap: 2px; }
            .meta-item { border: none; padding: 0; min-width: 0; align-items: flex-start; flex-direction: row; gap: 4px; }
            .meta-pax .pax-val { font-size: 1.1rem; }
            .meta-pax .pax-lbl { font-size: 0.65rem; margin-top: 3px; }
            .meta-time .time-val { font-size: 0.95rem; }
            .meta-time .mesa-val { display: none !important; }

            /* Ajuste da Obs Box Mobile */
         .sec-obs-container {
    grid-row: 1 / 3;
    grid-column: 2 / 3;
    padding: 6px;
    height: 100%;       /* mesma altura do card */
    display: flex;
}
.obs-box {
    width: 40%;         /* metade da largura */
    height: 100%;       /* mesma altura */
    max-width: none;
    font-size: 0.75rem;
    padding: 4px 6px;
    background: #fdfdfd;
    border: 1px solid #e0e0e0;
    overflow-y: auto;   /* scroll vertical */
    overflow-x: hidden; /* evita scroll horizontal */
    border-radius: 6px;
}
            .btn-mobile-toggle { display: flex; position: absolute; top: 0; right: 0; bottom: 0; width: 30px; background: #f8f9fa; border: none; border-left: 1px solid #eee; align-items: center; justify-content: center; color: #888; font-size: 1rem; cursor: pointer; z-index: 6; }

            /* MENU LATERAL COM SCROLL */
            .sec-actions {
                position: fixed; top: 0; right: 0; bottom: 0; width: 220px; 
                height: 100vh; background: white; z-index: 9999;
                flex-direction: column; padding: 50px 10px 20px 10px;
                box-shadow: -5px 0 15px rgba(0,0,0,0.1);
                transform: translateX(100%); transition: transform 0.3s ease-in-out;
                overflow-y: auto;
            }
            .reserva-card.show-menu .sec-actions { transform: translateX(0); }
            
            .btn-close-actions { display: flex; position: absolute; top: 10px; right: 10px; width: 30px; height: 30px; border: none; background: #f0f0f0; border-radius: 50%; color: #333; align-items: center; justify-content: center; font-size: 1rem; cursor: pointer; z-index: 10000; }
            .btn-action { width: 100%; height: 45px; margin-bottom: 8px; font-size: 0.9rem; justify-content: flex-start; padding-left: 20px; }
            
            /* Ajuste Filtros Mobile */
            .filter-bar { display: flex; flex-direction: row; flex-wrap: wrap; gap: 10px; }
            .filter-bar .col-md-3 { flex: 1 1 45%; min-width: 140px; } 
            .filter-bar button, .filter-bar a { width: 100%; }
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-3 no-print">

        <!-- WRAPPER CALENDÁRIO -->
        <div id="cal-wrapper">
            <?= $calendarHtml ?>
        </div>

        <!-- BOTÃO TOGGLE -->
        <div class="toggle-cal-container">
            <button class="btn-toggle-cal" onclick="toggleCal()" id="btnToggleText">
                Esconder Calendário <i class="fas fa-chevron-up"></i>
            </button>
        </div>

        <!-- FILTROS -->
        <div class="filter-bar row g-2">
            <div class="col-6 col-md-3">
                <label class="form-label fw-bold small mb-1">Data</label>
                <input type="date" name="filtro_data" id="filtro_data" class="form-control" value="<?= htmlspecialchars($filtroData) ?>" onchange="carregarListaAjax()">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-bold small mb-1">Busca</label>
                <input type="text" name="busca_texto" id="busca_texto" class="form-control" placeholder="Nome/Tel" value="<?= htmlspecialchars($buscaTexto) ?>" onkeyup="carregarListaAjax()">
            </div>
            <div class="col-12 col-md-3 mt-md-0 mt-2">
                <label class="form-label d-none d-md-block mb-1">&nbsp;</label>
                <div class="btn-group w-100" role="group">
                    <button type="button" onclick="setPeriodo('todos')" class="btn btn-outline-secondary active" id="btn-todos">Todos</button>
                    <button type="button" onclick="setPeriodo('almoco')" class="btn btn-outline-secondary" id="btn-almoco">Almoço</button>
                    <button type="button" onclick="setPeriodo('jantar')" class="btn btn-outline-secondary" id="btn-jantar">Jantar</button>
                </div>
                <input type="hidden" name="periodo" id="periodoInput" value="<?= $periodo ?>">
            </div>
            <div class="col-12 col-md-3 mt-md-0 mt-2">
                <label class="form-label d-none d-md-block mb-1">&nbsp;</label>
                <div class="d-flex gap-2 w-100">
                    <button type="button" onclick="imprimirReservas()" class="btn btn-secondary flex-grow-1"><i class="fas fa-print"></i></button>
                    <a href="adicionar_reserva.php" class="btn btn-primary flex-grow-1"><i class="fas fa-plus"></i> Nova</a>
                </div>
            </div>
        </div>

        <!-- LISTA AJAX -->
        <div id="area-lista-reservas">
            <?= renderizarListaReservas($pdo, $filtroData, $buscaTexto, $periodo) ?>
        </div>

    </div>

    <!-- MODAIS -->
    <div class="modal fade" id="modalPerfil" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title">Histórico</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="bg-light p-3 text-center d-flex justify-content-around">
                        <div><h5 id="pTotal" class="fw-bold m-0">0</h5><small>Reservas</small></div>
                        <div><h5 id="pCancel" class="fw-bold text-danger m-0">0</h5><small>Cancel</small></div>
                        <div><h5 id="pPessoas" class="fw-bold text-success m-0">0</h5><small>Pax</small></div>
                    </div>
                    <ul class="list-group list-group-flush small p-2" id="listaHistorico"></ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modalZap" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title">WhatsApp</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-grid gap-2">
                    <button class="btn btn-outline-success" id="btnZapConfirmar">Confirmar & Enviar</button>
                    <button class="btn btn-outline-secondary" id="btnZapDireto">Apenas Abrir</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MODAL DIA - ADICIONADO STYLE DISPLAY NONE NO HTML PARA EVITAR GHOSTING -->
    <div class="modal-overlay-dia" id="modalDia" style="display:none;">
        <div class="modal-box-dia">
            <div class="modal-header-dia"><h5 class="m-0" id="modalTitle">Dia</h5><button class="btn-close" onclick="fecharModalDia()"></button></div>
            <div class="modal-body-dia" id="modalContent"><div class="text-center p-3">Carregando...</div></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const URL_ATUAL = "<?= basename($_SERVER['PHP_SELF']); ?>";

        function toggleActions(id) {
            const card = document.getElementById('card-' + id);
            // Fecha outros
            document.querySelectorAll('.reserva-card.show-menu').forEach(c => {
                if (c !== card) c.classList.remove('show-menu');
            });
            card.classList.toggle('show-menu');
        }

        function toggleCal() {
            const el = document.getElementById('cal-wrapper');
            const btn = document.getElementById('btnToggleText');
            if (el.classList.contains('collapsed')) {
                el.classList.remove('collapsed');
                btn.innerHTML = 'Esconder Calendário <i class="fas fa-chevron-up"></i>';
            } else {
                el.classList.add('collapsed');
                btn.innerHTML = 'Mostrar Calendário <i class="fas fa-chevron-down"></i>';
            }
        }

        function setPeriodo(val) {
            document.getElementById('periodoInput').value = val;
            document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
            document.getElementById('btn-' + val).classList.add('active');
            carregarListaAjax();
        }

        function carregarListaAjax(dataISO = null) {
            if (dataISO) document.getElementById('filtro_data').value = dataISO;
            const d = document.getElementById('filtro_data').value;
            const b = document.getElementById('busca_texto').value;
            const p = document.getElementById('periodoInput').value;
            const container = document.getElementById('area-lista-reservas');
            container.style.opacity = '0.6';
            
            const params = new URLSearchParams({ acao: 'listar_ajax', filtro_data: d, busca_texto: b, periodo: p });
            fetch(URL_ATUAL + '?' + params.toString())
                .then(r => r.text())
                .then(html => {
                    container.innerHTML = html;
                    container.style.opacity = '1';
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('filtro_data', d);
                    window.history.pushState({}, '', newUrl);
                });
        }

        function mudarData(dataISO, el) {
            document.querySelectorAll('.cal-table td').forEach(c => c.classList.remove('selected'));
            if (el) el.classList.add('selected');
            carregarListaAjax(dataISO);
        }

        function verReservasDia(data, e) {
            if (e) e.stopPropagation();
            const modal = document.getElementById('modalDia');
            document.getElementById('modalTitle').innerText = data.split('-').reverse().join('/');
            modal.style.display = 'flex';
            document.getElementById('modalContent').innerHTML = '<div class="text-center p-3 text-muted">Buscando...</div>';
            fetch('ajax_reservas_dia.php?data=' + data).then(r => r.json()).then(json => {
                if (json.erro || !json.lista) { document.getElementById('modalContent').innerHTML = '<div class="p-3 text-center">Nada encontrado.</div>'; return; }
                let h = '';
                json.lista.forEach(r => {
                    h += `<div class="reserva-item"><div><strong>${r.nome}</strong><br><small class="text-muted">${r.num_pessoas} pax • ${r.horario.substring(0, 5)}</small></div><a href="editar_reserva.php?id=${r.id}" class="btn btn-sm btn-outline-primary"><i class="fas fa-pen"></i> Editar</a></div>`;
                });
                h += `<div class="p-2 text-center bg-light fw-bold">Total: ${json.resumo.total} pessoas</div>`;
                document.getElementById('modalContent').innerHTML = h;
            }).catch(() => document.getElementById('modalContent').innerHTML = '<div class="p-3 text-center text-danger">Erro</div>');
        }
        function fecharModalDia() { document.getElementById('modalDia').style.display = 'none'; }
        window.onclick = function (e) { if (e.target == document.getElementById('modalDia')) fecharModalDia(); }

        let curId, curLink, curLinkD;
        function abrirModalZap(id, l1, l2) { curId = id; curLink = l1; curLinkD = l2; new bootstrap.Modal(document.getElementById('modalZap')).show(); }
        document.getElementById('btnZapConfirmar').onclick = () => {
            window.open(curLink, '_blank');
            const fd = new FormData(); fd.append('acao', 'confirmar_reserva'); fd.append('id', curId);
            fetch(URL_ATUAL, { method: 'POST', body: fd }).then(() => setTimeout(() => carregarListaAjax(), 1000));
        };
        document.getElementById('btnZapDireto').onclick = () => window.open(curLinkD, '_blank');

        function abrirModalPerfil(tel) {
            new bootstrap.Modal(document.getElementById('modalPerfil')).show();
            fetch(URL_ATUAL + '?acao=ver_perfil&telefone=' + tel).then(r => r.json()).then(d => {
                document.getElementById('pTotal').innerText = d.stats.total_reservas || 0;
                document.getElementById('pCancel').innerText = d.stats.total_canceladas || 0;
                document.getElementById('pPessoas').innerText = d.stats.total_pessoas_trazidas || 0;
                let h = '';
                d.historico.forEach(x => {
                    h += `<li class="list-group-item d-flex justify-content-between"><span>${new Date(x.data).toLocaleDateString('pt-BR')}</span><span>${x.num_pessoas}p <i class="fas ${x.status == 0 ? 'fa-times text-danger' : 'fa-check text-success'}"></i></span></li>`;
                });
                document.getElementById('listaHistorico').innerHTML = h || '<li class="list-group-item text-center">Sem histórico.</li>';
            });
        }

        function imprimirReservas() {
            const c = document.getElementById('print-data-hidden').innerHTML || document.getElementById('area-lista-reservas').innerHTML;
            const w = window.open('', '', 'height=800,width=1000');
            w.document.write('<html><head><title>Imprimir</title><style>body{font-family:sans-serif} table{width:100%;border-collapse:collapse} th,td{border:1px solid #000;padding:5px}</style></head><body>' + c + '</body></html>');
            w.document.close(); w.print();
        }
    </script>
</body>
</html>