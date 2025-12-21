<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

// LÓGICA AJAX: Retorna apenas os dados se solicitado via JS
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $data = $_GET['data'] ?? date('Y-m-d');
    $periodo = $_GET['periodo'] ?? 'todos';

    $query = "SELECT * FROM clientes WHERE data = :data";
    $params = [':data' => $data];

    if ($periodo === 'almoco') {
        $query .= " AND horario BETWEEN '11:00:00' AND '16:59:59'";
    } elseif ($periodo === 'jantar') {
        $query .= " AND horario BETWEEN '17:00:00' AND '23:59:59'";
    }

    $query .= " ORDER BY horario ASC";
    $sql = $pdo->prepare($query);
    $sql->execute($params);
    echo json_encode($sql->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

require 'cabecalho.php';
?>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">

<div class="page-wrapper">
    <!-- BARRA DE PESQUISA COMPACTA (FIXA NO TOPO SE QUISER) -->
    <div class="toolbar no-print">
        <div class="search-container">
            <div class="date-wrapper">
                <span class="material-icons">calendar_today</span>
                <input type="date" id="inputData" value="<?= date('Y-m-d') ?>" onchange="buscarReservas()">
            </div>

            <div class="period-toggle">
                <button class="btn-icon active" onclick="setPeriodo('todos', this)" title="Dia Inteiro">
                    <span class="material-icons">filter_list</span>
                </button>
                <button class="btn-icon" onclick="setPeriodo('almoco', this)" title="Almoço">
                    <span class="material-icons">light_mode</span>
                </button>
                <button class="btn-icon" onclick="setPeriodo('jantar', this)" title="Jantar">
                    <span class="material-icons">dark_mode</span>
                </button>
            </div>

            <button onclick="window.print()" class="btn-icon-print" title="Imprimir">
                <span class="material-icons">print</span>
            </button>
        </div>
    </div>

    <!-- CONTAINER ONDE OS CARDS APARECEM -->
    <div id="reservas-container" class="container-reservas">
        <!-- Injetado via AJAX -->
    </div>

    <!-- LOADING SPINNER -->
    <div id="loader" class="loader" style="display:none;">Carregando...</div>
</div>

<script>
    let periodoAtual = 'todos';

    function setPeriodo(p, btn) {
        periodoAtual = p;
        document.querySelectorAll('.btn-icon').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        buscarReservas();
    }

    async function buscarReservas() {
        const data = document.getElementById('inputData').value;
        const container = document.getElementById('reservas-container');
        const loader = document.getElementById('loader');

        loader.style.display = 'block';
        container.style.opacity = '0.5';

        try {
            const response = await fetch(`?ajax=1&data=${data}&periodo=${periodoAtual}`);
            const reservas = await response.json();

            container.innerHTML = '';

            if (reservas.length === 0) {
                container.innerHTML = '<div class="empty-msg">Nenhuma reserva encontrada.</div>';
            } else {
                reservas.forEach(res => {
                    // Formatação de data e hora para exibição
                    const hora = res.horario.substring(0, 5);
                    const dataBr = data.split('-').reverse().join('/');

                    container.innerHTML += `
                        <div class="card-reserva">
                            <div class="card-header-img">
                                <img src="rossetto28.png" alt="Logo">
                            </div>
                            <div class="card-content">
                                <h2 class="res-nome">${res.nome.toUpperCase()}</h2>
                                <div class="res-destaque">
                                    <span class="big-time">${hora}</span>
                                    <span class="big-people">${res.num_pessoas} PESSOAS</span>
                                </div>
                                <div class="res-footer">
                                    <span>${dataBr}</span>
                                    <span>${res.forma_pagamento}</span>
                                    ${res.num_mesa ? `<span>MESA: ${res.num_mesa}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        } catch (error) {
            console.error("Erro ao buscar:", error);
        } finally {
            loader.style.display = 'none';
            container.style.opacity = '1';
        }
    }

    // Busca inicial ao carregar a página
    window.onload = buscarReservas;
</script>

<style>
    /* RESET E BASE */
    :root {
        --ios-blue: #007AFF;
        --card-bg: #ffffff;
        --text-main: #000000;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: #f2f2f7;
        margin: 0;
        padding-top: 20px;
    }

    .page-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 15px;
    }

    /* TOOLBAR COMPACTA */
    .toolbar {
        background: white;
        padding: 10px 20px;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        position: sticky;
        top: 85px;
        /* Ajuste conforme seu cabeçalho fixo */
        z-index: 100;
    }

    .search-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
    }

    .date-wrapper {
        display: flex;
        align-items: center;
        background: #f2f2f7;
        padding: 5px 15px;
        border-radius: 20px;
        gap: 8px;
    }

    .date-wrapper input {
        border: none;
        background: transparent;
        font-weight: 700;
        font-family: 'Inter';
        outline: none;
    }

    .period-toggle {
        display: flex;
        background: #f2f2f7;
        padding: 3px;
        border-radius: 30px;
        gap: 5px;
    }

    .btn-icon {
        border: none;
        background: transparent;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #8e8e93;
        transition: 0.2s;
    }

    .btn-icon.active {
        background: white;
        color: var(--ios-blue);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-icon-print {
        background: var(--ios-blue);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
    }

    /* GRID E CARDS GIGANTES */
    .container-reservas {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        transition: 0.3s;
    }

    .card-reserva {
        background: white;
        border: 2px solid #000;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        page-break-inside: avoid;
    }

    .card-header-img {
        padding: 10px;
        text-align: center;
        background: #fff;
        border-bottom: 1px dashed #ccc;
    }

    .card-header-img img {
        width: 120px;
    }

    .card-content {
        padding: 20px;
        text-align: center;
    }

    .res-nome {
        font-size: 28px;
        /* LETRA MAIOR */
        font-weight: 900;
        margin: 0 0 15px 0;
        line-height: 1.1;
    }

    .res-destaque {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-bottom: 20px;
    }

    .big-time {
        font-size: 45px;
        /* HORA GIGANTE */
        font-weight: 900;
        color: var(--ios-blue);
        display: block;
    }

    .big-people {
        font-size: 20px;
        font-weight: 700;
        color: #333;
    }

    .res-footer {
        display: flex;
        justify-content: center;
        gap: 15px;
        font-weight: 700;
        font-size: 14px;
        color: #666;
        border-top: 1px solid #eee;
        padding-top: 15px;
    }

    .empty-msg {
        grid-column: 1 / -1;
        text-align: center;
        padding: 50px;
        font-size: 18px;
        color: #8e8e93;
    }

    .loader {
        text-align: center;
        padding: 20px;
        font-weight: bold;
        color: var(--ios-blue);
    }

    /* IMPRESSÃO */
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white;
            padding: 0;
        }

        .page-wrapper {
            max-width: 100%;
            padding: 0;
        }

        .container-reservas {
            grid-template-columns: 1fr 1fr;
            /* 2 cards por folha */
            gap: 5px;
        }

        .card-reserva {
            border: 2px solid #000;
        }
    }

    @media (max-width: 600px) {
        .toolbar {
            border-radius: 15px;
        }

        .search-container {
            flex-direction: column;
        }

        .container-reservas {
            grid-template-columns: 1fr;
        }
    }
</style>