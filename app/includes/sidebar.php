
<div class="sidebar d-flex flex-column">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-utensils"></i>
        </div>
        <h2>Restaurante</h2>
    </div>
    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-chart-line me-2"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage == 'menu.php') ? 'active' : ''; ?>" href="menu.php">
                <i class="fas fa-list-alt me-2"></i> <span>Cardápio</span>
            </a>
        </li>
        </ul>
    <hr class="text-white-50 my-3 d-none d-md-block">
    <div class="mt-auto sidebar-bottom d-none d-md-block">
        <p class="text-white-50 mb-1">Olá, <?php echo $userName; ?>!</p>
        <a href="logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt me-2"></i> <span>Sair</span>
        </a>
    </div>
</div>