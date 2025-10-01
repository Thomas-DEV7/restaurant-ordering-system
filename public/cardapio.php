<?php
// public/cardapio.php
$pageTitle = "Cardápio Digital - Flor Da Vila";
require_once '../app/config/database.php';

$restauranteNome = "Flor Da Vila"; 

// Mapeamento de ícones e cores para categorias
$categoriaInfo = [
    'prato' => [
        'nome' => 'Pratos Principais',
        'icone_class' => 'fa-utensils',
        'cor' => '#d32f2f',
        'cor_clara' => '#ffdddd' // Corrigida para ser mais suave
    ],
    'bebida' => [
        'nome' => 'Bebidas',
        'icone_class' => 'fa-wine-glass-alt',
        'cor' => '#c2185b',
        'cor_clara' => '#ffdae6'
    ],
    'diversos' => [
        'nome' => 'Sobremesas',
        'icone_class' => 'fa-ice-cream',
        'cor' => '#e64a19',
        'cor_clara' => '#ffdbd4'
    ]
];

// Busca e Agrupamento dos Produtos
try {
    $produtosStmt = $pdo->query("
        SELECT * FROM products 
        WHERE status = 'active' 
        ORDER BY category, name
    ");
    $produtos = $produtosStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $produtosPorCategoria = [];
    foreach ($produtos as $produto) {
        $produtosPorCategoria[$produto['category']][] = $produto;
    }
    $error = null;
    
} catch (PDOException $e) {
    $error = "Erro ao carregar cardápio: Verifique a conexão com o banco de dados.";
    $produtosPorCategoria = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* Paleta de cores baseada no seu design */
        :root {
            --vermelho-principal: #d32f2f;
            --laranja: #e64a19;
            --texto-escuro: #2c1810;
            --texto-medio: #5d4037;
            --fundo-claro: #fffaf8;
            --sombra-suave: 0 2px 12px rgba(211, 47, 47, 0.1);
        }

        /* Reset e Base */
        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--fundo-claro);
            color: var(--texto-escuro);
            padding-bottom: 30px;
        }
        html { scroll-behavior: smooth; }

        /* Header Impactante */
        .cardapio-header {
            background: linear-gradient(135deg, var(--vermelho-principal) 0%, var(--laranja) 100%);
            color: white;
            padding: 3rem 1rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .restaurante-nome { font-size: 2rem; font-weight: 800; }
        .restaurante-slogan { font-size: 1rem; opacity: 0.9; }

        /* Navegação Sticky */
        .categorias-sticky {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: white;
            padding: 0.8rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .categorias-container {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 0 1rem;
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }
        .categorias-container::-webkit-scrollbar { display: none; }
        .categoria-btn {
            flex-shrink: 0;
            border: 1px solid var(--vermelho-principal);
            background: white;
            color: var(--vermelho-principal);
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .categoria-btn.active {
            background-color: var(--vermelho-principal);
            color: white;
        }

        /* Seções de Itens */
        .categoria-section {
            padding: 1.5rem 1rem;
        }
        .categoria-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--vermelho-principal);
            border-bottom: 2px solid var(--vermelho-principal);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        /* Card do Produto (Layout de Lista Simples e Usável) */
        .produto-card {
            display: flex;
            background: white;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: var(--sombra-suave);
            overflow: hidden;
        }
        
        .produto-imagem-container {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            position: relative;
        }
        .produto-imagem {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .imagem-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            opacity: 0.7;
        }
        
        .produto-info {
            padding: 1rem;
            flex-grow: 1;
        }
        .produto-nome {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
            color: var(--texto-escuro);
        }
        .produto-preco {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--laranja);
            margin-top: 0.5rem;
        }
        .produto-descricao {
            font-size: 0.85rem;
            color: var(--texto-medio);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Responsive */
        @media (min-width: 768px) {
            .cardapio-main {
                max-width: 900px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="cardapio-container">
    <header class="cardapio-header">
        <h1 class="restaurante-nome">🍽️ <?php echo $restauranteNome; ?></h1>
        <p class="restaurante-slogan">Sabores caseiros que aquecem o coração</p>
    </header>

    <nav class="categorias-sticky">
        <div class="categorias-container">
            <button class="categoria-btn active" data-categoria="todos">
                Todos
            </button>
            <?php foreach ($categoriaInfo as $categoriaKey => $info): ?>
                <button class="categoria-btn" data-categoria="<?php echo $categoriaKey; ?>">
                    <?php echo $info['nome']; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </nav>

    <main class="cardapio-main">
        <?php if ($error): ?>
            <div class="alert alert-danger mx-3 mt-3"><?php echo $error; ?></div>
        <?php elseif (empty($produtos)): ?>
            <div class="alert alert-info text-center mx-3 mt-3">Cardápio em Preparação. Volte em breve!</div>
        <?php else: ?>

            <?php foreach ($categoriaInfo as $categoriaKey => $info): ?>
                <?php if (!empty($produtosPorCategoria[$categoriaKey])): ?>
                    
                    <section class="categoria-section" id="categoria-<?php echo $categoriaKey; ?>">
                        <h2 class="categoria-title"><?php echo $info['nome']; ?></h2>
                        
                        <?php foreach ($produtosPorCategoria[$categoriaKey] as $produto): ?>
                            <div class="produto-card produto-item" data-categoria="<?php echo $categoriaKey; ?>">
                                
                                <div class="produto-imagem-container" style="background-color: <?php echo $info['cor_clara']; ?>">
                                    <?php 
                                        $hasImage = !empty($produto['image']);
                                        $imagePath = htmlspecialchars($produto['image']);
                                        $iconeClass = $info['icone_class'];
                                    ?>
                                    
                                    <?php if ($hasImage): ?>
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($produto['name']); ?>"
                                             class="produto-imagem"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <?php endif; ?>
                                    
                                    <div class="imagem-placeholder" style="color: <?php echo $info['cor']; ?>; <?php echo $hasImage ? 'display: none;' : ''; ?>">
                                        <i class="fas <?php echo $iconeClass; ?>"></i>
                                    </div>
                                </div>
                                
                                <div class="produto-info">
                                    <div class="produto-cabecalho">
                                        <h3 class="produto-nome"><?php echo htmlspecialchars($produto['name']); ?></h3>
                                    </div>
                                    
                                    <?php if (!empty($produto['description'])): ?>
                                        <p class="produto-descricao"><?php echo htmlspecialchars($produto['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="produto-preco">R$ <?php echo number_format($produto['price'], 2, ',', '.'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>

                <?php endif; ?>
            <?php endforeach; ?>

        <?php endif; ?>
    </main>
</div>

<script>
// JavaScript para Filtro de Categorias e Scroll Suave
document.addEventListener('DOMContentLoaded', function() {
    const categoriaBtns = document.querySelectorAll('.categoria-btn');
    const produtoItems = document.querySelectorAll('.produto-item');
    const sections = document.querySelectorAll('.categoria-section');
    
    // Função para aplicar filtro visualmente
    function filterProducts(categoria) {
        sections.forEach(section => {
            section.style.display = 'none';
        });

        if (categoria === 'todos') {
            sections.forEach(section => {
                section.style.display = 'block';
            });
        } else {
            const sectionId = 'categoria-' + categoria;
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.style.display = 'block';
            }
        }
    }

    // Listener para os botões de filtro
    categoriaBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            categoriaBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const categoria = this.getAttribute('data-categoria');
            
            // Faz a filtragem na visualização
            filterProducts(categoria);

            // Rola suavemente para o topo no mobile
            if (window.innerWidth < 768) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    });
    
    // Inicializa a filtragem em "Todos"
    filterProducts('todos');
});
</script>

</body>
</html>