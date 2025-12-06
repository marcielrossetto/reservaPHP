<?php
session_start();
require 'config.php';

if (!empty($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = $pdo->prepare("UPDATE login SET status = 1 WHERE id = :id");
    $sql->bindValue(":id", $id);
    $sql->execute();

    header("Location: pesquisaUsuario.php");
}
?>
