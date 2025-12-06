<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}
if (!empty($_POST['email']) && !empty($_POST['senha'])) {
    $nome = addslashes($_POST['nome']);
    $email = addslashes($_POST['email']);
    $senha = md5(addslashes($_POST['senha']));
    $sql = $pdo->prepare("SELECT * FROM login WHERE nome = :nome AND email = :email AND senha = :senha");
    $sql->bindValue(":nome", $nome);
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();
    if ($sql->rowCount() == 0) {
        $sql = $pdo->prepare("INSERT INTO login (nome, email, senha, status) VALUES(:nome, :email, :senha, 0)");
        $sql->bindValue(":nome", $nome);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha);
        $sql->execute();
        header("Location: cadastrousuario.php");
    } else {
        echo "<script>alert('Já existe este usuário cadastrado!'); window.location='index.php';</script>";
    }
}
?>

<?php require 'cabecalho.php'; ?>

<!-- Estilização com espaço superior de 120px -->
<style>
    /* Geral */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        color: #333;
        line-height: 1.6;
    }

    /* Espaço superior */
    .main-container {
        margin-top: 60px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        padding: 20px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Botões */
    .btn {
        padding: 10px 20px;
        color: #fff;
        background-color: #333;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }

    .btn:hover {
        background-color: #555;
    }

    /* Layout da tabela */
    .table-responsive {
        overflow-x: auto;
        margin-top: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }

    th {
        background-color: #333;
        color: #fff;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    /* Mensagens */
    .message {
        font-size: 14px;
        margin: 10px 0;
        color: #333;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<!-- Conteúdo principal da página -->
<div class="main-container">
    <h1 style="text-align: center;">Gerenciamento de Cadastro e Backup</h1>

    <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 20px;">
        <a href="backup.php" class="btn">Fazer Backup B.D</a>
        <a href="verBackup.php" class="btn">Ver Backup B.D</a>
    </div>

    <!-- Tabela Responsiva -->
    <div class="table-responsive">
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Senha</th>
                <th>Ações</th>
            </tr>
            <?php
            $sql = "SELECT * FROM login WHERE status = 0";
            $result = $pdo->query($sql);
            if ($result && $result->rowCount() > 0) {
                foreach ($result->fetchAll() as $login) {
                    echo '<tr>';
                    echo '<td>' . $login['id'] . '</td>';
                    echo '<td>' . $login['nome'] . '</td>';
                    echo '<td>' . $login['email'] . '</td>';
                    echo '<td>' . $login['senha'] . '</td>';
                    echo '<td><div class="btn-group"><a href="excluirUsuario.php?id=' . $login['id'] . '" class="btn" style="background-color: red;">Excluir</a></div></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5" style="text-align: center;">Nenhum registro encontrado</td></tr>';
            }
            ?>
        </table>
    </div>
</div>
