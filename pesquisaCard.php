<?php
session_start();
require 'config.php';
require 'cabecalho.php';



if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

/**
 * Função para criar filtros baseados nos inputs.
 */
function criarFiltros($data_inicio, $data_fim, $hora_inicio, $hora_fim, $busca)
{
    $filtros = [];

    if ($data_inicio && $data_fim) {
        $filtros[] = "data BETWEEN '$data_inicio' AND '$data_fim'";
    }
    if ($hora_inicio && $hora_fim) {
        $filtros[] = "horario BETWEEN '$hora_inicio' AND '$hora_fim'";
    }
    if ($busca) {
        $filtros[] = "(nome LIKE '%$busca%' OR telefone LIKE '%$busca%')";
    }

    return $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';
}

/**
 * Função para formatar o número de pessoas no padrão brasileiro.
 */
function formatarNumeroBrasileiro($numero)
{
    return number_format($numero, 0, ',', '.');
}

/**
 * Função para exibir os resultados de reservas.
 */
function exibirReservas($sql)
{
    $i = 0; // Contador para identificar os cards pares

    if ($sql->rowCount() > 0) {
        foreach ($sql->fetchAll() as $reserva) {
            $i++; // Incrementa o contador a cada card
            $nome = ucwords(strtolower(htmlspecialchars($reserva['nome'])));
            $status_class = $reserva['status'] == 0 ? 'color: red; text-decoration: line-through;' : '';
            $telefone = preg_replace('/[^0-9]/', '', $reserva['telefone']);
            $mensagem = "Olá " . htmlspecialchars($reserva['nome']) . "! Tudo bem? Aqui é da Churrascaria! Estamos confirmando sua reserva para " . $reserva['num_pessoas'] . " pessoas no dia " . date("d/m/Y", strtotime($reserva['data'])) . " às " . htmlspecialchars($reserva['horario']) . ". Confirme com OK. Agradecemos!";
            $link_whatsapp = "https://wa.me/55$telefone?text=" . urlencode($mensagem);

            // Define a sombra maior para os cards pares
            $sombra = $i % 2 === 0 ? '0 8px 15px rgba(0, 0, 0, 0.8)' : '0 2px 5px rgba(0, 0, 0, 0.8)';
            ?>
            <div
                style="border: 1px solid #ddd;margin-top: 60px; border-radius: 10px; padding: 15px; width: 300px; box-shadow: <?= $sombra; ?>; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h4 style="<?= $status_class; ?> white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                        title="<?= htmlspecialchars($reserva['nome']); ?>">
                        <?= htmlspecialchars($nome); ?>
                    </h4>
                    <p><strong>ID:</strong> <?= htmlspecialchars($reserva['id']); ?></p>
                    <div style="display: flex; align-items: center; gap: 10px;">
    <p style="margin: 0;"><strong>Data:</strong></p>
    <div 
        contenteditable="true" 
        onblur="salvarData(<?= $reserva['id']; ?>, this.innerText)" 
        style="max-width: 150px; padding: 5px; border: 1px solid #ccc; border-radius: 5px; text-align: center;">
        <?= date('d/m/Y', strtotime($reserva['data'])); ?>
    </div>
</div>


<div style="display: flex; align-items: center; gap: 10px;">
    <p style="margin: 0;"><strong>N° Pessoas:</strong></p>
    <div 
        contenteditable="true" 
        onblur="salvarNumPessoas(<?= $reserva['id']; ?>, this.innerText)" 
        style="max-width: 100px; padding: 5px; border: 1px solid #ccc; border-radius: 5px; text-align: center;">
        <?= formatarNumeroBrasileiro($reserva['num_pessoas']); ?>
    </div>
</div>


                    
                    <p><strong>Hora:</strong> <?= htmlspecialchars(substr($reserva['horario'], 0, 
                    5)); ?></p>
                    <p><strong>Telefone:</strong> <?= htmlspecialchars($reserva['telefone']); ?></p>
                    <p><strong>Tipo de Evento:</strong> <?= htmlspecialchars($reserva['tipo_evento']); ?></p>

                    <p><strong>Observação:</strong></p>
                    <div 
                        contenteditable="true" 
                        onblur="salvarObservacao(<?= $reserva['id']; ?>, this.innerText)" 
                        style="max-height: 100px; overflow-y: auto; padding: 5px; border: 1px solid #ccc; border-radius: 5px;">
                        <?= nl2br(htmlspecialchars($reserva['observacoes'])); ?>
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <select onchange="window.location.href=this.value"
                        style="width: 100%; padding: 5px; font-size: 0.9rem; border-radius: 5px;">
                        <option value='' disabled selected>Escolha ação</option>
                        <option value='editar_reserva.php?id=<?= $reserva['id']; ?>'>Editar</option>
                        <option value='excluir_reserva.php?id=<?= $reserva['id']; ?>'>Excluir</option>
                        <option value='<?= $link_whatsapp; ?>'>Confirmar</option>
                        <option value='obsCliente.php?id=<?= $reserva['id']; ?>'>Obs. Cliente</option>
                        <option value='ativar_reserva.php?id=<?= $reserva['id']; ?>'>Ativar Reserva</option>
                    </select>
                </div>
            </div>
            <?php
        }
    } else {
        echo "<p style='text-align: center;'>Nenhuma reserva encontrada.</p>";
    }
}

