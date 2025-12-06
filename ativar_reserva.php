<?php
session_start();
require 'config.php';

// Certifique-se de que nada foi enviado antes do redirecionamento
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$status = '';

// Verifica se o ID foi passado na URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = addslashes($_GET['id']);
    $sql = "UPDATE clientes SET status = 1 WHERE id = '$id'";
    $pdo->query($sql);

    // Define status de sucesso
    $status = 'success';
} else {
    header("Location: excluir_reserva.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar Reserva</title>
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
                <h2>Ativar Reserva</h2>
            </div>
            <div class="card-body">
                <!-- Exibe mensagens de erro ou sucesso -->
                <?php
                if ($status === 'success') {
                    echo "<div class='alert alert-success container' role='alert'>Reserva ativada com sucesso!</div>";
                }
                ?>

                <!-- Modal -->
                <script>
                    $(document).ready(function() {
                        // Abre o modal assim que a página carregar, caso a reserva tenha sido ativada
                        <?php if ($status === 'success') { ?>
                            $('#ativacaoModal').modal('show');
                        <?php } ?>

                        // Redireciona para pesquisar.php após o fechamento do modal
                        $('#ativacaoModal').on('hidden.bs.modal', function () {
                            window.location.href = 'pesquisar.php';
                        });
                    });
                </script>

                <!-- Modal -->
                <div class="modal fade" id="ativacaoModal" tabindex="-1" role="dialog" aria-labelledby="ativacaoModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="ativacaoModalLabel">Reserva Ativada</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                A reserva foi ativada com sucesso!
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
