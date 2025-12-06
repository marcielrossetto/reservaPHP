<?php
session_start();
require 'config.php';

// Certifique-se de que nada foi enviado antes do redirecionamento
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

// Verifica se o ID foi passado na URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    // Se o formulário foi enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $motivos_cancelamento = $_POST['motivo_cancelamento']; // Recebe os motivos selecionados

        if (!empty($motivos_cancelamento)) {
            try {
                // Atualiza o banco de dados com o motivo do cancelamento
                $sql = $pdo->prepare("UPDATE clientes SET status = 0, motivo_cancelamento = :motivo WHERE id = :id");
                $sql->bindValue(':motivo', implode(', ', $motivos_cancelamento)); // Salva os motivos como uma string
                $sql->bindValue(':id', $id);
                $sql->execute();

                // Mensagem de sucesso
                $_SESSION['mensagem'] = "<div class='alert alert-success container' role='alert'>Reserva cancelada com sucesso!</div>";
                
                // Redireciona para a página inicial (index.php) com um atraso para mostrar o alerta
                echo "<script>
                        alert('Reserva cancelada com sucesso!');
                        window.location.href = 'excluir_reserva.php';
                      </script>";
                exit;
            } catch (PDOException $e) {
                // Captura qualquer erro e exibe uma mensagem
                $_SESSION['mensagem'] = "<div class='alert alert-danger container' role='alert'>Erro ao cancelar a reserva: " . $e->getMessage() . "</div>";
            }
        } else {
            $_SESSION['mensagem'] = "<div class='alert alert-danger container' role='alert'>Por favor, selecione um ou mais motivos para o cancelamento.</div>";
        }
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelar Reserva</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Inclui o jQuery e o Bootstrap JS para o modal -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h2>Cancelar Reserva</h2>
            </div>
            <div class="card-body">
                <!-- Exibe mensagens de erro ou sucesso -->
                <?php
                if (isset($_SESSION['mensagem'])) {
                    echo $_SESSION['mensagem'];
                    unset($_SESSION['mensagem']); // Limpa a mensagem após exibir
                }
                ?>
                
                <!-- Botão para abrir o modal (já aparece ao carregar a página) -->
                <script>
                    $(document).ready(function() {
                        $('#cancelamentoModal').modal('show');
                    });
                </script>

                <!-- Modal -->
                <div class="modal fade" id="cancelamentoModal" tabindex="-1" role="dialog" aria-labelledby="cancelamentoModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="cancelamentoModalLabel">Selecione o motivo do cancelamento</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="motivo_cancelamento">Motivos de Cancelamento</label>
                                        <select name="motivo_cancelamento[]" class="form-control" multiple required>
                                            <option value="Mudança de planos">Mudança de planos</option>
                                            <option value="Problemas de saúde">Problemas de saúde</option>
                                            <option value="Problemas financeiros">Problemas financeiros</option>
                                            <option value="Falecimento na família">Falecimento na família</option>
                                            <option value="Erro na reserva">Erro na reserva</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                                    <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