/**
 * Processamento do formulário de busca.
 */
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fim = $_POST['hora_fim'] ?? '';
$busca = $_POST['busca'] ?? '';

// Criação dos filtros para a consulta
$filtros = criarFiltros($data_inicio, $data_fim, $hora_inicio, $hora_fim, $busca);

// Consulta principal
$query = "SELECT * FROM clientes $filtros ORDER BY id DESC";
$sql = $pdo->query($query);

// Total de pessoas
$total_pessoas_query = "SELECT SUM(num_pessoas) as total FROM clientes $filtros";
$total_pessoas = $pdo->query($total_pessoas_query)->fetch()['total'] ?? 0;
$total_pessoas_formatado = formatarNumeroBrasileiro($total_pessoas);

// Exibindo os filtros aplicados
$filtro_aplicado = "";
if ($data_inicio && $data_fim)
    $filtro_aplicado .= "Período: $data_inicio até $data_fim. ";
if ($hora_inicio && $hora_fim)
    $filtro_aplicado .= "Horário: $hora_inicio até $hora_fim. ";
if ($busca)
    $filtro_aplicado .= "Busca por: " . htmlspecialchars($busca) . ". ";

// Processamento de atualização de número de pessoas ou observação
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id']) && isset($_POST['num_pessoas'])) {
        $id = $_POST['id'];
        $num_pessoas = filter_var($_POST['num_pessoas'], FILTER_SANITIZE_NUMBER_INT);
        
        if (is_numeric($num_pessoas) && $num_pessoas > 0) {
            $stmt = $pdo->prepare("UPDATE clientes SET num_pessoas = :num_pessoas WHERE id = :id");
            $stmt->bindParam(':num_pessoas', $num_pessoas);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo 'Número de pessoas atualizado com sucesso!';
            } else {
                echo 'Erro ao atualizar o número de pessoas.';
            }
        }
    } elseif (isset($_POST['id']) && isset($_POST['observacao'])) {
        $id = $_POST['id'];
        $observacao = filter_var($_POST['observacao'], FILTER_SANITIZE_STRING);
        
        $stmt = $pdo->prepare("UPDATE clientes SET observacoes = :observacao WHERE id = :id");
        $stmt->bindParam(':observacao', $observacao);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            echo 'Observação salva com sucesso!';
        } else {
            echo 'Erro ao salvar a observação.';
        }
    }
}

?>

