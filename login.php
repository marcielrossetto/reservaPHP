<?php
session_start();
require 'config.php';

// Verifica se os dados do formulário foram enviados
if (!empty($_POST['email'])) {
    $email = addslashes($_POST['email']);
    $senha = md5(addslashes($_POST['senha']));

    // Prepara e executa a consulta SQL
    $sql = $pdo->prepare("SELECT * FROM login WHERE email = :email AND senha = :senha");
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();

    // Verifica se a consulta retornou algum resultado
    if ($sql->rowCount() > 0) {
        $usuario = $sql->fetch();
        $_SESSION['mmnlogin'] = $usuario['id'];
        header("Location: index.php");
        exit;
    } else {
        $erro = "Usuário ou senha incorretos.";
    }
}

require 'cabecalho.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login</h5>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="email">E-mail:</label>
                        <input 
                            class="form-control" 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Digite seu e-mail." 
                            autofocus 
                            required 
                        />
                    </div>
                    <div class="form-group mt-3">
                        <label for="senha">Senha:</label>
                        <input 
                            class="form-control" 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            placeholder="Digite sua senha." 
                            required 
                        />
                    </div>
                    <div class="form-group mt-4">
                        <input class="btn btn-secondary btn-lg w-100" type="submit" value="Entrar" />
                    </div>
                    <div class="form-group mt-2">
                        <a class="btn btn-secondary btn-lg w-100" href="senhaentrar.php">Cadastrar</a>
                    </div>
                </form>
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger mt-3" role="alert">
                        <?= htmlspecialchars($erro); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 20px;
            padding: 0;
        }

        .modal-dialog {
            max-width: 400px;
            margin: 50px auto;
        }

        .modal-content {
            margin-top: 100px;
            border-radius: 15px;
            box-shadow: 0px 10px 30px #333;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            border-bottom: none;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-body {
            padding: 30px;
            background-color: #ffffff;
        }

        .form-group label {
            font-weight: 500;
            color: #555;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            font-size: 1rem;
            color: #333;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #555;
            box-shadow: 0 0 5px #555;
            outline: none;
        }

        .btn {
            background-color: #555;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            box-shadow: 0 5px 15px #555;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(1px);
            box-shadow: 0 3px 7px rgba(108, 99, 255, 0.2);
        }

        .alert {
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 8px;
            margin-top: 15px;
            padding: 10px;
        }

        .alert-danger {
            background-color: #ffdddd;
            color: #d9534f;
            border: 1px solid #f5c6cb;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>