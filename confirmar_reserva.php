<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado, caso contrário, redireciona para a página de login
if (!isset($_SESSION['mmnlogin']) || $_SESSION['mmnlogin'] == '') {
    header("Location: login.php");
    exit;
}

require 'config.php';


// Função para atualizar o status via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_status') {
    $id_reserva = $_POST['id'];
    $confirmado = $_POST['confirmado'];

    $update_query = "UPDATE clientes SET confirmado = :confirmado WHERE id = :id";
    $stmt = $pdo->prepare($update_query);
    $stmt->bindParam(':confirmado', $confirmado, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id_reserva, PDO::PARAM_INT);

    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

// Função para buscar reservas
function buscarReservas($pdo, $nome_ou_telefone, $data_reserva) {
    $query = "SELECT * FROM clientes WHERE 1";

    if ($nome_ou_telefone) {
        $query .= " AND (nome LIKE :busca OR telefone LIKE :busca)";
    }

    if ($data_reserva) {
        $query .= " AND data = :data_reserva";
    }

    $query .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($query);

    if ($nome_ou_telefone) {
        $stmt->bindValue(':busca', "%" . $nome_ou_telefone . "%");
    }

    if ($data_reserva) {
        $stmt->bindValue(':data_reserva', $data_reserva);
    }

    $stmt->execute();
    return $stmt->fetchAll();
}

// Parâmetros de busca
$nome_ou_telefone = $_GET['nome_ou_telefone'] ?? '';
$data_reserva = $_GET['data_reserva'] ?? '';
$reservas = buscarReservas($pdo, $nome_ou_telefone, $data_reserva);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Reserva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
    <a href="index.php" class="btn btn-secondary mb-4">Voltar</a>
    <h3>Pesquisar e Confirmar Reserva</h3>

        <!-- Formulário de Busca -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="nome_ou_telefone" class="form-control" placeholder="Nome ou Telefone" value="<?= htmlspecialchars($nome_ou_telefone); ?>">
                </div>
                <div class="col-md-4">
                    <input type="date" name="data_reserva" class="form-control" value="<?= htmlspecialchars($data_reserva); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </div>
        </form>

        <a href="adicionar_reserva.php" class="btn btn-success mb-4">Nova Reserva</a>
        
        <?php if (count($reservas) > 0): ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Número de Pessoas</th>
                        <th>Horário</th>
                        <th>Confirmado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <?php 
                            $telefone = preg_replace('/[^0-9]/', '', $reserva['telefone']);
                            $mensagem = "Olá " . htmlspecialchars($reserva['nome']) . "! Estamos entrando em contato para confirmar sua reserva no dia " . date("d/m/Y", strtotime($reserva['data'])) . " às " . $reserva['horario'] . " para " . $reserva['num_pessoas'] . " pessoas. Lembramos que sua reserva tem uma tolerância de 20 minutos para a chegada de todos os convidados. Após esse período, os lugares não ocupados poderão ser disponibilizados para outros clientes que estiverem aguardando. Caso a reserva ultrapasse o tempo de tolerância, ela será transferida automaticamente para a lista de espera.Agradecemos pela compreensão e preferência!";
                            $link_whatsapp = "https://wa.me/55$telefone?text=" . urlencode($mensagem);
                        ?>
                        <tr>
                            <td><?= $reserva['id']; ?></td>
                            <td><?= htmlspecialchars($reserva['nome']); ?></td>
                            <td><?= $reserva['num_pessoas']; ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($reserva['data'] . ' ' . $reserva['horario'])); ?></td>
                           <td style="display: flex; justify-content: center; align-items: center;">
    <button class="btn <?= $reserva['confirmado'] == 1 ? 'btn-success' : 'btn-danger'; ?>" 
            onclick="atualizarStatus(<?= $reserva['id']; ?>, <?= $reserva['confirmado'] == 1 ? 0 : 1; ?>, '<?= $link_whatsapp; ?>')">
        <?= $reserva['confirmado'] == 1 ? '✓' : '×'; ?>
    </button>
</td>

<td style="border: 1px solid #ddd; padding: 8px;">
    <select onchange="window.location.href=this.value" 
            style="width: 150px; padding: 5px; font-size: 0.9rem; border-radius: 5px;">
        <option value="" disabled selected>Escolha ação</option>
        <option value="editar_reserva.php?id=<?= $reserva['id']; ?>">Editar</option>
        <option value="excluir_reserva.php?id=<?= $reserva['id']; ?>">Excluir</option>
    </select>
</td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">Nenhuma reserva encontrada para os critérios informados.</div>
        <?php endif; ?>
    </div>

    <script>
        function atualizarStatus(id, confirmado, linkWhatsapp) {
            const formData = new FormData();
            formData.append('acao', 'atualizar_status');
            formData.append('id', id);
            formData.append('confirmado', confirmado);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (confirmado == 1) {
                        window.open(linkWhatsapp, '_blank');
                    }
                    location.reload();
                } else {
                    alert('Erro ao atualizar o status.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }
    </script>
</body>
</html>
