<?php
session_start();
require 'config.php';

if (!empty($_POST['email'])) {
    $email = addslashes($_POST['email']);
    $senha = md5(addslashes($_POST['senha']));

    $sql = $pdo->prepare("
        SELECT l.*, e.nome_empresa, e.status as empresa_status, e.data_expiracao 
        FROM login l
        INNER JOIN empresas e ON e.id = l.empresa_id
        WHERE l.email = :email AND l.senha = :senha
    ");
    $sql->bindValue(":email", $email);
    $sql->bindValue(":senha", $senha);
    $sql->execute();

    if ($sql->rowCount() > 0) {
        $usuario = $sql->fetch();
        $hoje = new DateTime();
        $expiracao = new DateTime($usuario['data_expiracao']);

        if ($usuario['empresa_status'] == 0) {
            $erro = "Sua empresa está desativada. Contate o suporte.";
        } 
        elseif ($hoje > $expiracao) {
            $erro = "Sua licença expirou em " . $expiracao->format('d/m/Y');
        } 
        else {
            $_SESSION['mmnlogin'] = $usuario['id'];
            $_SESSION['empresa_id'] = $usuario['empresa_id'];
            $_SESSION['nome_empresa'] = $usuario['nome_empresa'];
            $_SESSION['nivel'] = $usuario['nivel'];
            header("Location: index.php");
            exit;
        }
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #1e1e2d 0%, #2c2c44 100%); 
            font-family: 'Inter', sans-serif;
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; margin: 0; padding: 15px;
        }
        .login-card { 
            width: 100%; 
            max-width: 350px; /* Largura máxima em desktops */
            padding: 25px; 
            background: white; border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
        }
        .logo-container { text-align: center; margin-bottom: 15px; }
        .logo-container img { width: 100%; max-width: 160px; height: auto; }
        
        h5 { color: #3f4254; font-weight: 700; margin-bottom: 15px; font-size: 1.1rem; }
        
        .form-label { margin-bottom: 3px; font-size: 0.7rem; color: #6c757d; letter-spacing: 0.5px; }
        
        .form-control { 
            padding: 10px 12px; border-radius: 8px; border: 1px solid #e1e3ea; 
            background-color: #f3f6f9; font-size: 0.9rem; transition: 0.3s;
        }
        .form-control:focus { 
            box-shadow: 0 0 0 3px rgba(54, 153, 255, 0.15); 
            border-color: #3699ff; background-color: #fff;
        }
        
        .btn-primary { 
            background-color: #3699ff; border: none; padding: 10px; 
            font-weight: 700; border-radius: 8px; transition: 0.3s;
            font-size: 0.9rem; margin-top: 10px;
        }
        
        .alert { border-radius: 8px; font-size: 0.8rem; padding: 8px; margin-top: 12px; }
        .footer-link { font-size: 0.75rem; margin-top: 15px; text-align: center; }

        /* Ajustes para telas muito pequenas (iPhone SE, etc) */
        @media (max-width: 380px) {
            .login-card { padding: 20px; }
            .logo-container img { max-width: 130px; }
            h5 { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="rossetto33.png" alt="Logo">
        </div>
        
        <h5 class="text-center">Bem-vindo</h5>
        
        <form method="POST">
            <div class="mb-2">
                <label class="form-label fw-bold text-uppercase">E-mail</label>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold text-uppercase">Senha</label>
                <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 shadow-sm">ENTRAR NO PAINEL</button>
            
            <div class="footer-link">
                <span class="text-muted">Não tem conta?</span> 
                <a href="cadastrar_empresa.php" class="text-decoration-none text-primary fw-bold">Teste Grátis</a>
            </div>
        </form>

        <?php if(!empty($erro)): ?>
            <div class="alert alert-danger text-center"><?php echo $erro; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>