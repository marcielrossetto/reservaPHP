<?php
session_start();
require 'config.php';

// 1. LOGIN MASTER
if (isset($_POST['master_login'])) {
    $u = addslashes($_POST['user']);
    $p = md5($_POST['pass']);
    $sql = $pdo->prepare("SELECT * FROM master_admin WHERE usuario = ? AND senha = ?");
    $sql->execute([$u, $p]);
    if ($sql->rowCount() > 0) { $_SESSION['master_logged'] = true; header("Location: cpanel.php"); exit; }
}

if (isset($_GET['sair'])) { session_destroy(); header("Location: cpanel.php"); exit; }

if (!isset($_SESSION['master_logged'])): 
?>
<!DOCTYPE html><html><head><title>Login Master</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Inter', sans-serif; }</style></head>
<body>
    <div class="card p-5 shadow-lg border-0" style="width:400px; border-radius:25px;">
        <div class="text-center mb-4"><i class="fas fa-shield-alt fa-3x text-primary mb-3"></i><h3 class="fw-bold text-primary">MASTER LOGIN</h3></div>
        <form method="POST">
            <input type="hidden" name="master_login" value="1">
            <div class="mb-3"><label class="small fw-bold">USUÁRIO</label><input type="text" name="user" class="form-control border-2 shadow-none" required></div>
            <div class="mb-4"><label class="small fw-bold">SENHA</label><input type="password" name="pass" class="form-control border-2 shadow-none" required></div>
            <button class="btn btn-primary w-100 fw-bold py-3 shadow">ACESSAR PAINEL</button>
        </form>
    </div>
</body></html>
<?php exit; endif;

$tab = $_GET['tab'] ?? 'dashboard';
$sel_mes = (int)($_GET['sel_mes'] ?? date('m'));
$sel_ano = (int)($_GET['sel_ano'] ?? date('Y'));
$mes_hoje = (int)date('m'); $ano_hoje = (int)date('Y');

/* --- AÇÕES --- */

// Toggle pagamento (marca/desmarca recebimento)
if (isset($_GET['toggle_pago'])) {
    $id_emp = (int)$_GET['emp_id']; $mes = (int)$_GET['mes']; $ano = (int)$_GET['ano'];
    $check = $pdo->prepare("SELECT id FROM pagamentos_mensais WHERE empresa_id = ? AND mes = ? AND ano = ?");
    $check->execute([$id_emp, $mes, $ano]);
    if ($check->rowCount() > 0) {
        $pdo->prepare("DELETE FROM pagamentos_mensais WHERE empresa_id = ? AND mes = ? AND ano = ?")->execute([$id_emp, $mes, $ano]);
    } else {
        $pdo->prepare("INSERT INTO pagamentos_mensais (empresa_id, mes, ano, data_registro) VALUES (?, ?, ?, NOW())")->execute([$id_emp, $mes, $ano]);
    }
    header("Location: cpanel.php?tab=$tab&sel_mes=$sel_mes&sel_ano=$sel_ano"); exit;
}

// Toggle status (ativa/bloqueia empresa - mantém nos relatórios)
if (isset($_GET['toggle_status'])) {
    $pdo->prepare("UPDATE empresas SET status = 1 - status WHERE id = ?")->execute([$_GET['toggle_status']]);
    header("Location: cpanel.php?tab=empresas"); exit;
}

// EXCLUIR empresa (move para inativas - remove dos cálculos)
if (isset($_GET['excluir_empresa'])) {
    $id = (int)$_GET['excluir_empresa'];
    $pdo->prepare("UPDATE empresas SET excluida = 1, data_exclusao = NOW() WHERE id = ?")->execute([$id]);
    header("Location: cpanel.php?tab=empresas"); exit;
}

// RESTAURAR empresa excluída
if (isset($_GET['restaurar_empresa'])) {
    $id = (int)$_GET['restaurar_empresa'];
    $pdo->prepare("UPDATE empresas SET excluida = 0, data_exclusao = NULL WHERE id = ?")->execute([$id]);
    header("Location: cpanel.php?tab=inativas"); exit;
}

