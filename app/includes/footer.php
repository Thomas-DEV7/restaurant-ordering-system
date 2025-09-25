
    </div> <div class="bottom-nav d-flex d-md-none">
        <a class="nav-link <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-chart-line"></i>
            <small>Dashboard</small>
        </a>
        <a class="nav-link <?php echo ($currentPage == 'menu.php') ? 'active' : ''; ?>" href="menu.php">
            <i class="fas fa-list-alt"></i>
            <small>Cardápio</small>
        </a>
        <a class="nav-link <?php echo ($currentPage == 'comandas.php') ? 'active' : ''; ?>" href="comandas.php">
            <i class="fas fa-tablet-alt"></i>
            <small>Comandas</small>
        </a>
        <a class="nav-link" href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <small>Sair</small>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>