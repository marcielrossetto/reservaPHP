<?php
session_start();
require 'config.php';

// Bloqueia se não for Master
if ($_SESSION['nivel'] !== 'master') {
    die("Acesso negado. Apenas o Master da empresa pode gerenciar usuários.");
}

if (!empty($_POST['email'])) {
    $nome = addslashes($_POST['nome']);
    $email = addslashes($_POST['email']);
    $senha = md5($_POST['senha']);
    $nivel = $_POST['nivel']; // master ou operacional
    
    // O empresa_id é SEMPRE o mesmo do Master logado
    $empresa_id = $_SESSION['empresa_id'];

    $sql = $pdo->prepare("INSERT INTO login (empresa_id, nome, email, senha, nivel) VALUES (?, ?, ?, ?, ?)");
    $sql->execute([$empresa_id, $nome, $email, $senha, $nivel]);
    echo "Usuário cadastrado com sucesso!";
}
?>
<!-- Formulário de cadastro de funcionários -->