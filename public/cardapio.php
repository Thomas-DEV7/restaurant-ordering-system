<?php
// cardapio.php
$pageTitle = "Cardápio Digital";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

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
/* Estilos específicos para o cardápio */
.cardapio-hero {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
    border-radius: 0 0 20px 20px;
}

.hero-content {
    text-align: center;
}

.hero-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 1.5rem;
}

.hero-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-size: 0.9rem;
}

.categoria-section {
    margin-bottom: 3rem;
}

.categoria-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 3px solid;
}

.categoria-icon {
    font-size: 2rem;
    margin-right: 1rem;
}

.categoria-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}

.produto-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
}

.produto-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.produto-image {
    height: 200px;
    background: linear-gradient(45deg, #f8f9fc, #e3e6f0);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.produto-image .default-icon {
    font-size: 4rem;
    color: #4e73df;
    opacity: 0.7;
}

.produto-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #e74c3c;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.produto-body {
    padding: 1.5rem;
}

.produto-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.produto-descricao {
    color: #6c757d;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 1rem;
    min-height: 70px;
}

.produto-preco {
    font-size: 1.5rem;
    font-weight: 700;
    color: #e74c3c;
    margin-bottom: 0;
}

.produto-detalhes {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.veg-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #28a745;
    position: relative;
}

.veg-indicator.non-veg {
    border-color: #e74c3c;
}

.veg-indicator.non-veg::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 10px;
    height: 10px;
    background: #e74c3c;
    border-radius: 50%;
}

.destaque-badge {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 10px;
}

/* Filtros */
.filtros-section {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
}

.filtro-btn {
    border: 2px solid #4e73df;
    color: #4e73df;
    background: white;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    margin: 0.25rem;
    transition: all 0.3s ease;
}

.filtro-btn:hover,
.filtro-btn.active {
    background: #4e73df;
    color: white;
}

/* Animações */
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

/* Responsividade */
@media (max-width: 768px) {
    .cardapio-hero {
        padding: 2rem 0;
        margin-bottom: 1.5rem;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .categoria-title {
        font-size: 1.5rem;
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
}

/* Efeitos visuais */
.highlight-card {
    position: relative;
    border: 3px solid #4e73df !important;
}

.highlight-card::before {
    content: "🌟 MAIS PEDIDO";
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #4e73df;
    color: white;
    padding: 0.3rem 1rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 10;
}

/* Loading states */
.loading-placeholder {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>

<div class="content-wrapper">
    <!-- Hero Section -->
    <div class="cardapio-hero">
        <div class="container-fluid">
            <div class="hero-content">
                <div class="hero-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h1 class="hero-title">Cardápio Digital</h1>
                <p class="hero-subtitle">Sabores extraordinários para momentos especiais</p>
                <div class="hero-badge">
                    <i class="fas fa-star me-1"></i> Mais de 50 pratos especiais
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
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" placeholder="Buscar prato..." id="buscarProduto">
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo do Cardápio -->
        <div class="row">
            <div class="col-12">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($categorias) && empty($produtosPorCategoria)): ?>
                    <!-- Estado vazio -->
                    <div class="text-center py-5">
                        <i class="fas fa-utensils fa-4x text-muted mb-3"></i>
                        <h3 class="text-muted">Cardápio em Desenvolvimento</h3>
                        <p class="text-muted">Nosso cardápio está sendo preparado com muito carinho.</p>
                    </div>
                <?php else: ?>
                    <!-- Cardápio com conteúdo -->
                    <?php foreach ($categorias as $categoria): ?>
                        <?php if (isset($produtosPorCategoria[$categoria['id']])): ?>
                            <section class="categoria-section" id="categoria-<?php echo $categoria['id']; ?>">
                                <div class="categoria-header" style="border-bottom-color: <?php echo $categoria['color'] ?? '#4e73df'; ?>">
                                    <div class="categoria-icon" style="color: <?php echo $categoria['color'] ?? '#4e73df'; ?>">
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
                                                        <span class="produto-badge">Destaque</span>
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
                                                        <div class="veg-indicator <?php echo $produto['is_vegetarian'] ? '' : 'non-veg'; ?>"></div>
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
                            <div class="categoria-header" style="border-bottom-color: #6c757d">
                                <div class="categoria-icon" style="color: #6c757d">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <h2 class="categoria-title">Outros Produtos</h2>
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
                                                <span class="produto-preco">R$ <?php echo number_format($produto['price'], 2, ',', '.'); ?></span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtros por categoria
    const filtroBtns = document.querySelectorAll('.filtro-btn');
    const produtoItems = document.querySelectorAll('.produto-item');
    const searchInput = document.getElementById('buscarProduto');
    
    filtroBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active de todos os botões
            filtroBtns.forEach(b => b.classList.remove('active'));
            // Adiciona active ao botão clicado
            this.classList.add('active');
            
            const categoria = this.getAttribute('data-categoria');
            
            produtoItems.forEach(item => {
                if (categoria === 'todos') {
                    item.style.display = 'block';
                    item.closest('.categoria-section').style.display = 'block';
                } else {
                    const itemCategoria = item.getAttribute('data-categoria');
                    if (itemCategoria === categoria) {
                        item.style.display = 'block';
                        item.closest('.categoria-section').style.display = 'block';
                    } else {
                        item.style.display = 'none';
                        // Esconde a seção inteira se não houver itens visíveis
                        const section = item.closest('.categoria-section');
                        const visibleItems = section.querySelectorAll('.produto-item[style="display: block"]');
                        if (visibleItems.length === 0) {
                            section.style.display = 'none';
                        }
                    }
                }
            });
        });
    });
    
    // Busca em tempo real
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        produtoItems.forEach(item => {
            const itemName = item.getAttribute('data-nome');
            const itemText = item.textContent.toLowerCase();
            
            if (itemName.includes(searchTerm) || itemText.includes(searchTerm)) {
                item.style.display = 'block';
                item.closest('.categoria-section').style.display = 'block';
            } else {
                item.style.display = 'none';
                
                // Esconde a seção inteira se não houver itens visíveis
                const section = item.closest('.categoria-section');
                const visibleItems = section.querySelectorAll('.produto-item[style="display: block"]');
                if (visibleItems.length === 0) {
                    section.style.display = 'none';
                }
            }
        });
    });
    
    // Efeitos de hover nos cards
    produtoItems.forEach(item => {
        const card = item.querySelector('.produto-card');
        
        item.addEventListener('mouseenter', function() {
            card.style.transform = 'translateY(-5px)';
        });
        
        item.addEventListener('mouseleave', function() {
            card.style.transform = 'translateY(0)';
        });
    });
    
    // Animação de entrada dos cards
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);
    
    produtoItems.forEach(item => {
        observer.observe(item);
    });
});
</script>

<?php
require_once '../app/includes/footer.php';
?>