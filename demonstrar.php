<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

/* ============================================================
   FUNÇÃO AUXILIAR: GERAR HTML DA LISTA (REUTILIZÁVEL)
============================================================ */
function renderizarListaReservas($pdo, $filtroData, $buscaTexto, $periodo) {
    $sqlWhere = " WHERE status != 0 ";
    $params   = [];

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
        $sqlWhere       .= " AND horario < '18:00:00'";
        $tituloPeriodo   = "Almoço";
    } elseif ($periodo === 'jantar') {
        $sqlWhere       .= " AND horario >= '18:00:00'";
        $tituloPeriodo   = "Jantar";
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
    <div class="alert alert-secondary py-1 px-3 d-flex justify-content-between align-items-center mb-3 shadow-sm" style="font-size:0.9rem; border-radius:8px; border:none; background:#e9ecef;">
        <span>
            <i class="fas fa-calendar-day text-primary"></i> <strong><?= date('d/m', strtotime($filtroData)) ?></strong> - <?= $tituloPeriodo ?>
        </span>
        <span class="badge bg-dark">Total: <?= $total_pessoas ?></span>
    </div>

    <div class="lista-reservas-conteudo">
        <?php if(count($reservas) > 0): ?>
            <?php foreach($reservas as $r):
                $isConfirmado = ($r['confirmado'] == 1);
                $classeBorda  = $isConfirmado ? 'status-confirmado' : 'status-pendente';
                $horaShort    = date("H:i", strtotime($r['horario']));
                $nome         = ucwords(strtolower($r['nome']));
                $telLimpo     = preg_replace('/[^0-9]/', '', $r['telefone']);
                $linkZapDireto= "https://wa.me/55$telLimpo";
                $msgZap       = "Olá $nome! Confirmando reserva para dia " . date('d/m', strtotime($r['data'])) . " às $horaShort para {$r['num_pessoas']} pessoas.";
                $linkZapComMsg= "https://wa.me/55$telLimpo?text=" . urlencode($msgZap);
            ?>
                <div class="reserva-card <?= $classeBorda ?>">
                    <!-- Badge só aparece no Desktop -->
                    <span class="badge-status <?= $isConfirmado ? 'badge-ok' : 'badge-wait' ?>">
                        <?= $isConfirmado ? 'Confirmado' : 'Pendente' ?>
                    </span>

                    <div class="sec-info">
                        <div class="client-name">
                            <span class="id-reserva">#<?= $r['id'] ?></span> <?= htmlspecialchars($nome) ?>
                        </div>
                        <!-- Link histórico (some no mobile muito pequeno se necessário, mas mantivemos icon) -->
                        <span class="btn-perfil" onclick="abrirModalPerfil('<?= $telLimpo ?>')">
                            <i class="fas fa-history"></i> <span class="d-none d-md-inline">Histórico</span>
                        </span>
                    </div>

                    <!-- Agrupamento Mobile: Pax e Hora juntos -->
                    <div class="sec-mobile-meta">
                        <div class="sec-pax">
                            <span class="pax-count"><?= (int)$r['num_pessoas'] ?></span>
                            <span class="pax-label">Pax</span>
                        </div>
                        <div class="sec-time">
                            <div class="time-display"><?= $horaShort ?></div>
                            <?php if(!empty($r['num_mesa'])): ?>
                                <div class="mesa-display">M: <?= htmlspecialchars($r['num_mesa']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sec-obs">
                        <?= empty($r['observacoes']) ? '<span class="text-muted">...</span>' : htmlspecialchars($r['observacoes']) ?>
                    </div>

                    <div class="sec-actions">
                        <button class="btn-action btn-whatsapp" onclick="abrirModalZap(<?= $r['id'] ?>, '<?= $linkZapComMsg ?>', '<?= $linkZapDireto ?>')">
                            <i class="fab fa-whatsapp"></i> <span class="btn-text">Zap</span>
                        </button>
                        <a href="editar_reserva.php?id=<?= $r['id'] ?>" class="btn-action">
                            <i class="fas fa-pen text-primary"></i> <span class="btn-text">Editar</span>
                        </a>
                        <a href="excluir_reserva.php?id=<?= $r['id'] ?>" class="btn-action" onclick="return confirm('Excluir?')">
                            <i class="fas fa-trash text-danger"></i> <span class="btn-text">Excluir</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted">Nenhuma reserva encontrada para este filtro.</div>
        <?php endif; ?>
    </div>
    
   <div id="print-data-hidden" style="display:none;">
    <div class="print-header">
        <h3>Lista de Reservas</h3>
        <p><?= date('d/m/Y', strtotime($filtroData)) ?> | <?= $tituloPeriodo ?></p>
    </div>

    <table class="print-table" style="width:100%; border-collapse:collapse; font-size:10pt; font-family:Arial;">
        <thead>
            <tr>
                <th style="border:1px solid #000;padding:4px;">ID</th>
                <th style="border:1px solid #000;padding:4px;">Nome</th>
                <th style="border:1px solid #000;padding:4px;">Qtd</th>
                <th style="border:1px solid #000;padding:4px;">Hora</th>
                <th style="border:1px solid #000;padding:4px;">Obs</th>
                <th style="border:1px solid #000;padding:4px;">Mesa</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach($reservas as $r): ?>
            <tr>

                <td style="border:1px solid #000;padding:4px;">
                    <?= htmlspecialchars($r['id'] ?? '') ?>
                </td>

                <td style="border:1px solid #000;padding:4px;">
                    <?= htmlspecialchars($r['nome'] ?? '') ?>
                </td>

                <td style="border:1px solid #000;padding:4px; text-align:center;">
                    <b><?= (int)($r['num_pessoas'] ?? 0) ?></b>
                </td>

                <td style="border:1px solid #000;padding:4px; text-align:center;">
                    <?= !empty($r['horario']) ? date("H:i", strtotime($r['horario'])) : '' ?>
                </td>

                <td style="border:1px solid #000;padding:4px;">
                    <?= htmlspecialchars($r['observacoes'] ?? '') ?>
                </td>

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

// AJAX HANDLERS
if (isset($_GET['acao']) && $_GET['acao'] === 'listar_ajax') {
    echo renderizarListaReservas($pdo, $_GET['filtro_data'] ?? date('Y-m-d'), $_GET['busca_texto'] ?? '', $_GET['periodo'] ?? 'todos');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'confirmar_reserva') {
    $id = $_POST['id'] ?? null;
    echo json_encode(['success' => ($id && $pdo->prepare("UPDATE clientes SET confirmado = 1 WHERE id = :id")->execute([':id'=>$id]))]);
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

// PARÂMETROS INICIAIS
$filtroData = $_REQUEST['filtro_data'] ?? date('Y-m-d');
$buscaTexto = $_REQUEST['busca_texto'] ?? '';
$periodo    = $_REQUEST['periodo'] ?? 'todos';

/* ============================================================
   LÓGICA CALENDÁRIO
============================================================ */
function generateCalendar(PDO $pdo, int $month, int $year): string {
    global $filtroData, $buscaTexto, $periodo;
    
    // Dados de Reservas
    $stmt = $pdo->prepare("SELECT data, SUM(CASE WHEN horario BETWEEN '11:00:00' AND '17:59:00' THEN IF(status!=0, num_pessoas, 0) ELSE 0 END) AS almoco, SUM(CASE WHEN horario BETWEEN '18:00:00' AND '23:59:00' THEN IF(status!=0, num_pessoas, 0) ELSE 0 END) AS jantar FROM clientes WHERE MONTH(data) = :m AND YEAR(data) = :y GROUP BY data");
    $stmt->execute(['m' => $month, 'y' => $year]);
    $map = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) $map[$row['data']] = $row;

    // Totais do Mês
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total_res, SUM(num_pessoas) as total_pax FROM clientes WHERE MONTH(data) = :m AND YEAR(data) = :y AND status != 0");
    $stmtTotal->execute(['m' => $month, 'y' => $year]);
    $totaisMes = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalPaxMes = $totaisMes['total_pax'] ?? 0;
    $totalResMes = $totaisMes['total_res'] ?? 0;

    $firstDayTs = mktime(0,0,0,$month,1,$year);
    $numDays    = (int)date('t', $firstDayTs);
    $dayOfWeek  = (int)date('w', $firstDayTs);
    $today      = date('Y-m-d');

    $prevM = $month-1; $prevY = $year; if($prevM<1){$prevM=12;$prevY--;}
    $nextM = $month+1; $nextY = $year; if($nextM>12){$nextM=1;$nextY++;}
    $q = http_build_query(['filtro_data'=>$filtroData, 'busca_texto'=>$buscaTexto, 'periodo'=>$periodo]);
    
    $monthName = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][$month-1] ?? $month;
    
    $html = "<div class='calendar-container'>";
    $html .= "<div class='cal-header-modern'>";
    $html .= "<a href='?month=$prevM&year=$prevY&$q' class='btn-nav-cal'><i class='fas fa-chevron-left'></i></a>";
    $html .= "<div class='cal-title-group'>";
    $html .= "  <span class='cal-month-year'>{$monthName} {$year}</span>";
    $html .= "  <div class='cal-stats-badges'>";
    $html .= "      <span title='Total de Pessoas'><i class='fas fa-users'></i> {$totalPaxMes}</span>";
    $html .= "      <span class='divider'>•</span>";
    $html .= "      <span title='Total de Reservas'><i class='fas fa-file-alt'></i> {$totalResMes}</span>";
    $html .= "  </div>";
    $html .= "</div>";
    $html .= "<a href='?month=$nextM&year=$nextY&$q' class='btn-nav-cal'><i class='fas fa-chevron-right'></i></a>";
    $html .= "</div>";

    $html .= "<div class='table-responsive'><table class='cal-table'>";
    $html .= "<thead><tr><th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th></tr></thead><tbody><tr>";

    if($dayOfWeek > 0) { for($i=0;$i<$dayOfWeek;$i++) $html .= "<td class='empty'></td>"; }

    $d = 1;
    while($d <= $numDays){
        if($dayOfWeek==7){ $dayOfWeek=0; $html.="</tr><tr>"; }
        $currDate = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $alm = $map[$currDate]['almoco'] ?? 0;
        $jan = $map[$currDate]['jantar'] ?? 0;
        $cls = ($currDate === $today) ? 'today' : '';
        if($currDate === $filtroData) $cls .= ' selected';

        $html .= "<td class='day-cell $cls' onclick=\"mudarData('$currDate', this)\">";
        $html .= "  <div class='d-flex justify-content-between align-items-start'>";
        $html .= "      <span class='day-num'>$d</span>";
        $html .= "      <button class='btn-eye-sm' onclick=\"verReservasDia('$currDate', event)\"><i class='fas fa-eye'></i></button>";
        $html .= "  </div>";
        $html .= "  <div class='pills-container'>";
        if($alm > 0) $html .= "<span class='pill pill-a'>A: $alm</span>";
        if($jan > 0) $html .= "<span class='pill pill-j'>J: $jan</span>";
        $html .= "  </div>";
        $html .= "</td>";
        $d++; $dayOfWeek++;
    }
    if($dayOfWeek!=7){ for($i=0;$i<(7-$dayOfWeek);$i++) $html .= "<td class='empty'></td>"; }
    $html .= "</tr></tbody></table></div></div>";

    return $html;
}

$refMes = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m', strtotime($filtroData));
$refAno = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y', strtotime($filtroData));
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

        /* ============================
           ESTILO CALENDÁRIO DARK MODERNO
        ============================ */
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
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            padding: 0;
            overflow: hidden;
            max-width: 900px; margin: 0 auto;
        }

        .cal-header-modern {
            background: var(--cal-header); padding: 15px 20px; display: flex;
            justify-content: space-between; align-items: center; border-bottom: 1px solid #3d4146;
        }
        .cal-title-group { text-align: center; display: flex; flex-direction: column; align-items: center; }
        .cal-month-year { font-size: 1.25rem; font-weight: 700; text-transform: capitalize; color: #fff; margin-bottom: 2px; }
        .cal-stats-badges { font-size: 0.85rem; color: var(--cal-muted); display: flex; align-items: center; gap: 8px; }
        .cal-stats-badges i { color: var(--accent); margin-right: 3px; }
        .divider { color: #495057; }
        .btn-nav-cal {
            color: var(--cal-muted); font-size: 1.1rem; width: 35px; height: 35px;
            display: flex; align-items: center; justify-content: center; border-radius: 50%;
            transition: 0.2s; text-decoration: none; background: rgba(255,255,255,0.05);
        }
        .btn-nav-cal:hover { background: rgba(255,255,255,0.15); color: #fff; }

        .cal-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .cal-table th { text-align: center; color: var(--cal-muted); font-size: 0.75rem; text-transform: uppercase; padding: 8px 0; background: var(--cal-bg); }
        .cal-table td {
            background: var(--cal-cell); border: 1px solid #3d4146; height: 60px;
            vertical-align: top; padding: 4px; cursor: pointer; transition: background 0.2s; position: relative;
        }
        .cal-table td:not(.empty):hover { background: var(--cal-hover); }
        .cal-table td.today { background: #3c4149; border: 1px solid var(--accent); }
        .cal-table td.selected { background: #495057; box-shadow: inset 0 0 0 1px #fff; }

        .day-num { font-size: 0.85rem; font-weight: 600; color: #fff; margin-left: 2px; }
        .btn-eye-sm {
            background: none; border: none; padding: 0; color: var(--cal-muted);
            width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 0.75rem; transition: 0.2s;
        }
        .btn-eye-sm:hover { color: #fff; background: rgba(255,255,255,0.2); }
        .pills-container { display: flex; justify-content: flex-start; gap: 3px; margin-top: 2px; flex-wrap: wrap; }
        .pill { font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; font-weight: 700; color: #000; line-height: 1; display: inline-block; }
        .pill-a { background: var(--pill-a); }
        .pill-j { background: var(--pill-j); }

        .toggle-cal-container { text-align: center; margin-top: -10px; margin-bottom: 20px; position: relative; z-index: 10; }
        .btn-toggle-cal {
            background: var(--cal-header); color: var(--cal-muted); border: 1px solid #3d4146; border-top: none;
            padding: 5px 20px; font-size: 0.8rem; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;
            cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.2s;
        }
        .btn-toggle-cal:hover { background: var(--cal-hover); color: #fff; }

        .filter-bar { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }

        /* ============================
           LISTA DE RESERVAS (RESPONSIVA)
        ============================ */
        .reserva-card {
            background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 12px; display: flex; flex-wrap: wrap; border-left: 5px solid #ccc;
            transition: transform 0.15s;
            position: relative;
            padding-right: 10px; /* espaço na direita */
        }
        
        .status-confirmado { border-left-color: #198754 !important; }
        .status-pendente { border-left-color: #fd7e14 !important; }

        /* Colunas Padrão (Desktop) */
        .sec-info { flex: 2; padding: 12px; border-right: 1px solid #f0f0f0; }
        .client-name { font-weight: 700; color: #333; font-size: 1rem; }
        .id-reserva { color: #888; font-weight: 900; font-size: 0.85rem; }
        .btn-perfil { font-size: 0.75rem; color: var(--accent); cursor: pointer; text-decoration: none; font-weight: 600; }
        
        .sec-mobile-meta { display:contents; } /* Default desktop: display contents faz os filhos agirem como diretos */
        
        .sec-pax, .sec-time { flex: 1; padding: 10px; text-align: center; border-right: 1px solid #f0f0f0; display: flex; flex-direction: column; justify-content: center; }
        .pax-count { font-size: 1.3rem; font-weight: 800; color: #fd7e14; line-height: 1; }
        .time-display { font-size: 1.2rem; font-weight: 700; color: #333; }
        .mesa-display { font-size: 0.75rem; color: #666; background: #eee; padding: 1px 4px; border-radius: 4px; margin-top: 2px; }

        .sec-obs { flex: 3; padding: 12px; background: #f8f9fa; font-size: 0.85rem; color: #666; max-height: 100px; overflow:hidden; }
        
        .sec-actions { flex: 1; padding: 8px; display: flex; flex-direction: column; gap: 5px; justify-content: center; min-width: 100px; }
        .btn-action { width: 100%; border: 1px solid #dee2e6; background: #fff; color: #555; border-radius: 6px; padding: 4px; font-size: 0.8rem; cursor: pointer; text-align: center; text-decoration: none; transition:0.2s; }
        .btn-action:hover { background: #f8f9fa; }
        .btn-whatsapp { color: #198754; border-color: #198754; }
        .btn-whatsapp:hover { background: #198754; color: #fff; }
        .btn-text { display: inline; }

        .badge-status { position: absolute; top: 8px; right: 8px; font-size: 0.65rem; padding: 3px 6px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        .badge-ok { background: #d1e7dd; color: #0f5132; }
        .badge-wait { background: #fff3cd; color: #664d03; }

        /* =========================================
           MOBILE E TABLET (Max 991px)
           Regra: Altura max 60px, botões icones
        ========================================= */
        @media (max-width: 991px) {
            .reserva-card {
                height: 60px; /* Altura fixa exigida */
                flex-wrap: nowrap;
                align-items: center;
                padding: 0 10px;
                overflow: hidden; /* Corta o que sobrar */
                border-left-width: 6px; /* Borda mais grossa pra ver status */
            }

            /* Esconder itens desnecessários no mobile */
            .badge-status, .sec-obs, .pax-label { display: none !important; }

            /* Ajuste Info (Nome) */
            .sec-info {
                flex: 1; /* Ocupa espaço restante */
                border: none;
                padding: 0;
                overflow: hidden;
                white-space: nowrap;
            }
            .client-name {
                font-size: 0.95rem;
                white-space: nowrap;
                text-overflow: ellipsis;
                overflow: hidden;
            }
            .id-reserva { font-size: 0.8rem; margin-right: 3px; }

            /* Ajuste Meta (Pax e Hora) */
            .sec-mobile-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-right: 10px;
            }
            .sec-pax, .sec-time {
                flex: none;
                border: none;
                padding: 0;
                width: auto;
                text-align: right;
                display: block;
            }
            .pax-count { font-size: 1rem; margin-right: 2px; }
            .time-display { font-size: 0.9rem; }
            .mesa-display { display: none; } /* Oculta mesa se faltar espaço, ou deixe inline pequeno */

            /* Botões somente ícones */
            .sec-actions {
                flex: 0 0 auto; /* Não encolhe */
                flex-direction: row; /* Horizontal */
                width: auto;
                min-width: 0;
                padding: 0;
                gap: 5px;
            }
            .btn-text { display: none; } /* Some texto */
            
            .btn-action {
                width: 36px; height: 36px;
                padding: 0;
                display: flex; align-items: center; justify-content: center;
                font-size: 1rem;
                border-radius: 8px;
            }
        }
        
        /* Modal Dia */
        .modal-overlay-dia { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; display: none; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
        .modal-box-dia { background: #fff; width: 90%; max-width: 450px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden; display: flex; flex-direction: column; max-height: 85vh; }
        .modal-header-dia { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; }
        .modal-body-dia { padding: 0; overflow-y: auto; }
        .reserva-item { padding: 12px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>

<div class="container-fluid mt-3 no-print">

    <!-- WRAPPER DO CALENDÁRIO (Colapsável) -->
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
    <div class="filter-bar">
        <form method="GET" class="row g-2 align-items-end" id="formFiltro">
            <div class="col-md-3 col-6">
                <label class="form-label fw-bold small mb-1">Data</label>
                <input type="date" name="filtro_data" id="filtro_data" class="form-control" value="<?= htmlspecialchars($filtroData) ?>" onchange="carregarListaAjax()">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label fw-bold small mb-1">Busca</label>
                <input type="text" name="busca_texto" id="busca_texto" class="form-control" placeholder="Nome/Tel" value="<?= htmlspecialchars($buscaTexto) ?>" onkeyup="carregarListaAjax()">
            </div>
            <div class="col-md-3 col-12">
                <div class="btn-group w-100" role="group">
                    <button type="button" onclick="setPeriodo('todos')" class="btn btn-outline-secondary active" id="btn-todos">Todos</button>
                    <button type="button" onclick="setPeriodo('almoco')" class="btn btn-outline-secondary" id="btn-almoco">Almoço</button>
                    <button type="button" onclick="setPeriodo('jantar')" class="btn btn-outline-secondary" id="btn-jantar">Jantar</button>
                </div>
                <input type="hidden" name="periodo" id="periodoInput" value="<?= $periodo ?>">
            </div>
            <div class="col-md-3 col-12 d-flex gap-2">
                <button type="button" onclick="imprimirReservas()" class="btn btn-secondary flex-grow-1"><i class="fas fa-print"></i></button>
                <a href="adicionar_reserva.php" class="btn btn-primary flex-grow-1"><i class="fas fa-plus"></i> Nova</a>
            </div>
        </form>
    </div>

    <!-- LISTA AJAX -->
    <div id="area-lista-reservas">
        <?= renderizarListaReservas($pdo, $filtroData, $buscaTexto, $periodo) ?>
    </div>

</div>

<!-- MODAIS (Perfil, Zap, Dia) -->
<div class="modal fade" id="modalPerfil" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header bg-primary text-white"><h6 class="modal-title">Histórico</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><div class="bg-light p-3 text-center d-flex justify-content-around"><div><h5 id="pTotal" class="fw-bold m-0">0</h5><small>Reservas</small></div><div><h5 id="pCancel" class="fw-bold text-danger m-0">0</h5><small>Cancel</small></div><div><h5 id="pPessoas" class="fw-bold text-success m-0">0</h5><small>Pax</small></div></div><ul class="list-group list-group-flush small p-2" id="listaHistorico"></ul></div></div></div></div>
<div class="modal fade" id="modalZap" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-header bg-success text-white"><h6 class="modal-title">WhatsApp</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body d-grid gap-2"><button class="btn btn-outline-success" id="btnZapConfirmar">Confirmar & Enviar</button><button class="btn btn-outline-secondary" id="btnZapDireto">Apenas Abrir</button></div></div></div></div>
<div class="modal-overlay-dia" id="modalDia"><div class="modal-box-dia"><div class="modal-header-dia"><h5 class="m-0" id="modalTitle">Dia</h5><button class="btn-close" onclick="fecharModalDia()"></button></div><div class="modal-body-dia" id="modalContent"><div class="text-center p-3">Carregando...</div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const URL_ATUAL = "<?= basename($_SERVER['PHP_SELF']); ?>";

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
        document.getElementById('btn-'+val).classList.add('active');
        carregarListaAjax();
    }

    function carregarListaAjax(dataISO = null) {
        if(dataISO) document.getElementById('filtro_data').value = dataISO;
        const d = document.getElementById('filtro_data').value;
        const b = document.getElementById('busca_texto').value;
        const p = document.getElementById('periodoInput').value;
        const container = document.getElementById('area-lista-reservas');
        container.style.opacity = '0.5';
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
        if(el) el.classList.add('selected');
        carregarListaAjax(dataISO);
    }

   function verReservasDia(data, e) {
    if(e) e.stopPropagation();
    const modal = document.getElementById('modalDia');
    document.getElementById('modalTitle').innerText = data.split('-').reverse().join('/');
    modal.style.display = 'flex';
    document.getElementById('modalContent').innerHTML = '<div class="text-center p-3 text-muted">Buscando...</div>';
    
    fetch('ajax_reservas_dia.php?data=' + data)
        .then(r => r.json())
        .then(json => {
            if(json.erro || !json.lista){ document.getElementById('modalContent').innerHTML='<div class="p-3 text-center">Nada encontrado.</div>'; return; }
            let h = '';
            json.lista.forEach(r => {
                // AQUI ESTÁ A MUDANÇA:
                h += `<div class="reserva-item">
                        <div>
                            <strong>${r.nome}</strong><br>
                            <small class="text-muted">${r.num_pessoas} pax • ${r.horario.substring(0,5)}</small>
                        </div>
                        <a href="editar_reserva.php?id=${r.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-pen"></i> Editar
                        </a>
                      </div>`;
            });
            h += `<div class="p-2 text-center bg-light fw-bold">Total: ${json.resumo.total} pessoas</div>`;
            document.getElementById('modalContent').innerHTML = h;
        })
        .catch(() => {
            document.getElementById('modalContent').innerHTML = '<div class="p-3 text-center text-danger">Erro (Verifique ajax_reservas_dia.php)</div>';
        });
}
    function fecharModalDia(){ document.getElementById('modalDia').style.display='none'; }
    window.onclick = function(e){ if(e.target == document.getElementById('modalDia')) fecharModalDia(); }

    let curId, curLink, curLinkD;
    function abrirModalZap(id, l1, l2) { curId=id; curLink=l1; curLinkD=l2; new bootstrap.Modal(document.getElementById('modalZap')).show(); }
    document.getElementById('btnZapConfirmar').onclick = () => {
        window.open(curLink, '_blank');
        const fd = new FormData(); fd.append('acao','confirmar_reserva'); fd.append('id',curId);
        fetch(URL_ATUAL, {method:'POST', body:fd}).then(() => setTimeout(() => carregarListaAjax(), 1000));
    };
    document.getElementById('btnZapDireto').onclick = () => window.open(curLinkD, '_blank');

    function abrirModalPerfil(tel) {
        new bootstrap.Modal(document.getElementById('modalPerfil')).show();
        fetch(URL_ATUAL + '?acao=ver_perfil&telefone='+tel).then(r=>r.json()).then(d => {
            document.getElementById('pTotal').innerText = d.stats.total_reservas||0;
            document.getElementById('pCancel').innerText = d.stats.total_canceladas||0;
            document.getElementById('pPessoas').innerText = d.stats.total_pessoas_trazidas||0;
            let h = '';
            d.historico.forEach(x => {
                h += `<li class="list-group-item d-flex justify-content-between"><span>${new Date(x.data).toLocaleDateString('pt-BR')}</span><span>${x.num_pessoas}p <i class="fas ${x.status==0?'fa-times text-danger':'fa-check text-success'}"></i></span></li>`;
            });
            document.getElementById('listaHistorico').innerHTML = h || '<li class="list-group-item text-center">Sem histórico.</li>';
        });
    }

    function imprimirReservas() {
        const c = document.getElementById('print-data-hidden').innerHTML || document.getElementById('area-lista-reservas').innerHTML;
        const w = window.open('','','height=800,width=1000');
        w.document.write('<html><head><title>Imprimir</title><style>body{font-family:sans-serif} table{width:100%;border-collapse:collapse} th,td{border:1px solid #000;padding:5px}</style></head><body>'+c+'</body></html>');
        w.document.close(); w.print();
    }
</script>
</body>
</html>