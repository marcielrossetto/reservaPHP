<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rodapé</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos gerais */
        body {
            padding-bottom: 100px; /* Espaço para o rodapé fixo */
            margin: 0;
            font-family: Arial, sans-serif;
        }

        #footer {
            background-color: #343a40;
            color: white;
            font-size: 18px;
            height: 50px; /* Altura do rodapé */
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            z-index: 9999;
            transition: opacity 0.5s;
        }

        #footer .social-icons {
            display: flex;
        }

        #footer .social-icons a {
            color: white;
            font-size: 30px;
            margin-right: 15px;
        }

        #footer .social-icons a:hover {
            color: #007bff; /* Mudando a cor ao passar o mouse */
        }

        #footer .privacy-link {
            font-size: 18px;
            text-decoration: none;
            color: white;
        }

        #footer .privacy-link:hover {
            color: #007bff;
        }

        /* Ajustes para telas menores */
        @media (max-width: 768px) {
            #footer {
                flex-direction: column;
                height: auto;
                padding: 10px 0;
            }

            #footer .social-icons {
                justify-content: center;
                margin-bottom: 10px;
            }

            #footer .privacy-link {
                font-size: 14px; /* Reduzir o tamanho do texto */
            }
        }
    </style>
</head>
<body>
    <!-- Rodapé -->
    <footer id="footer">
        <div class="social-icons">
            <a href="https://www.facebook.com" target="_blank">
                <i class="fab fa-facebook-f"></i> <!-- Ícone do Facebook -->
            </a>
            <a href="https://twitter.com" target="_blank">
                <i class="fab fa-twitter"></i> <!-- Ícone do Twitter -->
            </a>
            <a href="https://www.instagram.com/marciel_rossetto" target="_blank">
                <i class="fab fa-instagram"></i> <!-- Ícone do Instagram -->
            </a>
            <a href="https://www.linkedin.com/in/marciel-rossetto-383b182b3/" target="_blank">
                <i class="fab fa-linkedin-in"></i> <!-- Ícone do LinkedIn -->
            </a>
        </div>
        
        <div>
            <a href="politica_de_privacidade.php" class="privacy-link">Política de Privacidade</a>
        </div>
        <div>
            <a href="pagina_de_vendas.php" class="privacy-link">Página de vendas</a>
        </div>
    </footer>

    <script>
      
    </script>
</body>
</html>
