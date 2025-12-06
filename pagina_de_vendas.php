<?php
session_start();
require 'config.php';
require 'cabecalho.php'; // Mantém o menu superior para navegação

// Verifica se o usuário já está logado para mudar o botão principal
$logado = !empty($_SESSION['mmnlogin']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReservaPro - Inteligência para Restaurantes</title>
    
    <!-- Fontes e Ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --dark: #0f172a;
            --light: #f8fafc;
            --accent: #3b82f6;
            --gradient-start: #1e293b;
            --gradient-end: #0f172a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
            color: #334155;
            overflow-x: hidden;
        }

        /* --- HERO SECTION (TOPO) --- */
        .hero {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 80px 20px 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
        }

        /* Efeito de fundo sutil */
        .hero::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 60%);
            animation: rotate 60s linear infinite;
        }

        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .hero-badge {
            background: rgba(255,255,255,0.1);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 1.25rem;
            color: #cbd5e1;
            max-width: 700px;
            margin: 0 auto 40px;
            line-height: 1.6;
        }

        .hero-btn {
            background: var(--primary);
            color: white;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.5);
            background: #1d4ed8;
            color: white;
            text-decoration: none;
        }

        /* --- DASHBOARD PREVIEW --- */
        .preview-container {
            margin-top: -60px;
            padding: 0 20px;
            display: flex;
            justify-content: center;
            position: relative;
            z-index: 10;
        }

        .dashboard-mockup {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            max-width: 1000px;
            width: 100%;
            padding: 20px;
            border: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .mock-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .mock-val { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
        .mock-lbl { font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; }

        /* --- FEATURES --- */
        .features {
            padding: 100px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        .section-title h2 { font-size: 2.5rem; font-weight: 700; color: var(--dark); }
        .section-title p { color: #64748b; font-size: 1.1rem; }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 35px;
            border-radius: 20px;
            transition: 0.3s;
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            border-color: var(--primary);
        }

        .f-icon {
            width: 60px; height: 60px;
            background: #eff6ff;
            color: var(--primary);
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 25px;
        }

        .feature-card h3 { font-size: 1.4rem; font-weight: 700; margin-bottom: 15px; color: var(--dark); }
        .feature-card p { color: #64748b; line-height: 1.6; }

        /* --- STATS STRIP --- */
        .stats-strip {
            background: var(--dark);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }
        .stats-grid-strip {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .stat-item h4 { font-size: 3rem; font-weight: 800; color: var(--accent); margin: 0; }
        .stat-item p { font-size: 1.1rem; opacity: 0.8; }

        /* --- FOOTER --- */
        footer {
            background: white;
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            margin-top: 50px;
        }

        @media(max-width: 768px) {
            .hero h1 { font-size: 2.2rem; }
            .dashboard-mockup { grid-template-columns: 1fr 1fr; }
            .stats-grid-strip { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- HERO SECTION -->
<header class="hero">
    <span class="hero-badge">Versão Premium 2.0</span>
    <h1>Não Apenas Organize.<br>Domine Suas Reservas.</h1>
    <p>A plataforma completa de Business Intelligence (BI) e gestão que transforma o atendimento do seu restaurante em uma máquina de fidelização.</p>
    
    <?php if($logado): ?>
        <a href="dashboard.php" class="hero-btn"><i class="fas fa-rocket"></i> Acessar Meu Dashboard</a>
    <?php else: ?>
        <a href="login.php" class="hero-btn"><i class="fas fa-sign-in-alt"></i> Entrar no Sistema</a>
    <?php endif; ?>
</header>

<!-- PREVIEW (MOCKUP) -->
<div class="preview-container">
    <div class="dashboard-mockup">
        <div class="mock-card">
            <div class="mock-val text-primary">150+</div>
            <div class="mock-lbl">Reservas Hoje</div>
        </div>
        <div class="mock-card">
            <div class="mock-val text-success">98%</div>
            <div class="mock-lbl">Presença Confirmada</div>
        </div>
        <div class="mock-card">
            <div class="mock-val text-warning">24d</div>
            <div class="mock-lbl">Ciclo de Retorno</div>
        </div>
        <div class="mock-card">
            <div class="mock-val text-danger">3%</div>
            <div class="mock-lbl">Cancelamentos</div>
        </div>
    </div>
</div>

<!-- FEATURES -->
<section class="features">
    <div class="section-title">
        <h2>Por que escolher este sistema?</h2>
        <p>Ferramentas poderosas desenhadas para eliminar o "No-Show" e aumentar o lucro.</p>
    </div>

    <div class="feature-grid">
        
        <!-- Feature 1 -->
        <div class="feature-card">
            <div class="f-icon"><i class="fas fa-chart-pie"></i></div>
            <h3>Inteligência de Dados (BI)</h3>
            <p>Não apenas guarde dados. Entenda-os. Saiba quais eventos trazem mais gente, a antecedência média de reserva e os motivos reais de cancelamento.</p>
        </div>

        <!-- Feature 2 -->
        <div class="feature-card">
            <div class="f-icon"><i class="fab fa-whatsapp"></i></div>
            <h3>Automação WhatsApp</h3>
            <p>Esqueça a digitação manual. Com um clique, o sistema abre o WhatsApp do cliente com uma mensagem personalizada de confirmação.</p>
        </div>

        <!-- Feature 3 -->
        <div class="feature-card">
            <div class="f-icon"><i class="fas fa-user-clock"></i></div>
            <h3>Histórico & Fidelidade</h3>
            <p>Ao digitar um telefone, o sistema puxa a "capivara" do cliente: quantas vezes veio, se costuma cancelar e há quanto tempo não aparece.</p>
        </div>

        <!-- Feature 4 -->
        <div class="feature-card">
            <div class="f-icon"><i class="fas fa-calendar-check"></i></div>
            <h3>Gestão Visual</h3>
            <p>Controle visual de status: Verde para confirmados, Laranja para pendentes. Saiba a ocupação do salão em tempo real.</p>
        </div>

        <!-- Feature 5 -->
        <div class="feature-card">
            <div class="f-icon"><i class="fas fa-shield-halved"></i></div>
            <h3>Admin & Segurança</h3>
            <p>Painel administrativo completo com gestão de usuários, backup automático do banco de dados e controle de preços do rodízio.</p>
        </div>

        <!-- Feature 6 -->
        <div class="feature-card">
            <div class="f-icon"><i class="fas fa-mobile-screen"></i></div>
            <h3>100% Responsivo</h3>
            <p>Use no computador da recepção ou no celular do gerente. O layout se adapta perfeitamente, mantendo tabelas e gráficos legíveis.</p>
        </div>

    </div>
</section>

<!-- STATS STRIP -->
<section class="stats-strip">
    <div class="stats-grid-strip">
        <div class="stat-item">
            <h4>-30%</h4>
            <p>Redução média de No-Show (Faltas)</p>
        </div>
        <div class="stat-item">
            <h4>+15h</h4>
            <p>Horas economizadas por mês na recepção</p>
        </div>
        <div class="stat-item">
            <h4>100%</h4>
            <p>Controle sobre sua operação</p>
        </div>
    </div>
</section>

<footer>
    <p><strong>Sistema de Gestão Premium</strong> &copy; <?= date("Y"); ?>. Todos os direitos reservados.</p>
    <small>Desenvolvido para alta performance em restaurantes e eventos.</small>
</footer>

</body>
</html>