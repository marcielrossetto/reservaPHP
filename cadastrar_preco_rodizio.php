<?php
session_start();
require 'config.php';

if (!empty($_POST['almoco']) && !empty($_POST['jantar'])) {
    $almoco = addslashes($_POST['almoco']);
    $jantar = addslashes($_POST['jantar']);
    $domingo_almoco = addslashes($_POST['domingo_almoco']);
    $outros = addslashes($_POST['outros']);

    // Inserção direta do preço no banco de dados, sem verificação
    $sql = $pdo->prepare("INSERT INTO preco_rodizio (almoco, jantar, domingo_almoco, outros) VALUES (:almoco, :jantar, :domingo_almoco, :outros)");
    $sql->bindValue(":almoco", $almoco);
    $sql->bindValue(":jantar", $jantar);
    $sql->bindValue(":domingo_almoco", $domingo_almoco);
    $sql->bindValue(":outros", $outros);
    $sql->execute();
    header("Location: cadastrar_preco_rodizio.php");
    exit; // Garantir que o código não continue após o redirecionamento
}
?>

<?php require 'cabecalho.php'; ?>
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">Cadastrar Rodízio</h5>
        </div>
        <div class="modal-body">
            <form method="POST">
                <div class="form-group">
                    Almoço: <input class="form-control" type="text" name="almoco" required>
                    Jantar: <input class="form-control" type="text" name="jantar" required>  
                    Sábado: <input class="form-control" type="text" name="outros" required><br>
                    Domingo Almoço: <input class="form-control" type="text" name="domingo_almoco" required><br>
                    <input class="btn btn-dark btn-lg" type="submit" value="Cadastrar">
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    body {
        font-family: 'Poppins', Arial, sans-serif;
        background-color: #f9f9f9;
        margin: 20px;
        padding: 0;
    }

    .modal-dialog {
        max-width: 500px;
        margin: 50px auto;
    }

    .modal-content {
        margin-top: 100px;
        border-radius: 15px;
        box-shadow: 0px 10px 30px #333;
        border: none;
        overflow: hidden;
    }

    .modal-header {
        background-color: #333;
        color: white;
        text-align: center;
        padding: 20px;
        border-bottom: none;
    }

    .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .modal-body {
        padding: 30px;
        background-color: #ffffff;
    }

    .form-group label {
        font-weight: 500;
        color: #555;
    }

    .form-control {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        font-size: 1rem;
        color: #333;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #555;
        box-shadow: 0 0 5px #555;
        outline: none;
    }

    .btn {
        background-color: #555;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn:hover {
        box-shadow: 0 5px 15px #555;
        transform: translateY(-2px);
    }

    .btn:active {
        transform: translateY(1px);
        box-shadow: 0 3px 7px rgba(108, 99, 255, 0.2);
    }
</style>

</body>
</html>

<?php require 'rodape.php'; ?>
