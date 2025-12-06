<?php
session_start();
require 'config.php';
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}
require 'cabecalho.php';
?>
<!-- Tabela das últimas 20 reservas -->
<div class="bg-light p-4 rounded shadow-sm mt-4">
    <h2 class="text-center mb-4">Últimas 20 Reservas</h2>
    <div class="table-responsive">
    <a href="adicionar_reserva.php" class="btn btn-success">
            <ion-icon name="add-circle-outline"></ion-icon> Nova Reserva
        </a>
        <table class="table table-striped table-bordered table-hover table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Data</th>
                    <th>N° Pessoas</th>
                    <th>Horário</th>
                    <th>Telefone</th>
                    <th>Tipo de Evento</th>
                    <th>Observação</th>
                    <th>Responsável</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT * FROM clientes ORDER BY id DESC LIMIT 20";
            $sql = $pdo->query($sql);
            if ($sql->rowCount() > 0) {
                foreach ($sql->fetchAll() as $reserva) {
                    $corId = ($reserva['confirmado'] == 1) ? 'green' : 'red';
                    $nome = ucwords(strtolower(htmlspecialchars($reserva['nome'])));
                    $telefone = preg_replace('/[^0-9]/', '', $reserva['telefone']); // Remove caracteres não numéricos
                    $mensagem = "Olá " . htmlspecialchars($reserva['nome']) . "! Tudo bem? Aqui é da Churrascaria Verdanna! Estamos passando para confirmar sua reserva para " . $reserva['num_pessoas'] . " pessoas no dia " . date("d/m/Y", strtotime($reserva['data'])) . " às " . htmlspecialchars($reserva['horario']) . ". Se tudo estiver certo, me confirma com um OK. Agradecemos pela confiança ! Qualquer dúvida, estamos à disposição!";
                    $link_whatsapp = "https://wa.me/55$telefone?text=" . urlencode($mensagem);
            
                    echo '<tr>';
                    echo '<td class="id-column" style="border: 1px solid #ddd; padding: 8px; font-weight: bold; color: ' . $corId . ';">' . $reserva['id'] . ' </td>';                    echo '<td>' . htmlspecialchars($nome) . '</td>';
                    echo '<td>' . date("d/m/Y", strtotime($reserva['data'])) . '</td>';
                    echo '<td>' . htmlspecialchars($reserva['num_pessoas']) . '</td>';
                    echo '<td>' . htmlspecialchars($reserva['horario']) . '</td>';
                    echo '<td>' . htmlspecialchars($reserva['telefone']) . '</td>';
                    echo '<td>' . htmlspecialchars($reserva['tipo_evento']) . '</td>';
                    echo '<td>
                            <div class="obs-content">
                                ' . htmlspecialchars($reserva['observacoes']) . '
                            </div>
                          </td>';
                    echo '<td>' . htmlspecialchars($reserva['usuario_id']) . '</td>';
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
                    echo '</tr>';
                }
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
<?php require 'rodape.php'; ?>