// CADASTRAR/EDITAR empresa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $nome = $_POST['nome_empresa']; $cnpj = $_POST['cnpj_cpf']; $exp = $_POST['data_expiracao'];
    $plano = $_POST['plano']; $valor = $_POST['valor']; $email = $_POST['email']; $senha = $_POST['senha']; $obs = $_POST['observacoes'];
    $logo = (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) ? fopen($_FILES['logo']['tmp_name'], 'rb') : null;

    if ($_POST['acao'] == 'cadastrar_empresa') {
        $inc = $_POST['data_inclusao'] ?? date('Y-m-d');
        $sql = $pdo->prepare("INSERT INTO empresas (nome_empresa, cnpj_cpf, data_inclusao, data_expiracao, plano, valor, observacoes, logo, status, excluida) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 0)");
        $sql->execute([$nome, $cnpj, $inc, $exp, $plano, $valor, $obs, $logo]);
        $new_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO login (empresa_id, nome, email, senha, senha_texto, nivel, status) VALUES (?, ?, ?, ?, ?, 'master', 1)")->execute([$new_id, $nome, $email, md5($senha), $senha]);
    } else {
        $id = $_POST['id']; $inc = $_POST['data_inclusao'];
        if ($logo) {
            $sql = $pdo->prepare("UPDATE empresas SET nome_empresa=?, cnpj_cpf=?, data_inclusao=?, data_expiracao=?, plano=?, valor=?, observacoes=?, logo=? WHERE id=?");
            $sql->execute([$nome, $cnpj, $inc, $exp, $plano, $valor, $obs, $logo, $id]);
        } else {
            $sql = $pdo->prepare("UPDATE empresas SET nome_empresa=?, cnpj_cpf=?, data_inclusao=?, data_expiracao=?, plano=?, valor=?, observacoes=? WHERE id=?");
            $sql->execute([$nome, $cnpj, $inc, $exp, $plano, $valor, $obs, $id]);
        }
        if (!empty($email)) { $pdo->prepare("UPDATE login SET email=?, senha=?, senha_texto=? WHERE empresa_id=? AND nivel='master'")->execute([$email, md5($senha), $senha, $id]); }
    }
    header("Location: cpanel.php?tab=empresas"); exit;
}

/* --- BUSCA DE DADOS --- */

// Empresas ATIVAS (excluida = 0)
$empresas = $pdo->query("SELECT e.*, l.email, l.senha_texto FROM empresas e LEFT JOIN login l ON l.empresa_id = e.id AND l.nivel = 'master' WHERE e.excluida = 0 ORDER BY e.nome_empresa ASC")->fetchAll(PDO::FETCH_ASSOC);

// Empresas EXCLUÍDAS (para aba inativas)
$empresas_inativas = $pdo->query("SELECT e.*, l.email, l.senha_texto FROM empresas e LEFT JOIN login l ON l.empresa_id = e.id AND l.nivel = 'master' WHERE e.excluida = 1 ORDER BY e.data_exclusao DESC")->fetchAll(PDO::FETCH_ASSOC);

// Pagamentos registrados
$pagos_sql = $pdo->prepare("SELECT empresa_id, mes, data_registro FROM pagamentos_mensais WHERE ano = ?");
$pagos_sql->execute([$sel_ano]);
$pagamentos_db = [];
while ($p = $pagos_sql->fetch()) { $pagamentos_db[$p['empresa_id']][$p['mes']] = $p['data_registro']; }

/* --- CÁLCULOS FINANCEIROS (apenas empresas ATIVAS e não excluídas) --- */
$recebidoMes = $pdo->query("SELECT SUM(e.valor) FROM empresas e INNER JOIN pagamentos_mensais p ON e.id = p.empresa_id WHERE p.mes = $sel_mes AND p.ano = $sel_ano AND e.excluida = 0")->fetchColumn() ?? 0;

$projecaoMensal = 0; 
$projecaoAnual = 0; 
$empresasPendentes = [];
$empresasRecebidas = [];

foreach ($empresas as $emp) {
    if ($emp['status'] == 1) { // Apenas empresas ativas e não bloqueadas
        $projecaoMensal += $emp['valor'];
        $projecaoAnual += ($emp['plano'] == 'mensal' ? $emp['valor'] * 12 : $emp['valor']);
        
        if (!isset($pagamentos_db[$emp['id']][$sel_mes])) { 
            $empresasPendentes[] = $emp; 
        } else {
            $empresasRecebidas[] = $emp;
        }
    }
}

$faltaReceber = $projecaoMensal - $recebidoMes;
$percentualRecebido = $projecaoMensal > 0 ? round(($recebidoMes / $projecaoMensal) * 100, 1) : 0;

// Métricas por plano
$total_mensal = $pdo->query("SELECT COUNT(*) FROM empresas WHERE plano = 'mensal' AND status = 1 AND excluida = 0")->fetchColumn();
$total_anual = $pdo->query("SELECT COUNT(*) FROM empresas WHERE plano = 'anual' AND status = 1 AND excluida = 0")->fetchColumn();
$receita_mensal = $pdo->query("SELECT SUM(valor) FROM empresas WHERE plano = 'mensal' AND status = 1 AND excluida = 0")->fetchColumn() ?? 0;
$receita_anual = $pdo->query("SELECT SUM(valor) FROM empresas WHERE plano = 'anual' AND status = 1 AND excluida = 0")->fetchColumn() ?? 0;

