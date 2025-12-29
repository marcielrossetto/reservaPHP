<?php
session_start();
require 'config.php';

if (empty($_SESSION['mmnlogin'])) { header("Location: login.php"); exit; }
$emp_id = $_SESSION['empresa_id'];

// --- HANDLERS DE BUSCA (AJAX para Edição) ---
if (isset($_GET['get_info'])) {
    $id = (int)$_GET['id'];
    $tipo = $_GET['get_info'];
    
    if ($tipo == 'cardapio') $sql = "SELECT id, nome FROM menu_cardapios WHERE id = ? AND empresa_id = ?";
    if ($tipo == 'categoria') $sql = "SELECT id, nome, cardapio_id FROM menu_categorias WHERE id = ? AND cardapio_id IN (SELECT id FROM menu_cardapios WHERE empresa_id = ?)";
    if ($tipo == 'produto') $sql = "SELECT id, categoria_id, cod_pdv, nome, preco, descricao, layout_tipo FROM menu_produtos WHERE id = ? AND empresa_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $emp_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

/* ============================================================
   PROCESSAMENTO DE AÇÕES (POST / GET)
============================================================ */

// 1. DUPLICAR PRODUTO
if (isset($_GET['duplicar_prod'])) {
    $id = (int)$_GET['duplicar_prod'];
    $pdo->prepare("INSERT INTO menu_produtos (categoria_id, empresa_id, cod_pdv, nome, descricao, preco, foto, status, layout_tipo) 
                   SELECT categoria_id, empresa_id, cod_pdv, CONCAT(nome, ' (Cópia)'), descricao, preco, foto, status, layout_tipo 
                   FROM menu_produtos WHERE id = ? AND empresa_id = ?")->execute([$id, $emp_id]);
    header("Location: configCardapio.php"); exit;
}

// 2. EXCLUIR
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['id'];
    $tipo = $_GET['excluir'];
    if($tipo == 'cardapio') {
        $pdo->prepare("DELETE FROM menu_produtos WHERE empresa_id = ? AND categoria_id IN (SELECT id FROM menu_categorias WHERE cardapio_id = ?)")->execute([$emp_id, $id]);
        $pdo->prepare("DELETE FROM menu_categorias WHERE cardapio_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM menu_cardapios WHERE id = ? AND empresa_id = ?")->execute([$id, $emp_id]);
    } elseif($tipo == 'categoria') {
        $pdo->prepare("DELETE FROM menu_produtos WHERE categoria_id = ? AND empresa_id = ?")->execute([$id, $emp_id]);
        $pdo->prepare("DELETE FROM menu_categorias WHERE id = ?")->execute([$id]);
    } elseif($tipo == 'produto') {
        $pdo->prepare("DELETE FROM menu_produtos WHERE id = ? AND empresa_id = ?")->execute([$id, $emp_id]);
    }
    header("Location: configCardapio.php"); exit;
}

// 3. REORDENAR
if (isset($_GET['ordem'])) {
    $id = (int)$_GET['id'];
    $op = $_GET['ordem']; 
    $stmt = $pdo->prepare("SELECT ordem FROM menu_categorias WHERE id = ?");
    $stmt->execute([$id]);
    $atual = $stmt->fetchColumn();
    $nova = ($op == 'up') ? $atual - 1 : $atual + 1;
    $pdo->prepare("UPDATE menu_categorias SET ordem = ? WHERE id = ?")->execute([$nova, $id]);
    header("Location: configCardapio.php"); exit;
}

// 4. SALVAR (CARDÁPIO, CATEGORIA OU PRODUTO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_salvar'])) {
    $tipo = $_POST['tipo_item'];
    $id = $_POST['id_item'];

    if ($tipo == 'cardapio') {
        if (empty($id)) $pdo->prepare("INSERT INTO menu_cardapios (empresa_id, nome) VALUES (?,?)")->execute([$emp_id, $_POST['nome']]);
        else $pdo->prepare("UPDATE menu_cardapios SET nome=? WHERE id=? AND empresa_id=?")->execute([$_POST['nome'], $id, $emp_id]);
    }
    
    if ($tipo == 'categoria') {
        if (empty($id)) $pdo->prepare("INSERT INTO menu_categorias (cardapio_id, nome) VALUES (?,?)")->execute([$_POST['card_id'], $_POST['nome']]);
        else $pdo->prepare("UPDATE menu_categorias SET nome=?, cardapio_id=? WHERE id=?")->execute([$_POST['nome'], $_POST['card_id'], $id]);
    }

    if ($tipo == 'produto') {
        $foto = (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) ? file_get_contents($_FILES['foto']['tmp_name']) : null;
        $preco = str_replace(',', '.', $_POST['preco']);
        $cat_id = $_POST['categoria_id'];
        $layout = $_POST['layout_tipo'] ?? 'normal'; // Novo campo

        if (empty($id)) {
            $sql = $pdo->prepare("INSERT INTO menu_produtos (categoria_id, empresa_id, cod_pdv, nome, preco, descricao, foto, status, layout_tipo) VALUES (?,?,?,?,?,?,?,1,?)");
            $sql->execute([$cat_id, $emp_id, $_POST['cod_pdv'], $_POST['nome'], $preco, $_POST['desc'], $foto, $layout]);
        } else {
            if ($foto) {
                $sql = $pdo->prepare("UPDATE menu_produtos SET categoria_id=?, cod_pdv=?, nome=?, preco=?, descricao=?, foto=?, layout_tipo=? WHERE id=? AND empresa_id=?");
                $sql->execute([$cat_id, $_POST['cod_pdv'], $_POST['nome'], $preco, $_POST['desc'], $foto, $layout, $id, $emp_id]);
            } else {
                $sql = $pdo->prepare("UPDATE menu_produtos SET categoria_id=?, cod_pdv=?, nome=?, preco=?, descricao=?, layout_tipo=? WHERE id=? AND empresa_id=?");
                $sql->execute([$cat_id, $_POST['cod_pdv'], $_POST['nome'], $preco, $_POST['desc'], $layout, $id, $emp_id]);
            }
        }
    }
    header("Location: configCardapio.php"); exit;
}

