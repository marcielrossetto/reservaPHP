<?php
session_start();
require 'config.php';

// Segurança: Verifica se está logado
if (empty($_SESSION['mmnlogin'])) {
    echo json_encode(['success' => false, 'error' => 'Não logado']);
    exit;
}

$empresa_id = $_SESSION['empresa_id'];

// Recebe os dados
$id = (int)$_POST['id'];
$tipo = $_POST['tipo']; // 'produto', 'categoria' ou 'cardapio'
$novo_status = (int)$_POST['status'];

try {
    if ($tipo == 'produto') {
        $sql = $pdo->prepare("UPDATE menu_produtos SET status = ? WHERE id = ? AND empresa_id = ?");
        $sql->execute([$novo_status, $id, $empresa_id]);
    } elseif ($tipo == 'categoria') {
        $sql = $pdo->prepare("UPDATE menu_categorias SET status = ? WHERE id = ? AND cardapio_id IN (SELECT id FROM menu_cardapios WHERE empresa_id = ?)");
        $sql->execute([$novo_status, $id, $empresa_id]);
    } elseif ($tipo == 'cardapio') {
        $sql = $pdo->prepare("UPDATE menu_cardapios SET status = ? WHERE id = ? AND empresa_id = ?");
        $sql->execute([$novo_status, $id, $empresa_id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}