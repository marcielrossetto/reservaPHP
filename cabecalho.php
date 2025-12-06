<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';

// Inicializa variáveis padrão
$nome_usuario = "Visitante";
$status = null;
$letra_avatar = "U";

if (isset($_SESSION['mmnlogin'])) {
    $user_id = $_SESSION['mmnlogin'];
    
    try {
        $sql = $pdo->prepare("SELECT nome, status, email FROM login WHERE id = :id");
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
        // Silêncio em caso de erro momentâneo no banco
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
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary-bg: #1e1e2d;       
            --active-color: #3699ff;     
            --text-color: #a2a3b7;       
            --text-hover: #ffffff;
        }

        body {
            padding-top: 70px;
            background-color: #f5f8fa;
        }

        /* NAVBAR PRINCIPAL */
        .navbar-custom {
            background-color: var(--primary-bg);
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 0.5rem 1rem;
        }

        .navbar-brand img {
            height: 35px;
            transition: transform 0.2s;
        }
        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        /* LINKS DO MENU */
        .navbar-dark .navbar-nav .nav-link {
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .show > .nav-link {
            color: var(--text-hover);
            background-color: rgba(255,255,255,0.05);
        }
        
        .material-icons-outlined {
            font-size: 20px;
        }

        /* PERFIL DO USUÁRIO */
        .user-profile-widget {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 50px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile-widget:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3699ff, #0055ff);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .user-name {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-role {
            color: var(--text-color);
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        /* DROPDOWNS */
        .dropdown-menu {
            border: none;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-top: 10px;
            padding: 10px 0;
        }

        .dropdown-item {
            padding: 10px 20px;
            font-size: 0.9rem;
            color: #3f4254;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
            color: #b5b5c3;
        }

        .dropdown-item:hover {
            background-color: #f3f6f9;
            color: var(--active-color);
        }
        .dropdown-item:hover i {
            color: var(--active-color);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-top: 1px solid #ebedf3;
        }

        /* MOBILE */
        @media (max-width: 991px) {
            .user-info { display: none; }
            .user-profile-widget { margin-right: 10px; padding: 0; }
            .navbar-collapse { background: var(--primary-bg); padding: 15px; border-radius: 0 0 15px 15px; }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-custom">
    
    <!-- LOGO -->
    <a class="navbar-brand mr-auto" href="index.php">
        <img src="./rossetto28.png" alt="Logo">
    </a>

    <!-- WIDGET USUÁRIO (MOBILE) -->
    <div class="d-lg-none dropdown">
        <div class="user-profile-widget" data-toggle="dropdown">
            <div class="user-avatar"><?= $letra_avatar ?></div>
        </div>
        <div class="dropdown-menu dropdown-menu-right">
            <h6 class="dropdown-header">Olá, <?= $nome_usuario ?></h6>
            
            <?php if ($status == 1): ?>
                <a class="dropdown-item" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a class="dropdown-item" href="painel_administrativo.php"><i class="fas fa-cogs"></i> Painel Admin</a>
            <?php endif; ?>
            
            <!-- Adicionado Sobre o Sistema no Mobile -->
            <a class="dropdown-item" href="pagina_de_vendas.php"><i class="fas fa-info-circle"></i> Sobre o Sistema</a>
            
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="sair.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>

    <!-- BOTÃO MENU (HAMBURGER) -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- MENU PRINCIPAL -->
    <div class="collapse navbar-collapse" id="menuPrincipal">
        
        <ul class="navbar-nav mr-auto ml-3">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="material-icons-outlined">home</i> Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="adicionar_reserva.php">
                    <i class="material-icons-outlined">add_circle_outline</i> Nova Reserva
                </a>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropPesquisa" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="material-icons-outlined">search</i> Pesquisar
                </a>
                <div class="dropdown-menu shadow-sm" aria-labelledby="dropPesquisa">
                    <a class="dropdown-item" href="pesquisar.php"><i class="fas fa-search"></i> Pesquisa Geral</a>
                    <a class="dropdown-item" href="pesquisar_data.php"><i class="far fa-calendar-alt"></i> Por Data</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="ultimasReservas.php"><i class="fas fa-history"></i> Últimas Reservas</a>
                    <a class="dropdown-item" href="confirmar_reserva.php"><i class="fas fa-check-double"></i> Confirmar Reservas</a>
                    <a class="dropdown-item" href="itensCancelados.php"><i class="fas fa-ban"></i> Canceladas</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">Relatórios</h6>
                    <a class="dropdown-item" href="pesquisar_almoco.php">Almoço</a>
                    <a class="dropdown-item" href="pesquisar_janta.php">Jantar</a>
                    <a class="dropdown-item" href="pesquisar_diaria_almoco.php">Diário Almoço</a>
                    <a class="dropdown-item" href="pesquisa_diaria_jantar.php">Diário Jantar</a>
                    <a class="dropdown-item" href="demonstrar.php">Demonstrar</a>
                </div>
            </li>

            <!-- ÁREA ADMINISTRATIVA (Somente Master) -->
            <?php if ($status == 1): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropAdmin" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="material-icons-outlined">admin_panel_settings</i> Admin
                </a>
                <div class="dropdown-menu shadow-sm" aria-labelledby="dropAdmin">
                    <!-- Dashboard Analítico -->
                    <a class="dropdown-item" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard (BI)
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <!-- Novo Painel Administrativo Centralizado -->
                    <a class="dropdown-item" href="painel_administrativo.php">
                        <i class="fas fa-user-shield"></i> Painel Administrativo
                    </a>
                    
                    <div class="dropdown-divider"></div>

                    <!-- Adicionado Sobre o Sistema na aba Admin -->
                    <a class="dropdown-item" href="pagina_de_vendas.php">
                        <i class="fas fa-info-circle"></i> Sobre o Sistema
                    </a>
                </div>
            </li>
            <?php endif; ?>
        </ul>

        <!-- WIDGET USUÁRIO (DESKTOP) -->
        <ul class="navbar-nav d-none d-lg-flex align-items-center">
            <li class="nav-item dropdown">
                <div class="user-profile-widget" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="user-info text-right">
                        <span class="user-name"><?= htmlspecialchars($nome_usuario) ?></span>
                        <span class="user-role"><?= ($status == 1) ? 'Administrador' : 'Colaborador' ?></span>
                    </div>
                    <div class="user-avatar">
                        <?= $letra_avatar ?>
                    </div>
                    <i class="fas fa-chevron-down text-muted ml-1" style="font-size: 0.8rem;"></i>
                </div>
                
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="px-3 py-2">
                        <small class="text-muted">Logado como</small><br>
                        <strong><?= htmlspecialchars($nome_usuario) ?></strong>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="pagina_de_vendas.php">
                        <i class="fas fa-info-circle"></i> Sobre o Sistema
                    </a>
                    <a class="dropdown-item text-danger" href="sair.php">
                        <i class="fas fa-sign-out-alt"></i> Sair do Sistema
                    </a>
                </div>
            </li>
        </ul>

    </div>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Correção para fechar o menu mobile ao clicar em um link
    $(document).ready(function(){
        $('.navbar-nav .nav-link').on('click', function(){
            if(!$(this).hasClass('dropdown-toggle')){
                $('.navbar-collapse').collapse('hide');
            }
        });
    });
</script>

</body>
</html>