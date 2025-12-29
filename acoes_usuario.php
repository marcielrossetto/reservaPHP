<?php
session_start();
require_once 'config.php';

// Ativa exibição de erros para debug durante o desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['mmnlogin']) || $_SESSION['nivel'] !== 'master') {
    header("Location: login.php");
    exit;
}

$empresa_id = $_SESSION['empresa_id'];
$acao = $_REQUEST['acao'] ?? '';

// --- AÇÃO DE CADASTRAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (empty($acao) || $acao == 'cadastrar')) {
    
    $nome  = trim(addslashes($_POST['nome']));
    $email = trim(strtolower(addslashes($_POST['email'])));
    $senha = $_POST['senha'];
    $nivel = $_POST['nivel'];

    // Validação básica
    if (empty($nome) || empty($email) || empty($senha)) {
        echo "<script>alert('Preencha todos os campos!'); window.history.back();</script>";
        exit;
    }

    // Verifica se e-mail já existe
    $check = $pdo->prepare("SELECT id FROM login WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        echo "<script>alert('ERRO: Este e-mail já está cadastrado!'); window.history.back();</script>";
        exit;
    }

    try {
        // SALVAMENTO COM SENHA_TEXTO PARA O PAINEL MASTER
        $sql = $pdo->prepare("INSERT INTO login (empresa_id, nome, email, senha, senha_texto, nivel, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
        
        $sucesso = $sql->execute([
            $empresa_id,
            $nome,
            $email,
            md5($senha),
            $senha, // Senha em texto puro
            $nivel
        ]);

        if ($sucesso) {
            header("Location: index.php?status=usuario_criado");
            exit;
        }

    } catch (PDOException $e) {
        die("Erro ao salvar no Banco de Dados: " . $e->getMessage());
    }
}

// --- AÇÃO DE EXCLUIR ---
if ($acao === 'excluir' && isset($_GET['id'])) {
    $id_excluir = (int)$_GET['id'];
    $meu_id = $_SESSION['mmnlogin'];

    // Segurança: Bloqueia excluir a si mesmo e garante que é da mesma empresa
    $sql = $pdo->prepare("UPDATE login SET status = 0 WHERE id = ? AND empresa_id = ? AND id != ?");
    $sql->execute([$id_excluir, $empresa_id, $meu_id]);

    header("Location: index.php?status=usuario_removido");
    exit;
}