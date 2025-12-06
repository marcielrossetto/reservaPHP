<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Alterado para 0 para não quebrar o JSON em caso de warning

session_start();
require 'config.php';

// Verifica Login
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

// ========================= FUNÇÕES AUXILIARES =========================

function validarTelefone($telefone) {
    $telefone = preg_replace('/\D/', '', $telefone);
    return preg_match('/^[1-9]{2}9\d{8}$/', $telefone);
}

function validarData($data) {
    $data = trim($data);
    if ($data === '') return false;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) return true;
    if (!preg_match('/^(0?[1-9]|[12][0-9]|3[01])[\/-](0?[1-9]|1[0-2])[\/-](\d{2}|\d{4})$/', $data)) return false;
    return true;
}

function normalizarDataParaBanco($data) {
    $data = trim($data);
    if ($data === '') return false;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) return $data;
    $data = str_replace('-', '/', $data);
    $partes = explode('/', $data);
    if (count($partes) !== 3) return false;
    list($dia, $mes, $ano) = $partes;
    $dia = str_pad($dia, 2, '0', STR_PAD_LEFT);
    $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);
    if (strlen($ano) == 2) $ano = '20' . $ano;
    return "$ano-$mes-$dia";
}

function validarHorario($horario) {
    $horario = trim($horario);
    if ($horario === '') return false;
    return preg_match('/^([01]\d|2[0-3])[:.;]([0-5]\d)$/', $horario);
}

function validarHorarioFuncionamento($horarioBanco) {
    $inicio = "11:00:00";
    $fim = "23:59:59"; 
    if ($horarioBanco < $inicio) return "Horário antes das 11:00 — fora do horário de funcionamento.";
    if ($horarioBanco > $fim) return "Horário após 00:00 — fora do horário de funcionamento.";
    return true;
}

function normalizarHorarioParaBanco($horario) {
    $horario = trim($horario);
    if ($horario === '') return false;
    $horario = str_replace([';', '.'], ':', $horario);
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $horario)) return false;
    return $horario . ':00';
}

function tempoAtras($data) {
    $dt = new DateTime($data);
    $now = new DateTime();
    $dt->setTime(0,0); 
    $now->setTime(0,0);
    if ($dt > $now) return "Reserva Futura";
    $diff = $now->diff($dt);
    if ($diff->days == 0) return "Hoje";
    if ($diff->days == 1) return "Ontem";
    if ($diff->y > 0) return $diff->y == 1 ? "1 ano atrás" : $diff->y . " anos atrás";
    if ($diff->m > 0) return $diff->m == 1 ? "1 mês atrás" : $diff->m . " meses atrás";
    return $diff->d . " dias atrás";
}

