<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';
?>

<meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0">

<div class="bg-light p-4 rounded shadow-sm mt-4">
    <?php
    // Verifica se há um filtro de data
    $filtro = isset($_GET['data']) ? $_GET['data'] : "";
    if ($filtro != "") {
        $data_formatada = date("d/m/Y", strtotime($filtro));
        echo "<h2 class='text-center mb-4'>Reservas para o dia: $data_formatada</h2>";
    } else {
        echo "<h2 class='text-center mb-4'>Últimas 20 Reservas</h2>";
    }
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="adicionar_reserva.php" class="btn btn-success">
            <ion-icon name="add-circle-outline"></ion-icon> Nova Reserva
        </a>
        <form method="GET" class="d-flex">
            <input type="date" name="data" class="form-control me-2" value="<?= $filtro ?? '' ?>">
            <button type="submit" class="btn btn-success">Filtrar</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover table-sm w-100">
            <thead class="thead-dark">
            <tr>
                    <th style="border: 1px solid #ddd; padding: 8px;">ID</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Nome</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Data e Hora</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">N° Pessoas</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Telefone</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Tipo de Evento</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Forma de Pagamento</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Valor</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Mesa</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Observações</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Usuário</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Motivo cancelamento</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Obs-Cliente</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Data emissão</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Ajuste na consulta SQL para ignorar status 0
                $sql = "SELECT * FROM clientes WHERE data LIKE '$filtro%' AND status != 0 ORDER BY id DESC LIMIT 20";
                $sql = $pdo->query($sql);
                if ($sql->rowCount() > 0) {
                    foreach ($sql->fetchAll() as $reserva) {
                        $corId = ($reserva['confirmado'] == 1) ? 'green' : 'red';       
                        $nome = ucwords(strtolower(htmlspecialchars($reserva['nome'])));
                        $status_class = $reserva['status'] == 0 ? 'color: red; text-decoration: line-through;' : '';
                        $telefone = preg_replace('/[^0-9]/', '', $reserva['telefone']);
                        $mensagem = "Olá " . htmlspecialchars($reserva['nome']) . "! Tudo bem? Aqui é da Churrascaria! Estamos confirmando sua reserva para " . $reserva['num_pessoas'] . " pessoas no dia " . date("d/m/Y", strtotime($reserva['data'])) . " às " . htmlspecialchars($reserva['horario']) . ". Confirme com OK. Agradecemos!";
                        $link_whatsapp = "https://wa.me/55$telefone?text=" . urlencode($mensagem);
                
                        echo "<tr style='$status_class'>";
                        echo '<td class="id-column" style="border: 1px solid #ddd; padding: 8px; font-weight: bold; color: ' . $corId . ';">' . $reserva['id'] . ' </td>';
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>$nome</td>";        
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . date('d/m/Y H:i', strtotime($reserva['data'] . ' ' . $reserva['horario'])) . "</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['num_pessoas']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['telefone']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['tipo_evento']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['forma_pagamento']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>R$-{$reserva['valor_rodizio']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['num_mesa']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>
                                <div style='max-width: 300px; max-height: 100px; overflow-y: auto; word-wrap: break-word; font-size: auto;'>{$reserva['observacoes']}</div>
                              </td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['status']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$reserva['usuario_id']}</td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>
                        <div style='max-width: 150px; max-height: 100px; overflow-y: auto; word-wrap: break-word; font-size: auto;'>{$reserva['motivo_cancelamento']}</div>
                      </td>";
                
                      echo "<td style='border: 1px solid #ddd; padding: 8px;'>
                      <div style='max-width: 150px; max-height: 100px; overflow-y: auto; word-wrap: break-word; font-size: auto;'>{$reserva['obsCliente']}</div>
                    </td>";
                      echo "<td style='border: 1px solid #ddd; padding: 8px;'>
                      <div style='max-width: 150px; max-height: 100px; overflow-y: auto; word-wrap: break-word; font-size: auto;'>{$reserva['data_emissao']}</div>
                    </td>";
                        echo "<td style='border: 1px solid #ddd; padding: 8px;'>
                                <select onchange=\"window.location.href=this.value\" 
                                        style='width: 150px; padding: 5px; font-size: 0.9rem; border-radius: 5px;'>
                                    <option value='' disabled selected>Escolha ação</option>
                                    <option value='editar_reserva.php?id={$reserva['id']}'>Editar</option>
                                    <option value='excluir_reserva.php?id={$reserva['id']}'>Excluir</option>
                                    <option value='{$link_whatsapp}' target='_blank'>Confirmar</option>
                                    <option value='obsCliente.php?id={$reserva['id']}'>Obs. Cliente</option>
                                    <option value='ativar_reserva.php?id={$reserva['id']}'>Ativar Reserva</option>
                                </select>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='13' style='border: 1px solid #ddd; padding: 8px;'>Nenhuma reserva encontrada.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Estilo geral */
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }

    .thead-dark th {
        background-color: #343a40;
        color: #fff;
    }

    /* Estilo para o campo de observação */
    .obs-content {
        max-height: 100px; /* Limita a altura do campo */
        overflow-y: auto; /* Adiciona rolagem quando necessário */
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 5px;
        border-radius: 4px;
    }
</style>
