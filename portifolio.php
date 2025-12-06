<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfólio de Desenvolvedor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png" type="image/png">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #00796b;
            color: white;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 3em;
            margin: 0;
        }

        .navbar {
            display: flex;
            justify-content: center;
            background-color: #333;
            padding: 10px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            font-size: 1.2em;
            text-transform: uppercase;
        }

        .navbar a:hover {
            background-color: #00796b;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .section-title {
            text-align: center;
            font-size: 2.5em;
            color: #00796b;
            margin: 50px 0 20px;
            font-weight: bold;
        }

        .projects {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 40px 0;
        }

        .project {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 480px;
            margin: 15px;
            text-align: center;
            transition: transform 0.3s ease-in-out;
        }

        .project:hover {
            transform: scale(1.05);
        }

        .project img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .project h3 {
            font-size: 1.6em;
            color: #00796b;
        }

        .project p {
            color: #555;
            font-size: 1.1em;
            margin-top: 15px;
        }

        .cta {
            text-align: center;
            background-color: #00796b;
            color: white;
            padding: 50px 20px;
            border-radius: 12px;
            margin-top: 40px;
        }

        .cta h2 {
            font-size: 2.5em;
            margin: 0 0 20px;
        }

        .cta p {
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .cta a {
            display: inline-flex;
            align-items: center;
            background-color: #ffffff;
            color: #00796b;
            font-size: 1.2em;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            transition: background-color 0.3s ease-in-out;
        }

        .cta a:hover {
            background-color: #f1f1f1;
        }

        .cta a img {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #00796b;
            color: white;
            font-size: 1em;
        }

        .social-links {
            text-align: center;
            margin-top: 30px;
        }

        .social-links a {
            color: #00796b;
            font-size: 1.5em;
            text-decoration: none;
            margin: 0 15px;
        }

        .social-links a:hover {
            color: #004d40;
        }
    </style>
</head>

<body>
    <header>
        <h1>Portfólio de Desenvolvedor Web</h1>
    </header>

    <div class="navbar">
        <a href="#about">Sobre Mim</a>
        <a href="#projects">Projetos</a>
        <a href="#contact">Contato</a>
    </div>

    <div class="container">
        <!-- Sobre Mim -->
        <section id="about">
            <h2 class="section-title">Sobre Mim</h2>
            <p>Sou um desenvolvedor web apaixonado por criar soluções eficazes e de alto desempenho para diferentes tipos de negócios. Tenho uma ampla experiência em desenvolvimento front-end e back-end, utilizando tecnologias como HTML, CSS, JavaScript, PHP, React, Node.js, e bancos de dados relacionais e não relacionais. Ao longo da minha carreira, tenho me dedicado a aprimorar a experiência do usuário e garantir a funcionalidade robusta dos sistemas que crio. Meu compromisso é oferecer soluções que atendam às necessidades do cliente e superem suas expectativas.</p>
        </section>

        <!-- Projetos -->
        <section id="projects">
            <h2 class="section-title">Meus Projetos</h2>
            <div class="projects">
                <div class="project">
                    <img src="https://www.w3schools.com/w3images/fjords.jpg" alt="Sistema de Gestão de Reservas">
                    <h3>Sistema de Gestão de Reservas</h3>
                    <p>Desenvolvimento de um sistema robusto para gerenciamento de reservas, com interface intuitiva e integração com banco de dados. A aplicação conta com formulários dinâmicos, validação de dados e uma gestão eficiente de recursos.</p>
                    <a href="https://github.com/seuusuario/projeto1" target="_blank">Ver no GitHub</a>
                </div>
                <div class="project">
                    <img src="https://www.w3schools.com/w3images/mountains.jpg" alt="Aplicativo de Tarefas">
                    <h3>Aplicativo de Tarefas</h3>
                    <p>Aplicativo desenvolvido com React e Redux para gerenciamento de tarefas. O projeto inclui funcionalidades como adição, edição, e remoção de tarefas, além de integração com APIs externas para persistência de dados.</p>
                    <a href="https://github.com/seuusuario/projeto2" target="_blank">Ver no GitHub</a>
                </div>
                <div class="project">
                    <img src="https://www.w3schools.com/w3images/rocks.jpg" alt="Portfolio Pessoal">
                    <h3>Portfólio Pessoal</h3>
                    <p>Portfólio moderno e responsivo desenvolvido para exibir meus projetos, habilidades e conquistas. Utilizei tecnologias como HTML5, CSS3 e JavaScript para criar uma interface interativa e otimizada para todos os dispositivos.</p>
                    <a href="https://github.com/seuusuario/projeto3" target="_blank">Ver no GitHub</a>
                </div>
            </div>
        </section>

        <!-- Contato -->
        <section id="contact" class="cta">
            <h2>Entre em Contato</h2>
            <p>Estou sempre aberto a novas oportunidades de desenvolvimento. Se você tem uma ideia ou projeto em mente, estou pronto para ajudar a torná-lo realidade. Fique à vontade para me contatar.</p>
            <a href="https://wa.me/5521996169369" target="_blank"><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/1200px-WhatsApp.svg.png" alt="WhatsApp Icon">Fale Conosco no WhatsApp</a>
        </section>

        <!-- Redes Sociais -->
        <section class="social-links">
            <a href="https://www.linkedin.com/in/seulinkedin/" target="_blank">LinkedIn</a>
            <a href="https://github.com/seuusuario" target="_blank">GitHub</a>
        </section>
    </div>

    <footer>
        <p>&copy; 2024 Portfólio - Todos os direitos reservados</p>
    </footer>
</body>

</html>
