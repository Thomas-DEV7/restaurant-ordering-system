<?php
// public/dashboard.php
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';
?>
<div class="content-wrapper">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Seja bem-vindo(a) ao painel de controle do restaurante.</p>
    
    <div class="row g-4 mt-4">
        <div class="col-md-6 col-lg-4">
            <a href="menu.php" class="shortcut-card d-block">
                <div class="text-center">
                    <i class="fas fa-list-alt shortcut-icon"></i>
                    <h5 class="shortcut-title mt-2">Gerenciar Cardápio</h5>
                    <p class="shortcut-text">Adicione, edite ou remova pratos e bebidas.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="shortcut-card d-block">
                <div class="text-center">
                    <i class="fas fa-tablet-alt shortcut-icon"></i>
                    <h5 class="shortcut-title mt-2">Comanda Digital</h5>
                    <p class="shortcut-text">Funcionalidade em desenvolvimento.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="shortcut-card d-block">
                <div class="text-center">
                    <i class="fas fa-chart-bar shortcut-icon"></i>
                    <h5 class="shortcut-title mt-2">Relatórios</h5>
                    <p class="shortcut-text">Acompanhe as vendas e o desempenho.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../app/includes/footer.php';
?>