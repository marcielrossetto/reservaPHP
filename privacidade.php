<?php
session_start();
// Não exigimos login aqui para que futuros clientes possam ler antes de contratar
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - ReservaPro BI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f7fa; font-family: 'Inter', sans-serif; color: #3f4254; line-height: 1.6; }
        .container-privacy { max-width: 800px; margin: 50px auto; padding: 0 20px; }
        .card-privacy { background: white; border-radius: 25px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #ebedf3; }
        h1 { font-weight: 800; color: #1e1e2d; margin-bottom: 30px; border-bottom: 3px solid #3699ff; display: inline-block; }
        h2 { font-size: 1.4rem; font-weight: 700; color: #1e1e2d; margin-top: 30px; }
        p, li { font-size: 0.95rem; color: #7e8299; }
        .highlight { color: #3699ff; font-weight: 600; }
        .back-btn { text-decoration: none; color: #3699ff; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container-privacy">
    <a href="javascript:history.back()" class="back-btn">← Voltar</a>
    
    <div class="card-privacy">
        <h1>Política de Privacidade</h1>
        <p>A <strong>Rossetto TI</strong> (ReservaPro BI Edition) valoriza a privacidade e a segurança dos dados de seus clientes (estabelecimentos) e dos usuários finais (clientes dos estabelecimentos). Esta política descreve como coletamos, usamos e protegemos suas informações.</p>

        <h2>1. Coleta de Informações</h2>
        <p>Coletamos dados necessários para a operação do sistema de reservas, incluindo:</p>
        <ul>
            <li><span class="highlight">Estabelecimentos:</span> Nome da empresa, CNPJ/CPF, e-mail administrativo, logotipos e dados de faturamento.</li>
            <li><span class="highlight">Usuários do Sistema:</span> Nome e credenciais de acesso para fins de auditoria (saber quem realizou cada reserva).</li>
            <li><span class="highlight">Clientes Finais:</span> Nome, número de telefone (WhatsApp) e histórico de comparecimento/cancelamento.</li>
        </ul>

        <h2>2. Uso dos Dados e Business Intelligence</h2>
        <p>Os dados coletados são utilizados exclusivamente para:</p>
        <ul>
            <li>Gerenciamento de reservas e fila de espera em tempo real.</li>
            <li>Geração de gráficos de <span class="highlight">Business Intelligence (BI)</span>, como taxa de ocupação, Lead Time (antecedência de reserva) e ranking de clientes VIP.</li>
            <li>Facilitar o contato via WhatsApp para confirmação de agendamentos.</li>
        </ul>

        <h2>3. Segurança e Isolamento (Multi-Tenant)</h2>
        <p>Nosso sistema utiliza arquitetura de <strong>Isolamento Multi-Empresa</strong>. Isso significa que:</p>
        <ul>
            <li>Os dados de uma empresa são tecnicamente isolados das demais através de chaves de segurança (ID de Empresa).</li>
            <li>Nenhuma empresa tem acesso aos nomes, telefones ou estatísticas de clientes de outro estabelecimento concorrente.</li>
            <li>As senhas são armazenadas com criptografia (Hashing) e as logos são salvas em bancos de dados binários seguros.</li>
        </ul>

        <h2>4. Direitos do Usuário (LGPD)</h2>
        <p>Em conformidade com a LGPD brasileira, os usuários e estabelecimentos têm direito a:</p>
        <ul>
            <li>Solicitar a correção de dados incompletos ou inexatos.</li>
            <li>Solicitar a exclusão definitiva de usuários de sua equipe.</li>
            <li>Obter informações sobre o compartilhamento de dados (que não ocorre com terceiros).</li>
        </ul>

        <h2>5. Responsabilidade do Estabelecimento</h2>
        <p>O estabelecimento cliente é o <strong>Controlador de Dados</strong> de seus próprios clientes. O ReservaPro atua como <strong>Operador</strong>, fornecendo a ferramenta técnica para o processamento dessas informações.</p>

        <h2>6. Retenção de Dados</h2>
        <p>Mantemos os dados de reservas para fins estatísticos históricos do estabelecimento. Em caso de cancelamento da licença por mais de 90 dias, o sistema reserva-se o direito de realizar a limpeza definitiva da base de dados daquela empresa.</p>

        <div class="mt-5 pt-4 border-top">
            <p class="small text-muted">Última atualização: Dezembro de 2025.<br>
            <strong>Rossetto TI - Inteligência para Gastronomia</strong></p>
        </div>
    </div>
</div>

</body>
</html>