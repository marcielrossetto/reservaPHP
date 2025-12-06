<?php
session_start();
require 'config.php';
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}
require 'cabecalho.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, user-scalable=no">
    <title>Relatório Jantar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .table th, .table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .header {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .id-column, .nome-column, .mesa-column {
            width: 15%;
        }
        .combined-column {
            width: 35%;
        }
        .obs-column {
            width: 35%;
        }
        .obs-column .container {
            display: block;
            width: 90%;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        @media print {
            .obs-column .container {
                display: block;
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
                word-break: break-word;
            }
            .btn {
                display: none;
            }
            .nome-column{
                width:100px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <br>
    <div class="container-fluid">
        <h3 class="text-center">Relatório Jantar</h3>
        <form method="POST" class="form-inline row justify-content-center">
            <input class="form-control mr-sm-4" name="filtro" required type="date">
            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Pesquisar</button>
        </form>
    </div>
</div>

<?php
$filtro = isset($_POST['filtro']) ? $_POST['filtro'] : "";
if ($filtro != "") {
    $dataFormatada = date('d/m/Y', strtotime($filtro));
    echo "<p>Data <strong>$dataFormatada</strong> das 18:00 às 23:59 hs</p><br>";

    $stmt = $pdo->prepare("SELECT SUM(num_pessoas) AS total FROM clientes WHERE data = :filtro AND horario BETWEEN '18:00:00' AND '23:59:00' AND status = 1");
    $stmt->execute(['filtro' => $filtro]);

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $row['total'];
        echo "<h4>Total de pessoas: $total</h4><br>";
    }
}
?>

<button class="btn btn-primary" onclick="printTable()">Imprimir Relatório</button>    <a href="adicionar_reserva.php" class="btn btn-success">
            <ion-icon name="add-circle-outline"></ion-icon> Nova Reserva
        </a>
<br>
<div id="printableTable" class="table-responsive">
    <table class="table">
        <tr class="header">
            <th class="id-column">Id</th>
            <th class="nome-column">Nome</th>
            <th class="combined-column">N° pessoas / Horário / Tipo de Evento</th>
            <th class="obs-column">Obs</th>
            <th class="mesa-column">N° mesa</th>
        </tr>
        <?php
        $sql = "SELECT * FROM clientes WHERE data = '$filtro' AND horario BETWEEN '18:00:00' AND '23:59:00' AND status = 1 ORDER BY `data` ASC";
        $sql = $pdo->query($sql);

        if ($sql->rowCount() > 0) {
            foreach ($sql->fetchAll() as $clientes) {
                $horarioFormatado = date('H:i', strtotime($clientes['horario']));
                $corId = ($clientes['confirmado'] == 1) ? 'green' : 'red';
                echo '<tr>';
                echo '<td class="id-column" style="font-weight: bold; color: ' . $corId . ';">' . $clientes['id'] . '</td>';
                echo '<td class="nome-column"><strong>' . strtoupper($clientes['nome']) . '</strong></td>';
                echo '<td class="combined-column"><strong style="font-size: 1.2em;">' . $clientes['num_pessoas'] . '<br>' . $horarioFormatado . ' - hs' . '<br>' . $clientes['forma_pagamento'] . '<strong><br></td>';
                echo '<td class="obs-column">' . htmlspecialchars($clientes['observacoes'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="mesa-column">' . $clientes['num_mesa'] . '</td>';
                echo '</tr>';
            }
        }
        
        ?>
    </table>
</div>

<script>
function printTable() {
    var printContents = document.getElementById('printableTable').innerHTML;
    var totalPessoas = "<?php echo '<h2>Total de pessoas: ' . $total . '</h2><br>'; ?>";
    var dataReserva = "<?php echo '<p>Data <strong>' . $dataFormatada . '</strong>- JANTAR</p>'; ?>";
    var originalContents = document.body.innerHTML;

    var printStyles = `
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }
            .table th, .table td {
                padding: 8px;
                border: 1px solid #ddd;
                text-align: center;
                word-wrap: break-word;
                overflow-wrap: break-word;
                word-break: break-word;
            }
            .header {
                background-color: #f9f9f9;
                font-weight: bold;
            }
            .id-column, .mesa-column {
                width: 10%;
            }
                .nome-column{
                width: 15%;
                }
            .combined-column {
                width: 25%;
            }
            .obs-column {
                width: 35%;
            }
            .obs-column .container {
                display: block;
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
                word-break: break-word;
            }
        </style>
    `;

    document.body.innerHTML = totalPessoas + dataReserva + printStyles + printContents;
    window.print();
    document.body.innerHTML = originalContents;
}
</script>

</body>
</html>
