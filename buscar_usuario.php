<?php
session_start();
require 'config.php';

// Verifica se a senha master foi enviada
if (!empty($_POST['senha_master'])) {
    $senha_master = md5(addslashes($_POST['senha_master']));

    // Verifica se a senha master está correta
    if ($senha_master === '21232f297a57a5a743894a0e4a801fc3,e38e37a99f7de1f45d169efcdb288dd1 ') { // Substitua 'SENHA_MASTER_HASH' pelo hash da sua senha master
        // Busca os usuários cadastrados no banco de dados
        $sql = $pdo->query("SELECT nome, email, data_emissao FROM login ORDER BY data_emissao DESC");
        $usuarios = $sql->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "<script>alert('Senha master incorreta!'); window.location='login.php'</script>";
        exit;
    }
} else {
    echo "<script>alert('Por favor, insira a senha master.'); window.location='login.php'</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Usuários Cadastrados</title>
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Usuários Cadastrados</h1>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Data de Cadastro</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['data_emissao']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Nenhum usuário encontrado</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
