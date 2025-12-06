<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ---------------------- VALIDA TELEFONE ---------------------- */
function validarTelefone($telefone)
{
    $telefone = preg_replace('/\D/', '', $telefone);
    return preg_match('/^(\d{2})9\d{8}$/', $telefone);
}

/* -------------------- PROCESSA FORMULÁRIO -------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario_id = $_SESSION['mmnlogin'];
    $id_reserva = (int) $_POST['id_reserva'];

    // REMOVE MÁSCARAS
    $telefone  = preg_replace('/\D/', '', $_POST['telefone']);
    $telefone2 = !empty($_POST['telefone2']) ? preg_replace('/\D/', '', $_POST['telefone2']) : null;

    $nome            = trim($_POST['nome']);
    $data            = trim($_POST['data']);
    $num_pessoas     = trim($_POST['num_pessoas']);
    $horario         = trim($_POST['horario']);
    $tipo_evento     = trim($_POST['tipo_evento']);
    $forma_pagamento = trim($_POST['forma_pagamento']);
    $valor_rodizio   = trim($_POST['valor_rodizio']);
    $num_mesa        = trim($_POST['num_mesa']);
    $observacoes     = trim($_POST['observacoes']);

    if (!validarTelefone($telefone) || ($telefone2 && !validarTelefone($telefone2))) {
        echo "<script>alert('Telefone inválido. Verifique o formato.'); window.history.back();</script>";
        exit;
    }

    try {
        $sql = $pdo->prepare("
            UPDATE clientes SET
                nome = :nome,
                data = :data,
                num_pessoas = :num_pessoas,
                horario = :horario,
                telefone = :telefone,
                telefone2 = :telefone2,
                tipo_evento = :tipo_evento,
                forma_pagamento = :forma_pagamento,
                valor_rodizio = :valor_rodizio,
                num_mesa = :num_mesa,
                observacoes = :observacoes
            WHERE id = :id");

        $sql->execute([
            ":id"              => $id_reserva,
            ":nome"            => $nome,
            ":data"            => $data,
            ":num_pessoas"     => $num_pessoas,
            ":horario"         => $horario,
            ":telefone"        => $telefone,
            ":telefone2"       => $telefone2,
            ":tipo_evento"     => $tipo_evento,
            ":forma_pagamento" => $forma_pagamento,
            ":valor_rodizio"   => $valor_rodizio,
            ":num_mesa"        => $num_mesa,
            ":observacoes"     => $observacoes
        ]);

        echo "<script>alert('Reserva atualizada com sucesso!'); window.location.href='index.php';</script>";
        exit;

    } catch (PDOException $e) {
        echo "<script>alert('Erro: ".$e->getMessage()."'); window.history.back();</script>";
        exit;
    }
}

/* -------------------- BUSCA A RESERVA -------------------- */
if (!isset($_GET['id'])) {
    echo "<script>alert('ID inválido.'); window.location.href='index.php';</script>";
    exit;
}

$id_reserva = (int) $_GET['id'];

$sql = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$sql->bindParam(":id", $id_reserva);
$sql->execute();
$reserva = $sql->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    echo "<script>alert('Reserva não encontrada.'); window.location.href='index.php';</script>";
    exit;
}

$formaPg  = strtolower(trim($reserva['forma_pagamento']));
$tipoEvt  = trim($reserva['tipo_evento']);
$numMesa  = trim($reserva['num_mesa']);
$valorRod = trim($reserva['valor_rodizio']);

$sqlPreco = $pdo->query("SELECT * FROM preco_rodizio ORDER BY id DESC LIMIT 1");
$ultimo_preco = $sqlPreco->fetch(PDO::FETCH_ASSOC);

require 'cabecalho.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Editar Reserva</title>

<style>
    body {
        background:#f2f3f7;
        font-family:-apple-system, BlinkMacSystemFont, "SF Pro Display", Poppins, sans-serif;
        padding:20px;
    }

    .card-ios {
        max-width: 600px;
        margin:110px auto;
        background:#fff;
        border-radius:22px;
        padding:25px;
        box-shadow:0 10px 30px rgba(0,0,0,0.12);
    }

    h2 {
        text-align:center;
        font-size:1.6rem;
        font-weight:700;
        margin-bottom:25px;
    }

    label {
        font-weight:600;
        margin-top:10px;
        color:#333;
    }

    input, select, textarea {
        margin-top:5px;
        border-radius:12px!important;
        border:1px solid #d1d1d6!important;
        padding:10px 12px!important;
        font-size:1rem!important;
        width:100%;
        background:#fafafa;
        transition:.2s;
    }

    input:focus, select:focus, textarea:focus {
        border-color:#007AFF!important;
        box-shadow:0 0 0 3px rgba(0,122,255,0.25)!important;
        background:#fff;
    }

    .btn-ios {
        margin-top:25px;
        width:100%;
        background:#007AFF;
        color:#fff;
        padding:13px;
        border:none;
        border-radius:14px;
        font-size:1.1rem;
        font-weight:600;
        cursor:pointer;
        box-shadow:0 8px 18px rgba(0,0,0,.2);
        transition:.2s;
    }

    .btn-ios:hover {
        background:#005ecb;
        transform:translateY(-2px);
    }
