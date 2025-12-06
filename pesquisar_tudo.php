<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';
?>

<style>
/* ESTILO GERAL */
body {
    background:#f5f6fa;
    font-family: "Poppins", sans-serif;
}

/* Barra de pesquisa */
.search-card {
    background:white;
    padding:15px;
    border-radius:12px;
    box-shadow:0 4px 14px rgba(0,0,0,0.1);
    margin-bottom:20px;
}

/* CARTÃO DA RESERVA */
.reserva-card {
    background:white;
    margin-bottom:14px;
    border-radius:14px;
    padding:14px 18px;
    display:flex;
    border-left:6px solid #ccc;
    box-shadow:0 4px 18px rgba(0,0,0,0.12);
}

/* Coluna esquerda */
.reserva-info {
    flex:1;
}

/* Nome */
.reserva-nome {
    font-weight:600;
    font-size:1.2rem;
    margin-bottom:4px;
}

/* Pessoas */
.reserva-pessoas {
    font-size:1.4rem;
    font-weight:700;
    margin-bottom:2px;
}

/* Status */
.status-novo {
    color:#f4b000;
}
.status-confirmado {
    color:#27ae60;
}
.status-cancelado {
    color:#e63946;
}
.status-outro {
    color:#555;
}

/* Caixa de observações */
.obs-box {
    margin-top:10px;
    background:#fafafa;
    border:1px solid #ddd;
    padding:8px;
    border-radius:8px;
    font-size:.85rem;
}

/* Ações */
.acoes {
    width:200px;
    display:flex;
    flex-direction:column;
    gap:8px;
}

.btn-modern {
    padding:8px;
    border:none;
    border-radius:8px;
    font-size:.85rem;
    font-weight:600;
    width:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    cursor:pointer;
}

.btn-cancelar {
    background:#f7f7f7;
    color:#333;
    border:1px solid #ddd;
}

.btn-whats {
    background:#25D366;
    color:white;
}

.btn-editar {
    background:#007aff;
    color:white;
}

.btn-sentar {
    background:#333;
    color:white;
}

/* Lateral colorida */
.borda-amarela { border-left-color:#f4b000 !important; }
.borda-verde { border-left-color:#27ae60 !important; }
.borda-vermelha { border-left-color:#e63946 !important; }
.borda-cinza { border-left-color:#ccc !important; }
</style>

<div class="container">

    <!-- PESQUISA -->
    <div class="search-card">
        <form method="POST" class="row">
            <div class="col-9">
                <input class="form-control" name="filtro_pesquisar" required type="text"
                       placeholder="Buscar cliente, telefone...">
            </div>
            <div class="col-3">
                <button class="btn btn-primary w-100">Pesquisar</button>
            </div>
        </form>
    </div>

    <a href="adicionar_reserva.php" class="btn btn-success mb-3">
        + Nova Reserva
    </a>

<?php
$filtro = isset($_POST['filtro_pesquisar']) ? $_POST['filtro_pesquisar'] : "";

$sql = $pdo->query("
    SELECT * FROM clientes 
    WHERE (nome LIKE '%$filtro%' OR telefone LIKE '%$filtro%' OR telefone2 LIKE '%$filtro%')
    AND status != 0
    ORDER BY data ASC, horario ASC
");

if ($sql->rowCount() > 0) {
    foreach ($sql->fetchAll() as $c) {

        // DEFINIR A COR DO CARTÃO
        $cor = "borda-cinza";
        $statusTexto = "Novo";

        if ($c["status"] == 1) {
            $cor = "borda-verde";
            $statusTexto = "Confirmado";
        } elseif ($c["status"] == 2) {
            $cor = "borda-amarela";
            $statusTexto = "Novo";
        } elseif ($c["status"] == 0) {
            $cor = "borda-vermelha";
            $statusTexto = "Cancelado";
        }

        // WhatsApp
        $telefone = preg_replace('/\D/', '', $c['telefone']);
        $mensagem = "Olá "
                    . ucfirst(strtolower($c['nome']))
                    . "! Confirmando sua reserva para "
                    . $c['num_pessoas'] . " pessoas no dia "
                    . date("d/m/Y", strtotime($c['data']))
                    . " às " . $c['horario'] . ".";
        $link_whats = "https://wa.me/55$telefone?text=" . urlencode($mensagem);
?>

    <!-- CARTÃO -->
    <div class="reserva-card <?= $cor ?>">

        <div class="reserva-info">
            <div class="reserva-nome"><?= htmlspecialchars($c['nome']) ?></div>
            
            <div class="reserva-pessoas" style="color:#f4b000;">
                <?= $c['num_pessoas'] ?> <span style="font-size:0.9rem;font-weight:400;">Pessoas</span>
            </div>

            <div><strong>Horário:</strong> <?= $c['horario'] ?></div>
            <div><strong>Salão:</strong> <?= $c['num_mesa'] ?></div>
            <div><strong>Status:</strong> 
                <span class="
                    <?= $c['status']==1?'status-confirmado':'' ?>
                    <?= $c['status']==2?'status-novo':'' ?>
                    <?= $c['status']==0?'status-cancelado':'' ?>
                ">
                    <?= $statusTexto ?>
                </span>
            </div>

            <div class="obs-box">
                <?= nl2br(htmlspecialchars($c['observacoes'])) ?>
            </div>
        </div>

        <!-- AÇÕES -->
        <div class="acoes">
            <a class="btn-modern btn-editar" href="editar_reserva.php?id=<?= $c['id'] ?>">
                ✏️ Editar
            </a>

            <a class="btn-modern btn-whats" target="_blank" href="<?= $link_whats ?>">
                💬 WhatsApp
            </a>

            <a class="btn-modern btn-cancelar" href="excluir_reserva.php?id=<?= $c['id'] ?>"
               onclick="return confirm('Excluir esta reserva?')">
                ❌ Cancelar
            </a>

            <a class="btn-modern btn-sentar" href="obsCliente.php?id=<?= $c['id'] ?>">
                🪑 Sentar
            </a>
        </div>

    </div>

<?php }} ?>

</div>
