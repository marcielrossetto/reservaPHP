<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Inicializa variáveis padrão
$nome_usuario = "Visitante";
$status = null;
$letra_avatar = "U";

if (isset($_SESSION['mmnlogin'])) {
    $user_id = $_SESSION['mmnlogin'];
    try {
        $sql = $pdo->prepare("SELECT nome, status FROM login WHERE id = :id");
        $sql->bindValue(':id', $user_id);
        $sql->execute();
        if ($sql->rowCount() > 0) {
            $usuario = $sql->fetch(PDO::FETCH_ASSOC);
            $nome_completo = ucwords(strtolower($usuario['nome']));
            $partes_nome = explode(' ', $nome_completo);
            $nome_usuario = $partes_nome[0];
            $status = $usuario['status'];
            $letra_avatar = strtoupper(substr($nome_usuario, 0, 1));
        }
    } catch (PDOException $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Reservas</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined|Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary-bg: #1e1e2d;
            --active-color: #3699ff;
            --text-color: #a2a3b7;
            --text-hover: #ffffff;
        }

        body {
            padding-top: 75px;
            background-color: #f5f8fa;
        }

        /* NAVBAR */
        .navbar-custom {
            background-color: var(--primary-bg);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }

        .navbar-brand img {
            height: 35px;
        }

        .navbar-dark .navbar-nav .nav-link {
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0.8rem 1rem;
            transition: all 0.2s ease;
        }

        .navbar-dark .navbar-nav .nav-link:hover {
            color: var(--text-hover);
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        /* BOTÃO NOVA RESERVA NA NAVBAR */
        .btn-nova-reserva-nav {
            background-color: #3699ff;
            color: #fff !important;
            border-radius: 50px;
            padding: 8px 20px !important;
            font-weight: 700;
            margin-right: 10px;
        }

        .btn-nova-reserva-nav:hover {
            background-color: #2b7cce;
            text-decoration: none;
        }

        /* FAB - BOTÃO FLUTUANTE (LINK DIRETO) */
        .fab-reserva {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background-color: #3699ff;
            color: white !important;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.5);
            z-index: 1050;
            transition: 0.3s;
            text-decoration: none;
        }

        .fab-reserva:hover {
            transform: scale(1.1);
            background-color: #2b7cce;
            text-decoration: none;
        }

        /* USUÁRIO */
        .user-profile-widget {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #3699ff;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .user-info {
            line-height: 1.2;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-custom">
        <a class="navbar-brand mr-auto" href="index.php">
            <img src="./rossetto28.png" alt="Logo">
        </a>

        <!-- WIDGET USUÁRIO (MOBILE) -->
        <div class="d-lg-none dropdown">
            <div class="user-profile-widget" data-toggle="dropdown">
                <div class="user-avatar"><?= $letra_avatar ?></div>
            </div>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item text-danger" href="sair.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav mr-auto ml-3">

                <!-- Link Nova Reserva (Navbar) -->


                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="material-icons-outlined">home</i> Home</a>
                </li>

                <!-- DROP PESQUISA -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropPesquisa" data-toggle="dropdown">
                        <i class="material-icons-outlined">search</i> Pesquisar
                    </a>
                    <div class="dropdown-menu shadow-sm">
                        <a class="dropdown-item" href="pesquisar.php"><i class="fas fa-search"></i> Pesquisa Geral</a>
                        <a class="dropdown-item" href="pesquisar_data.php"><i class="far fa-calendar-alt"></i> Por
                            Data</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="ultimasReservas.php"><i class="fas fa-history"></i> Reservas
                            criadas</a>
                        <a class="dropdown-item" href="impressaoDisplay.php"><i class="fas fa-history"></i>Ver Cards</a>

                    </div>
                </li>

                <!-- DROP ADMIN -->
                <?php if ($status == 1): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropAdmin" data-toggle="dropdown">
                            <i class="material-icons-outlined">admin_panel_settings</i> Admin
                        </a>
                        <div class="dropdown-menu shadow-sm">
                            <a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i>
                                Dashboard</a>
                            <a class="dropdown-item" href="painel_administrativo.php"><i class="fas fa-cogs"></i> Painel
                                Admin</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- WIDGET USUÁRIO (DESKTOP) -->
            <ul class="navbar-nav d-none d-lg-flex align-items-center">
                <li class="nav-item dropdown">
                    <div class="user-profile-widget" data-toggle="dropdown">
                        <div class="user-info text-right text-white mr-2">
                            <small class="d-block" style="color:var(--text-color)">Olá,</small>
                            <span class="font-weight-bold"><?= htmlspecialchars($nome_usuario) ?></span>
                        </div>
                        <div class="user-avatar"><?= $letra_avatar ?></div>
                    </div>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="pagina_de_vendas.php"><i class="fas fa-info-circle"></i>
                            Sobre</a>
                        <a class="dropdown-item text-danger" href="sair.php"><i class="fas fa-sign-out-alt"></i>
                            Sair</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- BOTÃO FLUTUANTE (FAB) - AGORA LINK DIRETO -->
    <a href="adicionar_reserva.php" class="fab-reserva" title="Nova Reserva">
        <i class="fas fa-plus"></i>
    </a>

    <!-- Scripts necessários para os dropdowns funcionarem -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>