/* --- RELATÓRIO DE PERFORMANCE --- */
$relatorio_performance = $pdo->query("
    SELECT 
        e.nome_empresa,
        e.plano,
        (SELECT COUNT(*) FROM clientes WHERE empresa_id = e.id AND status = 'confirmado') as total_reservas,
        (SELECT COUNT(*) FROM clientes WHERE empresa_id = e.id AND status = 'cancelado') as reservas_canceladas,
        (SELECT COUNT(*) FROM clientes WHERE empresa_id = e.id AND status = 'fila') as quantidade_fila,
        (SELECT COUNT(*) FROM clientes WHERE empresa_id = e.id AND status = 'desistencia_fila') as desistencia_fila,
        (SELECT SUM(num_pessoas) FROM clientes WHERE empresa_id = e.id AND status = 'confirmado') as total_pax,
        (SELECT SUM(num_pessoas) FROM clientes WHERE empresa_id = e.id AND status = 'fila') as pax_fila
    FROM empresas e 
    WHERE e.excluida = 0
    ORDER BY total_reservas DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Totalizadores gerais
$totais = [
    'reservas' => array_sum(array_column($relatorio_performance, 'total_reservas')),
    'canceladas' => array_sum(array_column($relatorio_performance, 'reservas_canceladas')),
    'fila' => array_sum(array_column($relatorio_performance, 'quantidade_fila')),
    'desistencia_fila' => array_sum(array_column($relatorio_performance, 'desistencia_fila')),
    'pax' => array_sum(array_column($relatorio_performance, 'total_pax')),
    'pax_fila' => array_sum(array_column($relatorio_performance, 'pax_fila')),
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Master Panel BI Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --primary: #3699ff; 
            --dark-side: #1e1e2d; 
            --success: #1bc5bd; 
            --danger: #f64e60; 
            --warning: #ffa800;
            --info: #8950fc;
        }
        body { background: #f4f7fa; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        .sidebar { background: var(--dark-side); min-height: 100vh; position: fixed; width: 260px; padding: 25px; color: white; z-index: 2000; transition: 0.3s; left: 0; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar.hidden { left: -260px; }
        .content { margin-left: 260px; transition: 0.3s; min-height: 100vh; }
        .content.full { margin-left: 0; }
        
        .top-bar { background: white; padding: 15px 30px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #ebedf3; position: sticky; top: 0; z-index: 1500; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .top-bar h2 { margin: 0; margin-left: 55px; font-size: 1.3rem; font-weight: 800; color: var(--dark-side); }

        .nav-link { color: #a2a3b7; padding: 14px 16px; border-radius: 12px; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem; transition: all 0.2s; }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
        .nav-link.active { background: linear-gradient(135deg, var(--primary), #2684ff); color: white; box-shadow: 0 4px 12px rgba(54, 153, 255, 0.3); }
        .nav-link i { width: 20px; }
        
        .card-custom { border-radius: 18px; border: none; box-shadow: 0 3px 12px rgba(0,0,0,0.04); background: white; margin-bottom: 20px; transition: transform 0.2s; }
        .card-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        
        /* CÉLULA DO MÊS */
        .month-cell { 
            width: 45px; height: 52px; 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            border-radius: 12px; border: 2px solid #f1f1f1; 
            cursor: pointer; transition: all 0.2s; text-decoration: none; line-height: 1; margin: 0 auto;
        }
        .month-cell b { font-size: 1rem; font-weight: 800; }
        .month-cell span { font-size: 0.45rem; font-weight: 600; margin-top: 3px; display: block; text-align: center; }
        
        .month-paid { background: linear-gradient(135deg, var(--success), #0e9f98); color: white !important; border-color: var(--success); box-shadow: 0 4px 10px rgba(27, 197, 189, 0.25); }
        .month-paid:hover { transform: scale(1.05); }
        .month-pending { background: #fff; color: var(--danger); border-color: var(--danger); font-weight: 700; }
        .month-pending:hover { background: #fff5f5; transform: scale(1.05); }
        .month-disabled { background: #f8f9fa; color: #d1d3e0 !important; cursor: not-allowed; border-color: #e4e6ef; pointer-events: none; opacity: 0.6; }
        
        #btn-hamburger { position: fixed; top: 17px; left: 20px; z-index: 2500; background: var(--primary); color: white; border: none; width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(54, 153, 255, 0.3); transition: 0.2s; }
        #btn-hamburger:hover { transform: scale(1.08); }
        
        .badge-plano { font-size: 0.65rem; padding: 4px 8px; border-radius: 6px; font-weight: 700; }
        
        .stat-card { border-left: 4px solid; padding: 18px; }
        .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; opacity: 0.7; margin-bottom: 8px; }
        .stat-value { font-size: 1.8rem; font-weight: 800; margin: 0; }
        .stat-sub { font-size: 0.75rem; margin-top: 5px; opacity: 0.6; }
        
        .empresa-nome-td { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.8rem; font-weight: 700; }
        
        .table-hover tbody tr:hover { background: #f8f9fa; }
        
        .progress-ring { width: 100px; height: 100px; }
        .progress-ring-circle { transition: stroke-dashoffset 0.35s; transform: rotate(-90deg); transform-origin: 50% 50%; }
        
        @media (max-width: 768px) { 
            .month-cell { width: 38px; height: 46px; } 
            .month-cell b { font-size: 0.85rem; } 
            .empresa-nome-td { max-width: 90px; font-size: 0.7rem; }
            .stat-value { font-size: 1.4rem; }
        }
        
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        
        .filter-selector { background: white; padding: 8px 12px; border-radius: 10px; border: 2px solid #e4e6ef; font-weight: 600; font-size: 0.85rem; }
        
        .sidebar-brand { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 15px; margin-bottom: 25px; }
    </style>
</head>
<body>

<button id="btn-hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

<div class="sidebar shadow-lg" id="mainSidebar">
    <div class="sidebar-brand text-center">
        <i class="fas fa-gem text-primary fa-3x mb-2"></i>
        <h4 class="mt-2 fw-bold text-white mb-0">MASTER BI</h4>
        <small class="text-white-50">Business Intelligence</small>
    </div>
    
    <nav class="nav flex-column">
        <a href="?tab=dashboard" class="nav-link <?= $tab=='dashboard'?'active':'' ?>"><i class="fas fa-chart-line me-2"></i> Dashboard Financeiro</a>
        <a href="?tab=empresas" class="nav-link <?= $tab=='empresas'?'active':'' ?>"><i class="fas fa-building me-2"></i> Gerenciar Empresas</a>
        <a href="?tab=performance" class="nav-link <?= $tab=='performance'?'active':'' ?>"><i class="fas fa-chart-bar me-2"></i> Performance & Reservas</a>
        <a href="?tab=inativas" class="nav-link <?= $tab=='inativas'?'active':'' ?>"><i class="fas fa-archive me-2"></i> Empresas Excluídas</a>
        
        <hr class="opacity-25 my-4">
        <a href="cpanel.php?sair=1" class="nav-link text-danger"><i class="fas fa-power-off me-2"></i> Sair do Sistema</a>
    </nav>
</div>

<div class="content" id="mainContent">
    <div class="top-bar">
        <h2>
            <?php 
                if($tab == 'dashboard') echo '<i class="fas fa-chart-line me-2"></i> Dashboard Financeiro';
                elseif($tab == 'empresas') echo '<i class="fas fa-building me-2"></i> Gerenciar Empresas';
                elseif($tab == 'performance') echo '<i class="fas fa-chart-bar me-2"></i> Performance & Reservas';
                elseif($tab == 'inativas') echo '<i class="fas fa-archive me-2"></i> Empresas Excluídas';
            ?>
        </h2>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-primary px-3 py-2"><i class="fas fa-calendar me-1"></i> <?= date('d/m/Y') ?></span>
        </div>
    </div>
    
    <div class="container-padding p-4">

    <?php if($tab == 'dashboard'): ?>
        
        <!-- SELETOR DE MÊS/ANO -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Análise Financeira Detalhada</h5>
                <small class="text-muted">Visão completa de recebimentos e projeções</small>
            </div>
            <form class="d-flex gap-2">
                <input type="hidden" name="tab" value="dashboard">
                <select name="sel_mes" class="filter-selector" onchange="this.form.submit()">
                    <?php 
                    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                    for($m=1;$m<=12;$m++) {
                        $selected = ($sel_mes == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>{$meses[$m-1]} ($m)</option>";
                    }
                    ?>
                </select>
                <select name="sel_ano" class="filter-selector" onchange="this.form.submit()">
                    <?php for($y=2024; $y<=2050; $y++) { 
                        $sel = ($sel_ano == $y) ? 'selected' : '';
                        echo "<option value='$y' $sel>$y</option>";
                    } ?>
                </select>
            </form>
        </div>

        <!-- CARDS DE ESTATÍSTICAS -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom stat-card border-primary">
                    <div class="stat-label text-primary"><i class="fas fa-calendar-alt me-1"></i> Projeção Mensal</div>
                    <div class="stat-value text-dark">R$ <?= number_format($projecaoMensal, 2, ',', '.') ?></div>
                    <div class="stat-sub"><?= count($empresas) ?> empresas ativas</div>
                </div>
            </div>
            
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom stat-card border-success">
                    <div class="stat-label text-success"><i class="fas fa-check-circle me-1"></i> Recebido no Mês</div>
                    <div class="stat-value text-success">R$ <?= number_format($recebidoMes, 2, ',', '.') ?></div>
                    <div class="stat-sub">
                        <span class="badge bg-success"><?= $percentualRecebido ?>%</span> 
                        <?= count($empresasRecebidas) ?> pagamentos confirmados
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom stat-card border-danger">
                    <div class="stat-label text-danger"><i class="fas fa-exclamation-circle me-1"></i> Falta Receber</div>
                    <div class="stat-value text-danger">R$ <?= number_format($faltaReceber, 2, ',', '.') ?></div>
                    <div class="stat-sub"><?= count($empresasPendentes) ?> empresas pendentes</div>
                </div>
            </div>
            
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-custom stat-card border-dark">
                    <div class="stat-label text-dark"><i class="fas fa-chart-line me-1"></i> Projeção Anual</div>
                    <div class="stat-value text-dark">R$ <?= number_format($projecaoAnual, 2, ',', '.') ?></div>
                    <div class="stat-sub">Estimativa total <?= $sel_ano ?></div>
                </div>
            </div>
        </div>

        <!-- MÉTRICAS POR PLANO -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card card-custom">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-calendar me-2 text-info"></i> Planos Mensais</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 fw-bold text-info mb-0"><?= $total_mensal ?></div>
                                <small class="text-muted">empresas ativas</small>
                            </div>
                            <div class="text-end">
                                <div class="h5 fw-bold text-dark mb-0">R$ <?= number_format($receita_mensal, 0, ',', '.') ?></div>
                                <small class="text-muted">receita mensal</small>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 8px;">
                            <div class="progress-bar bg-info" style="width: <?= $total_mensal > 0 ? ($total_mensal / count($empresas)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card card-custom">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-star me-2 text-warning"></i> Planos Anuais</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="h4 fw-bold text-warning mb-0"><?= $total_anual ?></div>
                                <small class="text-muted">empresas ativas</small>
                            </div>
                            <div class="text-end">
                                <div class="h5 fw-bold text-dark mb-0">R$ <?= number_format($receita_anual, 0, ',', '.') ?></div>
                                <small class="text-muted">valor único</small>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 8px;">
                            <div class="progress-bar bg-warning" style="width: <?= count($empresas) > 0 ? ($total_anual / count($empresas)) * 100 : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAPA DE PAGAMENTOS -->
        <div class="card card-custom overflow-hidden shadow">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-table me-2"></i> Mapa de Recebimentos <?= $sel_ano ?></h5>
                <small class="text-muted">Clique nas células para marcar/desmarcar pagamentos</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle table-hover mb-0">
                    <thead class="bg-light text-center small fw-bold">
                        <tr>
                            <th class="text-start ps-3 py-3" style="min-width: 150px;">EMPRESA</th>
                            <?php for($m=1;$m<=12;$m++) echo "<th style='min-width: 50px;'>".$meses[$m-1]."</th>"; ?>
                            <th class="bg-dark text-white" style="min-width: 80px;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($empresas as $e): 
                            $hist = $pagamentos_db[$e['id']] ?? []; 
                            $total_e = 0; 
                            $tem_anual = ($e['plano'] == 'anual' && count($hist) > 0);
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        <div class="empresa-nome-td fw-bold"><?= htmlspecialchars($e['nome_empresa']) ?></div>
                                        <span class="badge badge-plano <?= $e['plano']=='mensal'?'bg-info':'bg-warning text-dark' ?>">
                                            <?= strtoupper($e['plano']) ?> - R$<?= number_format($e['valor'], 0) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <?php for($m=1;$m<=12;$m++): 
                                $d_reg = $hist[$m] ?? null; 
                                $isP = !empty($d_reg);
                                $is_old = ($sel_ano < $ano_hoje) || ($sel_ano == $ano_hoje && $m < $mes_hoje);
                                $is_future = ($sel_ano > $ano_hoje) || ($sel_ano == $ano_hoje && $m > $mes_hoje);
                                
                                $cl = $isP ? 'month-paid' : 'month-pending';
                                if (($is_old && !$isP) || ($e['plano'] == 'anual' && $tem_anual && !$isP)) $cl = 'month-disabled';
                                if ($is_future && !$isP) $cl = 'month-disabled';
                                
                                if ($isP) $total_e += $e['valor'];
                            ?>
                                <td class="text-center p-1">
                                    <a href="javascript:void(0)" 
                                       class="month-cell <?= $cl ?>" 
                                       onclick="confirmarPagto(<?= $e['id'] ?>, <?= $m ?>, <?= $isP?1:0 ?>, '<?= htmlspecialchars($e['nome_empresa']) ?>')">
                                        <b><?= $m ?></b>
                                        <span><?= $isP ? date('d/m', strtotime($d_reg)) : ($is_future ? 'FUT' : 'PEND') ?></span>
                                    </a>
                                </td>
                            <?php endfor; ?>
                            <td class="fw-bold text-center bg-light">
                                <div class="badge bg-primary px-3 py-2">R$ <?= number_format($total_e, 0) ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-dark text-white fw-bold">
                        <tr>
                            <td class="ps-3">TOTAIS</td>
                            <?php 
                            for($m=1;$m<=12;$m++) {
                                $total_mes = 0;
                                foreach($empresas as $e) {
                                    if(isset($pagamentos_db[$e['id']][$m])) {
                                        $total_mes += $e['valor'];
                                    }
                                }
                                echo "<td class='text-center'>R$".number_format($total_mes, 0)."</td>";
                            }
                            ?>
                            <td class="text-center">R$ <?= number_format(array_sum(array_map(function($e) use ($pagamentos_db) { 
                                return count($pagamentos_db[$e['id']] ?? []) * $e['valor']; 
                            }, $empresas)), 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- EMPRESAS PENDENTES -->
        <?php if(count($empresasPendentes) > 0): ?>
        <div class="card card-custom mt-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Pagamentos Pendentes - <?= $meses[$sel_mes-1] ?>/<?= $sel_ano ?></h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <?php foreach($empresasPendentes as $pend): ?>
                    <div class="col-md-4">
                        <div class="alert alert-danger mb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($pend['nome_empresa']) ?></strong><br>
                                <small>R$ <?= number_format($pend['valor'], 2, ',', '.') ?></small>
                            </div>
                            <button class="btn btn-sm btn-light" onclick="confirmarPagto(<?= $pend['id'] ?>, <?= $sel_mes ?>, 0, '<?= htmlspecialchars($pend['nome_empresa']) ?>')">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    <?php elseif($tab == 'empresas'): ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Gerenciamento de Empresas</h5>
                <small class="text-muted"><?= count($empresas) ?> empresas cadastradas</small>
            </div>
            <button class="btn btn-primary fw-bold px-4 shadow" onclick="novoCliente()">
                <i class="fas fa-plus me-2"></i> Nova Empresa
            </button>
        </div>

        <div class="card card-custom overflow-hidden shadow">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="ps-3 py-3">EMPRESA</th>
                        <th>DADOS ACESSO</th>
                        <th>PLANO</th>
                        <th>VALOR</th>
                        <th>VENCIMENTO</th>
                        <th class="text-center">STATUS</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($empresas as $e): 
                        $isV = strtotime($e['data_expiracao']) < time(); 
                        $e_json = $e; unset($e_json['logo']);
                    ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold"><?= htmlspecialchars($e['nome_empresa']) ?></div>
                                <small class="text-muted"><i class="fas fa-id-card me-1"></i><?= $e['cnpj_cpf'] ?></small>
                            </td>
                            <td>
                                <small class="d-block"><i class="fas fa-envelope me-1"></i><?= $e['email'] ?></small>
                                <small class="d-block"><i class="fas fa-key me-1"></i><strong><?= $e['senha_texto'] ?></strong></small>
                            </td>
                            <td>
                                <span class="badge badge-plano <?= $e['plano']=='mensal'?'bg-info':'bg-warning text-dark' ?>">
                                    <i class="fas fa-<?= $e['plano']=='mensal'?'calendar':'star' ?> me-1"></i><?= strtoupper($e['plano']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-success">R$ <?= number_format($e['valor'], 2, ',', '.') ?></div>
                                <small class="text-muted"><?= $e['plano']=='mensal'?'por mês':'pagamento único' ?></small>
                            </td>
                            <td>
                                <div class="<?= $isV?'text-danger fw-bold':'text-dark' ?>">
                                    <?= date('d/m/Y', strtotime($e['data_expiracao'])) ?>
                                </div>
                                <?php if($isV): ?>
                                <span class="badge bg-danger">VENCIDA</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $e['status']?'success':'secondary' ?> px-3 py-2">
                                    <?= $e['status']?'ATIVA':'BLOQUEADA' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-light border btn-action" 
                                            onclick='prepararEdicao(<?= json_encode($e_json) ?>)'
                                            title="Editar">
                                        <i class="fas fa-edit text-primary"></i>
                                    </button>
                                    
                                    <a href="cpanel.php?toggle_status=<?= $e['id'] ?>" 
                                       class="btn btn-sm btn-<?= $e['status']?'warning':'success' ?> btn-action"
                                       title="<?= $e['status']?'Bloquear':'Ativar' ?>">
                                        <i class="fas fa-<?= $e['status']?'lock':'unlock' ?>"></i>
                                    </a>
                                    
                                    <button class="btn btn-sm btn-danger btn-action" 
                                            onclick="confirmarExclusao(<?= $e['id'] ?>, '<?= htmlspecialchars($e['nome_empresa']) ?>')"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
    <?php elseif($tab == 'performance'): ?>
        
        <div class="mb-4">
            <h5 class="fw-bold mb-1">Relatório de Performance & Reservas</h5>
            <small class="text-muted">Análise completa de operações por empresa</small>
        </div>

        <!-- TOTALIZADORES GERAIS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-custom stat-card border-success">
                    <div class="stat-label text-success">Total Reservas</div>
                    <div class="stat-value text-success"><?= number_format($totais['reservas']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-custom stat-card border-primary">
                    <div class="stat-label text-primary">Total PAX</div>
                    <div class="stat-value text-primary"><?= number_format($totais['pax']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-custom stat-card border-warning">
                    <div class="stat-label text-warning">Em Fila</div>
                    <div class="stat-value text-warning"><?= number_format($totais['fila']) ?></div>
                    <div class="stat-sub"><?= number_format($totais['pax_fila']) ?> pessoas</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-custom stat-card border-danger">
                    <div class="stat-label text-danger">Canceladas</div>
                    <div class="stat-value text-danger"><?= number_format($totais['canceladas']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-custom stat-card border-secondary">
                    <div class="stat-label text-secondary">Desist. Fila</div>
                    <div class="stat-value text-secondary"><?= number_format($totais['desistencia_fila']) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-custom stat-card border-info">
                    <div class="stat-label text-info">Média PAX</div>
                    <div class="stat-value text-info"><?= $totais['reservas'] > 0 ? number_format($totais['pax']/$totais['reservas'], 1) : 0 ?></div>
                </div>
            </div>
        </div>

        <!-- TABELA DETALHADA -->
        <div class="card card-custom overflow-hidden shadow">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i> Desempenho por Empresa</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-center">
                            <th class="text-start ps-3">Empresa</th>
                            <th><i class="fas fa-calendar-check text-success me-1"></i> Reservas</th>
                            <th><i class="fas fa-users text-primary me-1"></i> PAX Conf.</th>
                            <th><i class="fas fa-clock text-warning me-1"></i> Fila</th>
                            <th><i class="fas fa-user-friends text-info me-1"></i> PAX Fila</th>
                            <th><i class="fas fa-times-circle text-danger me-1"></i> Canceladas</th>
                            <th><i class="fas fa-user-times text-secondary me-1"></i> Desist. Fila</th>
                            <th><i class="fas fa-chart-line text-dark me-1"></i> Média PAX</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($relatorio_performance as $rp): 
                            $media = $rp['total_reservas'] > 0 ? round($rp['total_pax']/$rp['total_reservas'], 1) : 0;
                            $total_operacoes = $rp['total_reservas'] + $rp['quantidade_fila'] + $rp['reservas_canceladas'] + $rp['desistencia_fila'];
                        ?>
                        <tr class="text-center">
                            <td class="text-start ps-3">
                                <div class="fw-bold"><?= htmlspecialchars($rp['nome_empresa']) ?></div>
                                <small class="badge bg-<?= $rp['plano']=='mensal'?'info':'warning' ?> text-dark">
                                    <?= strtoupper($rp['plano']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-success px-3 py-2 fw-bold"><?= $rp['total_reservas'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary px-3 py-2 fw-bold"><?= $rp['total_pax']??0 ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark px-3 py-2 fw-bold"><?= $rp['quantidade_fila'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-info px-3 py-2 fw-bold"><?= $rp['pax_fila']??0 ?></span>
                            </td>
                            <td>
                                <span class="badge bg-danger px-3 py-2 fw-bold"><?= $rp['reservas_canceladas'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary px-3 py-2 fw-bold"><?= $rp['desistencia_fila'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark px-3 py-2 fw-bold"><?= $media ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-dark text-white fw-bold">
                        <tr class="text-center">
                            <td class="text-start ps-3">TOTAIS GERAIS</td>
                            <td><?= number_format($totais['reservas']) ?></td>
                            <td><?= number_format($totais['pax']) ?></td>
                            <td><?= number_format($totais['fila']) ?></td>
                            <td><?= number_format($totais['pax_fila']) ?></td>
                            <td><?= number_format($totais['canceladas']) ?></td>
                            <td><?= number_format($totais['desistencia_fila']) ?></td>
                            <td><?= $totais['reservas'] > 0 ? number_format($totais['pax']/$totais['reservas'], 1) : 0 ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    <?php elseif($tab == 'inativas'): ?>
        
        <div class="mb-4">
            <h5 class="fw-bold mb-1">Empresas Excluídas</h5>
            <small class="text-muted"><?= count($empresas_inativas) ?> empresas arquivadas (não contam nos cálculos)</small>
        </div>

        <?php if(count($empresas_inativas) == 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> Nenhuma empresa excluída no momento.
            </div>
        <?php else: ?>
        <div class="card card-custom overflow-hidden shadow">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th class="ps-3 py-3">EMPRESA</th>
                        <th>PLANO</th>
                        <th>DATA EXCLUSÃO</th>
                        <th>DADOS ACESSO</th>
                        <th class="text-center">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($empresas_inativas as $ei): ?>
                        <tr class="text-muted">
                            <td class="ps-3">
                                <div class="fw-bold"><?= htmlspecialchars($ei['nome_empresa']) ?></div>
                                <small><i class="fas fa-id-card me-1"></i><?= $ei['cnpj_cpf'] ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= strtoupper($ei['plano']) ?> - R$<?= number_format($ei['valor'], 0) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <?= $ei['data_exclusao'] ? date('d/m/Y H:i', strtotime($ei['data_exclusao'])) : '-' ?>
                                </div>
                            </td>
                            <td>
                                <small class="d-block"><?= $ei['email'] ?></small>
                                <small><strong><?= $ei['senha_texto'] ?></strong></small>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success btn-action" 
                                        onclick="confirmarRestauracao(<?= $ei['id'] ?>, '<?= htmlspecialchars($ei['nome_empresa']) ?>')"
                                        title="Restaurar empresa">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
    <?php endif; ?>
    
    </div>
</div>

<!-- MODAL EMPRESA -->
<div class="modal fade" id="modalEmpresa" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius:20px; border: none;">
            <form method="POST" enctype="multipart/form-data" id="formEmpresa">
                <input type="hidden" name="acao" id="form_acao" value="cadastrar_empresa">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header bg-primary text-white border-0 py-4">
                    <h5 class="fw-bold mb-0" id="modal_titulo"><i class="fas fa-building me-2"></i> Nova Empresa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-building me-1"></i> Nome Fantasia</label>
                            <input type="text" name="nome_empresa" id="edit_nome" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-id-card me-1"></i> CNPJ / CPF</label>
                            <input type="text" name="cnpj_cpf" id="edit_doc" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-envelope me-1"></i> Email de Acesso</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-key me-1"></i> Senha de Acesso</label>
                            <input type="text" name="senha" id="edit_senha" class="form-control" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-calendar-alt me-1"></i> Data Inclusão</label>
                            <input type="date" name="data_inclusao" id="edit_inc" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-calendar-check me-1"></i> Vencimento</label>
                            <input type="date" name="data_expiracao" id="edit_exp" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-tag me-1"></i> Plano</label>
                            <select name="plano" id="edit_plano" class="form-select">
                                <option value="mensal">Mensal</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-dollar-sign me-1"></i> Valor R$</label>
                            <input type="number" step="0.01" name="valor" id="edit_valor" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-image me-1"></i> Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="col-12">
                            <label class="fw-bold small text-uppercase mb-2"><i class="fas fa-comment-alt me-1"></i> Observações</label>
                            <textarea name="observacoes" id="edit_obs" class="form-control" rows="3" placeholder="Anotações internas..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                        <i class="fas fa-save me-2"></i> Salvar Empresa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() { 
        document.getElementById('mainSidebar').classList.toggle('hidden'); 
        document.getElementById('mainContent').classList.toggle('full'); 
    }
    
    function confirmarPagto(id, mes, jaPago, nomeEmp) { 
        const acao = jaPago ? 'REMOVER o pagamento' : 'CONFIRMAR o recebimento';
        const msg = `${acao} de ${nomeEmp}?\nMês: ${mes}/<?= $sel_ano ?>`;
        
        if (confirm(msg)) {
            window.location.href = `cpanel.php?toggle_pago=1&emp_id=${id}&mes=${mes}&ano=<?= $sel_ano ?>&sel_mes=<?= $sel_mes ?>&sel_ano=<?= $sel_ano ?>&tab=dashboard`;
        }
    }
    
    function confirmarExclusao(id, nome) {
        if(confirm(`ATENÇÃO!\n\nDeseja EXCLUIR a empresa "${nome}"?\n\nEla será movida para a aba "Empresas Excluídas" e NÃO CONTARÁ mais nos cálculos financeiros.\n\nEsta ação pode ser revertida.`)) {
            window.location.href = `cpanel.php?excluir_empresa=${id}`;
        }
    }
    
    function confirmarRestauracao(id, nome) {
        if(confirm(`Restaurar a empresa "${nome}"?\n\nEla voltará a contar nos cálculos financeiros.`)) {
            window.location.href = `cpanel.php?restaurar_empresa=${id}`;
        }
    }
    
    const modalEmp = new bootstrap.Modal(document.getElementById('modalEmpresa'));
    
    function novoCliente() {
        document.getElementById('form_acao').value = 'cadastrar_empresa'; 
        document.getElementById('modal_titulo').innerHTML = '<i class="fas fa-plus-circle me-2"></i> Nova Empresa';
        document.getElementById('formEmpresa').reset();
        document.getElementById('edit_inc').value = '<?= date('Y-m-d') ?>'; 
        modalEmp.show();
    }
    
    function prepararEdicao(d) {
        document.getElementById('form_acao').value = 'editar_empresa'; 
        document.getElementById('modal_titulo').innerHTML = '<i class="fas fa-edit me-2"></i> Editar: ' + d.nome_empresa;
        document.getElementById('edit_id').value = d.id;
        document.getElementById('edit_nome').value = d.nome_empresa;
        document.getElementById('edit_doc').value = d.cnpj_cpf;
        document.getElementById('edit_email').value = d.email;
        document.getElementById('edit_senha').value = d.senha_texto;
        document.getElementById('edit_exp').value = d.data_expiracao ? d.data_expiracao.split(' ')[0] : '';
        document.getElementById('edit_inc').value = d.data_inclusao;
        document.getElementById('edit_plano').value = d.plano;
        document.getElementById('edit_valor').value = d.valor;
        document.getElementById('edit_obs').value = d.observacoes;
        modalEmp.show();
    }
</script>
</body>
</html>