<!-- Botão para abrir o menu lateral -->
<div id="searchIcon" style="position: fixed; top: 20px; left: 15px; cursor: pointer; font-size: 24px; z-index: 1000;margin-top:50px;">
    🔍 <!-- Ícone de lupa -->
</div>



<!-- Menu lateral que se desliza -->
<div id="menuLateral" style="width: 0; height: 100%; background-color: #333; position: fixed; top: 0; left: 0; 
    z-index: 1000; overflow-x: hidden; transition: 0.3s; padding-top: 60px; display: flex; flex-direction: column;">
    
    <a href="javascript:void(0)" id="closeMenu" style="color: white; font-size: 36px; margin-left: auto; padding: 10px; cursor: pointer;">
        &times; <!-- Símbolo de fechar -->
    </a>

    <h3 style="color: white; text-align: center;">Pesquisar Reservas</h3>
    <form method="POST"
        style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; margin-bottom: 20px; padding: 10px;">
        <div style="flex: 1 1 200px;">
            <label>Data Inicial:</label>
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio); ?>" style="width: 100%; padding: 5px;border-radius: 10px;">
        </div>
        <div style="flex: 1 1 200px;">
            <label>Data Final:</label>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim); ?>" style="width: 100%; padding: 5px;border-radius: 10px;">
        </div>
        <div style="flex: 1 1 200px;">
            <label>Horário Inicial:</label>
            <input type="time" name="hora_inicio" value="<?= htmlspecialchars($hora_inicio); ?>" style="width: 100%; padding: 5px;border-radius: 10px;">
        </div>
        <div style="flex: 1 1 200px;">
            <label>Horário Final:</label>
            <input type="time" name="hora_fim" value="<?= htmlspecialchars($hora_fim); ?>" style="width: 100%; padding: 5px;border-radius: 10px;">
        </div>
        <div style="flex: 1 1 300px;">
            <label>Buscar</label>
            <input type="text" name="busca" value="<?= htmlspecialchars($busca); ?>" placeholder="Digite nome ou telefone" style="width: 100%; padding: 5px;border-radius: 10px;">
        </div>
        <div style="flex: 1 1 100px; display: flex; align-items: flex-end;">
            <button type="submit" style="padding: 8px 15px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Pesquisar
            </button>
        </div>
    </form>
</div>



    <h5 style="text-align: center;">Total: <?= $total_pessoas_formatado; ?></h5>
    <?php if ($filtro_aplicado): ?>
        <h5 style="text-align: center;"><?= $filtro_aplicado; ?></h5>
    <?php endif; ?>

    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
        <?php exibirReservas($sql); ?>
    </div>
</div>

<script>
    function salvarData(reservaId, data) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            alert('Data atualizada com sucesso!');
        } else {
            alert('Erro ao atualizar a data.');
        }
    };
    xhr.send('id=' + reservaId + '&data=' + encodeURIComponent(data));
}
 function salvarData(reservaId, data) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
        if (xhr.status === 200) {
            alert('Data atualizada com sucesso!');
        } else {
            alert('Erro ao atualizar a data.');
        }
    };
    xhr.send('id=' + reservaId + '&data=' + encodeURIComponent(data));
}

document.getElementById('searchIcon').addEventListener('click', function() {
        document.getElementById('menuLateral').style.width = '300px';
    });

    // Função para fechar o menu lateral
    document.getElementById('closeMenu').addEventListener('click', function() {
        document.getElementById('menuLateral').style.width = '0';
    });
    function salvarObservacao(reservaId, observacao) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Observação salva com sucesso!');
            } else {
                alert('Erro ao salvar a observação.');
            }
        };
        xhr.send('id=' + reservaId + '&observacao=' + encodeURIComponent(observacao));
    }

    function salvarNumPessoas(reservaId, numPessoas) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Número de pessoas atualizado com sucesso!');
            } else {
                alert('Erro ao atualizar o número de pessoas.');
            }
        };
        xhr.send('id=' + reservaId + '&num_pessoas=' + encodeURIComponent(numPessoas));
    }
</script>
