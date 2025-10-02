<?php
// public/dashboard.php
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Definir a página atual para o sidebar
$currentPage = 'dashboard.php';
?>

<div class="content-wrapper">
    <h1 class="page-title"><i class="fas fa-chart-line me-2"></i>Dashboard de Vendas</h1>
    <p class="page-subtitle">Desempenho comercial em tempo real</p>

    <!-- Cards de Vendas por Período -->
    <div class="row g-4 mb-4">
        <!-- Vendas Hoje -->
        <div class="col-md-4">
            <div class="card p-4 shadow-sm border-0 bg-gradient-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75"><i class="fas fa-sun me-2"></i>Vendas Hoje</p>
                        <h2 class="fw-bold mb-0" id="sales-today">R$ 0,00</h2>
                        <small class="opacity-75" id="orders-today">0 pedidos</small>
                    </div>
                    <div class="text-end">
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vendas Semana -->
        <div class="col-md-4">
            <div class="card p-4 shadow-sm border-0 bg-gradient-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1 opacity-75"><i class="fas fa-calendar-week me-2"></i>Vendas Esta Semana</p>
                        <h2 class="fw-bold mb-0" id="sales-week">R$ 0,00</h2>
                        <small class="opacity-75" id="orders-week">0 pedidos</small>
                    </div>
                    <div class="text-end">
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vendas Mês -->
        <div class="col-md-4">
            <div class="card p-4 shadow-sm border-0 bg-gradient-warning text-dark">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1"><i class="fas fa-calendar-alt me-2"></i>Vendas Este Mês</p>
                        <h2 class="fw-bold mb-0" id="sales-month">R$ 0,00</h2>
                        <small class="opacity-75" id="orders-month">0 pedidos</small>
                    </div>
                    <div class="text-end">
                        <div class="bg-dark bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas Principais -->
    <div class="row g-4 mb-4">
        <!-- Prato Mais Vendido -->
        <div class="col-md-6">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0 text-dark"><i class="fas fa-utensils me-2 text-primary"></i>Prato Mais Vendido</h5>
                </div>
                <div class="card-body text-center p-4">
                    <div id="top-dish">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bebida Mais Vendida -->
        <div class="col-md-6">
            <div class="card shadow-lg border-0 h-100">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0 text-dark"><i class="fas fa-glass-cheers me-2 text-success"></i>Bebida Mais Vendida</h5>
                </div>
                <div class="card-body text-center p-4">
                    <div id="top-drink">
                        <div class="d-flex justify-content-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendas Diárias -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0 text-dark"><i class="fas fa-calendar-day me-2 text-info"></i>Vendas por Dia</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4">Data</th>
                                    <th class="border-0">Faturamento</th>
                                    <th class="border-0">Pedidos</th>
                                    <th class="border-0">Ticket Médio</th>
                                    <th class="border-0">Desempenho</th>
                                </tr>
                            </thead>
                            <tbody id="daily-sales-table">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Carregando dados...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Produtos -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark"><i class="fas fa-trophy me-2 text-warning"></i>Produtos Mais Vendidos</h5>
                    <span class="badge bg-primary">Este Mês</span>
                </div>
                <div class="card-body">
                    <div id="top-products">
                        <div class="d-flex justify-content-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas de Desempenho -->
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card text-center p-4 border-0 shadow-sm bg-white h-100">
                <div class="card-body p-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-receipt fa-lg text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark" id="avg-ticket">R$ 0,00</h4>
                    <small class="text-muted">Ticket Médio</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-4 border-0 shadow-sm bg-white h-100">
                <div class="card-body p-3">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-utensils fa-lg text-success"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark" id="total-products">0</h4>
                    <small class="text-muted">Itens Vendidos</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-4 border-0 shadow-sm bg-white h-100">
                <div class="card-body p-3">
                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-users fa-lg text-info"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark" id="avg-items">0.0</h4>
                    <small class="text-muted">Itens/Pedido</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center p-4 border-0 shadow-sm bg-white h-100">
                <div class="card-body p-3">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock fa-lg text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark" id="busy-hours">-</h4>
                    <small class="text-muted">Horário Pico</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

.bg-gradient-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%) !important;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.04);
}

