<?php
session_start();
require 'config.php';
require 'cabecalho.php';
// Caminho para a pasta de backups no servidor
$backupDir = __DIR__ . '/backups';

// Verifica se a pasta existe
if (!is_dir($backupDir)) {
    echo "<script>alert('A pasta de backups não foi encontrada.'); window.location='index.php';</script>";
    exit;
}

// Lista os arquivos da pasta de backups
$files = array_diff(scandir($backupDir), ['.', '..']);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Backups</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        .container {
            max-width: 800px;
            margin-top: 150p;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-top: 100px;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 0.9rem;
            text-align: center;
        }

        table th {
            background-color: #f7f7f7;
            color: #333;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            font-size: 0.85rem;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin: 2px;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-secondary {
            background-color: #6c757d;
            text-align: center;
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 10px;
        }

        .btn:hover {
            opacity: 0.9;
        }

        @media (max-width: 600px) {
            table th, table td {
                font-size: 0.8rem;
                padding: 8px;
            }

            .btn {
                font-size: 0.8rem;
                padding: 6px;
            }
        }

        .no-files {
            text-align: center;
            color: #777;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Backups</h1>

        <?php if (count($files) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Data de Criação</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td><?php echo date('d/m/Y H:i:s', filemtime($backupDir . '/' . $file)); ?></td>
                            <td>
                                <a href="backups/<?php echo urlencode($file); ?>" class="btn btn-primary" download>Baixar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-files">Não há arquivos de backup disponíveis.</p>
        <?php endif; ?>

        <a href="index.php" class="btn btn-secondary">Voltar</a>
    </div>
</body>
</html>

