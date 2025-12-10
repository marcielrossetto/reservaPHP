<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Inicializa variáveis padrão
$nome_usuario = "Visitante";
$status = null;
$letra_avatar = "U";

// Busca Preços para o Modal (Necessário para o select de valores)
$ultimo_preco = null;
try {
    $sqlPreco = $pdo->query("SELECT * FROM preco_rodizio ORDER BY id DESC LIMIT 1");
    if($sqlPreco->rowCount() > 0){
        $ultimo_preco = $sqlPreco->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { }

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
    } catch (PDOException $e) { }
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
        :root { --primary-bg: #1e1e2d; --active-color: #3699ff; --text-color: #a2a3b7; --text-hover: #ffffff; }
        body { padding-top: 75px; background-color: #f5f8fa; }

        /* NAVBAR */
        .navbar-custom { background-color: var(--primary-bg); box-shadow: 0 0 15px rgba(0,0,0,0.1); padding: 0.5rem 1rem; }
        .navbar-brand img { height: 35px; transition: transform 0.2s; }
        .navbar-brand:hover img { transform: scale(1.05); }

        .navbar-dark .navbar-nav .nav-link { color: var(--text-color); font-weight: 500; font-size: 0.95rem; display: flex; align-items: center; gap: 6px; padding: 0.8rem 1rem; border-radius: 6px; transition: all 0.2s ease; }
        .navbar-dark .navbar-nav .nav-link:hover, .navbar-dark .navbar-nav .show > .nav-link { color: var(--text-hover); background-color: rgba(255,255,255,0.05); }

        /* --- BOTÃO DESTAQUE "NOVA RESERVA" (Sinal de + e Bolinha) --- */
        .btn-nova-reserva {
            background-color: #3699ff;
            color: #ffffff !important;
            border-radius: 50px; /* Formato pílula/bolinha */
            padding: 8px 24px !important;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(54, 153, 255, 0.4);
            transition: all 0.3s ease;
            margin-right: 15px;
        }
        .btn-nova-reserva:hover {
            background-color: #2b7cce;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(54, 153, 255, 0.6);
            color: #fff !important;
            text-decoration: none;
        }
        .icon-plus-circle {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* PERFIL NAV */
        .user-profile-widget { cursor: pointer; padding: 5px 10px; border-radius: 50px; transition: background 0.2s; display: flex; align-items: center; gap: 10px; }
        .user-profile-widget:hover { background-color: rgba(255,255,255,0.1); }
        .user-avatar { width: 38px; height: 38px; background: linear-gradient(135deg, #3699ff, #0055ff); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 3px 6px rgba(0,0,0,0.2); border: 2px solid rgba(255,255,255,0.1); }
        .user-info { display: flex; flex-direction: column; line-height: 1.1; }
        .user-name { color: white; font-weight: 600; font-size: 0.9rem; }
        .user-role { color: var(--text-color); font-size: 0.7rem; text-transform: uppercase; }
        
        .dropdown-menu { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); margin-top: 10px; padding: 10px 0; }
        .dropdown-item { padding: 10px 20px; font-size: 0.9rem; color: #3f4254; display: flex; align-items: center; gap: 10px; }
        .dropdown-item:hover { background-color: #f3f6f9; color: var(--active-color); }
        
        @media (max-width: 991px) {
            .user-info { display: none; }
            .user-profile-widget { margin-right: 10px; padding: 0; }
            .navbar-collapse { background: var(--primary-bg); padding: 15px; border-radius: 0 0 15px 15px; margin-top: 10px; }
            .btn-nova-reserva { width: 100%; justify-content: center; margin-bottom: 15px; }
        }

        /* ESTILOS DO MODAL E PERFIL INTERNO */
        .modal-ios .modal-content { border-radius: 22px; border: none; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
        .modal-ios .modal-header { border-bottom: 1px solid #f0f0f0; padding: 15px 25px; }
        .modal-ios .modal-body { padding: 20px 25px; background: #f9f9fb; }
        .form-control-ios { border-radius: 12px; border: 1px solid #d1d1d6; padding: 8px 12px; background: #fff; }
        .btn-ios-primary { background:#007AFF; color:#fff; border:none; border-radius:999px; padding:10px 20px; font-weight:600; width:100%; transition:0.2s; }
        .btn-ios-secondary { background:#e5e5ea; color:#333; border:none; border-radius:999px; padding:8px 15px; font-weight:600; transition:0.2s; }

        .profile-card-modal { border: 1px solid #28a745; background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .profile-header { display: flex; align-items: flex-start; gap: 15px; }
        .avatar-circle-modal { width: 50px; height: 50px; background: #f0f0f0; border-radius: 50%; display: flex; justify-content: center; align-items: center; flex-shrink: 0; }
        .client-details h5 { margin: 0; font-size: 1rem; font-weight: 800; color: #333; }
        .client-details p { margin: 2px 0 0; font-size: 0.85rem; color: #666; }
        .stats-grid-modal { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px; }
        .stat-box { text-align: left; }
        .stat-box small { font-size: 0.7rem; color: #888; display: block; }
        .stat-box strong { font-size: 0.9rem; color: #333; }
        .alert-obs-modal { background: #fff3cd; color: #856404; padding: 8px; border-radius: 6px; font-size: 0.8rem; margin-top: 10px; border: 1px solid #ffeeba; }
        .btn-trocar-cliente { font-size: 0.75rem; color: #007AFF; cursor: pointer; background: none; border: none; padding: 0; text-decoration: underline; margin-top: 5px; }
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
            <div class="px-4 py-2">
                <small class="text-muted">Olá,</small><br>
                <strong><?= htmlspecialchars($nome_usuario) ?></strong>
            </div>
            <div class="dropdown-divider"></div>
            <?php if ($status == 1): ?>
                <a class="dropdown-item" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a class="dropdown-item" href="painel_administrativo.php"><i class="fas fa-cogs"></i> Painel Admin</a>
            <?php endif; ?>
            <a class="dropdown-item" href="pagina_de_vendas.php"><i class="fas fa-info-circle"></i> Sobre o Sistema</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="sair.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>

    <!-- TOGGLER -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menuPrincipal">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- MENU PRINCIPAL -->
    <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav mr-auto ml-3 align-items-lg-center">
            
            <!-- BOTÃO COM SINAL DE + E BOLINHA -->
       <li class="nav-item">
    <!-- O código novo vai para o ARQUIVO -->
    <a class="nav-link btn-nova-reserva" href="adicionar_reserva.php">
        <span class="icon-plus-circle"><i class="fas fa-plus"></i></span>
        Nova Reserva
    </a>
</li>

            <li class="nav-item">
                <a class="nav-link" href="index.php"><i class="material-icons-outlined">home</i> Home</a>
            </li>
            
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropPesquisa" data-toggle="dropdown">
                    <i class="material-icons-outlined">search</i> Pesquisar
                </a>
                <div class="dropdown-menu shadow-sm">
                    <a class="dropdown-item" href="pesquisar.php"><i class="fas fa-search"></i> Pesquisa Geral</a>
                    <a class="dropdown-item" href="pesquisar_data.php"><i class="far fa-calendar-alt"></i> Por Data</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="ultimasReservas.php"><i class="fas fa-history"></i> Reservas criadas</a>
                    <a class="dropdown-item" href="confirmar_reserva.php"><i class="fas fa-check-double"></i> Confirmar Reservas</a>
                </div>
            </li>

            <?php if ($status == 1): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropAdmin" data-toggle="dropdown">
                    <i class="material-icons-outlined">admin_panel_settings</i> Admin
                </a>
                <div class="dropdown-menu shadow-sm">
                    <a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a class="dropdown-item" href="painel_administrativo.php"><i class="fas fa-cogs"></i> Painel Admin</a>
                </div>
            </li>
            <?php endif; ?>
        </ul>

        <!-- WIDGET USUÁRIO (DESKTOP) -->
        <ul class="navbar-nav d-none d-lg-flex align-items-center">
            <li class="nav-item dropdown">
                <div class="user-profile-widget" data-toggle="dropdown">
                    <div class="user-info text-right">
                        <span class="user-name"><?= htmlspecialchars($nome_usuario) ?></span>
                        <span class="user-role"><?= ($status == 1) ? 'Administrador' : 'Colaborador' ?></span>
                    </div>
                    <div class="user-avatar"><?= $letra_avatar ?></div>
                    <i class="fas fa-chevron-down text-muted ml-1" style="font-size: 0.8rem;"></i>
                </div>
                
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="px-3 py-2">
                        <small class="text-muted">Logado como</small><br>
                        <strong><?= htmlspecialchars($nome_usuario) ?></strong>
                    </div>
                    <div class="dropdown-divider"></div>
                    
                    <?php if ($status == 1): ?>
                        <a class="dropdown-item" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                        <a class="dropdown-item" href="painel_administrativo.php"><i class="fas fa-cogs"></i> Painel Admin</a>
                        <div class="dropdown-divider"></div>
                    <?php endif; ?>

                    <a class="dropdown-item" href="pagina_de_vendas.php"><i class="fas fa-info-circle"></i> Sobre o Sistema</a>
                    <a class="dropdown-item text-danger" href="sair.php"><i class="fas fa-sign-out-alt"></i> Sair do Sistema</a>
                </div>
            </li>
        </ul>
    </div>
</nav>

<!-- ========================================== -->
<!-- MODAL NOVA RESERVA (FORMULÁRIO AJAX) -->
<!-- ========================================== -->
<div class="modal fade modal-ios" id="modalNovaReserva" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Nova Reserva</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                
                <!-- Área WhatsApp -->
                <div class="mb-3 p-3 bg-white rounded shadow-sm border">
                    <label class="font-weight-bold text-muted small text-uppercase">Copiar do WhatsApp</label>
                    <textarea id="mdl_whats_dados" class="form-control form-control-ios mb-2" rows="2" placeholder="Cole aqui o texto da reserva..."></textarea>
                    <div class="d-flex gap-2">
                        <button type="button" onclick="importarWhatsModal()" class="btn-ios-secondary mr-2"><i class="fas fa-file-import"></i> Preencher</button>
                        <button type="button" onclick="analisarSalvarDiretoModal(this)" class="btn-ios-secondary text-primary"><i class="fas fa-bolt"></i> Salvar Direto</button>
                    </div>
                </div>

                <!-- Formulário Manual -->
                <form id="formModalReserva" onsubmit="event.preventDefault(); verificarEEnviarModal();">
                    
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small font-weight-bold">Telefone (Busca Automática)</label>
                            <input type="text" id="mdl_telefone" name="telefone" class="form-control form-control-ios" maxlength="15" required onkeyup="maskPhone(this)" onblur="buscarTelefoneModal()">
                        </div>
                    </div>

                    <!-- CARD DE PERFIL (ESCONDIDO INICIALMENTE) -->
                    <div id="mdl_card_perfil" class="profile-card-modal">
                        <div class="profile-header">
                            <div class="avatar-circle-modal"><i class="fas fa-user text-secondary"></i></div>
                            <div class="client-details">
                                <h5 id="mdl_lbl_nome">--</h5>
                                <p>Última: <strong id="mdl_lbl_ultima">--</strong> (<span id="mdl_lbl_tempo">--</span>)</p>
                                <button type="button" class="btn-trocar-cliente" onclick="resetarClienteModal()">Trocar Cliente / Novo</button>
                            </div>
                        </div>
                        <div class="stats-grid-modal">
                            <div class="stat-box"><small>Total</small><strong id="mdl_stat_total">0</strong></div>
                            <div class="stat-box"><small>Cancel</small><strong id="mdl_stat_cancel" class="text-danger">0</strong></div>
                            <div class="stat-box"><small>Histórico</small><strong id="mdl_stat_hist" style="font-size:0.75rem;">--</strong></div>
                        </div>
                        <div id="mdl_area_obs_db" class="alert-obs-modal" style="display:none;">
                            <strong>⚠️ Obs Cliente:</strong> <span id="mdl_txt_obs_db"></span>
                        </div>
                    </div>

                    <!-- Input Nome -->
                    <div class="mb-2" id="mdl_div_nome">
                        <label class="small font-weight-bold">Nome</label>
                        <input type="text" id="mdl_nome" name="nome" class="form-control form-control-ios" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="small font-weight-bold">Data</label>
                            <input type="date" id="mdl_data" name="data" class="form-control form-control-ios" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="small font-weight-bold">Horário</label>
                            <input type="time" id="mdl_horario" name="horario" class="form-control form-control-ios" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="small font-weight-bold">Pessoas</label>
                            <input type="number" id="mdl_num_pessoas" name="num_pessoas" class="form-control form-control-ios" required>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="small font-weight-bold">Tel. Alternativo</label>
                            <input type="text" id="mdl_telefone2" name="telefone2" class="form-control form-control-ios" onkeyup="maskPhone(this)">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small font-weight-bold">Pagamento</label>
                            <select name="forma_pagamento" id="mdl_forma_pagamento" class="form-control form-control-ios">
                                <option value="Não definido">Selecione</option>
                                <option value="unica">Única</option>
                                <option value="individual">Individual</option>
                                <option value="U (rod) I (beb)">Única (rod) Individual (beb)</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small font-weight-bold">Evento</label>
                            <select name="tipo_evento" id="mdl_tipo_evento" class="form-control form-control-ios">
                                <option value="">Selecione</option>
                                <option value="Aniversario">Aniversário</option>
                                <option value="Conf. fim de ano">Confraternização</option>
                                <option value="Formatura">Formatura</option>
                                <option value="Casamento">Casamento</option>
                                <option value="Conf. Familia">Família</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="small font-weight-bold">Valor Rodízio</label>
                            <select name="valor_rodizio" id="mdl_valor_rodizio" class="form-control form-control-ios">
                                <option value="">Selecione</option>
                                <?php if ($ultimo_preco): ?>
                                    <option value="<?= $ultimo_preco['almoco'] ?>">Almoço - R$ <?= $ultimo_preco['almoco'] ?></option>
                                    <option value="<?= $ultimo_preco['jantar'] ?>">Jantar - R$ <?= $ultimo_preco['jantar'] ?></option>
                                    <option value="<?= $ultimo_preco['outros'] ?>">Sábado - R$ <?= $ultimo_preco['outros'] ?></option>
                                    <option value="<?= $ultimo_preco['domingo_almoco'] ?>">Dom. Almoço - R$ <?= $ultimo_preco['domingo_almoco'] ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="small font-weight-bold">Mesa</label>
                            <select name="num_mesa" id="mdl_num_mesa" class="form-control form-control-ios">
                                <option value="">Auto</option>
                                <option value="Salão 1">Salão 1</option>
                                <option value="Salão 2">Salão 2</option>
                                <option value="Próximo à janela">Janela</option>
                                <?php for ($i = 1; $i <= 99; $i++) echo "<option value='$i'>$i</option>"; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small font-weight-bold">Observações</label>
                        <textarea id="mdl_observacoes" name="observacoes" class="form-control form-control-ios" rows="2"></textarea>
                    </div>

                    <button type="submit" id="btnSalvarModal" class="btn-ios-primary">Cadastrar Reserva</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Correção Menu Mobile
    $(document).ready(function(){
        $('.navbar-nav .nav-link').on('click', function(){
            if(!$(this).hasClass('dropdown-toggle') && $(this).attr('data-target') !== '#modalNovaReserva'){
                $('.navbar-collapse').collapse('hide');
            }
        });
    });

    function maskPhone(input) {
        let v = input.value.replace(/\D/g, "");
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length <= 10) { v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d{4})(\d)/, "$1-$2"); }
        else { v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d{5})(\d)/, "$1-$2"); }
        input.value = v;
    }

    // BUSCAR PERFIL NO MODAL
    function buscarTelefoneModal() {
        const telefone = document.getElementById('mdl_telefone').value.replace(/\D/g, '');
        if (telefone.length < 10) return;

        fetch(`adicionar_reserva.php?acao=buscar_perfil&telefone=${telefone}`)
        .then(r => r.json())
        .then(data => {
            if (data.encontrado) {
                const p = data.perfil;
                document.getElementById('mdl_lbl_nome').innerText = p.nome;
                document.getElementById('mdl_lbl_ultima').innerText = p.ultima_visita_data;
                document.getElementById('mdl_lbl_tempo').innerText = p.tempo_atras;
                document.getElementById('mdl_stat_total').innerText = p.total_reservas;
                document.getElementById('mdl_stat_cancel').innerText = p.canceladas;
                document.getElementById('mdl_stat_hist').innerText = p.historico_recente;

                const areaObs = document.getElementById('mdl_area_obs_db');
                if(p.obs_cliente && p.obs_cliente !== "Nenhuma observação registrada.") {
                    document.getElementById('mdl_txt_obs_db').innerText = p.obs_cliente;
                    areaObs.style.display = 'block';
                } else { areaObs.style.display = 'none'; }

                document.getElementById('mdl_nome').value = p.nome;
                document.getElementById('mdl_card_perfil').style.display = 'block';
            } else {
                resetarClienteModal();
            }
        });
    }

    function resetarClienteModal() {
        document.getElementById('mdl_card_perfil').style.display = 'none';
        document.getElementById('mdl_nome').value = '';
    }

    // IMPORTAR WHATSAPP
    function importarWhatsModal() {
        const texto = document.getElementById('mdl_whats_dados').value.trim();
        if (!texto) { alert('Cole dados.'); return; }
        const linhas = texto.split('\n');
        linhas.forEach(linha => {
            const partes = linha.split(':');
            if (partes.length < 2) return;
            const c = partes[0].trim().toLowerCase();
            const v = partes.slice(1).join(':').trim();
            if(c.includes('nome')) document.getElementById('mdl_nome').value = v;
            if(c.includes('telefone') && !c.includes('alt')) { 
                document.getElementById('mdl_telefone').value = v.replace(/\D/g,''); 
                maskPhone(document.getElementById('mdl_telefone')); 
                buscarTelefoneModal(); 
            }
            if(c.includes('data')) { if(v.includes('/')) { const [d,m,y] = v.split('/'); document.getElementById('mdl_data').value = `${y}-${m}-${d}`; } else document.getElementById('mdl_data').value = v; }
            if(c.includes('hor')) document.getElementById('mdl_horario').value = v;
            if(c.includes('pessoas')) document.getElementById('mdl_num_pessoas').value = v;
            if(c.includes('pagamento')) document.getElementById('mdl_forma_pagamento').value = v;
            if(c.includes('observa')) document.getElementById('mdl_observacoes').value = v;
            if(c.includes('mesa')) document.getElementById('mdl_num_mesa').value = v;
        });
    }

    // SALVAR DIRETO
    async function analisarSalvarDiretoModal(btn) {
        const texto = document.getElementById('mdl_whats_dados').value.trim();
        if (!texto) { alert('Cole dados.'); return; }
        const txtOriginal = btn.innerHTML;
        btn.innerHTML = "<i class='fas fa-spinner fa-spin'></i>"; btn.disabled = true;

        let fd = new FormData(); fd.append('acao', 'analisar_whats'); fd.append('whats_text', texto);
        try {
            let req = await fetch('adicionar_reserva.php', { method: 'POST', body: fd });
            let res = await req.json();
            btn.innerHTML = txtOriginal; btn.disabled = false;

            if (!res.success) { alert(res.message); return; }
            
            let listaParaSalvar = [];
            for (let item of res.lista) {
                if (!item.valido) { alert(`ERRO em ${item.dados.nome}: ` + item.erros.join(', ')); continue; }
                if (item.duplicado) {
                    if (confirm(`⚠️ DUPLICIDADE: ${item.dados.nome} já existe dia ${item.dados.data}.\nCriar mesmo assim?`)) listaParaSalvar.push(item.dados);
                } else { listaParaSalvar.push(item.dados); }
            }
            if (listaParaSalvar.length > 0) salvarListaFinalModal(listaParaSalvar);
        } catch (e) { btn.innerHTML = txtOriginal; btn.disabled = false; alert('Erro ao analisar.'); }
    }

    async function salvarListaFinalModal(lista) {
        let fd = new FormData(); fd.append('acao', 'salvar_lista_final'); fd.append('lista_json', JSON.stringify(lista));
        try {
            let req = await fetch('adicionar_reserva.php', { method: 'POST', body: fd });
            let res = await req.json();
            if (res.success) {
                $('#modalNovaReserva').modal('hide');
                mostrarSucesso(res.links[0].link, "Reservas salvas com sucesso!");
            }
        } catch (e) { alert('Erro ao salvar.'); }
    }

    // VERIFICAR E ENVIAR
    function verificarEEnviarModal() {
        const tel = document.getElementById('mdl_telefone').value.replace(/\D/g, '');
        const data = document.getElementById('mdl_data').value;
        const nome = document.getElementById('mdl_nome').value;
        if (tel.length < 10 || !data || !nome) { alert('Preencha os campos obrigatórios.'); return; }
        
        const btn = document.getElementById('btnSalvarModal'); btn.disabled = true; btn.innerText = "Verificando...";
        
        fetch(`adicionar_reserva.php?acao=checar_duplicidade&telefone=${tel}&data=${data}&nome=${encodeURIComponent(nome)}`)
        .then(r => r.json()).then(resp => {
            if(resp.erro_data) {
                btn.disabled = false; btn.innerText = "Cadastrar Reserva";
                alert(resp.msg); return;
            }
            if (resp.existe) { 
                if (confirm(`Já existe uma reserva para ${nome} nesta data. Continuar?`)) enviarReservaAjaxModal(); 
                else { btn.disabled = false; btn.innerText = "Cadastrar Reserva"; }
            } else {
                enviarReservaAjaxModal();
            }
        }).catch(e => { btn.disabled = false; btn.innerText = "Cadastrar Reserva"; alert("Erro na verificação."); });
    }

    function enviarReservaAjaxModal() {
        const fd = new FormData();
        fd.append('nome', document.getElementById('mdl_nome').value);
        fd.append('telefone', document.getElementById('mdl_telefone').value);
        fd.append('data', document.getElementById('mdl_data').value);
        fd.append('horario', document.getElementById('mdl_horario').value);
        fd.append('num_pessoas', document.getElementById('mdl_num_pessoas').value);
        fd.append('telefone2', document.getElementById('mdl_telefone2').value);
        fd.append('forma_pagamento', document.getElementById('mdl_forma_pagamento').value);
        fd.append('tipo_evento', document.getElementById('mdl_tipo_evento').value);
        fd.append('valor_rodizio', document.getElementById('mdl_valor_rodizio').value);
        fd.append('num_mesa', document.getElementById('mdl_num_mesa').value);
        fd.append('observacoes', document.getElementById('mdl_observacoes').value);

        fetch('adicionar_reserva.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            const btn = document.getElementById('btnSalvarModal');
            btn.disabled = false; btn.innerText = "Cadastrar Reserva";

            if(res.success) {
                $('#modalNovaReserva').modal('hide');
                document.getElementById('formModalReserva').reset();
                resetarClienteModal();
                mostrarSucesso(res.link_wpp);
            } else {
                alert(res.message || "Erro ao salvar.");
            }
        }).catch(e => { 
            document.getElementById('btnSalvarModal').disabled = false; 
            alert("Erro ao enviar."); 
        });
    }

    function mostrarSucesso(linkWpp, titulo = "Reserva Salva!") {
        let modalHtml = `<div id="modalSucessoTopo" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; display:flex; justify-content:center; align-items:center;">
            <div style="background:white; padding:30px; border-radius:15px; text-align:center; width:90%; max-width:350px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
                <div style="color:#28a745; font-size:40px; margin-bottom:10px;"><i class="fas fa-check-circle"></i></div>
                <h3 style="margin:0 0 10px 0;">${titulo}</h3>
                <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                    <button onclick="window.open('${linkWpp}', '_blank')" class="btn btn-success rounded-pill font-weight-bold py-2"><i class="fab fa-whatsapp"></i> Confirmar no Zap</button>
                    <button onclick="document.getElementById('modalSucessoTopo').remove(); window.location.reload();" class="btn btn-light rounded-pill border py-2">Fechar e Atualizar</button>
                </div>
            </div></div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
</script>

</body>
</html>