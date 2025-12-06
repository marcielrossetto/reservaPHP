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
    <title>Relatório Almoço</title>
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
            padding: 3px;
            border: 1px solid #ddd;
            text-align: center;
            word-wrap: break-word;
        }

        /* Definindo tamanho fixo para a coluna ID */
        .id-column {
            width: 40px;
        }

        .nome-column {
            width: 15%;
        }

        .mesa-column {
            width: 15%;
        }

        .combined-column {
            width: 30%;
        }

        .obs-column {
            width: 35%;
        }

        @media print {
            .btn {
                display: none;
            }
            .container-fluid{
                display: none;
            }
            .table{
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <br>
    <div class="container-fluid">
        <h3 class="text-center">Relatório Almoço</h3>
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
    echo "<p>Data <strong>$dataFormatada</strong> das 12:00 às 17:59 hs</p><br>";

    $stmt = $pdo->prepare("SELECT SUM(num_pessoas) AS total FROM clientes WHERE data = :filtro AND horario BETWEEN '12:00:00' AND '17:59:00' AND status = 1");
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
        <tr>
            <th class="id-column">Id</th>
            <th class="nome-column">Nome</th>
            <th class="combined-column">Quant</th>
            <th class="obs-column">Obs</th>
            <th class="mesa-column">N° mesa</th>
        </tr>
        <?php
        $sql = "SELECT * FROM clientes WHERE data = '$filtro' AND horario BETWEEN '11:00:00' AND '17:59:00' AND status = 1 ORDER BY `data` ASC";
        $sql = $pdo->query($sql);

        if ($sql->rowCount() > 0) {
            foreach ($sql->fetchAll() as $clientes) {
                $nome = ucwords(strtolower(htmlspecialchars($clientes['nome'])));
                $corId = ($clientes['confirmado'] == 1) ? 'green' : 'red';
                $horarioFormatado = date('H:i', strtotime($clientes['horario']));
                echo '<tr>';
                echo '<td class="id-column" style="font-weight: bold; color: ' . $corId . ';">' . '' . $clientes['id'] . ' </td>';
                echo '<td class="nome-column"><strong>' . strtoupper($nome) . '</strong></td>';
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
    var dataReserva = "<?php echo '<p>Data <strong>' . $dataFormatada . '</strong>- ALMOÇO</p>'; ?>";
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
