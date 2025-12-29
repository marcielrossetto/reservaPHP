<?php
session_start();
require 'config.php';
$empresa_id = $_SESSION['empresa_id'];
$data = $_GET['data'];

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE data = :d AND empresa_id = :emp AND status != 0 ORDER BY horario ASC");
$stmt->execute(['d' => $data, 'emp' => $empresa_id]);
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach($lista as $r) { $total += $r['num_pessoas']; }

echo json_encode([
    'lista' => $lista,
    'resumo' => ['total' => $total]
]);