<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

// CAPTURA DADOS DA EMPRESA LOGADA
$empresa_id = $_SESSION['empresa_id'];

// BUSCA A LOGO DA EMPRESA PARA O JAVASCRIPT USAR
$sqlEmp = $pdo->prepare("SELECT logo FROM empresas WHERE id = ?");
$sqlEmp->execute([$empresa_id]);
$dadosEmp = $sqlEmp->fetch();
$logo_empresa = !empty($dadosEmp['logo']) ? 'data:image/jpeg;base64,' . base64_encode($dadosEmp['logo']) : 'rossetto28.png';

// LÓGICA AJAX: Retorno rápido filtrado por empresa
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $data = $_GET['data'] ?? date('Y-m-d');
    $periodo = $_GET['periodo'] ?? 'todos';

    // Query com filtro de empresa_id
    $query = "SELECT * FROM clientes WHERE data = :data AND empresa_id = :emp_id";
    $params = [':data' => $data, ':emp_id' => $empresa_id];

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
    <div class="toolbar no-print">
        <div class="search-container">
            <div class="date-wrapper">
                <span class="material-icons">calendar_today</span>
                <input type="date" id="inputData" value="<?= date('Y-m-d') ?>" onchange="buscarReservas()">
            </div>

            <div class="period-toggle">
                <button class="btn-icon active" onclick="setPeriodo('todos', this)" title="Todos">
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

    <div id="reservas-container" class="container-reservas">
        <!-- JS injeta os cards aqui -->
    </div>

    <div id="loader" class="loader" style="display:none;">Sincronizando...</div>
</div>

<script>
    let periodoAtual = 'todos';
    // Logo da empresa vinda do PHP
    const logoEmpresa = "<?= $logo_empresa ?>";

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
        container.style.opacity = '0.3';

        try {
            const response = await fetch(`?ajax=1&data=${data}&periodo=${periodoAtual}`);
            const reservas = await response.json();

            container.innerHTML = '';

            if (reservas.length === 0) {
                container.innerHTML = '<div class="empty-msg">Nenhuma reserva.</div>';
            } else {
                reservas.forEach(res => {
                    const hora = res.horario.substring(0, 5);
                    const dataBr = data.split('-').reverse().join('/');
                    const numeroMesa = res.num_mesa ? res.num_mesa : '';

                    container.innerHTML += `
                        <div class="card-reserva">
                            <div class="card-header-mini">
                                <img src="${logoEmpresa}" alt="Logo">
                            </div>
                            <div class="card-main">
                                <h2 class="res-nome">${res.nome.toUpperCase()}</h2>
                                
                                <div class="res-middle">
                                    <span class="res-time">${hora}</span>
                                    <span class="res-people">${res.num_pessoas} PESSOAS</span>
                                </div>

                                <div class="res-info">
                                    <p>${dataBr}</p>
                                    <p>${res.forma_pagamento}</p>
                                </div>

                                <div class="mesa-manual-central">
                                    <small>MESA</small>
                                    <div class="square">
                                        ${numeroMesa}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
        } catch (error) { console.error(error); }
        finally {
            loader.style.display = 'none';
            container.style.opacity = '1';
        }
    }

    window.onload = buscarReservas;
</script>

<style>
    /* TODO O SEU CSS FOI MANTIDO EXATAMENTE IGUAL */
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f2f2f7;
        margin: 0;
    }

    .page-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 10px;
    }

    .toolbar {
        background: white;
        padding: 10px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        margin-top: 85px;
        margin-bottom: 20px;
        position: sticky;
        top: 80px;
        z-index: 1000;
    }

    .search-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .date-wrapper {
        display: flex;
        align-items: center;
        background: #f2f2f7;
        padding: 6px 12px;
        border-radius: 10px;
        gap: 8px;
    }

    .date-wrapper input {
        border: none;
        background: transparent;
        font-weight: 700;
        outline: none;
        font-size: 14px;
        color: #333;
    }

    .period-toggle {
        display: flex;
        background: #f2f2f7;
        padding: 4px;
        border-radius: 10px;
        gap: 4px;
    }

    .btn-icon {
        border: none;
        background: transparent;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        cursor: pointer;
        color: #8e8e93;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-icon.active {
        background: white;
        color: #007AFF;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .btn-icon-print {
        background: #007AFF;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .container-reservas {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 15px;
    }

    .card-reserva {
        background: white;
        border: 2px solid #000;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        page-break-inside: avoid;
        text-align: center;
        height: 100%;
    }

    .card-header-mini {
        padding: 3px;
        background: #6f6f70ff;
        border-bottom: 1px solid #000;
        line-height: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 45px;
    }

    .card-header-mini img {
        height: 40px;
        width: auto;
        object-fit: contain;
    }

    .card-main {
        padding: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        flex-grow: 1;
    }

    .res-nome {
        font-size: 18px;
        font-weight: 900;
        margin: 0 0 5px 0;
        color: #000;
        line-height: 1.1;
        width: 100%;
        word-wrap: break-word;
    }

    .res-middle {
        margin-bottom: 8px;
    }

    .res-time {
        font-size: 16px;
        font-weight: 400;
        color: #000;
        display: block;
    }

    .res-people {
        font-size: 13px;
        font-weight: 700;
        color: #444;
    }

    .res-info {
        font-size: 11px;
        color: #666;
        font-weight: 600;
        line-height: 1.2;
        margin-bottom: 10px;
        border-top: 1px solid #eee;
        padding-top: 5px;
        width: 100%;
    }

    .res-info p {
        margin: 2px 0;
    }

    .mesa-manual-central {
        margin-top: auto;
        padding-bottom: 5px;
    }

    .mesa-manual-central small {
        font-size: 9px;
        font-weight: 900;
        display: block;
        margin-bottom: 2px;
    }

    .square {
        width: 55px;
        height: 55px;
        border: 2px solid #000;
        background: white;
        border-radius: 6px;
        margin: 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 2px;
        font-size: 12px;
        font-weight: 900;
        color: #000;
    }

    .loader {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: #fff;
        padding: 10px 20px;
        border-radius: 20px;
        z-index: 2000;
    }

    .empty-msg {
        grid-column: 1/-1;
        text-align: center;
        padding: 60px;
        color: #888;
        font-size: 18px;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        @page {
            size: A4 portrait;
            margin: 0.5cm;
        }

        body {
            background: white;
            padding: 0;
        }

        .page-wrapper {
            max-width: 100%;
            padding: 0;
            margin: 0;
        }

        .container-reservas {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 2px;
            width: 100%;
            height: 78vh;
        }

        .card-reserva {
            border: 2px solid #000;
            height: 100%;
            border-radius: 20px;
        }

        .res-nome {
            font-size: 15px;
        }

        .res-time {
            font-size: 14px;
        }

        .square {
            width: 50px;
            height: 50px;
            font-size: 10px;
            padding-top: 1px;
        }
    }
</style>