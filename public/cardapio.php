<?php
// cardapio.php
$pageTitle = "Cardápio Digital - Flor Da Vila";
require_once '../app/config/database.php';

// Definir a página atual para o sidebar
$currentPage = 'cardapio.php';

// Buscar categorias e produtos do banco de dados
try {
    // Buscar categorias
    $categoriasStmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name");
    $categorias = $categoriasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar produtos
    $produtosStmt = $pdo->query("
        SELECT p.*, c.name as category_name, c.color as category_color 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        ORDER BY c.order_index, p.name
    ");
    $produtos = $produtosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar produtos por categoria
    $produtosPorCategoria = [];
    foreach ($produtos as $produto) {
        $categoriaId = $produto['category_id'] ?: 'sem-categoria';
        $produtosPorCategoria[$categoriaId][] = $produto;
    }
    
} catch (PDOException $e) {
    $error = "Erro ao carregar cardápio: " . $e->getMessage();
    $categorias = [];
    $produtosPorCategoria = [];
}
?>

<style>
/* ===== VARIÁVEIS E ESTILOS GLOBAIS ===== */
:root {
    --primary: #2c5530;
    --primary-light: #4a7c59;
    --secondary: #d4af37;
    --accent: #e74c3c;
    --light: #f8f9fa;
    --dark: #2c3e50;
    --gray: #6c757d;
    --success: #28a745;
    --border-radius: 16px;
    --shadow: 0 8px 30px rgba(0,0,0,0.08);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background-color: #f8f9fc;
    color: #333;
}

/* ===== HERO SECTION ===== */
.cardapio-hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    padding: 2.5rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 30px 30px;
    position: relative;
    overflow: hidden;
}

.cardapio-hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="rgba(255,255,255,0.05)"><path d="M20,20 C40,0 60,0 80,20 C100,40 100,60 80,80 C60,100 40,100 20,80 C0,60 0,40 20,20 Z"/></svg>');
    background-size: 120px;
}

.hero-content {
    text-align: center;
    position: relative;
    z-index: 2;
}

.restaurant-logo {
    font-size: 1.2rem;
    font-weight: 600;
    letter-spacing: 2px;
    margin-bottom: 0.5rem;
    opacity: 0.9;
}

.hero-icon {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    opacity: 0.9;
    color: var(--secondary);
}

.hero-title {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.hero-badges {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.hero-badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1.2rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 500;
    border: 1px solid rgba(255,255,255,0.2);
}

/* ===== CHAMADA GARÇOM ===== */
.garcom-section {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.btn-garcom {
    background: linear-gradient(135deg, var(--accent) 0%, #c0392b 100%);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 1rem 1.5rem;
    font-weight: 600;
    box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    animation: pulse 2s infinite;
}

.btn-garcom:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(231, 76, 60, 0.5);
}

@keyframes pulse {
    0% { box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4); }
    50% { box-shadow: 0 8px 25px rgba(231, 76, 60, 0.7); }
    100% { box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4); }
}

/* ===== FILTROS ===== */
.filtros-section {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    position: sticky;
    top: 10px;
    z-index: 100;
}

.filtro-btn {
    border: 2px solid var(--primary-light);
    color: var(--primary);
    background: white;
    padding: 0.6rem 1.2rem;
    border-radius: 50px;
    margin: 0.25rem;
    transition: var(--transition);
    font-weight: 500;
    font-size: 0.9rem;
}

.filtro-btn:hover,
.filtro-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* ===== CATEGORIAS ===== */
.categoria-section {
    margin-bottom: 3rem;
    animation: fadeInUp 0.6s ease-out;
}

.categoria-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem 0;
    position: relative;
}

.categoria-header::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 4px;
    background: var(--primary);
    border-radius: 2px;
}

.categoria-icon {
    font-size: 1.8rem;
    margin-right: 1rem;
    color: var(--primary);
}

.categoria-title {
    font-size: 1.6rem;
    font-weight: 700;
    margin: 0;
    color: var(--dark);
}

