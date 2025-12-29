<?php
require 'config.php';

// 1. IDENTIFICAÇÃO DA EMPRESA (Multi-empresa via URL)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Acesso Inválido</h3><p>Nenhuma empresa selecionada.</p></div>");
}

$empresa_id = (int)$_GET['id']; 

// 2. BUSCA DADOS DA EMPRESA (NOME E LOGO)
$sqlEmp = $pdo->prepare("SELECT nome_empresa, logo FROM empresas WHERE id = ?");
$sqlEmp->execute([$empresa_id]);
$empresa = $sqlEmp->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Empresa não cadastrada.</h3></div>");
}

// Função Auxiliar para converter BLOB/Resource em Imagem Base64
function processarImagem($blob) {
    if (empty($blob)) return 'https://via.placeholder.com/400x400?text=Sem+Foto';
    $dados = is_resource($blob) ? stream_get_contents($blob) : $blob;
    return 'data:image/jpeg;base64,' . base64_encode($dados);
}

$logo_src = processarImagem($empresa['logo']);

// 3. BUSCA OS CARDÁPIOS ATIVOS (Abas superiores)
$sqlMenus = $pdo->prepare("SELECT * FROM menu_cardapios WHERE empresa_id = ? AND status = 1 ORDER BY ordem ASC");
$sqlMenus->execute([$empresa_id]);
$meus_menus = $sqlMenus->fetchAll(PDO::FETCH_ASSOC);

// Se a empresa não tiver cardápio publicado
if (count($meus_menus) == 0) {
    echo "<div style='text-align:center; padding:100px; font-family:sans-serif;'><img src='$logo_src' style='height:60px;'><br><h4>Cardápio em manutenção.</h4></div>";
    exit;
}