// ========================= 1. AJAX: ANÁLISE WHATSAPP =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'analisar_whats') {
    header('Content-Type: application/json; charset=utf-8');
    $textoCompleto = trim($_POST['whats_text'] ?? '');
    if ($textoCompleto === '') { echo json_encode(['success' => false, 'message' => 'Texto vazio.']); exit; }

    $blocos = preg_split('/(?=Nome:)/i', $textoCompleto, -1, PREG_SPLIT_NO_EMPTY);
    $listaProcessada = [];

    foreach ($blocos as $bloco) {
        $linhas = explode("\n", $bloco);
        $dados = [];
        foreach ($linhas as $linha) {
            $partes = explode(":", $linha, 2);
            if (count($partes) == 2) $dados[strtolower(trim($partes[0]))] = trim($partes[1]);
        }
        $nome = $dados['nome'] ?? '';
        if (empty($nome)) continue; 

        $telefoneRaw = isset($dados['telefone']) ? preg_replace('/\D/', '', $dados['telefone']) : '';
        $dataRaw = $dados['data'] ?? '';
        $horarioRaw = $dados['horário'] ?? ($dados['horario'] ?? '');
        $num_pessoas = $dados['nº de pessoas'] ?? ($dados['nº pessoas'] ?? ($dados['pessoas'] ?? ''));
        $telefone2 = isset($dados['telefone alternativo']) ? preg_replace('/\D/', '', $dados['telefone alternativo']) : null;
        $tipo_evento = $dados['tipo de evento'] ?? '';
        $forma_pagamento = $dados['forma de pagamento'] ?? ($dados['pagamento'] ?? '');
        $valor_rodizio = $dados['valor do rodízio'] ?? '';
        $num_mesa = $dados['mesa'] ?? '';
        $observacoes = $dados['observações'] ?? ($dados['observacao'] ?? ($dados['obs'] ?? ''));

        $erros = [];
        if (empty($telefoneRaw) || !validarTelefone($telefoneRaw)) $erros[] = "Telefone inválido";
        
        if (empty($dataRaw) || !validarData($dataRaw)) { 
            $erros[] = "Data inválida"; 
        } else { 
            $dataBanco = normalizarDataParaBanco($dataRaw); 
            if ($dataBanco < date('Y-m-d')) $erros[] = "Data antiga (Anterior a hoje)"; 
        }

        if (empty($horarioRaw) || !validarHorario($horarioRaw)) { $erros[] = "Horário inválido"; } 
        else { $horarioBanco = normalizarHorarioParaBanco($horarioRaw); }

        $duplicado = false;
        if (empty($erros)) {
            $sqlCheck = $pdo->prepare("SELECT id FROM clientes WHERE nome=:n AND data=:d AND horario=:h AND num_pessoas=:np");
            $sqlCheck->execute([':n'=>$nome, ':d'=>$dataBanco, ':h'=>$horarioBanco, ':np'=>$num_pessoas]);
            if ($sqlCheck->rowCount() > 0) $duplicado = true;
        }

        $listaProcessada[] = [
            'valido' => empty($erros), 'erros' => $erros, 'duplicado' => $duplicado,
            'dados' => [
                'nome' => $nome, 'data' => $dataBanco, 'horario' => $horarioBanco, 'num_pessoas' => $num_pessoas,
                'telefone' => $telefoneRaw, 'telefone2' => $telefone2, 'tipo_evento' => $tipo_evento,
                'forma_pagamento' => $forma_pagamento, 'valor_rodizio' => $valor_rodizio, 'num_mesa' => $num_mesa, 'observacoes' => $observacoes
            ]
        ];
    }
    echo json_encode(['success' => true, 'lista' => $listaProcessada]); exit;
}

// ========================= 2. AJAX: SALVAR LISTA FINAL (Com Usuario_ID) =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_lista_final') {
    header('Content-Type: application/json; charset=utf-8');
    
    $usuario_id = $_SESSION['mmnlogin'];
    $json = $_POST['lista_json'] ?? '[]';
    $lista = json_decode($json, true);
    $sucessos = 0;
    $links = [];

    if (is_array($lista)) {
        foreach ($lista as $item) {
            try {
                $sql = $pdo->prepare("INSERT INTO clientes (usuario_id, nome, data, num_pessoas, horario, telefone, telefone2, tipo_evento, forma_pagamento, valor_rodizio, num_mesa, observacoes) VALUES (:uid, :nm, :dt, :np, :hr, :tel, :tel2, :evt, :pgt, :vlr, :ms, :obs)");
                $sql->execute([
                    ':uid' => $usuario_id, ':nm' => $item['nome'], ':dt' => $item['data'], ':np' => $item['num_pessoas'],
                    ':hr' => $item['horario'], ':tel' => $item['telefone'], ':tel2'=> $item['telefone2'], ':evt' => $item['tipo_evento'], 
                    ':pgt' => $item['forma_pagamento'], ':vlr' => $item['valor_rodizio'], ':ms' => $item['num_mesa'], ':obs' => $item['observacoes']
                ]);
                $dataBr = date('d/m/Y', strtotime($item['data']));
                $horaCurta = substr($item['horario'], 0, 5);
                $msgZap = "Olá, {$item['nome']}. Sua reserva para {$dataBr} às {$horaCurta} para {$item['num_pessoas']} pessoas foi confirmada.";
                $link = "https://wa.me/55{$item['telefone']}?text=" . urlencode($msgZap);
                $links[] = ['nome' => $item['nome'], 'link' => $link];
                $sucessos++;
            } catch (Exception $e) { }
        }
    }
    echo json_encode(['success' => true, 'salvos' => $sucessos, 'links' => $links]); exit;
}

