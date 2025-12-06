<?php
// ajax_reservas_dia.php
require 'config.php';

header('Content-Type: application/json');

$data = $_GET['data'] ?? '';

if (!$data) {
    echo json_encode(['erro' => true, 'msg' => 'Data não informada']);
    exit;
}

try {
    // 1. Buscar a Lista de Reservas (Detalhes)
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE data = :data ORDER BY horario ASC");
    $stmt->execute(['data' => $data]);
    $lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Calcular o Resumo (Almoço vs Jantar)
    $almoco = 0;
    $jantar = 0;
    $total  = 0;

    foreach ($lista as $reserva) {
        // Ignora cancelados (status 0) se necessário, ajuste conforme sua lógica de status
        if (isset($reserva['status']) && $reserva['status'] == 0) {
            continue;
        }

        $pessoas = (int)$reserva['num_pessoas'];
        $hora    = $reserva['horario'];
        
        $total += $pessoas;

        // Regra de Horário (ajuste se necessário)
        if ($hora >= '11:00:00' && $hora <= '17:59:59') {
            $almoco += $pessoas;
        } else {
            $jantar += $pessoas;
        }
    }

    // Retorna tudo em um único JSON
    echo json_encode([
        'erro'   => false,
        'lista'  => $lista,
        'resumo' => [
            'almoco' => $almoco,
            'jantar' => $jantar,
            'total'  => $total
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['erro' => true, 'msg' => 'Erro SQL: ' . $e->getMessage()]);
}
?>