/* ===== PRODUTOS ===== */
.produto-card {
    border: none;
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
    height: 100%;
    box-shadow: var(--shadow);
    background: white;
    position: relative;
}

.produto-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.produto-image {
    height: 180px;
    background: linear-gradient(45deg, #f8f9fc, #e3e6f0);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.produto-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}

.produto-card:hover .produto-image img {
    transform: scale(1.05);
}

.produto-image .default-icon {
    font-size: 3.5rem;
    color: var(--primary-light);
    opacity: 0.7;
}

.produto-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--accent);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 2;
}

.produto-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    height: calc(100% - 180px);
}

.produto-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.produto-descricao {
    color: var(--gray);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
    flex-grow: 1;
}

.produto-preco {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--accent);
    margin-bottom: 0;
}

.produto-detalhes {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.veg-indicator {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid var(--success);
    position: relative;
}

.veg-indicator.non-veg {
    border-color: var(--accent);
}

.veg-indicator.non-veg::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: var(--accent);
    border-radius: 50%;
}

.destaque-badge {
    background: linear-gradient(45deg, var(--secondary), #b8941f);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 10px;
}

/* ===== DESTAQUES ESPECIAIS ===== */
.highlight-card {
    position: relative;
    border: 3px solid var(--secondary) !important;
}

.highlight-card::before {
    content: "🌟 RECOMENDADO";
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--secondary);
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 10;
}

.recomendado-chef {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(44, 85, 48, 0.9);
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 2;
}

/* ===== ANIMAÇÕES ===== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.produto-card {
    animation: fadeInUp 0.6s ease-out;
}

/* ===== ESTADO VAZIO ===== */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1.5rem;
}

