<?php
session_start();
require 'config.php';

// Verifica se o usuário está logado
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

require 'cabecalho.php';

// Processa os filtros do formulário
$data = $_POST['data'] ?? "";
$hora_inicio = $_POST['hora_inicio'] ?? "";
$hora_fim = $_POST['hora_fim'] ?? "";

$reservas = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "SELECT * FROM clientes WHERE 1=1";

    if ($data) {
        $query .= " AND data = '$data'";
    }
    if ($hora_inicio && $hora_fim) {
        $query .= " AND horario BETWEEN '$hora_inicio' AND '$hora_fim'";
    }

    $sql = $pdo->query($query);
    if ($sql->rowCount() > 0) {
        $reservas = $sql->fetchAll();
    }
}
?>

<div style="width: 100%; margin: 20px auto; padding: 10px;">
    <h3 style="text-align: center; font-size: 30px; margin-bottom: 20px;">Pesquisar Reservas</h3>

    <form method="POST" style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; margin-bottom: 20px;">
        <div style="flex: 1 1 200px;">
            <label style="font-size: 18px;">Data:</label>
            <input type="date" name="data" style="width: 100%; padding: 5px; font-size: 18px;">
        </div>
        <div style="flex: 1 1 200px;">
            <label style="font-size: 18px;">Horário Inicial:</label>
            <input type="time" name="hora_inicio" style="width: 100%; padding: 5px; font-size: 18px;">
        </div>
        <div style="flex: 1 1 200px;">
            <label style="font-size: 18px;">Horário Final:</label>
            <input type="time" name="hora_fim" style="width: 100%; padding: 5px; font-size: 18px;">
        </div>
        <div style="flex: 1 1 100px; display: flex; align-items: flex-end;">
            <button type="submit" style="padding: 8px 15px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 18px;">
                Pesquisar
            </button>
        </div>
    </form>

    <!-- Botão para imprimir todas as reservas -->
    <?php if (!empty($reservas)): ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <button onclick="imprimirTodas()" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 18px;">
                Imprimir Todas as Reservas
            </button>
        </div>
    <?php endif; ?>

    <div class="container-reservas">
        <?php foreach ($reservas as $reserva): ?>
            <div class="card-reserva">
                <div style="text-align: center;">
                    <img src="rossetto28.png" alt="Imagem da Reserva" style="width: 150px; height: 100px; border-radius: 8%;">
                </div>
                <h4 style="font-size: 26px; margin-bottom: 5px;">Reserva</h4>
                <p style="font-size: 18px; margin: 2px;"><strong>Nome:</strong> <?= strtoupper(htmlspecialchars($reserva['nome'])) ?></p>
                <p style="font-size: 18px; margin: 2px;"><strong>Data:</strong> <?= date('d/m/Y', strtotime($reserva['data'])) ?></p>
                <p style="font-size: 18px; margin: 2px;"><strong>Horário:</strong> <?= date('H:i', strtotime($reserva['horario'])) ?></p>
                <p style="font-size: 18px; margin: 2px;"><strong>Pessoas:</strong> <?= htmlspecialchars($reserva['num_pessoas']) ?></p>
                <p style="font-size: 18px; margin: 2px;"><strong>Pagamento:</strong> <?= htmlspecialchars($reserva['forma_pagamento']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function imprimirTodas() {
        const conteudoOriginal = document.body.innerHTML;
        const conteudoImpressao = document.querySelector('.container-reservas').innerHTML;
        
        document.body.innerHTML = conteudoImpressao;
        window.print();
        document.body.innerHTML = conteudoOriginal;
    }
</script>

<style>
    /* Container Responsivo */
    .container-reservas {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-evenly;
        gap: 15px;
        padding: 10px;
    }

    /* Estilização do Card */
    .card-reserva {
        border: 2px solid #333; /* Garantindo borda consistente */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 9cm;
        height: 12cm;
        box-sizing: border-box;
        padding: 10px;
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 16px;
        border-radius: 8px;
    }

    .card-reserva img {
        width: 150px;
        height: 100px;
        border-radius: 8%;
        margin-bottom: 10px;
    }

    /* Estilização para impressão */
    <style>
    /* ... (existing CSS styles) ... */

    @media print {
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            margin: 0;
        }

        .container-reservas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card-reserva {
            border: 2px solid black;
            padding: 10px;
            margin: 0;
            box-sizing: border-box;
            page-break-inside: avoid;
        }

        .card-reserva img {
            width: 100px;
            height: 80px;
            margin-bottom: 10px;
        }

        h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        p {
            font-size: 12px;
            margin: 2px 0;
        }
    }
</style>
</style>