// ========================= 3. AJAX: BUSCAR PERFIL =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'buscar_perfil') {
    $telefone = preg_replace('/\D/', '', $_GET['telefone']);
    $sql = $pdo->prepare("SELECT * FROM clientes WHERE telefone = :telefone ORDER BY data DESC, horario DESC");
    $sql->execute([':telefone' => $telefone]);
    $historico = $sql->fetchAll(PDO::FETCH_ASSOC);

    if (count($historico) > 0) {
        $ultimo = $historico[0]; 
        $total = count($historico);
        $canceladas = 0;
        $obsClienteEncontrada = null;
        $ultimasDatas = [];
        $contadorRecentes = 0;

        foreach ($historico as $h) {
            if (isset($h['status']) && $h['status'] == 0) $canceladas++;
            if (empty($obsClienteEncontrada) && !empty($h['obsCliente'])) $obsClienteEncontrada = $h['obsCliente'];
            if ($contadorRecentes < 4) {
                $ultimasDatas[] = date('d/m/Y', strtotime($h['data']));
                $contadorRecentes++;
            }
        }

        $perfil = [
            'id' => $ultimo['id'],
            'nome' => $ultimo['nome'],
            'telefone' => $ultimo['telefone'],
            'ultima_visita_data' => date('d/m/Y', strtotime($ultimo['data'])),
            'tempo_atras' => tempoAtras($ultimo['data']), 
            'historico_recente' => implode(", ", $ultimasDatas),
            'total_reservas' => $total,
            'obs_cliente' => $obsClienteEncontrada,
            'canceladas' => $canceladas
        ];
        echo json_encode(['encontrado' => true, 'perfil' => $perfil]);
    } else {
        echo json_encode(['encontrado' => false]);
    }
    exit;
}

// ========================= 4. AJAX: ATUALIZAR NOME CLIENTE =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_nome_cliente') {
    header('Content-Type: application/json; charset=utf-8');
    $novoNome = trim($_POST['nome']);
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);

    if (empty($novoNome) || empty($telefone)) { echo json_encode(['success' => false, 'message' => 'Nome inválido.']); exit; }

    try {
        $sql = $pdo->prepare("UPDATE clientes SET nome = :nome WHERE telefone = :telefone");
        $sql->execute([':nome' => $novoNome, ':telefone' => $telefone]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) { echo json_encode(['success' => false, 'message' => 'Erro ao atualizar.']); }
    exit;
}

// ========================= 5. AJAX: CHECAR DUPLICIDADE E DATA =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] == 'checar_duplicidade') {
    $telefone = preg_replace('/\D/', '', $_GET['telefone']);
    $data = normalizarDataParaBanco($_GET['data']);
    $nome = trim($_GET['nome']);
    
    if ($data < date('Y-m-d')) { echo json_encode(['erro_data' => true, 'msg' => 'A data selecionada é anterior à data de hoje.']); exit; }

    $sql = $pdo->prepare("SELECT id FROM clientes WHERE telefone = :tel AND data = :dt AND nome = :nm LIMIT 1");
    $sql->execute([':tel' => $telefone, ':dt' => $data, ':nm' => $nome]);
    echo json_encode(['existe' => ($sql->rowCount() > 0)]); exit;
}

// ========================= 6. AJAX: SALVAR MANUAL (NOVA ROTINA) =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $usuario_id = $_SESSION['mmnlogin'];
    $nome = htmlspecialchars(trim($_POST['nome']));
    $data = htmlspecialchars(trim($_POST['data']));
    $num_pessoas = htmlspecialchars(trim($_POST['num_pessoas']));
    $horario = htmlspecialchars(trim($_POST['horario']));
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $telefone2 = !empty($_POST['telefone2']) ? preg_replace('/\D/', '', $_POST['telefone2']) : null;
    $tipo_evento = htmlspecialchars(trim($_POST['tipo_evento']));
    $forma_pagamento = htmlspecialchars(trim($_POST['forma_pagamento']));
    $valor_rodizio = htmlspecialchars(trim($_POST['valor_rodizio']));
    $num_mesa = htmlspecialchars(trim($_POST['num_mesa']));
    $observacoes = htmlspecialchars(trim($_POST['observacoes']));

    $dataBanco = normalizarDataParaBanco($data);
    $horarioBanco = normalizarHorarioParaBanco($horario);
    $validaHora = validarHorarioFuncionamento($horarioBanco);

    if ($validaHora !== true) {
        echo json_encode(['success' => false, 'message' => $validaHora]);
        exit;
    }
    if ($dataBanco < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'Erro: A data da reserva não pode ser anterior a hoje.']);
        exit;
    }
    
    try {
        $sql = $pdo->prepare("INSERT INTO clientes (usuario_id, nome, data, num_pessoas, horario, telefone, telefone2, tipo_evento, forma_pagamento, valor_rodizio, num_mesa, observacoes) VALUES (:uid, :nm, :dt, :np, :hr, :tel, :tel2, :evt, :pgt, :vlr, :ms, :obs)");
        $sql->execute([
            ':uid' => $usuario_id, ':nm' => $nome, ':dt' => $dataBanco, ':np' => $num_pessoas, 
            ':hr' => $horarioBanco, ':tel' => $telefone, ':tel2'=> $telefone2, ':evt' => $tipo_evento, 
            ':pgt' => $forma_pagamento, ':vlr' => $valor_rodizio, ':ms' => $num_mesa, ':obs' => $observacoes
        ]);

        $msg = "Olá, $nome. Sua reserva para " . date('d/m/Y', strtotime($dataBanco)) . " às " . substr($horarioBanco,0,5) . " para $num_pessoas pessoas foi agendada.";
        $link = "https://wa.me/55$telefone?text=" . urlencode($msg);

        echo json_encode(['success' => true, 'link_wpp' => $link]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados.']);
    }
    exit;
}

