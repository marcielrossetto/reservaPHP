<?php
session_start();
require 'config.php';

// Verifica se o usuário logado é master
if (!isset($_SESSION['nivel_acesso']) || $_SESSION['nivel_acesso'] !== 'master') {
    echo "<script>alert('Você não tem permissão para acessar esta página.'); window.location='index.php';</script>";
    exit;
}

if (!empty($_POST['email']) && !empty($_POST['senha'])) {
    $nome = addslashes($_POST['nome']);
    $email = addslashes($_POST['email']);
    $senha = password_hash(addslashes($_POST['senha']), PASSWORD_BCRYPT); // Usa hash mais seguro que md5

    // Verifica se o e-mail já existe na tabela master_users
    $sql = $pdo->prepare("SELECT * FROM master_users WHERE email = :email");
    $sql->bindValue(":email", $email);
    $sql->execute();

    if ($sql->rowCount() == 0) {
        // Insere na tabela master_users
        $sql = $pdo->prepare("INSERT INTO master_users (nome, email, senha) VALUES (:nome, :email, :senha)");
        $sql->bindValue(":nome", $nome);
        $sql->bindValue(":email", $email);
        $sql->bindValue(":senha", $senha);
        $sql->execute();

        echo "<script>alert('Administrador master cadastrado com sucesso!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Já existe um administrador master com este e-mail!');</script>";
    }
}
?>
