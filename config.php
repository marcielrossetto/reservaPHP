<?php
try {
    $pdo = new PDO("mysql:dbname=reservaPhpMulti;host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "FALHA: ".$e->getMessage();
    exit;
}
?>