require 'cabecalho.php';
?>

<div class="container mt-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2"></i>Configurar Menu</h4>
            <small class="text-muted">ID Empresa: <?= $emp_id ?></small>
        </div>
        <div class="btn-group">
            <button class="btn btn-outline-dark btn-sm fw-bold" onclick="abrirModal('cardapio')">+ Cardápio</button>
            <button class="btn btn-outline-dark btn-sm fw-bold" onclick="abrirModal('categoria')">+ Categoria</button>
            <button class="btn btn-primary btn-sm fw-bold px-4 shadow-sm" onclick="abrirModal('produto')">+ Novo Item</button>
        </div>
    </div>

    <?php
    $menus = $pdo->prepare("SELECT * FROM menu_cardapios WHERE empresa_id = ? ORDER BY ordem ASC");
    $menus->execute([$emp_id]);
    $meus_cardapios = $menus->fetchAll();

    foreach($meus_cardapios as $m):
    ?>
        <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center p-3">
                <h5 class="m-0 fw-bold"><i class="fas fa-book me-2"></i> <?= htmlspecialchars($m['nome']) ?></h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-light border-0" onclick="editarModal('cardapio', <?= $m['id'] ?>)"><i class="fas fa-edit"></i></button>
                    <a href="?excluir=cardapio&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Excluir cardápio?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
            
            <div class="card-body p-3 bg-light">
                <?php
                $cats = $pdo->prepare("SELECT * FROM menu_categorias WHERE cardapio_id = ? ORDER BY ordem ASC");
                $cats->execute([$m['id']]);
                foreach($cats->fetchAll() as $c):
                ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="fw-bold text-uppercase text-secondary small">
                                <i class="fas fa-layer-group me-2"></i> <?= htmlspecialchars($c['nome']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-3">
                                <div class="btn-group">
                                    <a href="?ordem=up&id=<?= $c['id'] ?>" class="btn btn-xs btn-light border"><i class="fas fa-chevron-up"></i></a>
                                    <a href="?ordem=down&id=<?= $c['id'] ?>" class="btn btn-xs btn-light border"><i class="fas fa-chevron-down"></i></a>
                                </div>
                                <button class="btn btn-xs btn-link text-primary p-0" onclick="editarModal('categoria', <?= $c['id'] ?>)">Editar</button>
                                <a href="?excluir=categoria&id=<?= $c['id'] ?>" class="btn btn-xs btn-link text-danger p-0 text-decoration-none" onclick="return confirm('Excluir categoria?')">Excluir</a>
                            </div>
                        </div>

                        <div class="row g-3">
                            <?php
                            $prods = $pdo->prepare("SELECT id, nome, preco, status, foto, cod_pdv, layout_tipo FROM menu_produtos WHERE categoria_id = ? AND empresa_id = ?");
                            $prods->execute([$c['id'], $emp_id]);
                            foreach($prods->fetchAll() as $p):
                                // CORREÇÃO DA IMAGEM: stream_get_contents para campos LONGBLOB
                                $foto_bin = is_resource($p['foto']) ? stream_get_contents($p['foto']) : $p['foto'];
                                $img = !empty($foto_bin) ? 'data:image/jpeg;base64,'.base64_encode($foto_bin) : '';
                                $layout = $p['layout_tipo'] ?? 'normal';
                                
                                // RENDERIZAÇÃO CONDICIONAL BASEADA NO LAYOUT
                                if ($layout == 'compacto'):
                            ?>
                                <!-- CARD COMPACTO 60px - IDEAL PARA LISTAS DE PREÇOS -->
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm d-flex flex-row align-items-center bg-white p-2" style="border-radius: 10px; height: 60px; border-left: 3px solid <?= $p['status'] ? '#1bc5bd' : '#f64e60' ?> !important;">
                                        <?php if($img): ?>
                                            <img src="<?= $img ?>" style="width:50px; height:50px; border-radius:6px; object-fit:cover;" class="me-3">
                                        <?php else: ?>
                                            <div style="width:50px; height:50px; background:#f8f9fa; border-radius:6px;" class="me-3 d-flex align-items-center justify-content-center text-muted border">
                                                <i class="fas fa-wine-bottle"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-truncate" style="font-size: 14px;"><?= htmlspecialchars($p['nome']) ?></div>
                                            <?php if(!empty($p['cod_pdv'])): ?>
                                                <small class="text-muted" style="font-size: 11px;">Cód: <?= htmlspecialchars($p['cod_pdv']) ?></small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="text-success fw-bold me-3" style="font-size: 16px; min-width: 90px; text-align: right;">
                                            R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                        </div>

                                        <div class="d-flex gap-1">
                                            <button class="btn btn-xs btn-<?= $p['status']?'light':'danger' ?> border" style="width: 28px; height: 28px; padding: 0;" onclick="toggleStatus(<?= $p['id'] ?>, <?= $p['status'] ?>)">
                                                <i class="fas <?= $p['status']?'fa-pause':'fa-play' ?>" style="font-size: 10px;"></i>
                                            </button>
                                            <button class="btn btn-xs btn-light border" style="width: 28px; height: 28px; padding: 0;" onclick="editarModal('produto', <?= $p['id'] ?>)">
                                                <i class="fas fa-pen text-primary" style="font-size: 10px;"></i>
                                            </button>
                                            <a href="?duplicar_prod=<?= $p['id'] ?>" class="btn btn-xs btn-light border" style="width: 28px; height: 28px; padding: 0;">
                                                <i class="fas fa-copy text-success" style="font-size: 10px;"></i>
                                            </a>
                                            <a href="?excluir=produto&id=<?= $p['id'] ?>" class="btn btn-xs btn-light border" style="width: 28px; height: 28px; padding: 0;" onclick="return confirm('Excluir?')">
                                                <i class="fas fa-trash text-danger" style="font-size: 10px;"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- CARD NORMAL - LAYOUT PADRÃO -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100 border-0 shadow-sm p-2 d-flex flex-row align-items-center bg-white" style="border-radius: 12px; border: 1px solid #eee !important;">
                                        <?php if($img): ?>
                                            <img src="<?= $img ?>" style="width:55px; height:55px; border-radius:8px; object-fit:cover;" class="me-2">
                                        <?php else: ?>
                                            <div style="width:55px; height:55px; background:#f8f9fa; border-radius:8px;" class="me-2 d-flex align-items-center justify-content-center text-muted border"><i class="fas fa-camera"></i></div>
                                        <?php endif; ?>
                                        
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-bold text-truncate small"><?= htmlspecialchars($p['nome']) ?></div>
                                            <div class="text-success fw-bold small">R$ <?= number_format($p['preco'], 2, ',', '.') ?></div>
                                        </div>

                                        <div class="d-flex flex-column gap-1 ms-2">
                                            <button class="btn btn-xs btn-<?= $p['status']?'light':'danger' ?> border" onclick="toggleStatus(<?= $p['id'] ?>, <?= $p['status'] ?>)"><i class="fas <?= $p['status']?'fa-pause':'fa-play' ?>"></i></button>
                                            <button class="btn btn-xs btn-light border" onclick="editarModal('produto', <?= $p['id'] ?>)"><i class="fas fa-pen text-primary"></i></button>
                                            <a href="?duplicar_prod=<?= $p['id'] ?>" class="btn btn-xs btn-light border"><i class="fas fa-copy text-success"></i></a>
                                            <a href="?excluir=produto&id=<?= $p['id'] ?>" class="btn btn-xs btn-light border" onclick="return confirm('Excluir?')"><i class="fas fa-trash text-danger"></i></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Botão para abrir o QR Code -->
<?php 
    $emp_id_sessao = $_SESSION['empresa_id'];
    $link_publico = "http://localhost/reservaPhpMulti/cardapio.php?id=" . $emp_id_sessao;
    $google_chart_api = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($link_publico);
?>

<button class="btn btn-outline-primary btn-sm fw-bold shadow-sm" onclick="abrirQRCode()">
    <i class="fas fa-qrcode me-2"></i>Ver QR Code do Cardápio
</button>

<script>
function abrirQRCode() {
    window.open('<?= $link_publico ?>', '_blank');
}
</script>

<!-- MODAL GERAL -->
<div class="modal fade" id="modalGeral" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content" style="border-radius:25px;">
            <input type="hidden" name="id_item" id="form_id">
            <input type="hidden" name="tipo_item" id="form_tipo">
            <div class="modal-header border-0 p-4 pb-0"><h5 class="fw-bold" id="modal_titulo">---</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div id="loader" class="text-center py-3" style="display:none;"><div class="spinner-border text-primary"></div></div>
                <div id="campos_comuns">
                    <label class="small fw-bold mb-1">NOME:</label>
                    <input type="text" name="nome" id="form_nome" class="form-control mb-3 shadow-none" required>
                </div>
                <div id="campos_categoria" style="display:none;">
                    <label class="small fw-bold mb-1">VINCULAR AO CARDÁPIO:</label>
                    <select name="card_id" id="form_card_id" class="form-select mb-3 shadow-none">
                        <?php foreach($meus_cardapios as $lm) echo "<option value='{$lm['id']}'>{$lm['nome']}</option>"; ?>
                    </select>
                </div>
                <div id="campos_produto" style="display:none;">
                    <label class="small fw-bold mb-1">CATEGORIA:</label>
                    <select name="categoria_id" id="form_cat_id" class="form-select mb-3 shadow-none">
                        <?php 
                        $cats_sql = $pdo->prepare("SELECT c.id, c.nome, m.nome as m_nome FROM menu_categorias c JOIN menu_cardapios m ON c.cardapio_id = m.id WHERE m.empresa_id = ?");
                        $cats_sql->execute([$emp_id]);
                        foreach($cats_sql->fetchAll() as $ac) echo "<option value='{$ac['id']}'>{$ac['m_nome']} > {$ac['nome']}</option>";
                        ?>
                    </select>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><label class="small fw-bold mb-1">PREÇO R$:</label><input type="text" name="preco" id="form_preco" class="form-control shadow-none"></div>
                        <div class="col-md-6"><label class="small fw-bold mb-1">CÓD. PDV:</label><input type="text" name="cod_pdv" id="form_pdv" class="form-control shadow-none"></div>
                    </div>
                    <label class="small fw-bold mb-1">DESCRIÇÃO:</label>
                    <textarea name="desc" id="form_desc" class="form-control mb-3 shadow-none" rows="2"></textarea>
                    <label class="small fw-bold mb-1">FOTO DO PRODUTO:</label>
                    <input type="file" name="foto" class="form-control shadow-none mb-3" accept="image/*">
                    
                    <label class="small fw-bold mb-1">TIPO DE EXIBIÇÃO:</label>
                    <select name="layout_tipo" id="form_layout" class="form-select shadow-none mb-2">
                        <option value="normal">📦 Card Normal (Padrão)</option>
                        <option value="compacto">📋 Card Compacto - Lista de Preços (60px)</option>
                    </select>
                    <div class="alert alert-info py-2 px-3 mb-0" style="font-size: 12px;">
                        <i class="fas fa-lightbulb me-1"></i> <strong>Dica:</strong> Use "Compacto" para cartas de vinho, bebidas, pratos do dia, etc.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0"><button type="submit" name="btn_salvar" class="btn btn-primary w-100 fw-bold py-3 shadow">SALVAR ALTERAÇÕES</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('modalGeral'));
function abrirModal(tipo) {
    document.getElementById('form_id').value = '';
    document.getElementById('form_tipo').value = tipo;
    document.getElementById('modal_titulo').innerText = 'Novo ' + tipo;
    $('#campos_categoria').toggle(tipo === 'categoria');
    $('#campos_produto').toggle(tipo === 'produto');
    modal.show();
}
function editarModal(tipo, id) {
    document.getElementById('form_id').value = id;
    document.getElementById('form_tipo').value = tipo;
    $('#loader').show(); modal.show();
    fetch(`configCardapio.php?get_info=${tipo}&id=${id}`).then(r => r.json()).then(data => {
        $('#loader').hide();
        document.getElementById('form_nome').value = data.nome;
        if(tipo === 'categoria') document.getElementById('form_card_id').value = data.cardapio_id;
        if(tipo === 'produto') {
            document.getElementById('form_cat_id').value = data.categoria_id;
            document.getElementById('form_preco').value = data.preco;
            document.getElementById('form_pdv').value = data.cod_pdv;
            document.getElementById('form_desc').value = data.descricao;
            document.getElementById('form_layout').value = data.layout_tipo || 'normal';
        }
    });
}
function toggleStatus(id, st) { $.post('ajax_status_menu.php', {id: id, tipo: 'produto', status: st == 1 ? 0 : 1}, () => location.reload()); }
</script>
</body>
</html>