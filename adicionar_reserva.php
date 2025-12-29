<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require 'config.php';

// ========================= VERIFICA LOGIN =========================
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

// CAPTURA O ID DA EMPRESA LOGADA
$empresa_id = $_SESSION['empresa_id'];

// ========================= FUNÇÕES AUXILIARES =========================

function validarTelefone($telefone)
{
    $telefone = preg_replace('/\D/', '', $telefone);
    return preg_match('/^[1-9]{2}9\d{8}$/', $telefone);
}

function validarData($data)
{
    $data = trim($data);
    if ($data === '')
        return false;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data))
        return true;

    if (!preg_match('/^(0?[1-9]|[12][0-9]|3[01])[\/-](0?[1-9]|1[0-2])[\/-](\d{2}|\d{4})$/', $data)) {
        return false;
    }

    return true;
}

function normalizarDataParaBanco($data)
{
    $data = trim($data);
    if ($data === '')
        return false;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data))
        return $data;

    $data = str_replace('-', '/', $data);
    $partes = explode('/', $data);
    if (count($partes) !== 3)
        return false;

    list($dia, $mes, $ano) = $partes;
    $dia = str_pad($dia, 2, '0', STR_PAD_LEFT);
    $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

    if (strlen($ano) == 2) {
        $ano = '20' . $ano;
    }

    return "$ano-$mes-$dia";
}

function validarHorario($horario)
{
    $horario = trim($horario);
    if ($horario === '')
        return false;
    return preg_match('/^([01]\d|2[0-3])[:.;]([0-5]\d)$/', $horario);
}

function validarHorarioFuncionamento($horarioBanco)
{
    $inicio = "11:00:00";
    $fim = "23:59:59";

    if ($horarioBanco < $inicio) {
        return "Horário antes das 11:00 — fora do horário de funcionamento.";
    }

    if ($horarioBanco > $fim) {
        return "Horário após 23:59 — fora do horário de funcionamento.";
    }

    return true;
}

function normalizarHorarioParaBanco($horario)
{
    $horario = trim($horario);
    if ($horario === '')
        return false;

    $horario = str_replace([';', '.'], ':', $horario);

    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $horario)) {
        return false;
    }

    return $horario . ':00';
}

function tempoAtras($data)
{
    $dt = new DateTime($data);
    $now = new DateTime();

    $dt->setTime(0, 0);
    $now->setTime(0, 0);

    if ($dt > $now)
        return "Reserva Futura";

    $diff = $now->diff($dt);

    if ($diff->days == 0)
        return "Hoje";
    if ($diff->days == 1)
        return "Ontem";

    if ($diff->y > 0) {
        return $diff->y == 1 ? "1 ano atrás" : $diff->y . " anos atrás";
    }

    if ($diff->m > 0) {
        return $diff->m == 1 ? "1 mês atrás" : $diff->m . " meses atrás";
    }

    return $diff->d . " dias atrás";
}

