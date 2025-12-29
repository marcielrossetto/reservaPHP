<?php
require 'config.php';

$erro = "";

// Lógica de Processamento (O que acontece ao clicar no botão)
if (!empty($_POST['nome_empresa'])) {
    $empresa = addslashes($_POST['nome_empresa']);
    $nome_user = addslashes($_POST['nome_usuario']);
    $email = addslashes($_POST['email']);
    $senha = md5(addslashes($_POST['senha']));
    
    // Verifica se o e-mail já existe
    $check = $pdo->prepare("SELECT id FROM login WHERE email = ?");
    $check->execute([$email]);
    
    if($check->rowCount() == 0) {
        // Define 7 dias de teste
        $data_expira = date('Y-m-d H:i:s', strtotime('+7 days'));

        // 1. Cria a Empresa
        $sql = $pdo->prepare("INSERT INTO empresas (nome_empresa, data_expiracao, status) VALUES (?, ?, 1)");
        $sql->execute([$empresa, $data_expira]);
        $id_empresa = $pdo->lastInsertId();

        // 2. Cria o Usuário Master vinculado a essa empresa
        $sql = $pdo->prepare("INSERT INTO login (empresa_id, nome, email, senha, nivel, status) VALUES (?, ?, ?, ?, 'master', 1)");
        $sql->execute([$id_empresa, $nome_user, $email, $senha]);

        header("Location: login.php?sucesso=1");
        exit;
    } else {
        $erro = "Este e-mail já está cadastrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Grátis - 7 Dias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: sans-serif;
            padding: 20px;
        }
        .register-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
        }
        .ios-input {
            border-radius: 12px;
            padding: 12px;
            border: 1px solid #ddd;
            width: 100%;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        .ios-btn {
            background: #007AFF;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            font-weight: bold;
            transition: 0.3s;
        }
        .ios-btn:hover {
            background: #0056b3;
        }
        label {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: block;
            color: #333;
        }
        .badge-trial {
            background: #e1f5fe;
            color: #0288d1;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="text-center">
        <span class="badge-trial">🎁 Período de Teste Grátis</span>
        <h4>Criar Minha Conta</h4>
        <p class="text-muted small">Tenha acesso total por 7 dias.</p>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger p-2 small"><?= $erro; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Nome da Empresa (Ex: Churrascaria Silva)</label>
        <input type="text" name="nome_empresa" class="ios-input" placeholder="Como se chama seu negócio?" required>

        <label>Seu Nome Completo</label>
        <input type="text" name="nome_usuario" class="ios-input" placeholder="Ex: João Silva" required>

        <label>Seu E-mail Profissional</label>
        <input type="email" name="email" class="ios-input" placeholder="email@exemplo.com" required>

        <label>Crie uma Senha</label>
        <input type="password" name="senha" class="ios-input" placeholder="Mínimo 6 caracteres" required>

        <button type="submit" class="ios-btn">Começar Teste de 7 Dias</button>
    </form>

    <div class="text-center mt-3">
        <a href="login.php" class="text-decoration-none small text-muted">Já tenho uma conta. Entrar.</a>
    </div>
</div>

</body>
</html>