</style>

<script>
// Máscara de telefone (iOS clean)
function maskPhone(input) {
    let v = input.value.replace(/\D/g, "");
    if (v.length > 11) v = v.slice(0,11);

    if (v.length <= 10) {
        input.value = v.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{4})(\d)/, "$1-$2");
    } else {
        input.value = v.replace(/(\d{2})(\d)/, "($1) $2")
                       .replace(/(\d{5})(\d)/, "$1-$2");
    }
}
</script>

</head>

<body>

<div class="card-ios">

<h2>Editar Reserva</h2>

<form method="POST">

<input type="hidden" name="id_reserva" value="<?= $reserva['id'] ?>">

<label>Telefone:</label>
<input type="text" name="telefone" maxlength="15" required
       onkeyup="maskPhone(this)"
       value="<?= preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $reserva['telefone']) ?>">

<label>Nome:</label>
<input type="text" name="nome" required value="<?= $reserva['nome'] ?>">

<div style="display:flex; gap:12px;">
    <div style="flex:1;">
        <label>Data:</label>
        <input type="date" name="data" required value="<?= $reserva['data'] ?>">
    </div>

    <div style="flex:1;">
        <label>Horário:</label>
        <input type="time" name="horario" required value="<?= $reserva['horario'] ?>">
    </div>
</div>

<label>Nº Pessoas:</label>
<input type="number" name="num_pessoas" required value="<?= $reserva['num_pessoas'] ?>">

<label>Telefone Alternativo:</label>
<input type="text" name="telefone2" maxlength="15"
       onkeyup="maskPhone(this)"
      value="<?= preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', preg_replace('/\D/', '', $reserva['telefone2'] ?? '')) ?>"

<label>Forma de Pagamento:</label>
<select name="forma_pagamento">
    <option value="" <?= $formaPg==""?"selected":"" ?>>Selecione</option>
    <option value="unica" <?= $formaPg=="unica"?"selected":"" ?>>Única</option>
    <option value="individual" <?= $formaPg=="individual"?"selected":"" ?>>Individual</option>
    <option value="U (rod) I (beb)" <?= $formaPg=="u (rod) i (beb)"?"selected":"" ?>>Única (rod) / Individual (beb)</option>
    <option value="outros" <?= $formaPg=="outros"?"selected":"" ?>>Outros</option>
</select>

<label>Tipo de Evento:</label>
<select name="tipo_evento">
    <option value="">Selecione</option>
    <option value="Aniversario" <?= $tipoEvt=="Aniversario"?"selected":"" ?>>Aniversário</option>
    <option value="Conf. fim de ano" <?= $tipoEvt=="Conf. fim de ano"?"selected":"" ?>>Confraternização Fim de Ano</option>
    <option value="Formatura" <?= $tipoEvt=="Formatura"?"selected":"" ?>>Formatura</option>
    <option value="Casamento" <?= $tipoEvt=="Casamento"?"selected":"" ?>>Casamento</option>
    <option value="Conf. Familia" <?= $tipoEvt=="Conf. Familia"?"selected":"" ?>>Confraternização Família</option>
</select>

<label>Valor do Rodízio:</label>
<select name="valor_rodizio">
<option value="">Selecione</option>

<?php if ($ultimo_preco): ?>
<option value="<?= $ultimo_preco['almoco'] ?>" <?= $valorRod==$ultimo_preco['almoco']?"selected":"" ?>>
    Almoço - R$ <?= $ultimo_preco['almoco'] ?>
</option>
<option value="<?= $ultimo_preco['jantar'] ?>" <?= $valorRod==$ultimo_preco['jantar']?"selected":"" ?>>
    Jantar - R$ <?= $ultimo_preco['jantar'] ?>
</option>
<option value="<?= $ultimo_preco['outros'] ?>" <?= $valorRod==$ultimo_preco['outros']?"selected":"" ?>>
    Sábado - R$ <?= $ultimo_preco['outros'] ?>
</option>
<option value="<?= $ultimo_preco['domingo_almoco'] ?>" <?= $valorRod==$ultimo_preco['domingo_almoco']?"selected":"" ?>>
    Domingo Almoço - R$ <?= $ultimo_preco['domingo_almoco'] ?>
</option>
<?php endif; ?>

</select>

<label>Número da Mesa:</label>
<select name="num_mesa">
<option value="">Selecione</option>

<option value="Salão 1" <?= $numMesa=="Salão 1"?"selected":"" ?>>Salão 1</option>
<option value="Salão 2" <?= $numMesa=="Salão 2"?"selected":"" ?>>Salão 2</option>
<option value="Salão 3" <?= $numMesa=="Salão 3"?"selected":"" ?>>Salão 3</option>

<?php for ($i=1;$i<=99;$i++): ?>
<option value="<?= $i ?>" <?= $numMesa==$i?"selected":"" ?>>Mesa <?= $i ?></option>
<?php endfor; ?>
</select>

<label>Observações:</label>
<textarea name="observacoes"><?= $reserva['observacoes'] ?></textarea>

<button class="btn-ios">Salvar Alterações</button>

</form>

</div>

</body>
</html>
