    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            padding-bottom: 60px;
        }

        .btn-actions-ios {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: #f8f9fa;
            color: #555;
            position: absolute;
            top: 0px;
            right: 2px;
            z-index: 20;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-actions-ios:hover {
            background: #e2e2e2;
        }

        .ios-menu {
            position: absolute;
            right: 45px;
            top: 10px;
            background: white;
            border-radius: 12px;
            padding: 5px;
            display: none;
            flex-direction: column;
            gap: 5px;
            width: 45px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 100;
        }

        .ios-menu.show {
            display: flex;
            animation: iosAppear 0.2s ease-out;
        }

        .ios-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            border: none;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            color: #383838ff;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.15s;
        }

        .ios-action:hover {
            background: #f0f0f0;
        }

        @keyframes iosAppear {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .sec-actions {
            display: none !important;
        }

        /* CALENDAR */
        :root {
            --cal-bg: #212529;
            --cal-header: #2c3034;
            --cal-cell: #2c3034;
            --cal-hover: #343a40;
            --cal-text: #e9ecef;
            --cal-muted: #adb5bd;
            --accent: #0d6efd;
            --pill-a: #ffc107;
            --pill-j: #0dcaf0;
        }

        #cal-wrapper {
            overflow: hidden;
            max-height: 1200px;
            opacity: 1;
            transition: max-height 0.5s ease-in-out, opacity 0.4s ease-in-out;
            margin-bottom: 0;
        }

        #cal-wrapper.collapsed {
            max-height: 0;
            opacity: 0;
        }

        .calendar-container {
            background: var(--cal-bg);
            color: var(--cal-text);
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 0;
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .cal-header-modern {
            background: var(--cal-header);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #3d4146;
        }

        .cal-title-group {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cal-month-year {
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: capitalize;
            color: #fff;
            margin-bottom: 2px;
        }

        .cal-stats-badges {
            font-size: 0.85rem;
            color: var(--cal-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nav-cal {
            color: var(--cal-muted);
            font-size: 1.1rem;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.2s;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-nav-cal:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .cal-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .cal-table th {
            text-align: center;
            color: var(--cal-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 8px 0;
            background: var(--cal-bg);
        }

        .cal-table td {
            background: var(--cal-cell);
            border: 1px solid #3d4146;
            height: 60px;
            vertical-align: top;
            padding: 4px;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .cal-table td:not(.empty):hover {
            background: var(--cal-hover);
        }

        .cal-table td.today {
            background: #3c4149;
            border: 1px solid var(--accent);
        }

        .cal-table td.selected {
            background: #495057;
            box-shadow: inset 0 0 0 1px #fff;
        }

        .day-num {
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            margin-left: 2px;
        }

        .btn-eye-sm {
            background: none;
            border: none;
            padding: 0;
            color: var(--cal-muted);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            transition: 0.2s;
        }

        .btn-eye-sm:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }

        .pills-container {
            display: flex;
            justify-content: flex-start;
            gap: 3px;
            margin-top: 2px;
            flex-wrap: wrap;
        }

        .pill {
            font-size: 0.65rem;
            padding: 1px 5px;
            border-radius: 4px;
            font-weight: 700;
            color: #000;
            line-height: 1;
            display: inline-block;
        }

        .pill-a {
            background: var(--pill-a);
        }

        .pill-j {
            background: var(--pill-j);
        }

        .toggle-cal-container {
            text-align: center;
            margin-top: -3px;
            margin-bottom: 20px;
            position: relative;
            z-index: 10;
        }

        .btn-toggle-cal {
            background: var(--cal-header);
            color: var(--cal-muted);
            border: 1px solid #3d4146;
            border-top: none;
            padding: 5px 20px;
            font-size: 0.8rem;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: 0.2s;
        }

        .btn-toggle-cal:hover {
            background: var(--cal-hover);
            color: #fff;
        }

        .filter-bar {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            /* Alinhamento com o calendário */
            max-width: 900px;
            margin: 0 auto 20px auto;
        }

        .alert-secondary {
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        #area-lista-reservas {
            max-width: 900px;
            margin: 0 auto;
        }

        /* CORREÇÃO DAS COLUNAS RETAS E ALINHAMENTO */
        .reserva-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 12px;
            border-left: 5px solid #ccc;
            position: relative;
            padding: 5px 10px;
            padding-right: 50px;
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            transition: 0.2s;
            overflow: visible !important;
        }

        .status-confirmado {
            border-left-color: #198754 !important;
        }

        .status-pendente {
            border-left-color: #fd7e14 !important;
        }

        .status-cancelado {
            border-left-color: #dc3545 !important;
            background-color: #fff5f5;
        }

        .card-content-wrapper {
            display: flex;
            width: 100%;
            align-items: center;
        }

        .sec-info {
            flex: 0 0 220px;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }

        .client-name {
            font-weight: 700;
            color: #333;
            font-size: clamp(12px, 4vw, 16px);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            width: 100%;
        }

        .btn-perfil {
            font-size: 0.75rem;
            color: var(--accent);
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            margin-top: 2px;
        }

        .sec-meta-group {
            flex: 0 0 110px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 1px solid #f0f0f0;
            padding: 0 10px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 45px;
        }

        .pax-val {
            font-size: 1rem;
            font-weight: 800;
            color: #fd7e14;
            line-height: 1;
        }

        .pax-lbl {
            font-size: 0.6rem;
            color: #888;
            text-transform: uppercase;
        }

        .time-val {
            font-size: 0.9rem;
            font-weight: 700;
            color: #333;
        }

        .mesa-val {
            font-size: 0.55rem;
            background: #eee;
            padding: 1px 6px;
            border-radius: 4px;
            color: #555;
            margin-top: 2px;
        }

        .sec-obs-container {
            flex: 1;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
        }

        .obs-box {
            background-color: #fcfcfc;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.8rem;
            color: #666;
            width: 100%;
            height: 60px;
            overflow-y: auto;
            white-space: normal;
        }

        /* Ajuste responsivo para manter reto em telas menores */
        @media (max-width: 768px) {
            .sec-info {
                flex: 0 0 150px;
            }

            .sec-meta-group {
                flex: 0 0 95px;
            }
        }

        .badge-status {
            position: absolute;
            top: 1px;
            right: 50px;
            font-size: 0.35rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            z-index: 5;
        }

        .badge-id-corner {
            position: absolute;
            top: 4px;
            left: 16px;
            font-size: 0.5rem;
            font-weight: 800;
            color: #adb5bd;
            z-index: 5;
        }

        .badge-ok {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-wait {
            background: #fff3cd;
            color: #664d03;
        }

        .badge-cancel {
            background: #dc3545;
            color: #fff;
        }

        .modal-overlay-dia {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(2px);
        }

        .modal-box-dia {
            background: #fff;
            width: 90%;
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 85vh;
        }

        .modal-header-dia {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .modal-body-dia {
            padding: 0;
            overflow-y: auto;
        }

        .reserva-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-switch .form-check-input {
            width: 2em;
            height: 1em;
            cursor: pointer;
        }

        .toggle-cancel-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 2px;
            display: block;
            text-align: center;
        }

        .btn-period {
            width: 44px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .btn-period.active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .btn-icon {
            width: 42px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-icon i {
            font-size: 14px;
        }

        /* Estilo do Modal de Edição (iOS Style) */
        .modal-ios-label {
            font-weight: 600;
            margin-top: 10px;
            color: #333;
            font-size: 0.9rem;
        }

        .modal-ios-input {
            margin-top: 5px;
            border-radius: 12px !important;
            border: 1px solid #d1d1d6 !important;
            padding: 10px 12px !important;
            font-size: 1rem !important;
            width: 100%;
            background: #fafafa;
            transition: .2s;
        }

        .modal-ios-input:focus {
            border-color: #007AFF !important;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.25) !important;
            background: #fff;
        }

        /* --- AJUSTES PARA IPHONE / TELAS PEQUENAS --- */
        @media (max-width: 480px) {

            /* 1. Limita o nome para cerca de 12-14 caracteres e coloca os pontinhos (...) */
            .client-name {
                max-width: 100px;
                /* Largura suficiente para ~12 letras */
                display: inline-block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            /* 2. Diminui o tamanho da coluna do nome para empurrar o resto para a esquerda */
            .sec-info {
                flex: 0 0 110px !important;
                padding-right: 5px;
            }

            /* 3. Move o Pax e Hora mais para a esquerda */
            .sec-meta-group {
                flex: 0 0 85px !important;
                padding: 0 5px !important;
                border-left: 1px solid #eee;
            }

            /* 4. Aumenta o campo de observações e garante visibilidade */
            .sec-obs-container {
                flex: 1 !important;
                padding: 4px 5px !important;
                justify-content: flex-start !important;
            }

            .obs-box {
                height: 50px !important;
                /* Altura um pouco menor para caber melhor no card */
                font-size: 0.75rem !important;
                /* Letra levemente menor para ler mais texto */
                padding: 4px 6px !important;
                max-width: none !important;
                /* Deixa ocupar todo o espaço que sobrar */
            }

            /* Ajuste extra: diminui a margem direita do card para o botão de 3 pontos não apertar */
            .reserva-card {
                padding-right: 40px !important;
            }
        }

        /* --- AJUSTE PARA TELAS GRANDES (MANTER RETO) --- */
        @media (min-width: 769px) {
            .sec-info {
                flex: 0 0 220px;
            }

            .sec-meta-group {
                flex: 0 0 110px;
            }

            .sec-obs-container {
                flex: 1;
            }

            .obs-box {
                max-width: 100%;
            }
        }
    </style>