/* ===== RESPONSIVIDADE ===== */
@media (max-width: 768px) {
    .cardapio-hero {
        padding: 2rem 0;
        margin-bottom: 1.5rem;
        border-radius: 0 0 20px 20px;
    }
    
    .hero-title {
        font-size: 1.8rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .hero-badges {
        flex-direction: column;
        align-items: center;
    }
    
    .categoria-title {
        font-size: 1.4rem;
    }
    
    .produto-image {
        height: 150px;
    }
    
    .produto-title {
        font-size: 1.1rem;
    }
    
    .produto-preco {
        font-size: 1.3rem;
    }
    
    .filtros-section {
        padding: 1rem;
        position: static;
    }
    
    .filtro-btn {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    
    .garcom-section {
        bottom: 15px;
        right: 15px;
    }
    
    .btn-garcom {
        padding: 0.8rem 1.2rem;
        font-size: 0.9rem;
    }
}

/* Melhorias para telas muito pequenas */
@media (max-width: 576px) {
    .produto-card {
        margin-bottom: 1.5rem;
    }
    
    .hero-icon {
        font-size: 3rem;
    }
    
    .categoria-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .categoria-icon {
        margin-bottom: 0.5rem;
    }
}

/* Melhorias de acessibilidade */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<div class="content-wrapper">
    <!-- Hero Section -->
    <div class="cardapio-hero">
        <div class="container-fluid">
            <div class="hero-content">
                <div class="restaurant-logo">FLOR DA VILA</div>
                <div class="hero-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h1 class="hero-title">Cardápio Digital</h1>
                <p class="hero-subtitle">Sabores artesanais preparados com ingredientes selecionados para uma experiência única</p>
                <div class="hero-badges">
                    <div class="hero-badge">
                        <i class="fas fa-leaf me-1"></i> Opções vegetarianas
                    </div>
                    <div class="hero-badge">
                        <i class="fas fa-award me-1"></i> Ingredientes frescos
                    </div>
                    <div class="hero-badge">
                        <i class="fas fa-clock me-1"></i> Preparo na hora
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Filtros -->
        <div class="filtros-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-3 text-primary"><i class="fas fa-filter me-2"></i>Filtrar por Categoria</h5>
                    <div class="filtros-container">
                        <button class="btn filtro-btn active" data-categoria="todos">
                            <i class="fas fa-th-large me-1"></i>Todos
                        </button>
                        <?php foreach ($categorias as $categoria): ?>
                            <button class="btn filtro-btn" data-categoria="categoria-<?php echo $categoria['id']; ?>">
                                <i class="fas fa-<?php echo $categoria['icon'] ?? 'utensils'; ?> me-1"></i>
                                <?php echo htmlspecialchars($categoria['name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Buscar prato..." id="buscarProduto">
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo do Cardápio -->
        <div class="row">
            <div class="col-12">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <?php if (empty($categorias) && empty($produtosPorCategoria)): ?>
                    <!-- Estado vazio -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3 class="text-muted mb-2">Cardápio em Preparação</h3>
                        <p class="text-muted">Nossos chefs estão criando pratos especiais para você.</p>
                        <button class="btn btn-primary mt-3" onclick="window.location.reload()">
                            <i class="fas fa-redo me-1"></i> Recarregar
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Cardápio com conteúdo -->
                    <?php foreach ($categorias as $categoria): ?>
                        <?php if (isset($produtosPorCategoria[$categoria['id']])): ?>
                            <section class="categoria-section" id="categoria-<?php echo $categoria['id']; ?>">
                                <div class="categoria-header">
                                    <div class="categoria-icon">
                                        <i class="fas fa-<?php echo $categoria['icon'] ?? 'utensils'; ?>"></i>
                                    </div>
                                    <h2 class="categoria-title"><?php echo htmlspecialchars($categoria['name']); ?></h2>
                                </div>
                                
                                <div class="row">
                                    <?php foreach ($produtosPorCategoria[$categoria['id']] as $index => $produto): ?>
                                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4 produto-item" 
                                             data-categoria="categoria-<?php echo $categoria['id']; ?>"
                                             data-nome="<?php echo strtolower(htmlspecialchars($produto['name'])); ?>">
                                            <div class="card produto-card <?php echo $index === 0 ? 'highlight-card' : ''; ?>">
                                                <div class="produto-image">
                                                    <?php if (!empty($produto['image_url'])): ?>
                                                        <img src="<?php echo htmlspecialchars($produto['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($produto['name']); ?>" 
                                                             class="img-fluid">
                                                    <?php else: ?>
                                                        <div class="default-icon">
                                                            <i class="fas fa-<?php echo $categoria['icon'] ?? 'utensils'; ?>"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($produto['is_featured']): ?>
                                                        <span class="produto-badge">DESTAQUE</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($index === 0): ?>
                                                        <span class="recomendado-chef">RECOMENDADO</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="produto-body">
                                                    <h3 class="produto-title"><?php echo htmlspecialchars($produto['name']); ?></h3>
                                                    <p class="produto-descricao"><?php echo htmlspecialchars($produto['description']); ?></p>
                                                    
                                                    <div class="produto-detalhes">
                                                        <div class="d-flex align-items-center">
                                                            <span class="produto-preco">R$ <?php echo number_format($produto['price'], 2, ',', '.'); ?></span>
                                                            <?php if ($produto['is_featured']): ?>
                                                                <span class="destaque-badge">POPULAR</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="veg-indicator <?php echo $produto['is_vegetarian'] ? '' : 'non-veg'; ?>" 
                                                             title="<?php echo $produto['is_vegetarian'] ? 'Vegetariano' : 'Não vegetariano'; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Produtos sem categoria -->
                    <?php if (isset($produtosPorCategoria['sem-categoria'])): ?>
                        <section class="categoria-section" id="sem-categoria">
                            <div class="categoria-header">
                                <div class="categoria-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h2 class="categoria-title">Outras Delícias</h2>
                            </div>
                            
                            <div class="row">
                                <?php foreach ($produtosPorCategoria['sem-categoria'] as $produto): ?>
                                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4 produto-item" 
                                         data-categoria="sem-categoria"
                                         data-nome="<?php echo strtolower(htmlspecialchars($produto['name'])); ?>">
                                        <div class="card produto-card">
                                            <div class="produto-body">
                                                <h3 class="produto-title"><?php echo htmlspecialchars($produto['name']); ?></h3>
                                                <p class="produto-descricao"><?php echo htmlspecialchars($produto['description']); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="produto-preco">R$ <?php echo number_format($produto['price'], 2, ',', '.'); ?></span>
                                                    <div class="veg-indicator <?php echo $produto['is_vegetarian'] ? '' : 'non-veg'; ?>"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Botão Flutuante Chamar Garçom -->
<div class="garcom-section">
    <button class="btn btn-garcom" onclick="chamarGarcom()">
        <i class="fas fa-bell"></i>
        <span class="d-none d-sm-inline">Chamar Garçom</span>
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros por categoria
    const filtroBtns = document.querySelectorAll('.filtro-btn');
    const produtoItems = document.querySelectorAll('.produto-item');
    const searchInput = document.getElementById('buscarProduto');
    
    // Função para filtrar produtos
    function filtrarProdutos() {
        const categoriaAtiva = document.querySelector('.filtro-btn.active').getAttribute('data-categoria');
        const termoBusca = searchInput.value.toLowerCase();
        
        produtoItems.forEach(item => {
            const itemCategoria = item.getAttribute('data-categoria');
            const itemNome = item.getAttribute('data-nome');
            const itemTexto = item.textContent.toLowerCase();
            
            const correspondeCategoria = categoriaAtiva === 'todos' || itemCategoria === categoriaAtiva;
            const correspondeBusca = termoBusca === '' || itemNome.includes(termoBusca) || itemTexto.includes(termoBusca);
            
            if (correspondeCategoria && correspondeBusca) {
                item.style.display = 'block';
                // Garantir que a seção pai seja visível
                let section = item.closest('.categoria-section');
                if (section) section.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Ocultar seções vazias
        document.querySelectorAll('.categoria-section').forEach(section => {
            const itensVisiveis = section.querySelectorAll('.produto-item[style="display: block"]');
            if (itensVisiveis.length === 0) {
                section.style.display = 'none';
            }
        });
    }
    
    // Event listeners para filtros
    filtroBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filtroBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtrarProdutos();
        });
    });
    
    // Busca em tempo real
    searchInput.addEventListener('input', filtrarProdutos);
    
    // Efeitos de hover nos cards
    produtoItems.forEach(item => {
        const card = item.querySelector('.produto-card');
        
        item.addEventListener('mouseenter', function() {
            card.style.transform = 'translateY(-8px)';
        });
        
        item.addEventListener('mouseleave', function() {
            card.style.transform = 'translateY(0)';
        });
    });
    
    // Scroll suave para categorias
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoria = this.getAttribute('data-categoria');
            if (categoria !== 'todos') {
                const section = document.getElementById(categoria);
                if (section) {
                    window.scrollTo({
                        top: section.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
});

// Função para chamar o garçom
function chamarGarcom() {
    // Aqui você pode integrar com sua API para notificar os garçons
    if (confirm('Deseja chamar o garçom para sua mesa?')) {
        // Simulação de chamada - substitua por sua implementação real
        alert('Garçom notificado! Ele virá até sua mesa em breve.');
        
        // Aqui você pode adicionar:
        // 1. Requisição AJAX para o backend
        // 2. Integração com sistema de notificação
        // 3. WebSocket para atualização em tempo real
        
        // Exemplo de requisição (descomente e adapte):
        /*
        fetch('/api/chamar-garcom', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                mesa: 'NUMERO_DA_MESA', // Você precisará identificar a mesa
                timestamp: new Date().toISOString()
            })
        })
        .then(response => response.json())
        .then(data => {
            alert('Garçom notificado com sucesso!');
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao chamar garçom. Tente novamente.');
        });
        */
    }
}

// Melhoria: Adicionar loading state durante buscas
function toggleLoading(state) {
    const loader = document.getElementById('loading-indicator') || createLoader();
    loader.style.display = state ? 'block' : 'none';
}

function createLoader() {
    const loader = document.createElement('div');
    loader.id = 'loading-indicator';
    loader.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Buscando...</p></div>';
    loader.style.display = 'none';
    document.querySelector('.container-fluid').appendChild(loader);
    return loader;
}
</script>