// ========================= 1. AJAX: ANÁLISE WHATSAPP =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'analisar_whats') {
    header('Content-Type: application/json; charset=utf-8');

    $textoCompleto = trim($_POST['whats_text'] ?? '');
    if ($textoCompleto === '') {
        echo json_encode(['success' => false, 'message' => 'Texto vazio.']);
        exit;
    }

    $blocos = preg_split('/(?=Nome:)/i', $textoCompleto, -1, PREG_SPLIT_NO_EMPTY);
    $listaProcessada = [];

    foreach ($blocos as $bloco) {
        $linhas = explode("\n", $bloco);
        $dados = [];

        foreach ($linhas as $linha) {
            $partes = explode(":", $linha, 2);
            if (count($partes) == 2) {
                $dados[strtolower(trim($partes[0]))] = trim($partes[1]);
            }
        }

        $nome = $dados['nome'] ?? '';
        if (empty($nome))
            continue;

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
        $dataBanco = null;
        $horarioBanco = null;

        if (empty($telefoneRaw) || !validarTelefone($telefoneRaw)) {
            $erros[] = "Telefone inválido";
        }

        if (empty($dataRaw) || !validarData($dataRaw)) {
            $erros[] = "Data inválida";
        } else {
            $dataBanco = normalizarDataParaBanco($dataRaw);
            if ($dataBanco < date('Y-m-d')) {
                $erros[] = "Data antiga (Anterior a hoje)";
            }
        }

        if (empty($horarioRaw) || !validarHorario($horarioRaw)) {
            $erros[] = "Horário inválido";
        } else {
            $horarioBanco = normalizarHorarioParaBanco($horarioRaw);
        }

        $duplicado = false;
        if (empty($erros)) {
            $sqlCheck = $pdo->prepare(
                "SELECT id FROM clientes 
                 WHERE nome = :n AND data = :d AND horario = :h AND num_pessoas = :np AND empresa_id = :emp"
            );
            $sqlCheck->execute([
                ':n' => $nome,
                ':d' => $dataBanco,
                ':h' => $horarioBanco,
                ':np' => $num_pessoas,
                ':emp' => $empresa_id
            ]);

            if ($sqlCheck->rowCount() > 0) {
                $duplicado = true;
            }
        }

        $listaProcessada[] = [
            'valido' => empty($erros),
            'erros' => $erros,
            'duplicado' => $duplicado,
            'dados' => [
                'nome' => $nome,
                'data' => $dataBanco,
                'horario' => $horarioBanco,
                'num_pessoas' => $num_pessoas,
                'telefone' => $telefoneRaw,
                'telefone2' => $telefone2,
                'tipo_evento' => $tipo_evento,
                'forma_pagamento' => $forma_pagamento,
                'valor_rodizio' => $valor_rodizio,
                'num_mesa' => $num_mesa,
                'observacoes' => $observacoes
            ]
        ];
    }

    echo json_encode(['success' => true, 'lista' => $listaProcessada]);
    exit;
}

// ========================= 2. AJAX: SALVAR LISTA FINAL =========================
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
                $sql = $pdo->prepare(
                    "INSERT INTO clientes 
                    (usuario_id, empresa_id, nome, data, num_pessoas, horario, telefone, telefone2, tipo_evento, forma_pagamento, valor_rodizio, num_mesa, observacoes) 
                    VALUES 
                    (:uid, :eid, :nm, :dt, :np, :hr, :tel, :tel2, :evt, :pgt, :vlr, :ms, :obs)"
                );

                $sql->execute([
                    ':uid' => $usuario_id,
                    ':eid' => $empresa_id,
                    ':nm' => $item['nome'],
                    ':dt' => $item['data'],
                    ':np' => $item['num_pessoas'],
                    ':hr' => $item['horario'],
                    ':tel' => $item['telefone'],
                    ':tel2' => $item['telefone2'],
                    ':evt' => $item['tipo_evento'],
                    ':pgt' => $item['forma_pagamento'],
                    ':vlr' => $item['valor_rodizio'],
                    ':ms' => $item['num_mesa'],
                    ':obs' => $item['observacoes']
                ]);

                $dataBr = date('d/m/Y', strtotime($item['data']));
                $horaCurta = substr($item['horario'], 0, 5);
                $msgZap = "Olá, {$item['nome']}. Sua reserva para {$dataBr} às {$horaCurta} para {$item['num_pessoas']} pessoas foi confirmada.";
                $link = "https://wa.me/55{$item['telefone']}?text=" . urlencode($msgZap);

                $links[] = ['nome' => $item['nome'], 'link' => $link];
                $sucessos++;
            } catch (Exception $e) {
            }
        }
    }

    echo json_encode(['success' => true, 'salvos' => $sucessos, 'links' => $links]);
    exit;
}

