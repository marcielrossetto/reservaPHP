<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['mmnlogin'])) {
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

if (isset($_FILES['nova_logo']) && !empty($_FILES['nova_logo']['tmp_name'])) {

    // Verifica se o arquivo tem erro de upload
    if ($_FILES['nova_logo']['error'] !== UPLOAD_ERR_OK) {
        die("Erro no upload do arquivo. Talvez seja grande demais para o seu servidor PHP.");
    }

    // Opcional: Limitar tamanho no PHP (ex: máximo 2MB)
    if ($_FILES['nova_logo']['size'] > 2 * 1024 * 1024) {
        echo "<script>alert('A imagem é muito grande! Use uma foto de até 2MB.'); window.location.href='index.php';</script>";
        exit;
    }

    try {
        $image = file_get_contents($_FILES['nova_logo']['tmp_name']);

        $up = $pdo->prepare("UPDATE empresas SET logo = :img WHERE id = :id");

        // Usar o tipo binário explicitamente para evitar erros de conversão
        $up->bindParam(':img', $image, PDO::PARAM_LOB);
        $up->bindParam(':id', $empresa_id, PDO::PARAM_INT);

        if ($up->execute()) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    } catch (PDOException $e) {
        // Se o erro do pacote persistir, ele cairá aqui em vez de dar Erro Fatal
        if ($e->getCode() == '08S01') {
            echo "<script>alert('Erro: A imagem ainda é muito grande para o Banco de Dados. Tente uma imagem menor.'); window.location.href='index.php';</script>";
        } else {
            echo "Erro no banco: " . $e->getMessage();
        }
        exit;
    }
} else {
    header("Location: index.php");
}