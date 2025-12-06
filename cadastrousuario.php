<?php
session_start();
require 'config.php';
require 'cabecalho.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$status = ''; // Variável para definir o status da operação

if (!empty($_POST['email']) && !empty($_POST['senha']) && isset($_POST['status'])) {
    $nome = addslashes($_POST['nome']);
    $email = addslashes($_POST['email']);
    $senha = md5(addslashes($_POST['senha']));
    $status_usuario = intval($_POST['status']); // Captura o valor do status

    $sql = $pdo->prepare("SELECT * FROM login WHERE email = :email");
    $sql->bindValue(":email", $email);
    $sql->execute();

    if ($sql->rowCount() == 0) {
        $sql = $pdo->prepare("INSERT INTO login (nome, email, senha, status) VALUES (:nome, :email, :senha, :status)");
        $sql->bindValue(":nome", $nome);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha);
        $sql->bindValue(":status", $status_usuario); // Adiciona o status ao SQL
        $sql->execute();

        $status = 'success';
        $_SESSION['mensagem'] = "Usuário cadastrado com sucesso!";
    } else {
        $status = 'error';
        $_SESSION['mensagem'] = "Já existe este usuário cadastrado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f9f9f9;
        }
        .modal-dialog {
            max-width: 400px;
            margin: 50px auto;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0px 10px 30px #333;
        }
        .modal-header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            border-bottom: none;
            border-radius: 12px 12px 0px 0px;
        }
        .modal-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
        }
        .btn {
            border-radius: 8px;
            padding: 12px 20px;
            color: #fff;
            background-color: #555;
        }
        .btn:hover {
            box-shadow: 0 5px 15px #555;
        }
    </style>
</head>
<body>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastrar Usuário</h5>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome:</label>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite seu nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail:</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Digite seu e-mail" required>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha:</label>
                        <input type="password" id="senha" name="senha" class="form-control" placeholder="Digite sua senha" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Tipo de Usuário:</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="0">Usuário</option>
                            <option value="1">Master</option>
                        </select>
                    </div>
                    <button type="submit" class="btn w-100">Cadastrar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmação -->
    <?php if ($status === 'success' || $status === 'error'): ?>
        <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?= $status === 'success' ? 'Sucesso' : 'Erro'; ?></h5>
                    </div>
                    <div class="modal-body">
                        <?= htmlspecialchars($_SESSION['mensagem']); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        </script>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