// Define qual menu está ativo (clicado ou o primeiro)
$menu_selecionado = isset($_GET['menu']) ? (int)$_GET['menu'] : $meus_menus[0]['id'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Menu | <?= htmlspecialchars($empresa['nome_empresa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap');
        :root { --accent: #C0A080; --dark: #1a1a1a; --gray-light: #f8f9fa; }
        
        body { background-color: #fff; font-family: 'Inter', sans-serif; color: #333; }
        
        /* Cabeçalho Luxo */
        .brand-header { background: var(--dark); padding: 30px; text-align: center; color: white; border-bottom: 5px solid var(--accent); }
        .logo-topo { height: 60px; width: auto; object-fit: contain; margin-bottom: 10px; }
        .brand-name { font-weight: 900; text-transform: uppercase; letter-spacing: 2px; font-size: 1.2rem; }

        /* Navegação Horizontal (Tabs) */
        .nav-scroller { white-space: nowrap; overflow-x: auto; padding: 15px; background: #fff; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 1000; }
        .nav-scroller::-webkit-scrollbar { display: none; }
        .nav-tab { display: inline-block; padding: 10px 25px; border-radius: 12px; background: #f4f4f4; color: #666; text-decoration: none; font-weight: 700; margin-right: 10px; font-size: 0.85rem; text-transform: uppercase; }
        .nav-tab.active { background: var(--accent); color: #fff; box-shadow: 0 4px 12px rgba(192, 160, 128, 0.3); }

        /* Categorias */
        .category-header { font-weight: 900; color: #1a1a1a; font-size: 1.5rem; margin: 40px 0 20px; display: flex; align-items: center; gap: 15px; text-transform: uppercase; }
        .category-header::after { content: ""; flex: 1; height: 1px; background: #e0e0e0; }

        /* --- LAYOUT NORMAL (3 COLUNAS) --- */
        .product-card-normal { 
            background: #fff; border-radius: 24px; padding: 12px; height: 100%; 
            transition: 0.4s; border: 1px solid #f0f0f0; display: flex; flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        }
        .product-card-normal:hover { transform: translateY(-5px); border-color: var(--accent); }
        .img-box-normal { width: 100%; height: 200px; border-radius: 18px; overflow: hidden; background: #f9f9f9; cursor: zoom-in; }
        .img-box-normal img { width: 100%; height: 100%; object-fit: cover; }

        /* --- LAYOUT COMPACTO (LISTA SLIM) --- */
        .product-card-compact {
            background: #fff; border-radius: 15px; padding: 10px 15px; margin-bottom: 10px;
            border: 1px solid #f2f2f2; display: flex; align-items: center; gap: 15px;
            transition: 0.2s; cursor: pointer;
        }
        .product-card-compact:hover { background: var(--gray-light); border-color: var(--accent); }
        .img-box-compact { width: 60px; height: 60px; border-radius: 10px; overflow: hidden; flex-shrink: 0; }
        .img-box-compact img { width: 100%; height: 100%; object-fit: cover; }

        .price { font-weight: 900; color: var(--accent); font-size: 1.15rem; }
        
        /* Modal Zoom */
        #modalZoom .modal-content { background: transparent; border: none; text-align: center; }
        #imgZoom { max-width: 95vw; max-height: 80vh; border-radius: 25px; box-shadow: 0 0 50px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="brand-header">
    <img src="<?= $logo_src ?>" class="logo-topo">
    <div class="brand-name"><?= htmlspecialchars($empresa['nome_empresa']) ?></div>
</div>

<!-- ABAS DE NAVEGAÇÃO -->
<div class="nav-scroller shadow-sm">
    <?php foreach($meus_menus as $m): 
        $act = ($m['id'] == $menu_selecionado) ? 'active' : '';
    ?>
        <a href="?id=<?= $empresa_id ?>&menu=<?= $m['id'] ?>" class="nav-tab <?= $act ?>">
            <?= htmlspecialchars($m['nome']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="container py-2">
    <?php
    // Busca Categorias do Menu Selecionado
    $sqlCat = $pdo->prepare("SELECT * FROM menu_categorias WHERE cardapio_id = ? AND status = 1 ORDER BY ordem ASC");
    $sqlCat->execute([$menu_selecionado]);
    $categorias = $sqlCat->fetchAll();

    foreach($categorias as $cat):
    ?>
        <div class="category-header"><?= htmlspecialchars($cat['nome']) ?></div>
        
        <div class="row g-3">
            <?php
            // Busca Produtos
            $sqlProd = $pdo->prepare("SELECT * FROM menu_produtos WHERE categoria_id = ? AND empresa_id = ? AND status = 1 ORDER BY nome ASC");
            $sqlProd->execute([$cat['id'], $empresa_id]);
            $produtos = $sqlProd->fetchAll();

            foreach($produtos as $p):
                $foto_item = processarImagem($p['foto']);
                $layout = $p['layout_tipo'] ?? 'normal';

                // --- CASO 1: LAYOUT COMPACTO (LISTA) ---
                if($layout == 'compacto'):
            ?>
                <div class="col-12" onclick="abrirZoom('<?= $foto_item ?>', '<?= addslashes($p['nome']) ?>')">
                    <div class="product-card-compact">
                        <div class="img-box-compact">
                            <img src="<?= $foto_item ?>" loading="lazy">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold m-0"><?= htmlspecialchars($p['nome']) ?></h6>
                            <?php if(!empty($p['descricao'])): ?>
                                <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= htmlspecialchars($p['descricao']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="price text-end">
                            R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                        </div>
                    </div>
                </div>

            <?php 
                // --- CASO 2: LAYOUT NORMAL (3 COLUNAS) ---
                else: 
            ?>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="product-card-normal">
                        <div class="img-box-normal" onclick="abrirZoom('<?= $foto_item ?>', '<?= addslashes($p['nome']) ?>')">
                            <img src="<?= $foto_item ?>" loading="lazy">
                        </div>
                        <div class="p-2 flex-grow-1">
                            <h5 class="fw-bold mt-2 mb-1"><?= htmlspecialchars($p['nome']) ?></h5>
                            <p class="small text-muted" style="line-height:1.3; height: 2.6em; overflow: hidden;">
                                <?= htmlspecialchars($p['descricao']) ?>
                            </p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-auto p-2">
                            <div class="price">R$ <?= number_format($p['preco'], 2, ',', '.') ?></div>
                            <button class="btn btn-sm btn-light border-circle" onclick="abrirZoom('<?= $foto_item ?>', '<?= addslashes($p['nome']) ?>')">
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal de Zoom -->
<div class="modal fade" id="modalZoom" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <img src="" id="imgZoom" class="img-fluid">
                <h4 id="txtZoom" class="text-white mt-3 fw-bold"></h4>
                <button type="button" class="btn btn-outline-light rounded-pill mt-2 w-100" data-bs-dismiss="modal">FECHAR</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modalZoom = new bootstrap.Modal(document.getElementById('modalZoom'));
    function abrirZoom(src, nome) {
        document.getElementById('imgZoom').src = src;
        document.getElementById('txtZoom').innerText = nome;
        modalZoom.show();
    }
</script>
</body>
</html>