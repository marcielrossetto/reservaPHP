<?php
session_start();
ob_start(); // Previne erros de header
require 'config.php';

// ====================================================================
// 1. VERIFICAÇÃO DE SEGURANÇA (APENAS MASTER/ADMIN)
// ====================================================================
if (empty($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['mmnlogin'];
$sqlUser = $pdo->prepare("SELECT * FROM login WHERE id = :id");
$sqlUser->bindValue(":id", $id_usuario);
$sqlUser->execute();
$dadosUser = $sqlUser->fetch();

if (!$dadosUser || $dadosUser['status'] != 1) {
    echo "<script>alert('Acesso negado. Apenas administradores.'); window.location='index.php';</script>";
    exit;
}

// ====================================================================
// 2. FUNÇÃO DE BACKUP (DATABASE DUMP)
// ====================================================================
function backupDatabase($pdo) {
    try {
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) { mkdir($backupDir, 0777, true); }

        $timestamp = date('Y-m-d_H-i-s');
        $sqlFile = $backupDir . '/backup_completo_' . $timestamp . '.sql';
        $sqlHandle = fopen($sqlFile, 'w');

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Gera CSV individual (Opcional, mantido conforme pedido)
            $csvFileName = $backupDir . '/' . $table . '_' . $timestamp . '.csv';
            $csvFile = fopen($csvFileName, 'w');
            
            // Cabeçalhos
            $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
            fputcsv($csvFile, $columns);

            // Dados
            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                fputcsv($csvFile, $row);
            }
            fclose($csvFile);

            // Gera SQL (Create + Insert)
            $createTableStmt = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
            fwrite($sqlHandle, "-- Estrutura da tabela: $table --\n");
            fwrite($sqlHandle, $createTableStmt['Create Table'] . ";\n\n");

            if(count($rows) > 0){
                fwrite($sqlHandle, "-- Dados da tabela: $table --\n");
                foreach ($rows as $row) {
                    // Sanitização básica para SQL
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? "NULL" : $pdo->quote($value);
                    }, array_values($row));
                    
                    $valuesStr = implode(", ", $values);
                    $sql = "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES ($valuesStr);\n";
                    fwrite($sqlHandle, $sql);
                }
            }
            fwrite($sqlHandle, "\n\n");
        }
        fclose($sqlHandle);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ====================================================================
// 3. PROCESSAMENTO DE FORMULÁRIOS (POST)
// ====================================================================
$msg = '';
$msgType = '';

// Ação: Gerar Backup
if (isset($_POST['acao']) && $_POST['acao'] == 'gerar_backup') {
    if(backupDatabase($pdo)) {
        $msg = "Backup realizado com sucesso! Arquivos salvos na pasta.";
        $msgType = "success";
    } else {
        $msg = "Erro ao criar backup. Verifique permissões da pasta.";
        $msgType = "danger";
    }
}

// Ação: Cadastrar Usuário
if (isset($_POST['acao']) && $_POST['acao'] == 'cadastrar_usuario') {
    $nome = addslashes($_POST['nome']);
    $email = addslashes($_POST['email']);
    $senha = md5(addslashes($_POST['senha']));
    $status_usuario = intval($_POST['status']);

    $sql = $pdo->prepare("SELECT * FROM login WHERE email = :email");
    $sql->bindValue(":email", $email);
    $sql->execute();

    if ($sql->rowCount() == 0) {
        $sql = $pdo->prepare("INSERT INTO login (nome, email, senha, status) VALUES (:nome, :email, :senha, :status)");
        $sql->execute([':nome'=>$nome, ':email'=>$email, ':senha'=>$senha, ':status'=>$status_usuario]);
        $msg = "Usuário cadastrado com sucesso!";
        $msgType = "success";
    } else {
        $msg = "E-mail já cadastrado!";
        $msgType = "danger";
    }
}