require 'cabecalho.php';
$sql = $pdo->query("SELECT * FROM preco_rodizio ORDER BY id DESC LIMIT 1");
$ultimo_preco = $sql->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Reserva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        body { font-family:-apple-system,BlinkMacSystemFont,"SF Pro Display",sans-serif; background: #f2f3f7; margin:0; padding:20px; }
        .page-wrapper { max-width: 960px; margin:110px auto 40px; }
        .card-ios { background:#fff; border-radius:22px; padding:22px 20px 24px; box-shadow:0 12px 30px rgba(0,0,0,0.14); }
        .form-control, .form-select { border-radius:12px; border:1px solid #d1d1d6; padding:9px 12px; background:#f9f9fb; }
        .btn-ios-primary { background:#007AFF; color:#fff; border:none; border-radius:999px; padding:11px 18px; width:100%; font-weight:600; margin-top:16px; cursor:pointer; }
        .btn-ios-outline { background:#fff; border:1px solid #d1d1d6; color:#111; border-radius:999px; padding:9px 12px; cursor:pointer; }
        .btn-ios-secondary { background:#e5e5ea; border:none; color:#111; border-radius:999px; padding:9px 12px; cursor:pointer; }
        
        .textarea-wrapper { position: relative; }
        .btn-clear-text { position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.1); border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; color: #555; display: flex; align-items: center; justify-content: center; }
        .btn-clear-text:hover { background: rgba(0,0,0,0.2); color: red; }

        .profile-card { border: 1px solid #28a745; background: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; display: none; }
        .profile-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .profile-info-area { display: flex; gap: 15px; flex: 1; }
        .avatar-circle { width: 60px; height: 60px; background: #ccc; border-radius: 50%; display: flex; justify-content: center; align-items: center; }
        .avatar-circle svg { width: 40px; height: 40px; fill: #666; }
        .client-details { flex: 1; }
        .client-details h3 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #333; display:flex; align-items:center; height: 30px;}
        .client-details p { margin: 2px 0 0; font-size: 0.9rem; color: #666; }
        
        .profile-actions { display: flex; flex-direction: column; gap: 8px; }
        .btn-profile-action { background: #fff; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; font-size: 0.85rem; cursor: pointer; color: #333; min-width: 110px; text-decoration: none; text-align: center; }
        .btn-profile-action:hover { background: #f9f9f9; }

        .edit-name-container { display: flex; align-items: center; gap: 5px; display: none; }
        .input-edit-nome { padding: 4px 8px; border: 1px solid #007AFF; border-radius: 4px; font-size: 1rem; width: 100%; }
        .btn-icon-save { background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; padding: 4px 8px; display: flex; align-items: center;}
        .btn-icon-cancel { background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; padding: 4px 8px; display: flex; align-items: center;}

        .stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); border-top: 1px solid #eee; padding-top: 10px; text-align: left; margin-top: 10px; }
        .stat-box small { display: block; font-size: 0.8rem; color: #666; }
        .stat-box strong { font-size: 1rem; font-weight: 800; color: #333; }
        
        .alert-obs { background: #fff3cd; color: #856404; padding: 8px; border-radius: 6px; font-size: 0.85rem; margin-top: 10px; border: 1px solid #ffeeba; }
        .alert-cancel { color: #dc3545; font-weight: bold; }
        
        @media (max-width: 600px) { .profile-header { flex-direction: column; gap: 10px; } .profile-actions { flex-direction: row; width: 100%; } .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; } }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="card-ios">
        <h2 style="text-align:center;">Nova Reserva</h2>

        <div class="mb-3">
            <label><strong>Colar dados do WhatsApp:</strong></label>
            <div class="textarea-wrapper">
                <textarea id="whats_dados" class="form-control" rows="5" placeholder="Cole aqui (um ou vários)..."></textarea>
                <button type="button" class="btn-clear-text" onclick="document.getElementById('whats_dados').value='';" title="Limpar">
                    <i class="material-icons" style="font-size:18px">close</i>
                </button>
            </div>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="button" onclick="importarWhatsParaFormulario()" class="btn-ios-outline">Importar p/ Form</button>
                <button type="button" onclick="analisarSalvarDireto()" class="btn-ios-secondary">Salvar Direto</button>
            </div>
        </div>
        <hr>

        <!-- Modificado: onSubmit chama verificarEEnviar, que agora usa AJAX -->
        <form id="formManual" method="POST" onsubmit="event.preventDefault(); verificarEEnviar();">
            <div class="mb-2">
                <label>Telefone:</label>
                <input type="text" id="telefone" name="telefone" class="form-control" maxlength="15" required onkeyup="maskPhone(this)" onblur="buscarTelefone()">
            </div>

            <!-- CARD PERFIL -->
            <div id="card-perfil-cliente" class="profile-card">
                <div class="profile-header">
                    <div class="profile-info-area">
                        <div class="avatar-circle"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
                        <div class="client-details">
                            <h3 id="card-nome">--</h3>
                            <div id="edit-name-container" class="edit-name-container">
                                <input type="text" id="input-edit-nome" class="input-edit-nome">
                                <button type="button" class="btn-icon-save" onclick="salvarNomeCliente()"><i class="material-icons" style="font-size:16px;">check</i></button>
                                <button type="button" class="btn-icon-cancel" onclick="cancelarEdicaoNome()"><i class="material-icons" style="font-size:16px;">close</i></button>
                            </div>

                            <p id="card-telefone">--</p>
                            <p style="font-size:0.85rem; margin-top:5px;">
                                Última visita: <span id="card-ultima" style="font-weight:bold;">--</span> 
                                (<span id="card-tempo" style="color:#007AFF;">--</span>)<br>
                                <span style="color:#666; font-size:0.8rem;">Histórico (4 últ.): <span id="card-historico">--</span></span>
                            </p>
                        </div>
                    </div>
                    <div class="profile-actions">
                        <button type="button" class="btn-profile-action" onclick="trocarCliente()">Trocar cliente</button>
                        <button type="button" class="btn-profile-action" id="btn-editar-cliente" onclick="editarCliente()">Editar cliente</button>
                    </div>
                </div>
                
                <div id="area-obs-db" style="display:none;" class="alert-obs">
                    <strong>⚠️ Obs Cliente:</strong> <span id="txt-obs-db"></span>
                </div>

                <div class="stats-grid">
                    <div class="stat-box"><small>Reservas</small><strong id="stat-reservas">0</strong></div>
                    <div class="stat-box"><small>Canceladas</small><strong id="stat-cancelada" class="alert-cancel">0</strong></div>
                </div>
            </div>

            <div class="mb-2" id="div-input-nome"> <label>Nome:</label> <input type="text" id="nome" name="nome" class="form-control" required> </div>

            <div class="row">
                <div class="col-md-6 mb-2"> <label>Data:</label> <input type="date" id="data" name="data" class="form-control" required> </div>
                <div class="col-md-6 mb-2"> <label>Horário:</label> <input type="time" id="horario" name="horario" class="form-control" required> </div>
            </div>
            
            <div class="mb-2"> <label>Nº de Pessoas:</label> <input type="number" id="num_pessoas" name="num_pessoas" class="form-control" required> </div>
            <div class="mb-2"> <label>Telefone Alt:</label> <input type="text" id="telefone2" name="telefone2" class="form-control" onkeyup="maskPhone(this)"> </div>
            
            <div class="mb-2">
                <label>Forma de Pagamento:</label>
                <select name="forma_pagamento" id="forma_pagamento" class="form-select">
                    <option value="Não definido">Selecione</option>
                    <option value="unica">Única</option>
                    <option value="individual">Individual</option>
                    <option value="U (rod) I (beb)">Única (rod) Individual (beb)</option>
                    <option value="outros">Outros</option>
                </select>
            </div>

            <div class="mb-2">
                <label>Tipo de Evento:</label>
                <select name="tipo_evento" id="tipo_evento" class="form-select">
                    <option value="">Selecione</option>
                    <option value="Aniversario">Aniversário</option>
                    <option value="Conf. fim de ano">Confraternização Fim de Ano</option>
                    <option value="Formatura">Formatura</option>
                    <option value="Casamento">Casamento</option>
                    <option value="Conf. Familia">Confraternização Família</option>
                </select>
            </div>

            <div class="mb-2">
                <label>Valor do Rodízio:</label>
                <select name="valor_rodizio" id="valor_rodizio" class="form-select">
                    <option value="">Selecione</option>
                    <?php if ($ultimo_preco): ?>
                        <option value="<?= $ultimo_preco['almoco'] ?>">Almoço - R$ <?= $ultimo_preco['almoco'] ?></option>
                        <option value="<?= $ultimo_preco['jantar'] ?>">Jantar - R$ <?= $ultimo_preco['jantar'] ?></option>
                        <option value="<?= $ultimo_preco['outros'] ?>">Sábado - R$ <?= $ultimo_preco['outros'] ?></option>
                        <option value="<?= $ultimo_preco['domingo_almoco'] ?>">Domingo Almoço - R$ <?= $ultimo_preco['domingo_almoco'] ?></option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-2">
                <label>Mesa:</label>
                <select name="num_mesa" id="num_mesa" class="form-select">
                    <option value="">Selecione</option>
                    <option value="Salão 1">Salão 1</option>
                    <option value="Salão 2">Salão 2</option>
                    <option value="Salão 3">Salão 3</option>
                    <option value="Próximo à janela">Próximo à janela</option>
                    <option value="Próximo ao jardim">Próximo ao jardim</option>
                    <option value="Centro do salão">Centro do salão</option>
                    <?php for ($i = 1; $i <= 99; $i++) echo "<option value='$i'>$i</option>"; ?>
                </select>
            </div>

            <div class="mb-2"> <label>Observações:</label> <textarea id="observacoes" name="observacoes" class="form-control" rows="2"></textarea> </div>

            <button type="submit" id="btnSalvarManual" class="btn-ios-primary">Cadastrar reserva</button>
        </form>
    </div>
</div>

<script>
function maskPhone(input) {
    let v = input.value.replace(/\D/g, "");
    if (v.length > 11) v = v.slice(0, 11);
    if (v.length <= 10) { v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d{4})(\d)/, "$1-$2"); }
    else { v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d{5})(\d)/, "$1-$2"); }
    input.value = v;
}

// --- FUNÇÕES DE EDIÇÃO DE NOME IN-LINE ---
function editarCliente() {
    const nomeAtual = document.getElementById('card-nome').innerText;
    document.getElementById('input-edit-nome').value = nomeAtual;
    
    document.getElementById('card-nome').style.display = 'none';
    document.getElementById('edit-name-container').style.display = 'flex';
    document.getElementById('btn-editar-cliente').style.display = 'none'; 
}

function cancelarEdicaoNome() {
    document.getElementById('card-nome').style.display = 'flex';
    document.getElementById('edit-name-container').style.display = 'none';
    document.getElementById('btn-editar-cliente').style.display = 'block';
}

function salvarNomeCliente() {
    const novoNome = document.getElementById('input-edit-nome').value.trim();
    const telefone = document.getElementById('telefone').value.replace(/\D/g, '');
    if(!novoNome) { alert("O nome não pode ser vazio."); return; }
    let fd = new FormData(); fd.append('acao', 'atualizar_nome_cliente'); fd.append('telefone', telefone); fd.append('nome', novoNome);
    fetch('adicionar_reserva.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        if(res.success) { document.getElementById('card-nome').innerText = novoNome; document.getElementById('nome').value = novoNome; cancelarEdicaoNome(); }
        else { alert("Erro ao atualizar."); }
    }).catch(e => alert("Erro de conexão."));
}

// --- FUNÇÃO PARA ENVIAR O FORMULÁRIO MANUAL VIA AJAX ---
function enviarReservaAjax() {
    const form = document.getElementById('formManual');
    const fd = new FormData(form);
    
    fetch('adicionar_reserva.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        const btn = document.getElementById('btnSalvarManual');
        btn.disabled = false; btn.innerText = "Cadastrar reserva";

        if(res.success) {
            // Cria o Modal igual ao do "Salvar Direto"
            let modalHtml = `<div id="modalConfirmacaoManual" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; justify-content:center; align-items:center;">
                <div style="background:white; padding:30px; border-radius:15px; text-align:center; width:90%; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                    <h2 style="margin-top:0;">Reserva Salva!</h2>
                    <p style="color:#666;">A reserva foi registrada com sucesso.</p>
                    <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                        <button onclick="window.open('${res.link_wpp}', '_blank')" style="background:#25D366; color:white; border:none; padding:12px; border-radius:99px; font-weight:bold; cursor:pointer; width:100%;">Confirmar WhatsApp</button>
                        <button onclick="fecharModalEAtualizar()" style="background:#e5e5ea; color:#333; border:none; padding:12px; border-radius:99px; cursor:pointer; width:100%;">Fechar / Nova Reserva</button>
                    </div>
                </div></div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        } else {
            alert(res.message || "Erro ao salvar.");
        }
    })
    .catch(e => {
        console.error(e);
        const btn = document.getElementById('btnSalvarManual');
        btn.disabled = false; btn.innerText = "Cadastrar reserva";
        alert("Erro ao enviar. Tente novamente.");
    });
}

function fecharModalEAtualizar() {
    document.getElementById('modalConfirmacaoManual').remove();
    // Limpa o formulário para uma nova reserva
    document.getElementById('formManual').reset();
    trocarCliente(); // Reseta o card de cliente
    // Se quiser recarregar a página descomente abaixo, mas para UX "Single Page" limpar é melhor
    // window.location.reload(); 
}

// --- LÓGICA DE VERIFICAÇÃO ANTES DE ENVIAR ---
function verificarEEnviar() {
    const tel = document.getElementById('telefone').value.replace(/\D/g, '');
    const data = document.getElementById('data').value;
    const nome = document.getElementById('nome').value;
    if (tel.length < 10 || !data || !nome) { alert('Preencha os campos obrigatórios.'); return; }
    
    const btn = document.getElementById('btnSalvarManual'); btn.disabled = true; btn.innerText = "Verificando...";
    
    fetch(`adicionar_reserva.php?acao=checar_duplicidade&telefone=${tel}&data=${data}&nome=${encodeURIComponent(nome)}`)
    .then(r => r.json()).then(resp => {
        if(resp.erro_data) {
            btn.disabled = false; btn.innerText = "Cadastrar reserva";
            alert(resp.msg);
            return;
        }

        if (resp.existe) { 
            if (confirm(`Já existe uma reserva para ${nome} nesta data. Continuar?`)) {
                enviarReservaAjax(); 
            } else {
                btn.disabled = false; btn.innerText = "Cadastrar reserva";
            }
        }
        else {
            enviarReservaAjax();
        }
    })
    .catch(e => {
        btn.disabled = false; btn.innerText = "Cadastrar reserva";
        alert("Erro na verificação. Tente novamente.");
    });
}

// --- FUNÇÕES "SALVAR DIRETO" (Mantidas) ---
async function analisarSalvarDireto() {
    const texto = document.getElementById('whats_dados').value.trim();
    if (!texto) { alert('Cole dados.'); return; }
    const btn = event.target; const txtOriginal = btn.innerText;
    btn.innerText = "Analisando..."; btn.disabled = true;

    let fd = new FormData(); fd.append('acao', 'analisar_whats'); fd.append('whats_text', texto);
    try {
        let req = await fetch('adicionar_reserva.php', { method: 'POST', body: fd });
        let res = await req.json();
        btn.innerText = txtOriginal; btn.disabled = false;
        if (!res.success) { alert(res.message); return; }
        
        let listaParaSalvar = [];
        for (let item of res.lista) {
            if (!item.valido) { alert(`ERRO em ${item.dados.nome}: ` + item.erros.join(', ') + ". Ignorada."); continue; }
            if (item.duplicado) {
                if (confirm(`⚠️ DUPLICIDADE: ${item.dados.nome} já existe dia ${item.dados.data}.\nCriar outra?`)) listaParaSalvar.push(item.dados);
            } else { listaParaSalvar.push(item.dados); }
        }
        if (listaParaSalvar.length > 0) salvarListaFinal(listaParaSalvar);
        else alert('Nenhuma reserva salva.');
    } catch (e) { btn.innerText = txtOriginal; btn.disabled = false; alert('Erro ao analisar.'); }
}

async function salvarListaFinal(lista) {
    let fd = new FormData(); fd.append('acao', 'salvar_lista_final'); fd.append('lista_json', JSON.stringify(lista));
    try {
        let req = await fetch('adicionar_reserva.php', { method: 'POST', body: fd });
        let res = await req.json();
        if (res.success) {
            let linksHtml = res.links.map(l => `<li>${l.nome}: <a href="${l.link}" target="_blank">Enviar Zap</a></li>`).join('');
            let modalHtml = `<div id="modalRelatorio" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; justify-content:center; align-items:center;">
                <div style="background:white; padding:20px; border-radius:15px; text-align:center; width:90%; max-width:400px; box-shadow:0 12px 30px rgba(0,0,0,0.14);">
                    <h3>Salvo!</h3><p>${res.salvos} reservas criadas.</p><ul style="text-align:left; max-height:200px; overflow:auto;">${linksHtml}</ul>
                    <button onclick="document.getElementById('modalRelatorio').remove();" class="btn-ios-primary" style="margin-top:10px;">Fechar</button>
                </div></div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
    } catch (e) { alert('Erro ao salvar final.'); }
}

function buscarTelefone() {
    const telefone = document.getElementById('telefone').value.replace(/\D/g, '');
    if (telefone.length < 10) return;
    fetch(`adicionar_reserva.php?acao=buscar_perfil&telefone=${telefone}`)
    .then(r => r.json()).then(data => {
        if (data.encontrado) {
            const p = data.perfil;
            document.getElementById('card-nome').innerText = p.nome;
            document.getElementById('card-telefone').innerText = document.getElementById('telefone').value;
            document.getElementById('card-ultima').innerText = p.ultima_visita_data;
            document.getElementById('card-tempo').innerText = p.tempo_atras;
            document.getElementById('card-historico').innerText = p.historico_recente;
            document.getElementById('stat-reservas').innerText = p.total_reservas;
            
            const statCancel = document.getElementById('stat-cancelada');
            statCancel.innerText = p.canceladas;
            statCancel.style.color = p.canceladas > 0 ? 'red' : '#333';

            const areaObs = document.getElementById('area-obs-db');
            if(p.obs_cliente && p.obs_cliente !== "Nenhuma observação registrada.") {
                document.getElementById('txt-obs-db').innerText = p.obs_cliente;
                areaObs.style.display = 'block';
            } else { areaObs.style.display = 'none'; }

            document.getElementById('nome').value = p.nome;
            cancelarEdicaoNome();
            document.getElementById('card-perfil-cliente').style.display = 'block';
            document.getElementById('div-input-nome').style.display = 'none';
        } else {
            document.getElementById('card-perfil-cliente').style.display = 'none';
            document.getElementById('div-input-nome').style.display = 'block';
        }
    });
}

function importarWhatsParaFormulario() {
    const texto = document.getElementById('whats_dados').value.trim();
    if (!texto) { alert('Cole dados.'); return; }
    const linhas = texto.split('\n');
    linhas.forEach(linha => {
        const partes = linha.split(':');
        if (partes.length < 2) return;
        const c = partes[0].trim().toLowerCase();
        const v = partes.slice(1).join(':').trim();
        if(c.includes('nome')) document.getElementById('nome').value = v;
        if(c.includes('telefone') && !c.includes('alt')) { document.getElementById('telefone').value = v.replace(/\D/g,''); maskPhone(document.getElementById('telefone')); buscarTelefone(); }
        if(c.includes('data')) { if(v.includes('/')) { const [d,m,y] = v.split('/'); document.getElementById('data').value = `${y}-${m}-${d}`; } else document.getElementById('data').value = v; }
        if(c.includes('hor')) document.getElementById('horario').value = v;
        if(c.includes('pessoas')) document.getElementById('num_pessoas').value = v;
        if(c.includes('pagamento')) document.getElementById('forma_pagamento').value = v;
        if(c.includes('observa')) document.getElementById('observacoes').value = v;
        if(c.includes('mesa')) document.getElementById('num_mesa').value = v;
    });
}

function trocarCliente() { document.getElementById('telefone').value = ''; document.getElementById('nome').value = ''; document.getElementById('card-perfil-cliente').style.display = 'none'; document.getElementById('div-input-nome').style.display = 'block'; }
</script>
</body>
</html>