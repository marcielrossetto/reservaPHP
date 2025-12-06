<?php
try {
    $pdo = new PDO(
        "mysql:dbname=reservaPHP;host=localhost;charset=utf8",
        "root",
        "",
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage();
    exit;
}
?>