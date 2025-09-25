<?php
// public/orders_management.php
$pageTitle = "Gerenciamento de Pedidos";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Definir a página atual para o sidebar
$currentPage = 'orders_management.php';
?>

<div class="content-wrapper">
    <h1 class="page-title"><i class="fas fa-clipboard-list me-2"></i> Monitor de Pedidos</h1>
    <p class="page-subtitle">Visão geral do status de todos os pedidos abertos e fechados recentemente.</p>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="table-primary">
                            <th>ID Pedido</th>
                            <th>Mesa</th>
                            <th>Atendente</th>
                            <th>Valor Total</th>
                            <th>Hora do Pedido</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        </tbody>
                </table>
            </div>
            <div id="loading-message" class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Carregando dados dos pedidos...</p>
            </div>
            <div id="no-orders-message" class="alert alert-info text-center d-none">
                Nenhum pedido encontrado no momento.
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.getElementById('orders-table-body');
        const loadingMessage = document.getElementById('loading-message');
        const noOrdersMessage = document.getElementById('no-orders-message');

        function formatCurrency(amount) {
            const num = parseFloat(amount);
            return 'R$ ' + num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function getStatusBadge(status) {
            let color = '';
            let text = '';
            switch (status) {
                case 'pending':
                    color = 'bg-warning text-dark';
                    text = 'Em Aberto';
                    break;
                case 'completed':
                    color = 'bg-success';
                    text = 'Fechado';
                    break;
                default:
                    color = 'bg-secondary';
                    text = 'Desconhecido';
            }
            return `<span class="badge ${color}">${text}</span>`;
        }

        function loadOrders() {
            loadingMessage.classList.remove('d-none');
            noOrdersMessage.classList.add('d-none');
            tableBody.innerHTML = '';

            fetch('api.php?action=get_all_orders_summary')
                .then(response => {
                    if (!response.ok) throw new Error('Erro de rede ou servidor.');
                    return response.json();
                })
                .then(data => {
                    loadingMessage.classList.add('d-none');
                    if (data.success && data.data.length > 0) {
                        renderOrders(data.data);
                    } else {
                        noOrdersMessage.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    loadingMessage.classList.add('d-none');
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">Erro ao carregar dados: ${error}</td></tr>`;
                    console.error('Erro ao carregar pedidos:', error);
                });
        }

        function renderOrders(orders) {
            let html = '';
            orders.forEach(order => {
                const totalValue = order.total_value ? order.total_value : '0.00';
                
                // Formatação de data/hora
                const orderTime = new Date(order.order_time).toLocaleString('pt-BR', {
                    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
                });

                html += `
                    <tr class="${order.status === 'pending' ? 'table-light' : 'table-success bg-opacity-10'}">
                        <td>#${order.order_id}</td>
                        <td>Mesa ${order.table_number}</td>
                        <td>${order.waiter_name}</td>
                        <td>${formatCurrency(totalValue * 1.1)}</td> <td>${orderTime}</td>
                        <td>${getStatusBadge(order.status)}</td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        }

        loadOrders();
        // Opcional: Atualizar automaticamente a cada 30 segundos
        setInterval(loadOrders, 30000); 
    });
</script>

<?php
require_once '../app/includes/footer.php';
?>