// Ação: Cadastrar Preço Rodízio
if (isset($_POST['acao']) && $_POST['acao'] == 'cadastrar_preco') {
    $almoco = addslashes($_POST['almoco']);
    $jantar = addslashes($_POST['jantar']);
    $domingo_almoco = addslashes($_POST['domingo_almoco']);
    $outros = addslashes($_POST['outros']);

    $sql = $pdo->prepare("INSERT INTO preco_rodizio (almoco, jantar, domingo_almoco, outros) VALUES (:almoco, :jantar, :domingo_almoco, :outros)");
    $sql->execute([':almoco'=>$almoco, ':jantar'=>$jantar, ':domingo_almoco'=>$domingo_almoco, ':outros'=>$outros]);
    $msg = "Preços atualizados com sucesso!";
    $msgType = "success";
}

// ====================================================================
// 4. LEITURA DE DADOS
// ====================================================================
// Listar arquivos de backup
$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) { mkdir($backupDir, 0777, true); }
$files = array_diff(scandir($backupDir), ['.', '..']);
rsort($files); // Mostra os mais recentes primeiro

// Listar Usuários
$sqlListaUsers = $pdo->query("SELECT * FROM login");
$listaUsuarios = $sqlListaUsers->fetchAll();

// Lembrete Backup
$dataAtual = date('d');
$mostrarLembrete = ($dataAtual == 14); 

