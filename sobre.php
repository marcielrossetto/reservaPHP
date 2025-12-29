<?php
session_start();
require 'config.php';
require 'cabecalho.php'; 

$logado = !empty($_SESSION['mmnlogin']);
?>

<style>
    :root {
        --primary: #3699ff;
        --dark: #1e1e2d;
        --success: #1bc5bd;
        --warning: #ffa800;
        --danger: #f64e60;
        --info: #8950fc;
        --light-bg: #f5f8fa;
    }

    /* --- HERO --- */
    .hero-section {
        background: linear-gradient(135deg, #1e1e2d 0%, #2c2c44 100%);
        padding: 100px 20px 140px;
        color: white;
        text-align: center;
        border-bottom-left-radius: 80px;
        border-bottom-right-radius: 80px;
        margin-top: -20px;
    }

    .hero-section h1 { font-size: 3.5rem; font-weight: 900; margin-bottom: 25px; letter-spacing: -2px; }
    .hero-section p { font-size: 1.4rem; opacity: 0.8; max-width: 850px; margin: 0 auto 45px; font-weight: 300; }

    .btn-cta {
        background-color: var(--primary);
        color: white !important;
        padding: 18px 45px;
        border-radius: 15px;
        font-weight: 800;
        font-size: 1.2rem;
        text-decoration: none !important;
        box-shadow: 0 10px 25px rgba(54, 153, 255, 0.3);
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }
    .btn-cta:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(54, 153, 255, 0.5); }

    /* --- GRID DE MÓDULOS --- */
    .container-modules { max-width: 1200px; margin: -80px auto 100px; padding: 0 20px; }
    
    .module-card {
        background: white;
        border-radius: 30px;
        padding: 50px;
        margin-bottom: 40px;
        box-shadow: 0 15px 50px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 60px;
        border: 1px solid #eff2f5;
        transition: 0.4s;
    }
    .module-card:nth-child(even) { flex-direction: row-reverse; }

    .module-text { flex: 1; }
    .module-text h2 { font-size: 2.3rem; font-weight: 800; color: var(--dark); margin-bottom: 25px; letter-spacing: -1px; }
    .module-text p { font-size: 1.15rem; line-height: 1.8; color: #7e8299; }
    
    .module-visual {
        flex: 1;
        background: #f3f6f9;
        border-radius: 25px;
        height: 380px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    /* --- ESTILOS DE LISTA --- */
    .benefit-list { list-style: none; padding: 0; margin-top: 25px; }
    .benefit-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; font-weight: 600; color: var(--dark); font-size: 1.05rem; }
    .benefit-list li i { color: var(--success); font-size: 1.3rem; }

    /* --- MOCKUP DE INPUT INTELIGENTE --- */
    .whatsapp-parser-mockup { width: 85%; background: #e9ecef; border-radius: 15px; padding: 15px; font-family: monospace; font-size: 0.85rem; color: #495057; position: relative; }
    .parser-cursor { position: absolute; bottom: 10px; right: 10px; background: var(--primary); color: white; padding: 5px 12px; border-radius: 50px; font-family: 'Inter'; font-weight: 800; font-size: 0.7rem; }

    /* --- MOCKUP BI --- */
    .bi-bars { display: flex; align-items: flex-end; gap: 8px; height: 100px; }
    .bi-bar { width: 15px; background: var(--primary); border-radius: 3px 3px 0 0; }
    .bi-bar.fila { background: var(--success); }

    .final-cta { background: var(--dark); padding: 100px 20px; text-align: center; color: white; border-radius: 80px 80px 0 0; }

    @media (max-width: 991px) {
        .module-card, .module-card:nth-child(even) { flex-direction: column; padding: 35px; text-align: center; }
        .hero-section h1 { font-size: 2.5rem; }
        .module-visual { width: 100%; height: 280px; }
    }
</style>

<div class="content-wrapper">
    <!-- HERO -->
    <section class="hero-section">
        <div class="container">
            <span class="badge badge-primary mb-3 py-2 px-4 shadow-sm" style="border-radius: 50px; font-weight: 900;">PLATAFORMA BUSINESS INTELLIGENCE</span>
            <h1>Tecnologia que acelera sua recepção.</h1>
            <p>Um ecossistema completo para gerenciar reservas, filas e fidelidade. Desenvolvido para estabelecimentos que não aceitam perder tempo nem clientes.</p>
            
            <?php if($logado): ?>
                <a href="index.php" class="btn-cta"><i class="fas fa-th-large"></i> ACESSAR MEU SISTEMA</a>
            <?php else: ?>
                <a href="login.php" class="btn-cta"><i class="fas fa-sign-in-alt"></i> TESTAR AGORA</a>
            <?php endif; ?>
        </div>
    </section>

    <div class="container-modules">

        <!-- NOVO MÓDULO: CADASTRO EM 10 SEGUNDOS -->
        <div class="module-card shadow-lg">
            <div class="module-text">
                <span class="text-primary fw-bold mb-2 d-block">VELOCIDADE MÁXIMA</span>
                <h2>Cadastro Inteligente via WhatsApp</h2>
                <p>Recebeu os dados do cliente no WhatsApp? Apenas copie e cole o texto no sistema. Nossa inteligência processa nome, telefone, pessoas e horário automaticamente.</p>
                <ul class="benefit-list">
                    <li><i class="fas fa-bolt"></i> <strong>Zero Digitação:</strong> Cadastro completo em menos de 10 segundos.</li>
                    <li><i class="fas fa-check-double"></i> <strong>Detecção de Erros:</strong> O sistema avisa se faltar alguma informação ou se a data for inválida.</li>
                    <li><i class="fas fa-clone"></i> <strong>Anti-Duplicidade:</strong> Alerta instantâneo se o cliente já tiver outra reserva no mesmo dia.</li>
                </ul>
            </div>
            <div class="module-visual">
                <div class="whatsapp-parser-mockup shadow">
                    Nome: Marciel Rossetto<br>
                    Data: 28/12/2025<br>
                    Pax: 15 pessoas<br>
                    Hora: 20:30<br>
                    <div class="parser-cursor">IMPORTAR DADOS <i class="fas fa-magic"></i></div>
                </div>
            </div>
        </div>

        <!-- MÓDULO: BI E DASHBOARD -->
        <div class="module-card">
            <div class="module-text">
                <span class="text-info fw-bold mb-2 d-block">BUSINESS INTELLIGENCE</span>
                <h2>Dashboard Estilo Power BI</h2>
                <p>Visualize a saúde do seu negócio com gráficos avançados. Entenda o comportamento do seu público para planejar sua equipe e estoque.</p>
                <ul class="benefit-list">
                    <li><i class="fas fa-chart-bar"></i> <strong>Gráfico de Palitos:</strong> Comparativo real entre Reservas vs Fila de Espera.</li>
                    <li><i class="fas fa-history"></i> <strong>Lead Time:</strong> Saiba a antecedência média que seus clientes reservam.</li>
                    <li><i class="fas fa-crown"></i> <strong>Top 10 VIPs:</strong> Ranking automático dos clientes que mais frequentam sua casa.</li>
                </ul>
            </div>
            <div class="module-visual">
                <div class="bi-preview shadow">
                    <div class="bi-bars mb-3">
                        <div class="bi-bar" style="height: 40%"></div><div class="bi-bar fila" style="height: 60%"></div>
                        <div class="bi-bar" style="height: 80%"></div><div class="bi-bar fila" style="height: 30%"></div>
                        <div class="bi-bar" style="height: 50%"></div><div class="bi-bar fila" style="height: 90%"></div>
                    </div>
                    <div class="text-muted small text-center fw-bold">Performance Diária de Ocupação</div>
                </div>
            </div>
        </div>

        <!-- MÓDULO: FILA DE ESPERA -->
        <div class="module-card">
            <div class="module-text">
                <span class="text-warning fw-bold mb-2 d-block">EFICIÊNCIA NA PORTA</span>
                <h2>Fila de Espera com Cronômetro</h2>
                <p>Organize a chegada dos clientes sem estresse. Acompanhe o tempo de espera real e chame os próximos via WhatsApp com um clique.</p>
                <ul class="benefit-list">
                    <li><i class="fas fa-stopwatch"></i> <strong>Tempo Real:</strong> Cronômetro individual para cada grupo na fila.</li>
                    <li><i class="fas fa-chair"></i> <strong>Gestão de Mesas:</strong> Defina o número da mesa no momento de sentar o cliente.</li>
                    <li><i class="fas fa-user-minus"></i> <strong>Controle de Desistência:</strong> Métricas precisas sobre quem saiu da fila.</li>
                </ul>
            </div>
            <div class="module-visual">
                <div class="waitlist-visual">
                    <div class="wait-item" style="border-left-color: var(--danger);"><span>1º THIAGO ROCHA</span> <span class="text-danger">15:20m</span></div>
                    <div class="wait-item" style="border-left-color: var(--primary);"><span>2º VINICIUS ARAUJO</span> <span class="text-muted">08:12m</span></div>
                </div>
            </div>
        </div>

        <!-- MÓDULO: AUDITORIA E MULTI-EMPRESA -->
        <div class="module-card">
            <div class="module-text">
                <span class="text-dark fw-bold mb-2 d-block">SEGURANÇA & CONTROLE</span>
                <h2>Hierarquia e Auditoria</h2>
                <p>Saiba exatamente o que acontece. O sistema registra qual funcionário fez cada reserva, garantindo total transparência.</p>
                <ul class="benefit-list">
                    <li><i class="fas fa-user-shield"></i> <strong>Master vs Operacional:</strong> Controle quem pode excluir dados ou ver o financeiro.</li>
                    <li><i class="fas fa-lock"></i> <strong>Isolamento Multi-Empresa:</strong> Seus dados são 100% privados e protegidos.</li>
                    <li><i class="fas fa-database"></i> <strong>Backup em Nuvem:</strong> Seus dados salvos e seguros contra perdas locais.</li>
                </ul>
            </div>
            <div class="module-visual">
                <i class="fas fa-shield-check fa-5x text-primary opacity-25"></i>
                <div style="position: absolute; bottom: 20px; font-weight: 800; color: var(--dark);">PROTEÇÃO DE DADOS ATIVA</div>
            </div>
        </div>

    </div>

    <!-- FINAL CTA -->
    <section class="final-cta">
        <div class="container">
            <h2>Pronto para dominar sua operação?</h2>
            <p class="mb-5 opacity-75">Otimize agora o fluxo do seu restaurante com a melhor ferramenta de BI do mercado.</p>
            <a href="index.php" class="btn-cta"><i class="fas fa-arrow-right"></i> ENTRAR NO SISTEMA</a>
        </div>
    </section>
</div>

<footer class="py-5 text-center text-muted border-top bg-white">
    <p class="mb-1"><strong>ReservaPro BI Edition</strong> &copy; <?= date("Y") ?>. Todos os direitos reservados.</p>
    <small>Versão Multi-Empresa • Engenharia de Software para Alta Performance</small>
</footer>

</body>
</html>