<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Proteção: Redireciona se não houver login
if (!isset($_SESSION['mmnlogin'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['mmnlogin'];
$empresa_id = $_SESSION['empresa_id'];
$nivel_usuario = $_SESSION['nivel'];

// 1. BUSCA DADOS DO USUÁRIO E DA EMPRESA (INCLUINDO LOGO)
try {
    $sql = $pdo->prepare("
        SELECT l.nome, e.nome_empresa, e.logo 
        FROM login l 
        INNER JOIN empresas e ON l.empresa_id = e.id 
        WHERE l.id = :id
    ");
    $sql->execute([':id' => $user_id]);
    $dados = $sql->fetch(PDO::FETCH_ASSOC);

    $nome_usuario = explode(' ', $dados['nome'])[0];
    $nome_empresa = $dados['nome_empresa'];
    $letra_avatar = strtoupper(substr($nome_usuario, 0, 1));

    // Tratamento da imagem da Logo
    $logo_bin = is_resource($dados['logo']) ? stream_get_contents($dados['logo']) : $dados['logo'];
    $logo_src = !empty($logo_bin) ? 'data:image/jpeg;base64,' . base64_encode($logo_bin) : "rossetto28.png";

} catch (PDOException $e) {
    $nome_empresa = "Erro de Conexão";
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nome_empresa) ?></title>

    <!-- Bootstrap 4.6 & Google Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary-bg: #1e1e2d;
            --active-color: #3699ff;
            --text-muted: #a2a3b7;
        }

        body {
            padding-top: 65px;
            /* Aumentado levemente para acomodar o nome abaixo da logo */
            background-color: #f5f8fa;
            font-family: 'Inter', sans-serif;
        }

        /* NAVBAR CUSTOM */
        .navbar-custom {
            background-color: var(--primary-bg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-height: 50px;
            /* Estava 80px */
            border-bottom: 2px solid var(--active-color);
        }

        /* 🔥 AJUSTE: LOGO EM CIMA E NOME EMBAIXO */
        .brand-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .logo-img {
            height: 25px;
            width: 80PX;
            max-width: 150px;
            border-radius: 6px;
            object-fit: contain;
            cursor: pointer;
            background: white;
            padding: 2px;
            border: 1px solid var(--active-color);
        }

        .company-link {
            color: white !important;
            font-weight: 600;
            font-size: 0.75rem;
            /* Tamanho menor como solicitado */
            margin-top: 4px;
            margin-left: 0 !important;
            /* Remove margem lateral */
            text-decoration: none !important;
            text-align: center;
            white-space: nowrap;
        }

        /* LINKS E DROPDOWNS ALINHADOS */
        .nav-link,
        .dropdown-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px;
            color: var(--text-muted) !important;
            font-weight: 500;
        }

        .nav-link i,
        .dropdown-item i {
            font-size: 20px;
            line-height: 1;
        }

        .nav-link:hover,
        .dropdown-item:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.05);
            color: #f5f8fa;
        }

        .dropdown-menu {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .dropdown-item {
            color: #3f4254 !important;
            padding: 12px 20px;
        }

        /* USUÁRIO */
        .user-avatar {
            width: 35px;
            height: 35px;
            background: var(--active-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* MODAL STYLING */
        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: #f8f9fa;
            border-radius: 20px 20px 0 0;
            border-bottom: 1px solid #eee;
        }

        .form-label-header {
            font-size: 0.7rem;
            font-weight: 800;
            color: #a2a3b7;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        /* FAB */
        .fab-reserva {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background-color: var(--active-color);
            color: white !important;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(54, 153, 255, 0.4);
            z-index: 1050;
            text-decoration: none;
        }

        @media (max-width: 991px) {
            .navbar-collapse {
                background: var(--primary-bg);
                padding: 15px;
                border-radius: 0 0 15px 15px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-custom">
        <div class="container-fluid">

            <div class="brand-area">
                <!-- Imagem no topo -->
                <img src="<?= $logo_src ?>" class="logo-img" onclick="document.getElementById('inputLogo').click();"
                    title="Trocar Logo">
                <!-- Nome logo abaixo -->
                <a href="index.php" class="company-link"><?= htmlspecialchars($nome_empresa) ?></a>

                <form id="formLogo" action="processar_logo.php" method="POST" enctype="multipart/form-data"
                    style="display:none;">
                    <input type="file" name="nova_logo" id="inputLogo" accept="image/*"
                        onchange="document.getElementById('formLogo').submit();">
                </form>
            </div>

            <!-- 🔥 MOBILE – Nome + Avatar + Dropdown com SAIR + Hambúrguer -->
            <div class="d-flex d-lg-none align-items-center ml-auto">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none"
                        data-toggle="dropdown">
                        <div class="text-right mr-2">
                            <small class="d-block" style="color:var(--text-muted)">Olá, <?= $nome_usuario ?></small>
                        </div>
                        <div class="user-avatar mr-2"><?= $letra_avatar ?></div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right mt-1">
                        <a class="dropdown-item text-danger" href="sair.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </div>
                </div>
                <button class="navbar-toggler border-0 ml-2" type="button" data-toggle="collapse"
                    data-target="#menuPrincipal">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav mr-auto ml-lg-4">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i
                                class="material-icons-outlined">home</i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="fila.php"><i
                                class="material-icons-outlined">groups</i> Fila</a></li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"><i
                                class="material-icons-outlined">search</i> Pesquisar</a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="pesquisar.php"><i class="fas fa-search"></i> Pesquisa
                                Geral</a>
                            <a class="dropdown-item" href="ultimasReservas.php"><i class="fas fa-history"></i> Reservas
                                Criadas</a>
                            <a class="dropdown-item" href="impressaoDisplay.php"><i class="fas fa-th-large"></i> Ver
                                Cards</a>
                        </div>
                    </li>

                    <?php if ($nivel_usuario == 'master'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"><i
                                    class="material-icons-outlined">admin_panel_settings</i> Admin</a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalNovoUsuario"><i
                                        class="fas fa-user-plus"></i> Novo Usuário</a>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalVerEquipe"><i
                                        class="fas fa-users"></i> Ver Equipe</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard
                                    BI</a>
                                <a class="dropdown-item" href="cardapio.php?id=<?= $empresa_id ?>" target="_blank"><i
                                        class="fas fa-utensils"></i> Cardápio</a>
                                <a class="dropdown-item" href="configCardapio.php"><i class="fas fa-cogs"></i>
                                    Configurar</a>
                            </div>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="nav-link" href="privacidade.php"><i
                                class="material-icons-outlined">gpp_maybe</i> Privacidade</a></li>
                </ul>

                <ul class="navbar-nav d-none d-lg-flex align-items-center">
                    <li class="nav-item dropdown">
                        <div class="d-flex align-items-center text-white" style="cursor:pointer" data-toggle="dropdown">
                            <div class="mr-2 text-right">
                                <small class="d-block" style="color:var(--text-muted)">Olá, <?= $nome_usuario ?></small>
                            </div>
                            <div class="user-avatar"><?= $letra_avatar ?></div>
                        </div>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item text-danger" href="sair.php"><i class="fas fa-sign-out-alt"></i>
                                Sair</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- MODAL: NOVO USUÁRIO (Restaurado com campos corretos) -->
    <div class="modal fade" id="modalNovoUsuario" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="acoes_usuario.php" method="POST">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="modal-header">
                        <h5 class="font-weight-bold">Cadastrar Novo Usuário</h5><button type="button" class="close"
                            data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group"><label class="form-label-header">Nome Completo</label><input type="text"
                                name="nome" class="form-control border-0 bg-light" required></div>
                        <div class="form-group"><label class="form-label-header">E-mail (Login)</label><input
                                type="email" name="email" class="form-control border-0 bg-light" required></div>
                        <div class="form-group"><label class="form-label-header">Senha</label><input type="password"
                                name="senha" class="form-control border-0 bg-light" required></div>
                        <div class="form-group"><label class="form-label-header">Nível de Permissão</label>
                            <select name="nivel" class="form-control border-0 bg-light">
                                <option value="operacional">Operacional (Faz Reservas)</option>
                                <option value="master">Master (Administrador)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0"><button type="submit"
                            class="btn btn-primary btn-block py-3 font-weight-bold shadow-sm">SALVAR ACESSO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: VER EQUIPE (Restaurado com consulta dinâmica) -->
    <div class="modal fade" id="modalVerEquipe" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="font-weight-bold">Equipe da Empresa</h5><button type="button" class="close"
                        data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">

                    <!-- 🔥 Correção responsiva para celular -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr class="text-muted small">
                                    <th>NOME</th>
                                    <th>E-MAIL</th>
                                    <th>CARGO</th>
                                    <th class="text-right">AÇÃO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $st = $pdo->prepare("SELECT id, nome, email, nivel FROM login WHERE empresa_id = ? AND status = 1");
                                $st->execute([$empresa_id]);
                                $lista_equipe = $st->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($lista_equipe as $u): ?>
                                    <tr>
                                        <td><b><?= htmlspecialchars($u['nome']) ?></b></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><span class="badge badge-info"><?= strtoupper($u['nivel']) ?></span></td>
                                        <td class="text-right">
                                            <?php if ($u['id'] != $user_id): ?>
                                                <button class="btn btn-sm btn-outline-danger border-0"
                                                    onclick="excluirUsuario(<?= $u['id'] ?>, '<?= addslashes($u['nome']) ?>')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">Você</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <a href="adicionar_reserva.php" class="fab-reserva" title="Nova Reserva"><i class="fas fa-plus"></i></a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function excluirUsuario(id, nome) {
            const msg = "⚠️ ATENÇÃO: Deseja realmente excluir o acesso de " + nome.toUpperCase() + "?\n\nAVISO: Após a exclusão, este usuário não poderá mais entrar no sistema. Para retornar, será necessário criar um NOVO cadastro.";
            if (confirm(msg)) { window.location.href = "acoes_usuario.php?acao=excluir&id=" + id; }
        }
    </script>
</body>

</html>