.performance-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funções de utilidade
    function formatCurrency(amount) {
        const num = parseFloat(amount || 0);
        return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', { 
            weekday: 'short',
            day: '2-digit', 
            month: '2-digit' 
        }).replace('.', '');
    }

    function getPerformanceBadge(revenue) {
        const value = parseFloat(revenue);
        if (value >= 200) return '<span class="badge bg-success performance-badge">Excelente</span>';
        if (value >= 100) return '<span class="badge bg-info performance-badge">Bom</span>';
        if (value >= 50) return '<span class="badge bg-warning performance-badge">Regular</span>';
        return '<span class="badge bg-secondary performance-badge">Baixo</span>';
    }

    function getCategoryIcon(category) {
        const icons = {
            'prato': 'fa-utensils text-primary',
            'bebida': 'fa-glass-cheers text-success',
            'diversos': 'fa-star text-warning'
        };
        return icons[category] || 'fa-box text-secondary';
    }

    function getCategoryLabel(category) {
        const labels = {
            'prato': 'Prato Principal',
            'bebida': 'Bebida',
            'diversos': 'Sobremesa'
        };
        return labels[category] || category;
    }

    // Carregar dados do dashboard
    function loadDashboardData() {
        fetch('api.php?action=get_sales_dashboard&period=7')
            .then(response => {
                if (!response.ok) throw new Error('Erro na API');
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos da API:', data);
                if (data.success) {
                    updateSalesMetrics(data.data);
                    updateDailySalesTable(data.data.daily_sales);
                    updateTopProducts(data.data.top_products);
                    updateTopDishAndDrink(data.data.top_dish, data.data.top_drink);
                    updateAdditionalMetrics(data.data.additional_metrics);
                } else {
                    console.error("Erro ao carregar dados:", data.message);
                    showError("Erro ao carregar dados do dashboard");
                }
            })
            .catch(error => {
                console.error('Falha na comunicação com a API:', error);
                showError("Falha na comunicação com o servidor");
            });
    }

    function showError(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
    }

    // Atualizar métricas de vendas
    function updateSalesMetrics(data) {
        const sales = data.sales_metrics;
        
        document.getElementById('sales-today').textContent = formatCurrency(sales.today.amount);
        document.getElementById('orders-today').textContent = `${sales.today.orders} pedido${sales.today.orders !== 1 ? 's' : ''}`;
        
        document.getElementById('sales-week').textContent = formatCurrency(sales.week.amount);
        document.getElementById('orders-week').textContent = `${sales.week.orders} pedido${sales.week.orders !== 1 ? 's' : ''}`;
        
        document.getElementById('sales-month').textContent = formatCurrency(sales.month.amount);
        document.getElementById('orders-month').textContent = `${sales.month.orders} pedido${sales.month.orders !== 1 ? 's' : ''}`;
    }

    // Atualizar tabela de vendas diárias
    function updateDailySalesTable(dailySales) {
        const container = document.getElementById('daily-sales-table');
        
        if (!dailySales || dailySales.length === 0) {
            container.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="fas fa-chart-bar fa-2x mb-3 d-block"></i>
                        Nenhuma venda no período
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        dailySales.forEach(sale => {
            const revenue = parseFloat(sale.revenue || 0);
            const orders = parseInt(sale.orders || 0);
            const avgTicket = orders > 0 ? revenue / orders : 0;
            
            html += `
                <tr>
                    <td class="ps-4 fw-bold">${formatDate(sale.date)}</td>
                    <td class="fw-bold text-success fs-6">${formatCurrency(revenue)}</td>
                    <td>
                        <span class="badge bg-opacity-20 text-primary border border-primary border-opacity-25">
                            ${orders} pedido${orders !== 1 ? 's' : ''}
                        </span>
                    </td>
                    <td class="text-info fw-semibold">${formatCurrency(avgTicket)}</td>
                    <td>${getPerformanceBadge(revenue)}</td>
                </tr>
            `;
        });
        
        container.innerHTML = html;
    }

    // Atualizar top produtos
    function updateTopProducts(products) {
        const container = document.getElementById('top-products');
        
        if (!products || products.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-utensils fa-3x mb-3 opacity-50"></i>
                    <p class="mb-0">Nenhum produto vendido no período</p>
                </div>
            `;
            return;
        }

        let html = '<div class="row g-3">';
        
        products.forEach((product, index) => {
            const quantity = parseInt(product.quantity || 0);
            const revenue = parseFloat(product.revenue || 0);
            const rankColors = ['bg-warning text-dark', 'bg-secondary', 'bg-dark'];
            const rankIcons = ['fa-trophy', 'fa-medal', 'fa-award'];
            
            html += `
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4 border">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle ${rankColors[index]} d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <i class="fas ${rankIcons[index] || 'fa-star'} fa-sm ${index === 0 ? 'text-dark' : 'text-white'}"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">${product.name}</h6>
                                    <small class="text-muted">
                                        <i class="fas ${getCategoryIcon(product.category)} me-1"></i>
                                        ${getCategoryLabel(product.category)}
                                    </small>
                                </div>
                            </div>
                            
                            <div class="row text-center border-top pt-3">
                                <div class="col-6 border-end">
                                    <div class="text-muted small">Quantidade</div>
                                    <div class="fw-bold fs-5 text-primary">${quantity}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Faturamento</div>
                                    <div class="fw-bold fs-5 text-success">${formatCurrency(revenue)}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }

    // Atualizar prato e bebida mais vendidos
    function updateTopDishAndDrink(topDish, topDrink) {
        // Prato mais vendido
        const dishContainer = document.getElementById('top-dish');
        if (topDish && topDish.name) {
            const dishQuantity = parseInt(topDish.quantity || 0);
            const dishRevenue = parseFloat(topDish.revenue || 0);
            
            dishContainer.innerHTML = `
                <div class="p-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-utensils fa-2x text-primary"></i>
                    </div>
                    <h4 class="text-dark fw-bold mb-3">${topDish.name}</h4>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1">Quantidade</div>
                                <div class="fw-bold fs-4 text-primary">${dishQuantity}</div>
                                <small class="text-muted">unidades</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1">Faturamento</div>
                                <div class="fw-bold fs-4 text-success">${formatCurrency(dishRevenue)}</div>
                                <small class="text-muted">total</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            dishContainer.innerHTML = `
                <div class="p-4 text-muted">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                    <h5 class="mb-2">Nenhum prato vendido</h5>
                    <p class="mb-0 small">Aguardando vendas...</p>
                </div>
            `;
        }

        // Bebida mais vendida
        const drinkContainer = document.getElementById('top-drink');
        if (topDrink && topDrink.name) {
            const drinkQuantity = parseInt(topDrink.quantity || 0);
            const drinkRevenue = parseFloat(topDrink.revenue || 0);
            
            drinkContainer.innerHTML = `
                <div class="p-3">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-glass-cheers fa-2x text-success"></i>
                    </div>
                    <h4 class="text-dark fw-bold mb-3">${topDrink.name}</h4>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1">Quantidade</div>
                                <div class="fw-bold fs-4 text-primary">${drinkQuantity}</div>
                                <small class="text-muted">unidades</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1">Faturamento</div>
                                <div class="fw-bold fs-4 text-success">${formatCurrency(drinkRevenue)}</div>
                                <small class="text-muted">total</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            drinkContainer.innerHTML = `
                <div class="p-4 text-muted">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-glass-cheers fa-2x"></i>
                    </div>
                    <h5 class="mb-2">Nenhuma bebida vendida</h5>
                    <p class="mb-0 small">Aguardando vendas...</p>
                </div>
            `;
        }
    }

    // Atualizar métricas adicionais
    function updateAdditionalMetrics(metrics) {
        document.getElementById('avg-ticket').textContent = formatCurrency(metrics.avg_ticket);
        document.getElementById('total-products').textContent = metrics.total_products_sold.toLocaleString('pt-BR');
        document.getElementById('avg-items').textContent = parseFloat(metrics.avg_items_per_order).toFixed(1);
        document.getElementById('busy-hours').textContent = metrics.busy_hours || '-';
    }

    // Inicializar o dashboard
    loadDashboardData();
    
    // Atualizar dados a cada 2 minutos
    setInterval(loadDashboardData, 120000);
});
</script>

<?php
require_once '../app/includes/footer.php';
?>