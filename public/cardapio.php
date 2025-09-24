<?php
// public/cardapio.php
require_once '../app/config/database.php';

// Busca todos os produtos ativos do banco de dados
$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY category, name");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cria um array associativo para agrupar os produtos por categoria
$groupedProducts = [
    'prato' => [],
    'bebida' => [],
    'diversos' => []
];

foreach ($products as $product) {
    if (isset($groupedProducts[$product['category']])) {
        $groupedProducts[$product['category']][] = $product;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cardápio - Nome do Seu Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #e74c3c;
            --secondary-color: #f39c12;
            --dark-text: #2c3e50;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        .header-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .header-bg .logo-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .header-bg h1 {
            font-weight: 700;
        }

        .header-bg p {
            max-width: 600px;
            margin: 0 auto;
        }

        .menu-section {
            padding: 3rem 0;
        }

        .menu-category h2 {
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
        }

        .menu-item {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }

        .menu-item-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 1rem 1rem 0 0;
        }

        .menu-item-body {
            padding: 1.5rem;
        }

        .menu-item h4 {
            font-weight: 600;
            color: var(--dark-text);
        }

        .menu-item .price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <!-- Início da página de cardápio -->
    <div class="header-bg">
        <i class="fas fa-utensils logo-icon"></i>
        <h1>Nosso Cardápio</h1>
        <p>Descubra os sabores únicos que preparamos para você, feitos com ingredientes frescos e o máximo de carinho.</p>
    </div>

    <div class="container menu-section">
        <?php foreach ($groupedProducts as $category => $items): ?>
            <?php if (!empty($items)): ?>
                <div class="menu-category mt-5">
                    <h2><?php echo ucfirst($category); ?></h2>
                </div>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($items as $item): ?>
                        <div class="col">
                            <div class="card menu-item h-100">
                                <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://via.placeholder.com/400x200?text=Sem+Imagem'); ?>" class="menu-item-img" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="card-body menu-item-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <span class="price">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></span>
                                    </div>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($item['description']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($products)): ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                O cardápio está sendo atualizado. Volte em breve!
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>