<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';
include 'lembrete_backup.php';

// --- FUNÇÕES PHP (Apenas para carga inicial do calendário) ---

function getReservations($pdo, $month, $year) {
    $stmt = $pdo->prepare("
        SELECT 
            data,
            SUM(CASE WHEN horario BETWEEN '11:00:00' AND '17:59:00' THEN IF(status != 0, num_pessoas, 0) ELSE 0 END) AS almoco,
            SUM(CASE WHEN horario BETWEEN '18:00:00' AND '23:59:00' THEN IF(status != 0, num_pessoas, 0) ELSE 0 END) AS jantar
        FROM clientes 
        WHERE MONTH(data) = :month AND YEAR(data) = :year
        GROUP BY data
    ");
    $stmt->execute(['month' => $month, 'year' => $year]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca dados iniciais de HOJE para exibir ao carregar a página
function getDayStats($pdo, $date) {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN horario BETWEEN '11:00:00' AND '17:59:00' THEN IF(status != 0, num_pessoas, 0) ELSE 0 END) AS almoco,
            SUM(CASE WHEN horario BETWEEN '18:00:00' AND '23:59:00' THEN IF(status != 0, num_pessoas, 0) ELSE 0 END) AS jantar,
            SUM(IF(status != 0, num_pessoas, 0)) AS total
        FROM clientes
        WHERE data = :data
    ");
    $stmt->execute(['data' => $date]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ?: ['almoco' => 0, 'jantar' => 0, 'total' => 0];
}

// --- CONFIGURAÇÃO DO CALENDÁRIO ---

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

if ($month < 1) { $month = 12; $year--; }
elseif ($month > 12) { $month = 1; $year++; }

$reservationsMap = [];
$rawReservations = getReservations($pdo, $month, $year);
foreach ($rawReservations as $res) {
    $reservationsMap[$res['data']] = $res;
}

// Dados iniciais (hoje)
$todayDate = date('Y-m-d');
$todayStats = getDayStats($pdo, $todayDate);

function generateCalendar($month, $year, $reservationsMap) {
    $daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $firstDayTimestamp = mktime(0, 0, 0, $month, 1, $year);
    $numberDays = date('t', $firstDayTimestamp);
    $dateComponents = getdate($firstDayTimestamp);
    $dayOfWeek = $dateComponents['wday'];
    
    $monthNames = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    $monthName = $monthNames[$month];

    $html = "<div class='calendar-container'>";
    $html .= "<div class='calendar-header-flex'>";
    $html .= "  <h2 class='month-title'>$monthName <span class='year-subtitle'>$year</span></h2>";
    $html .= "</div>";

    $html .= "<div class='table-responsive'>";
    $html .= "<table class='calendar-table'>";
    $html .= "<thead><tr>";
    foreach ($daysOfWeek as $day) {
        $html .= "<th>$day</th>";
    }
    $html .= "</tr></thead><tbody><tr>";

    if ($dayOfWeek > 0) {
        for ($i = 0; $i < $dayOfWeek; $i++) {
            $html .= "<td class='empty'></td>";
        }
    }

    $currentDay = 1;
    $today = date('Y-m-d');

    while ($currentDay <= $numberDays) {
        if ($dayOfWeek == 7) {
            $dayOfWeek = 0;
            $html .= "</tr><tr>";
        }

        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
        $almoco = $reservationsMap[$currentDate]['almoco'] ?? 0;
        $jantar = $reservationsMap[$currentDate]['jantar'] ?? 0;
        
        $isToday = ($currentDate === $today) ? 'today-cell' : '';

        $html .= "<td class='day-cell $isToday'>";
        
        // Header da célula: Número Clicável + Olho
        $html .= "  <div class='day-number-row'>";
        // AQUI ESTÁ A MUDANÇA: O número chama a função carregarResumo
        $html .= "      <button class='btn-day-number' onclick=\"carregarResumo('$currentDate')\">$currentDay</button>";
        $html .= "      <button class='btn-view-day' onclick=\"verReservasDia('$currentDate', event)\"><i class='material-icons'>visibility</i></button>";
        $html .= "  </div>";

        $html .= "  <div class='stats-column'>";
        if ($almoco > 0) {
            $html .= " <span class='pill pill-almoco'>A: $almoco</span>";
        }
        if ($jantar > 0) {
            $html .= " <span class='pill pill-jantar'>J: $jantar</span>";
        }
        $html .= "  </div>";
        
        $html .= "</td>";

        $currentDay++;
        $dayOfWeek++;
    }

    if ($dayOfWeek != 7) {
        $remainingDays = 7 - $dayOfWeek;
        for ($i = 0; $i < $remainingDays; $i++) {
            $html .= "<td class='empty'></td>";
        }
    }

    $html .= "</tr></tbody></table>";
    $html .= "</div></div>";

    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Calendário de Reservas</title>
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f4f6f9;
            --card-bg: #ffffff;
            --text-main: #2d3436;
            --text-muted: #636e72;
            --primary: #0984e3;
            --accent-almoco: #fdcb6e;
            --text-almoco: #d35400;
            --accent-jantar: #74b9ff;
            --text-jantar: #0984e3;
            --border-radius: 12px;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 80px 15px 100px 15px;
        }

        .main-container { max-width: 1200px; margin: 0 auto; }

        /* NAVEGAÇÃO */
        .top-controls {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 15px; margin-bottom: 20px;
        }
        .nav-group { display: flex; gap: 10px; }
        .btn-nav {
            background: var(--card-bg); border: 1px solid #dfe6e9; padding: 8px 15px;
            border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px;
            font-weight: 600; color: var(--text-muted); box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .btn-nav:hover { transform: translateY(-2px); color: var(--primary); }
        .btn-primary-action { background: var(--primary); color: #fff; border: none; }
        .btn-primary-action:hover { background: #0769b5; color:#fff;}

        /* CALENDÁRIO */
        .calendar-container {
            background: var(--card-bg); border-radius: var(--border-radius);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 30px;
        }
        .calendar-header-flex {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 15px; border-bottom: 2px solid #f1f2f6; padding-bottom: 10px;
        }
        .month-title { margin: 0; font-size: 1.5rem; text-transform: capitalize; }
        .year-subtitle { font-weight: 300; color: var(--text-muted); margin-left: 5px; }

        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .calendar-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .calendar-table th {
            text-align: center; padding: 10px; color: var(--text-muted);
            text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px;
        }
        .calendar-table td {
            border: 1px solid #f1f2f6; height: 100px; vertical-align: top;
            padding: 8px; width: 14.28%; transition: background 0.2s;
        }
        .calendar-table td:not(.empty):hover { background-color: #f8fbff; }
        .today-cell { background-color: #e3f2fd !important; border: 2px solid var(--primary) !important; }

        .day-number-row { display: flex; justify-content: space-between; margin-bottom: 5px; }

        /* Botão do dia (número) */
        .btn-day-number {
            background: none; border: none; font-weight: 700; font-size: 1.1rem;
            color: var(--text-main); cursor: pointer; padding: 0;
            text-decoration: underline; text-decoration-color: transparent;
            transition: 0.2s;
        }
        .btn-day-number:hover { color: var(--primary); text-decoration-color: var(--primary); transform: scale(1.1); }

        .btn-view-day { background: none; border: none; cursor: pointer; color: #b2bec3; padding: 0; }
        .btn-view-day:hover { color: var(--primary); }
        .btn-view-day .material-icons { font-size: 18px; }

        .stats-column { display: flex; flex-direction: column; gap: 4px; }
        .pill { font-size: 0.75rem; padding: 3px 6px; border-radius: 4px; font-weight: 600; white-space: nowrap; }
        .pill-almoco { background-color: var(--accent-almoco); color: var(--text-almoco); }
        .pill-jantar { background-color: var(--accent-jantar); color: #1e3799; }

        /* CARDS RESUMO */
        .summary-title {
            font-size: 1.2rem; margin-bottom: 15px; color: var(--text-muted);
            display: flex; align-items: center; gap: 8px;
        }
        .cards-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 40px;
        }
        .stat-card {
            background: var(--card-bg); padding: 20px; border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px;
            position: relative; overflow: hidden; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card::after {
            content: ''; position: absolute; right: 0; top: 0; bottom: 0; width: 6px;
        }
        .card-orange::after { background: var(--accent-almoco); }
        .card-blue::after { background: var(--primary); }
        .card-green::after { background: #00b894; }

        .icon-box {
            width: 50px; height: 50px; border-radius: 50%; display: flex;
            align-items: center; justify-content: center; font-size: 24px;
        }
        .ib-orange { background: #fff3e0; color: #e67e22; }
        .ib-blue { background: #e3f2fd; color: var(--primary); }
        .ib-green { background: #e6fffa; color: #00b894; }

        .stat-info h4 { margin: 0 0 5px 0; font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; }
        .stat-info .value { font-size: 1.8rem; font-weight: 800; color: var(--text-main); line-height: 1; }
        .stat-info small { font-size: 0.8rem; color: #b2bec3; }

        /* MODAL */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 1000; display: none;
            justify-content: center; align-items: center; backdrop-filter: blur(3px);
        }
        .modal-box {
            background: #fff; width: 90%; max-width: 500px; border-radius: 15px;
            padding: 0; box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease;
            max-height: 80vh; display: flex; flex-direction: column;
        }
        .modal-header {
            padding: 15px 20px; border-bottom: 1px solid #f1f2f6; display: flex;
            justify-content: space-between; align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 1.1rem; }
        .btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
        .modal-body { padding: 20px; overflow-y: auto; }
        .reserva-item {
            padding: 10px; border-bottom: 1px solid #f1f2f6; display: flex;
            justify-content: space-between; align-items: center;
        }
        .res-name { font-weight: 600; display: block; }
        .res-details { font-size: 0.85rem; color: #636e72; }
        .res-actions a { text-decoration: none; margin-left: 10px; color: var(--primary); font-weight: 600; font-size: 0.85rem; }

        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes spin { 100% { transform:rotate(360deg); } }

        .fab {
            position: fixed; bottom: 20px; right: 20px; background: var(--primary);
            color: white; width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 15px rgba(9, 132, 227, 0.4); cursor: pointer;
            border: none; z-index: 99; transition: transform 0.2s;
        }
        .fab:hover { transform: scale(1.1); }

        @media (max-width: 768px) {
            .top-controls { justify-content: center; }
            .calendar-container { padding: 10px; }
            .calendar-table td { height: 80px; padding: 4px; }
            .cards-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="main-container">

    <div class="top-controls">
        <div class="nav-group">
            <form method="GET" style="display:contents">
                <input type="hidden" name="month" value="<?= $month - 1 ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <button class="btn-nav"><i class="material-icons">chevron_left</i> Ant</button>
            </form>
            
            <form method="GET" style="display:contents">
                <input type="hidden" name="month" value="<?= $month + 1 ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <button class="btn-nav">Próx <i class="material-icons">chevron_right</i></button>
            </form>
        </div>

        <div class="nav-group">
            <button class="btn-nav btn-primary-action" onclick="window.location.href='adicionar_reserva.php'">
                <i class="material-icons">add</i> Nova
            </button>
            <button class="btn-nav" onclick="window.location.href='pesquisar.php'">
                <i class="material-icons">search</i> Buscar
            </button>
        </div>
    </div>

    <?= generateCalendar($month, $year, $reservationsMap) ?>

    <!-- SEÇÃO DE RESUMO DO DIA (Atualizada via JS) -->
    <h3 class="summary-title" id="titleResumo">
        <i class="material-icons">today</i> Resumo de: <?= date('d/m/Y', strtotime($todayDate)) ?>
    </h3>
    
    <div class="cards-grid">
        <div class="stat-card card-orange">
            <div class="icon-box ib-orange"><i class="material-icons">wb_sunny</i></div>
            <div class="stat-info">
                <h4>Almoço</h4>
                <div class="value" id="valAlmoco"><?= (int)$todayStats['almoco'] ?></div>
                <small>confirmados</small>
            </div>
        </div>

        <div class="stat-card card-blue">
            <div class="icon-box ib-blue"><i class="material-icons">nights_stay</i></div>
            <div class="stat-info">
                <h4>Jantar</h4>
                <div class="value" id="valJantar"><?= (int)$todayStats['jantar'] ?></div>
                <small>confirmados</small>
            </div>
        </div>

        <div class="stat-card card-green">
            <div class="icon-box ib-green"><i class="material-icons">groups</i></div>
            <div class="stat-info">
                <h4>Total Dia</h4>
                <div class="value" id="valTotal"><?= (int)$todayStats['total'] ?></div>
                <small>Geral</small>
            </div>
        </div>
    </div>

</div>

<!-- BOTÃO FLUTUANTE -->
<button class="fab" onclick="window.location.href='adicionar_reserva.php'">
    <i class="material-icons">add</i>
</button>

<!-- MODAL DE LISTAGEM -->
<div class="modal-overlay" id="modalDia">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Reservas do dia</h3>
            <button class="btn-close" onclick="fecharModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalContent">
            Carregando...
        </div>
    </div>
</div>

<script>
// Função para buscar dados no servidor
async function buscarDados(data) {
    try {
        const response = await fetch('ajax_reservas_dia.php?data=' + data);
        const json = await response.json();
        return json;
    } catch (e) {
        console.error("Erro AJAX:", e);
        return { erro: true, msg: "Erro de conexão" };
    }
}

// 1. Função quando clica no OLHO (Abre Modal com a lista)
function verReservasDia(data, event) {
    if(event) event.stopPropagation();
    
    const modal = document.getElementById('modalDia');
    const title = document.getElementById('modalTitle');
    const content = document.getElementById('modalContent');
    
    const dataParts = data.split('-');
    const dataFormatada = `${dataParts[2]}/${dataParts[1]}/${dataParts[0]}`;
    
    title.innerText = `Reservas: ${dataFormatada}`;
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="material-icons" style="animation:spin 1s infinite">refresh</i><br>Buscando dados...</div>';

    buscarDados(data).then(json => {
        if (json.erro) {
            content.innerHTML = '<div style="color:red; text-align:center; padding:20px;">' + (json.msg || 'Erro desconhecido') + '</div>';
            return;
        }

        if (!json.lista || json.lista.length === 0) {
            content.innerHTML = '<div style="text-align:center; padding:20px; color:#888">Nenhuma reserva para este dia.</div>';
            return;
        }

        let html = '';
        json.lista.forEach(reserva => {
            const pessoas = parseInt(reserva.num_pessoas);
            let linkWhats = '';
            
            if(reserva.telefone){
                let num = reserva.telefone.replace(/\D/g, '');
                linkWhats = `<a href="https://wa.me/55${num}" target="_blank"><i class="material-icons" style="font-size:16px; vertical-align:middle">chat</i></a>`;
            }

            // Exibir badge de cancelado se necessário
            let style = (reserva.status == 0) ? 'style="opacity:0.6; text-decoration:line-through"' : '';

            html += `
            <div class="reserva-item" ${style}>
                <div>
                    <span class="res-name">${reserva.nome}</span>
                    <span class="res-details">${pessoas} p. • ${reserva.horario.substring(0,5)}</span>
                </div>
                <div class="res-actions">
                    ${linkWhats}
                    <a href="editar_reserva.php?id=${reserva.id}">Editar</a>
                </div>
            </div>`;
        });
        
        // Adiciona um pequeno resumo no rodapé do modal também
        html += `<div style="background:#f1f2f6; padding:10px; margin-top:15px; text-align:center; border-radius:8px; font-weight:bold;">
                    Total: ${json.resumo.total} pessoas
                 </div>`;

        content.innerHTML = html;
    });
}

// 2. Função quando clica no NÚMERO DO DIA (Atualiza Cards de Resumo)
function carregarResumo(data) {
    const dataParts = data.split('-');
    const dataFormatada = `${dataParts[2]}/${dataParts[1]}/${dataParts[0]}`;

    // Atualiza título visualmente para dar feedback imediato
    document.getElementById('titleResumo').innerHTML = `<i class="material-icons">event</i> Resumo de: ${dataFormatada} <small>(Carregando...)</small>`;

    // Rola a tela até o resumo
    document.getElementById('titleResumo').scrollIntoView({ behavior: 'smooth' });

    buscarDados(data).then(json => {
        // Remove o "(Carregando...)"
        document.getElementById('titleResumo').innerHTML = `<i class="material-icons">event</i> Resumo de: ${dataFormatada}`;

        if (json.erro) {
            alert("Erro ao buscar resumo: " + (json.msg || "Desconhecido"));
            return;
        }
        
        // Efeito de atualização nos números
        animarValor('valAlmoco', json.resumo.almoco);
        animarValor('valJantar', json.resumo.jantar);
        animarValor('valTotal', json.resumo.total);
    });
}

// Pequena animação para trocar os números
function animarValor(idElemento, novoValor) {
    const el = document.getElementById(idElemento);
    el.style.opacity = 0;
    setTimeout(() => {
        el.innerText = novoValor;
        el.style.opacity = 1;
    }, 200);
}

function fecharModal() {
    document.getElementById('modalDia').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modalDia');
    if (event.target == modal) {
        fecharModal();
    }
}
</script>

</body>
</html>