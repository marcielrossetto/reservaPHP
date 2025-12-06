<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';
?>

<meta id="viewport" name="viewport" content="width=device-width, user-scalable=no">
<div class="container-fluid my-4">
    <div class="bg-light p-4 rounded shadow-sm">
        <h2 class="text-center mb-4">Gerenciar Reservas Jantar</h2>
        <form method="POST" class="form-inline row justify-content-center mb-4">
            <div class="input-group col-md-3 col-sm-12 mb-3">
                <input class="form-control" name="filtro" type="date" value="<?= $filtro ?? '' ?>">
            </div>
            <div class="input-group col-md-2 col-sm-12 mb-3">
                <button class="btn btn-success" type="submit">Pesquisar</button>
            </div>
        </form>

        <?php
        // Captura a data do filtro
        $filtro = isset($_POST['filtro']) ? $_POST['filtro'] : "";

        // Exibe filtro aplicado
        if ($filtro != "") {
            $data_formatada = date("d/m/Y", strtotime($filtro));
            echo "<h6 class='text-center'>Data <strong>$data_formatada</strong> das 18:00 às 23:59 hs</h6><br>";
        }

        // SQL para contar o total de reservas no intervalo de horário das 12:00 às 17:59
        $sql = "SELECT SUM(num_pessoas) AS total_pessoas FROM clientes 
                WHERE data = '$filtro' AND horario BETWEEN '18:00:00' AND '23:59:00' 
                AND status != 0 
                ORDER BY `data` ASC";
        $stmt = $pdo->query($sql);
        $total_pessoas = 0;
        if ($stmt->rowCount() > 0) {
            $total_pessoas = $stmt->fetch()['total_pessoas'];
            echo "<h4 class='text-center'>Total de pessoas: <span class='badge badge-primary'>$total_pessoas</span></h4><br>";
        } else {
            echo "<h6 class='text-center'>Nenhuma reserva encontrada para esse horário.</h6><br>";
        }
        ?>

        <div class="table-responsive">
        <a href="adicionar_reserva.php" class="btn btn-success">
            <ion-icon name="add-circle-outline"></ion-icon> Nova Reserva
        </a>
            <table class="table table-bordered table-hover table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Data</th>
                        <th>N° Pessoas</th>
                        <th>Horário</th>
                        <th>Telefone</th>
                        <th>Tipo de Evento</th>
                        <th>Forma de Pagamento</th>
                        <th>Observações</th>
                        <th>Data de Emissão</th>
                        <th>Usuário ID</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // SQL para buscar as reservas no intervalo de horário das 12:00 às 17:59
                $sql = "SELECT * FROM clientes 
                        WHERE data = '$filtro' AND horario BETWEEN '18:00:00' AND '23:59:00' 
                        AND status != 0 
                        ORDER BY `data` ASC";
                $stmt = $pdo->query($sql);
                if ($stmt->rowCount() > 0) {
                    foreach ($stmt->fetchAll() as $clientes) {
                        $nome = ucwords(strtolower(htmlspecialchars($clientes['nome'])));

                        $corId = ($clientes['confirmado'] == 1) ? 'green' : 'red';
                        $nome = ucfirst(strtolower($clientes['nome']));
                        $data = htmlspecialchars(date("d/m/Y", strtotime($clientes['data'])), ENT_QUOTES, 'UTF-8');
                        $horario = htmlspecialchars(date("H:i", strtotime($clientes['horario'])), ENT_QUOTES, 'UTF-8');
                        $num_pessoas = $clientes['num_pessoas'];
                        $telefone = preg_replace('/[^0-9]/', '', $clientes['telefone']); // Remove caracteres não numéricos

                        $mensagem = "Olá $nome!, Tudo bem? Aqui é da Churrascaria ! Estamos passando para confirmar sua reserva para $num_pessoas pessoas no dia $data, às $horario hs. Se tudo estiver certo, me confirma com um OK. Agradecemos pela confiança ! Qualquer dúvida, estamos à disposição!";
                        $link_whatsapp = "https://wa.me/55$telefone?text=" . urlencode($mensagem);

                        echo '<tr>';
                        echo '<td class="id-column" style="border: 1px solid #ddd; padding: 8px; font-weight: bold; color: ' . $corId . ';">' . $clientes['id'] . ' </td>';

                        echo '<td>' . $nome . '</td>';
                        echo '<td>' . date("d/m/Y", strtotime($clientes['data'])) . '</td>';
                        echo '<td>' . $clientes['num_pessoas'] . '</td>';
                        echo '<td>' . $clientes['horario'] . '</td>';
                        echo '<td>' . $clientes['telefone'] . '</td>';
                        echo '<td>' . $clientes['tipo_evento'] . '</td>';
                        echo '<td>' . $clientes['forma_pagamento'] . '</td>';
                        echo '<td class="obs-column"><div class="obs-content">' . htmlspecialchars($clientes['observacoes']) . '</div></td>';
                        echo '<td>' . date("d/m/Y", strtotime($clientes['data_emissao'])) . '</td>';
                        echo '<td>' . htmlspecialchars($clientes['usuario_id']) . '</td>';
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>
                            <select onchange=\"window.location.href=this.value\" 
                                    style='width: 150px; padding: 5px; font-size: 0.9rem; border-radius: 5px;'>
                                <option value='' disabled selected>Escolha ação</option>
                                <option value='editar_reserva.php?id={$clientes['id']}'>Editar</option>
                                <option value='excluir_reserva.php?id={$clientes['id']}'>Excluir</option>
                                <option value='{$link_whatsapp}' target='_blank'>Confirmar</option>
                                <option value='obsCliente.php?id={$clientes['id']}'>Obs. Cliente</option>
                                <option value='ativar_reserva.php?id={$clientes['id']}'>Ativar Reserva</option>
                            </select>
                          </td>";
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="11" class="text-center">Nenhuma reserva encontrada para essa data.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* Estilo geral */
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fa;
    }

    /* Botões */
    .btn-group .btn {
        margin: 0 3px;
    }
    .btn-group .btn:hover {
        transform: scale(1.05);
        transition: all 0.3s ease;
    }

    /* Tabela */
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }
    .table thead th {
        background-color: #343a40;
        color: #fff;
    }

    /* Observações */
    .obs-content {
        max-width: 250px;
        max-height: 100px;
        overflow-y: auto;
        white-space: pre-wrap;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 5px;
        border-radius: 4px;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .btn-group .btn {
            width: 100%;
            margin: 5px 0;
        }

        .obs-content {
            max-width: 100%;
        }
    }
</style>