require 'cabecalho.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Poppins', Arial, sans-serif; background-color: #f4f4f9; }
        .container-admin { max-width: 1200px; margin: 40px auto; padding: 20px; }
        
        .welcome-banner {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .card-menu {
            border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s; cursor: pointer; height: 100%; text-align: center;
            padding: 30px 20px; background: #fff;
        }
        .card-menu:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-icon { font-size: 3rem; margin-bottom: 15px; color: #2c3e50; }
        .card-title { font-size: 1.2rem; font-weight: 600; color: #555; }
        
        .modal-header { background-color: #2c3e50; color: white; }
        .btn-close { filter: invert(1); }
        
        .info-backup { background: #e0eafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; color: #333; border-left: 5px solid #2c3e50; }
        .info-backup h6 { font-weight: bold; margin-bottom: 10px; }
        .info-backup ul { padding-left: 20px; margin-bottom: 0; }
    </style>
</head>
<body>

<div class="container container-admin">
    
    <!-- Mensagens -->
    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php if($msgType == 'success'): ?><i class="fa-solid fa-check-circle me-2"></i><?php endif; ?>
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Banner -->
    <div class="welcome-banner">
        <h1><i class="fa-solid fa-user-shield"></i> Painel Administrativo</h1>
        <p class="mb-0">Gerenciamento completo do sistema: Usuários, Preços e Segurança de Dados.</p>
    </div>

    <!-- Cards Menu -->
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card-menu" data-bs-toggle="modal" data-bs-target="#modalCadUsuario">
                <div class="card-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="card-title">Novo Usuário</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card-menu" data-bs-toggle="modal" data-bs-target="#modalListarUsuarios">
                <div class="card-icon"><i class="fa-solid fa-users-gear"></i></div>
                <div class="card-title">Listar Usuários</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card-menu" data-bs-toggle="modal" data-bs-target="#modalPreco">
                <div class="card-icon"><i class="fa-solid fa-utensils"></i></div>
                <div class="card-title">Tabela de Preços</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card-menu" data-bs-toggle="modal" data-bs-target="#modalBackup">
                <div class="card-icon text-danger"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                <div class="card-title">Fazer Backup</div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAIS ================= -->

<!-- 1. MODAL USUÁRIO -->
<div class="modal fade" id="modalCadUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cadastrar Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar_usuario">
                    <div class="mb-3"><label>Nome:</label><input type="text" name="nome" class="form-control" required></div>
                    <div class="mb-3"><label>E-mail:</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label>Senha:</label><input type="password" name="senha" class="form-control" required></div>
                    <div class="mb-3"><label>Acesso:</label>
                        <select name="status" class="form-control" required>
                            <option value="0">Padrão</option>
                            <option value="1">Master</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Salvar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. MODAL LISTAR -->
<div class="modal fade" id="modalListarUsuarios" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Usuários Ativos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark"><tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th></tr></thead>
                        <tbody>
                            <?php foreach($listaUsuarios as $u): ?>
                            <tr>
                                <td><?= $u['id']; ?></td>
                                <td><?= htmlspecialchars($u['nome']); ?></td>
                                <td><?= htmlspecialchars($u['email']); ?></td>
                                <td><?= ($u['status'] == 1) ? '<span class="badge bg-success">Master</span>' : '<span class="badge bg-secondary">User</span>'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. MODAL PREÇOS -->
<div class="modal fade" id="modalPreco" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preços do Rodízio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="cadastrar_preco">
                    <div class="mb-3"><label>Almoço:</label><input type="text" name="almoco" class="form-control" required></div>
                    <div class="mb-3"><label>Jantar:</label><input type="text" name="jantar" class="form-control" required></div>
                    <div class="mb-3"><label>Sábado/Feriado:</label><input type="text" name="outros" class="form-control" required></div>
                    <div class="mb-3"><label>Domingo Almoço:</label><input type="text" name="domingo_almoco" class="form-control" required></div>
                    <button type="submit" class="btn btn-dark w-100">Atualizar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 4. MODAL BACKUP (Refatorado com a Info) -->
<div class="modal fade" id="modalBackup" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-database"></i> Backup & Segurança</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <!-- Informações de Segurança -->
                <div class="info-backup">
                    <h6><i class="fa-solid fa-shield-halved"></i> Por que fazer backup?</h6>
                    <ul>
                        <li><strong>Segurança:</strong> Prevenção contra perda de dados acidental ou falhas no servidor.</li>
                        <li><strong>Recuperação:</strong> Em caso de desastres, você restaura o sistema em minutos.</li>
                        <li><strong>Proteção:</strong> Contra ataques cibernéticos ou corrupção de arquivos.</li>
                    </ul>
                    <small>O arquivo gerado contém a estrutura e os dados de todas as tabelas.</small>
                </div>

                <!-- Botão de Ação -->
                <form method="POST" class="text-center mb-4">
                    <input type="hidden" name="acao" value="gerar_backup">
                    <button type="submit" class="btn btn-danger btn-lg w-100 p-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-file-export"></i> CLIQUE PARA GERAR NOVO BACKUP
                    </button>
                </form>

                <hr>

                <!-- Lista de Arquivos -->
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-list"></i> Backups Disponíveis (Servidor)</h6>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <?php if (count($files) > 0): ?>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Arquivo</th>
                                    <th>Tamanho</th>
                                    <th>Data</th>
                                    <th class="text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $file): 
                                    $path = $backupDir . '/' . $file;
                                    $size = round(filesize($path) / 1024, 2) . ' KB';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($file); ?></td>
                                        <td><?= $size; ?></td>
                                        <td><?= date('d/m/Y H:i', filemtime($path)); ?></td>
                                        <td class="text-center">
                                            <a href="backups/<?= urlencode($file); ?>" class="btn btn-primary btn-sm" download>
                                                <i class="fa-solid fa-download"></i> Baixar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted">Nenhum backup encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. LEMBRETE AUTOMÁTICO -->
<?php if ($mostrarLembrete): ?>
<div class="modal fade" id="modalLembrete" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content border-warning">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation"></i> Lembrete de Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <h4>Hoje é dia 14!</h4>
                <p class="mb-0">Para garantir a segurança dos dados, recomenda-se realizar um backup hoje.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Agora não</button>
                <button type="button" class="btn btn-primary fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalBackup">
                    Fazer Backup Agora
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', function() {
        new bootstrap.Modal(document.getElementById('modalLembrete')).show();
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>