// ========================= 3. AJAX: BUSCAR PERFIL (MULTI-EMPRESA) =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'buscar_perfil') {
    $telefone = preg_replace('/\D/', '', $_GET['telefone']);

    // Só busca o histórico deste cliente DENTRO desta empresa
    $sql = $pdo->prepare(
        "SELECT * FROM clientes 
         WHERE telefone = :telefone AND empresa_id = :emp
         ORDER BY data DESC, horario DESC"
    );
    $sql->execute([':telefone' => $telefone, ':emp' => $empresa_id]);
    $historico = $sql->fetchAll(PDO::FETCH_ASSOC);

    if (count($historico) > 0) {
        $ultimo = $historico[0];
        $total = count($historico);
        $canceladas = 0;
        $obsClienteEncontrada = null;
        $ultimasDatas = [];
        $contadorRecentes = 0;

        foreach ($historico as $h) {
            if (isset($h['status']) && $h['status'] == 0) {
                $canceladas++;
            }

            if (empty($obsClienteEncontrada) && !empty($h['obsCliente'])) {
                $obsClienteEncontrada = $h['obsCliente'];
            }

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

    if (empty($novoNome) || empty($telefone)) {
        echo json_encode(['success' => false, 'message' => 'Nome inválido.']);
        exit;
    }

    try {
        $sql = $pdo->prepare("UPDATE clientes SET nome = :nome WHERE telefone = :telefone AND empresa_id = :emp");
        $sql->execute([
            ':nome' => $novoNome,
            ':telefone' => $telefone,
            ':emp' => $empresa_id
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar.']);
    }
    exit;
}

// ========================= 5. AJAX: CHECAR DUPLICIDADE =========================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] == 'checar_duplicidade') {
    $telefone = preg_replace('/\D/', '', $_GET['telefone']);
    $data = normalizarDataParaBanco($_GET['data']);
    $nome = trim($_GET['nome']);

    if ($data < date('Y-m-d')) {
        echo json_encode(['erro_data' => true, 'msg' => 'A data selecionada é anterior à data de hoje.']);
        exit;
    }

    $sql = $pdo->prepare(
        "SELECT id FROM clientes 
         WHERE telefone = :tel AND data = :dt AND nome = :nm AND empresa_id = :emp
         LIMIT 1"
    );
    $sql->execute([
        ':tel' => $telefone,
        ':dt' => $data,
        ':nm' => $nome,
        ':emp' => $empresa_id
    ]);

    echo json_encode(['existe' => ($sql->rowCount() > 0)]);
    exit;
}

// ========================= 6. AJAX: SALVAR MANUAL (MULTI-EMPRESA) =========================
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
        $sql = $pdo->prepare(
            "INSERT INTO clientes 
            (usuario_id, empresa_id, nome, data, num_pessoas, horario, telefone, telefone2, tipo_evento, forma_pagamento, valor_rodizio, num_mesa, observacoes) 
            VALUES 
            (:uid, :eid, :nm, :dt, :np, :hr, :tel, :tel2, :evt, :pgt, :vlr, :ms, :obs)"
        );

        $sql->execute([
            ':uid' => $usuario_id,
            ':eid' => $empresa_id, // SALVA COM O ID DA EMPRESA LOGADA
            ':nm' => $nome,
            ':dt' => $dataBanco,
            ':np' => $num_pessoas,
            ':hr' => $horarioBanco,
            ':tel' => $telefone,
            ':tel2' => $telefone2,
            ':evt' => $tipo_evento,
            ':pgt' => $forma_pagamento,
            ':vlr' => $valor_rodizio,
            ':ms' => $num_mesa,
            ':obs' => $observacoes
        ]);

        $link = "";
        if (!empty($telefone)) {
            $msg = "Olá, $nome. Sua reserva para " . date('d/m/Y', strtotime($dataBanco)) .
                " às " . substr($horarioBanco, 0, 5) .
                " para $num_pessoas pessoas foi agendada.";
            $link = "https://wa.me/55$telefone?text=" . urlencode($msg);
        }

        echo json_encode(['success' => true, 'link_wpp' => $link]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco de dados.']);
    }
    exit;
}

// ========================= CARREGA CONFIG PREÇO =========================
require 'cabecalho.php';

// Filtra os preços também pela empresa logada
$sql = $pdo->prepare("SELECT * FROM preco_rodizio WHERE empresa_id = :emp ORDER BY id DESC LIMIT 1");
$sql->execute([':emp' => $empresa_id]);
$ultimo_preco = $sql->fetch(PDO::FETCH_ASSOC);
?>
<!-- O RESTANTE DO SEU HTML E JAVASCRIPT SEGUE EXATAMENTE IGUAL -->
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Reserva</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", sans-serif;
            background: #f2f3f7;
            margin: 0;
            padding: 20px;
        }

        .page-wrapper {
            max-width: 960px;
            margin: 110px auto 40px;
        }

        .card-ios {
            background: #fff;
            border-radius: 22px;
            padding: 22px 20px 24px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.14);
        }

        .form-control,
        .form-select,
        .modal-ios-input {
            border-radius: 12px;
            border: 1px solid #d1d1d6;
            padding: 9px 12px;
            background: #f9f9fb;
        }

        .btn-ios-primary {
            background: #007AFF;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 11px 18px;
            width: 100%;
            font-weight: 600;
            margin-top: 16px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-ios-primary:disabled {
            opacity: 0.5;
        }

        .btn-ios-outline {
            background: #fff;
            border: 1px solid #d1d1d6;
            color: #111;
            border-radius: 999px;
            padding: 9px 12px;
            cursor: pointer;
        }

        .btn-ios-secondary {
            background: #e5e5ea;
            border: none;
            color: #111;
            border-radius: 999px;
            padding: 9px 12px;
            cursor: pointer;
        }

        .textarea-wrapper {
            position: relative;
        }

        .btn-clear-text {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-card {
            border: 1px solid #28a745;
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .profile-info-area {
            display: flex;
            gap: 15px;
            flex: 1;
        }

        .avatar-circle {
            width: 60px;
            height: 60px;
            background: #ccc;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .avatar-circle svg {
            width: 40px;
            height: 40px;
            fill: #666;
        }

        .client-details h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 800;
            color: #333;
            display: flex;
            align-items: center;
            height: 30px;
        }

        .client-details p {
            margin: 2px 0 0;
            font-size: 0.9rem;
            color: #666;
        }

        .profile-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-profile-action {
            background: #fff;
            border: 1px solid #ccc;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            color: #333;
            min-width: 110px;
            text-decoration: none;
            text-align: center;
        }

        .edit-name-container {
            display: none;
            align-items: center;
            gap: 5px;
        }

        .input-edit-nome {
            padding: 4px 8px;
            border: 1px solid #007AFF;
            border-radius: 4px;
            font-size: 1rem;
            width: 100%;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            border-top: 1px solid #eee;
            padding-top: 10px;
            text-align: left;
            margin-top: 10px;
        }

        .alert-obs {
            background: #fff3cd;
            color: #856404;
            padding: 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-top: 10px;
            border: 1px solid #ffeeba;
        }

        .alert-cancel {
            color: #dc3545;
            font-weight: bold;
        }

        /* Modal Style */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }

        .modal-box {
            background: white;
            padding: 30px;
            border-radius: 22px;
            text-align: center;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        .row .col-md-6,
        .row .col-6 {
            display: flex;
            flex-direction: column;
        }

        #data,
        #horario {
            height: 42px;
        }

        #horario {
            margin-top: 24px !important;
        }


        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="card-ios">
            <h2 style="text-align:center; font-weight: 800; margin-bottom: 25px;">Nova Reserva</h2>

            <!-- ÁREA DE IMPORTAÇÃO -->
            <div class="mb-3">
                <label><strong>Colar dados do WhatsApp:</strong></label>
                <div class="textarea-wrapper">
                    <textarea id="whats_dados" class="form-control" rows="5"
                        placeholder="Cole aqui o texto do WhatsApp..."></textarea>
                    <button type="button" class="btn-clear-text"
                        onclick="document.getElementById('whats_dados').value='';" title="Limpar">
                        <i class="material-icons" style="font-size:18px">close</i>
                    </button>
                </div>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <button type="button" onclick="importarWhatsParaFormulario()" class="btn-ios-outline">
                        Importar p/ Form
                    </button>
                    <button type="button" onclick="analisarSalvarDireto()" class="btn-ios-secondary">
                        Salvar Direto
                    </button>
                </div>
            </div>
            <hr>

            <!-- FORMULÁRIO MANUAL -->
            <form id="formManual" onsubmit="event.preventDefault(); verificarEEnviar();">
                <div class="mb-2">
                    <label>Telefone:</label>
                    <input type="text" id="telefone" name="telefone" class="form-control" maxlength="15" required
                        onkeyup="maskPhone(this)" onblur="buscarTelefone()">

                    <!-- CHECKBOX: SEM TELEFONE -->
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="sem_telefone" name="sem_telefone"
                            onclick="toggleTelefone(this)">
                        <label class="form-check-label" for="sem_telefone" style="font-size: 0.85rem; color: #666;">
                            Não possuo o número de telefone
                        </label>
                    </div>
                </div>

                <!-- CARD PERFIL CLIENTE -->
                <div id="card-perfil-cliente" class="profile-card">
                    <div class="profile-header">
                        <div class="profile-info-area">
                            <div class="avatar-circle">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4
                                         1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8
                                         1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                </svg>
                            </div>
                            <div class="client-details">
                                <h3 id="card-nome">--</h3>

                                <div id="edit-name-container" class="edit-name-container">
                                    <input type="text" id="input-edit-nome" class="input-edit-nome">
                                    <button type="button" class="btn-ios-primary"
                                        style="width: auto; margin:0; padding: 5px 10px;"
                                        onclick="salvarNomeCliente()">OK</button>
                                    <button type="button" class="btn-ios-secondary" style="padding: 5px 10px;"
                                        onclick="cancelarEdicaoNome()">X</button>
                                </div>

                                <p id="card-telefone">--</p>
                                <p style="font-size:0.85rem; margin-top:5px;">
                                    Última visita:
                                    <span id="card-ultima" style="font-weight:bold;">--</span>
                                    (<span id="card-tempo" style="color:#007AFF;">--</span>)<br>
                                    <span style="color:#666; font-size:0.8rem;">
                                        Histórico (4 últ.):
                                        <span id="card-historico">--</span>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="button" class="btn-profile-action" onclick="trocarCliente()">
                                Trocar
                            </button>
                            <button type="button" class="btn-profile-action" id="btn-editar-cliente"
                                onclick="editarCliente()">
                                Editar Nome
                            </button>
                        </div>
                    </div>

                    <div id="area-obs-db" style="display:none;" class="alert-obs">
                        <strong>⚠️ Obs Cliente:</strong> <span id="txt-obs-db"></span>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-box">
                            <small>Reservas</small>
                            <strong id="stat-reservas">0</strong>
                        </div>
                        <div class="stat-box">
                            <small>Canceladas</small>
                            <strong id="stat-cancelada" class="alert-cancel">0</strong>
                        </div>
                    </div>
                </div>

                <!-- Campo Nome (exibido apenas se não achar perfil) -->
                <div class="mb-2" id="div-input-nome">
                    <label>Nome:</label>
                    <input type="text" id="nome" name="nome" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Data:</label>
                        <input type="date" id="data" name="data" class="form-control" required>
                    </div>
                    <div class="row">

                        <div class="col-6 mb-2 d-flex flex-column">
                            <label style="margin-bottom: 2px;">Horário:</label>
                            <select name="horario" id="horario" class="form-control" required style="height:42px;">
                                <optgroup label="Almoço">
                                    <option value="11:30">11:30</option>
                                    <option value="12:00">12:00</option>
                                    <option value="12:30">12:30</option>
                                    <option value="13:00">13:00</option>
                                    <option value="13:30">13:30</option>
                                    <option value="14:00">14:00</option>
                                    <option value="14:30">14:30</option>
                                    <option value="15:00">15:00</option>
                                </optgroup>
                                <optgroup label="Jantar">
                                    <option value="17:00">17:00</option>
                                    <option value="17:30">17:30</option>
                                    <option value="18:00">18:00</option>
                                    <option value="18:30">18:30</option>
                                    <option value="19:00">19:00</option>
                                    <option value="19:30">19:30</option>
                                    <option value="20:00">20:00</option>
                                    <option value="20:30">20:30</option>
                                    <option value="21:00">21:00</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="mb-2">
                    <label>Nº de Pessoas:</label>
                    <input type="number" id="num_pessoas" name="num_pessoas" class="form-control" required>
                </div>

                <div class="mb-2">
                    <label>Telefone Alt:</label>
                    <input type="text" id="telefone2" name="telefone2" class="form-control" onkeyup="maskPhone(this)">
                </div>

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
                            <option value="<?= $ultimo_preco['almoco'] ?>">
                                Almoço - R$ <?= $ultimo_preco['almoco'] ?>
                            </option>
                            <option value="<?= $ultimo_preco['jantar'] ?>">
                                Jantar - R$ <?= $ultimo_preco['jantar'] ?>
                            </option>
                            <option value="<?= $ultimo_preco['outros'] ?>">
                                Sábado - R$ <?= $ultimo_preco['outros'] ?>
                            </option>
                            <option value="<?= $ultimo_preco['domingo_almoco'] ?>">
                                Domingo Almoço - R$ <?= $ultimo_preco['domingo_almoco'] ?>
                            </option>
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
                        <?php for ($i = 1; $i <= 99; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label>Observações:</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" id="btnSalvarManual" class="btn-ios-primary">
                    Cadastrar reserva
                </button>
            </form>
        </div>
    </div>

    <script>
        // --- MÁSCARA DE TELEFONE ---
        function maskPhone(input) {
            let v = input.value.replace(/\D/g, "");
            if (v.length > 11) v = v.slice(0, 11);

            if (v.length <= 10) {
                v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                v = v.replace(/(\d{4})(\d)/, "$1-$2");
            } else {
                v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                v = v.replace(/(\d{5})(\d)/, "$1-$2");
            }
            input.value = v;
        }

        // --- TOGGLE TELEFONE (SEM TELEFONE) ---
        function toggleTelefone(checkbox) {
            const telInput = document.getElementById('telefone');
            const divNome = document.getElementById('div-input-nome');
            const cardPerfil = document.getElementById('card-perfil-cliente');

            if (checkbox.checked) {
                telInput.required = false;
                telInput.value = '';
                telInput.disabled = true;
                telInput.style.backgroundColor = '#e9ecef';

                cardPerfil.style.display = 'none';
                divNome.style.display = 'block';
            } else {
                telInput.required = true;
                telInput.disabled = false;
                telInput.style.backgroundColor = '#f9f9fb';
            }
        }

        function trocarCliente() {
            document.getElementById('telefone').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('card-perfil-cliente').style.display = 'none';
            document.getElementById('div-input-nome').style.display = 'block';
            document.getElementById('sem_telefone').checked = false;
            document.getElementById('telefone').disabled = false;
            document.getElementById('telefone').required = true;
        }

        function fecharModalEAtualizar() {
            const modal = document.getElementById('modalConfirmacaoManual');
            if (modal) modal.remove();

            document.getElementById('formManual').reset();
            document.getElementById('whats_dados').value = '';
            trocarCliente();
        }

        // --- ENVIO AJAX MANUAL ---
        function enviarReservaAjax() {
            const form = document.getElementById('formManual');
            const fd = new FormData(form);
            const btn = document.getElementById('btnSalvarManual');

            fetch('adicionar_reserva.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerText = "Cadastrar reserva";

                    if (res.success) {

                        const btnZapHtml = res.link_wpp
                            ? `<button onclick="window.open('${res.link_wpp}', '_blank')" 
                                   class="btn-ios-primary" 
                                   style="background:#25D366; margin:0;">
                               Confirmar via WhatsApp
                           </button>`
                            : '';

                        const modalHtml = `
                        <div id="modalConfirmacaoManual" class="modal-overlay">
                            <div class="modal-box">
                                <div class="material-icons"
                                     style="font-size: 60px; color: #28a745; margin-bottom: 15px;">
                                    check_circle
                                </div>
                                <h2 style="margin-top:0; font-weight: 800;">Reserva Salva!</h2>
                                <p style="color:#666;">A reserva foi registrada com sucesso no sistema.</p>
                                <div style="margin-top:25px; display:flex; flex-direction:column; gap:12px;">
                                    ${btnZapHtml}
                                    <button onclick="fecharModalEAtualizar()"
                                            class="btn-ios-secondary">
                                        Fechar e Nova Reserva
                                    </button>
                                </div>
                            </div>
                        </div>`;
                        document.body.insertAdjacentHTML('beforeend', modalHtml);
                    } else {
                        alert(res.message || "Erro ao salvar.");
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerText = "Cadastrar reserva";
                    alert("Erro de conexão.");
                });
        }

        function verificarEEnviar() {
            const semTel = document.getElementById('sem_telefone').checked;
            const telInput = document.getElementById('telefone');
            const tel = telInput.value.replace(/\D/g, '');
            const data = document.getElementById('data').value;
            const nome = document.getElementById('nome').value;

            if (!semTel && tel.length < 10) {
                alert('Por favor, insira um telefone válido ou marque a opção "Não possuo número".');
                return;
            }

            if (!data || !nome) {
                alert('Preencha os campos obrigatórios.');
                return;
            }

            const btn = document.getElementById('btnSalvarManual');
            btn.disabled = true;
            btn.innerText = "Verificando...";

            if (semTel) {
                enviarReservaAjax();
                return;
            }

            fetch(`adicionar_reserva.php?acao=checar_duplicidade&telefone=${tel}&data=${data}&nome=${encodeURIComponent(nome)}`)
                .then(r => r.json())
                .then(resp => {
                    if (resp.erro_data) {
                        btn.disabled = false;
                        btn.innerText = "Cadastrar reserva";
                        alert(resp.msg);
                        return;
                    }

                    if (resp.existe) {
                        if (confirm(`Atenção: Já existe uma reserva para ${nome} nesta data. Deseja duplicar?`)) {
                            enviarReservaAjax();
                        } else {
                            btn.disabled = false;
                            btn.innerText = "Cadastrar reserva";
                        }
                    } else {
                        enviarReservaAjax();
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerText = "Cadastrar reserva";
                });
        }

        // --- BUSCAR PERFIL POR TELEFONE ---
        function buscarTelefone() {
            const telefone = document.getElementById('telefone').value.replace(/\D/g, '');
            if (telefone.length < 10) return;

            fetch(`adicionar_reserva.php?acao=buscar_perfil&telefone=${telefone}`)
                .then(r => r.json())
                .then(data => {
                    if (data.encontrado) {
                        const p = data.perfil;

                        document.getElementById('card-nome').innerText = p.nome;
                        document.getElementById('card-telefone').innerText = document.getElementById('telefone').value;
                        document.getElementById('card-ultima').innerText = p.ultima_visita_data;
                        document.getElementById('card-tempo').innerText = p.tempo_atras;
                        document.getElementById('card-historico').innerText = p.historico_recente;
                        document.getElementById('stat-reservas').innerText = p.total_reservas;
                        document.getElementById('stat-cancelada').innerText = p.canceladas;

                        const areaObs = document.getElementById('area-obs-db');
                        if (p.obs_cliente && p.obs_cliente !== "Nenhuma observação registrada.") {
                            document.getElementById('txt-obs-db').innerText = p.obs_cliente;
                            areaObs.style.display = 'block';
                        } else {
                            areaObs.style.display = 'none';
                        }

                        document.getElementById('nome').value = p.nome;
                        document.getElementById('card-perfil-cliente').style.display = 'block';
                        document.getElementById('div-input-nome').style.display = 'none';
                    } else {
                        document.getElementById('card-perfil-cliente').style.display = 'none';
                        document.getElementById('div-input-nome').style.display = 'block';
                    }
                });
        }

        // --- EDIÇÃO DE NOME NO CARD ---
        function editarCliente() {
            document.getElementById('input-edit-nome').value =
                document.getElementById('card-nome').innerText;

            document.getElementById('card-nome').style.display = 'none';
            document.getElementById('edit-name-container').style.display = 'flex';
        }

        function cancelarEdicaoNome() {
            document.getElementById('card-nome').style.display = 'flex';
            document.getElementById('edit-name-container').style.display = 'none';
        }

        function salvarNomeCliente() {
            const novoNome = document.getElementById('input-edit-nome').value.trim();
            const telefone = document.getElementById('telefone').value.replace(/\D/g, '');

            if (!novoNome) return;

            const fd = new FormData();
            fd.append('acao', 'atualizar_nome_cliente');
            fd.append('telefone', telefone);
            fd.append('nome', novoNome);

            fetch('adicionar_reserva.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('card-nome').innerText = novoNome;
                        document.getElementById('nome').value = novoNome;
                        cancelarEdicaoNome();
                    }
                });
        }

        // --- IMPORTAÇÃO DO TEXTO DO WHATSAPP PARA O FORM ---
        function importarWhatsParaFormulario() {
            const texto = document.getElementById('whats_dados').value.trim();
            if (!texto) return;

            const linhas = texto.split('\n');

            linhas.forEach(linha => {
                const partes = linha.split(':');
                if (partes.length < 2) return;

                const c = partes[0].trim().toLowerCase();
                const v = partes.slice(1).join(':').trim();

                if (c.includes('nome')) {
                    document.getElementById('nome').value = v;
                }
                if (c.includes('telefone') && !c.includes('alt')) {
                    document.getElementById('telefone').value = v.replace(/\D/g, '');
                    maskPhone(document.getElementById('telefone'));
                    buscarTelefone();
                }
                if (c.includes('data')) {
                    if (v.includes('/')) {
                        const [d, m, y] = v.split('/');
                        document.getElementById('data').value = `${y}-${m}-${d}`;
                    } else {
                        document.getElementById('data').value = v;
                    }
                }
                if (c.includes('hor')) {
                    document.getElementById('horario').value = v;
                }
                if (c.includes('pessoas')) {
                    document.getElementById('num_pessoas').value = v;
                }
                if (c.includes('pagamento')) {
                    document.getElementById('forma_pagamento').value = v;
                }
                if (c.includes('observa')) {
                    document.getElementById('observacoes').value = v;
                }
                if (c.includes('mesa')) {
                    document.getElementById('num_mesa').value = v;
                }
            });
        }

        // --- ANÁLISE E SALVAR DIRETO DO WHATSAPP ---
        async function analisarSalvarDireto() {
            const texto = document.getElementById('whats_dados').value.trim();
            if (!texto) return;

            const btn = event.target;
            btn.innerText = "Processando...";
            btn.disabled = true;

            const fd = new FormData();
            fd.append('acao', 'analisar_whats');
            fd.append('whats_text', texto);

            try {
                const req = await fetch('adicionar_reserva.php', {
                    method: 'POST',
                    body: fd
                });

                const res = await req.json();
                btn.innerText = "Salvar Direto";
                btn.disabled = false;

                if (!res.success) return;

                const listaParaSalvar = [];

                for (const item of res.lista) {
                    if (!item.valido) continue;

                    if (item.duplicado) {
                        if (confirm(`Duplicidade: ${item.dados.nome} já existe. Salvar assim mesmo?`)) {
                            listaParaSalvar.push(item.dados);
                        }
                    } else {
                        listaParaSalvar.push(item.dados);
                    }
                }

                if (listaParaSalvar.length > 0) {
                    salvarListaFinal(listaParaSalvar);
                }

            } catch (e) {
                btn.innerText = "Salvar Direto";
                btn.disabled = false;
            }
        }

        async function salvarListaFinal(lista) {
            const fd = new FormData();
            fd.append('acao', 'salvar_lista_final');
            fd.append('lista_json', JSON.stringify(lista));

            try {
                const req = await fetch('adicionar_reserva.php', {
                    method: 'POST',
                    body: fd
                });
                const res = await req.json();

                if (res.success) {
                    const linksHtml = res.links.map(l =>
                        `<li>${l.nome}: <a href="${l.link}" target="_blank">WhatsApp</a></li>`
                    ).join('');

                    const modalHtml = `
                    <div id="modalRelatorio" class="modal-overlay">
                        <div class="modal-box">
                            <h3>Processado!</h3>
                            <p>${res.salvos} reservas criadas.</p>
                            <ul style="text-align:left; max-height:200px; overflow:auto;">
                                ${linksHtml}
                            </ul>
                            <button onclick="document.getElementById('modalRelatorio').remove(); fecharModalEAtualizar();"
                                    class="btn-ios-primary">
                                Fechar
                            </button>
                        </div>
                    </div>`;
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                }
            } catch (e) {
                // erro silencioso
            }
        }
    </script>
</body>

</html>