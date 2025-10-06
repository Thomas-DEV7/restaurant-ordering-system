<?php

$userRole = $_SESSION['user_role'] ?? 'guest';
$isAdmin = ($userRole === 'admin');
?>

<div class="sidebar d-flex flex-column">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-utensils"></i>
        </div>
        <h2>Restaurante</h2>
    </div>
    <ul class="nav flex-column mb-auto">
        <?php if ($isAdmin): ?>

        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
            </a>
        </li>
         <?php endif; ?>

        <?php if ($isAdmin): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'menu.php') ? 'active' : ''; ?>" href="menu.php">
                    <i class="fas fa-list-alt me-2"></i> <span>Gerenciar Cardápio</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'comandas.php') ? 'active' : ''; ?>" href="comandas.php">
                <i class="fas fa-clipboard-list me-2"></i> <span>Comanda Digital</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'kitchen_display.php') ? 'active' : ''; ?>" href="kitchen_display.php">
                <i class="fas fa-tv me-2"></i> <span>Visualização Cozinha</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'table_management.php') ? 'active' : ''; ?>" href="table_management.php">
                <i class="fas fa-table me-2"></i> <span>Gerenciamento de Mesas</span>
            </a>
        </li>

        <?php if ($isAdmin): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'orders_management.php') ? 'active' : ''; ?>" href="orders_management.php">
                    <i class="fas fa-tasks me-2"></i> <span>Monitor de Pedidos</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo ($currentPage == 'financial_report.php') ? 'active' : ''; ?>" href="financial_report.php">
                    <i class="fas fa-hand-holding-usd me-2"></i> <span>Relatório Financeiro</span>
                </a>
            </li>
        <?php endif; ?>

    </ul>
    <hr class="text-white-50 my-3 d-none d-md-block">
    <div class="mt-auto sidebar-bottom d-none d-md-block">
        <p class="text-white-50 mb-1"><strong><?php echo htmlspecialchars($userName); ?></strong></p>
        <!-- <p class="text-white-50 mb-1">Função: <?php echo htmlspecialchars(ucfirst($userRole)); ?></p> -->
        <a href="logout.php" class="nav-link text-white">
            <i class="fas fa-sign-out-alt me-2 logout-icon"></i> <span>Sair</span>
        </a>
    </div>
</div>