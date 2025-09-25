<?php
// public/kitchen_display.php - Tela de visualização da Cozinha (KDS)
$pageTitle = "Visualização Cozinha";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Definir a página atual para o sidebar
$currentPage = 'kitchen_display.php';
?>

<div class="content-wrapper">
    <h1 class="page-title"><i class="fas fa-fire me-2"></i> Pedidos da Cozinha (KDS)</h1>
    <p class="page-subtitle">Acompanhe os pedidos que precisam ser preparados. Esta tela atualiza automaticamente.</p>
    
    <div id="kitchen-orders" class="row g-4 mt-4">
        <div class="col-12 text-center my-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3 text-muted">Aguardando novos pedidos...</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ordersContainer = document.getElementById('kitchen-orders');
        let intervalId; 

        // --- Função de Tempo Corrigida e Funcional (Evita NaN) ---
        function getTimeAgo(timestamp) {
            const now = new Date();
            const sent = new Date(timestamp.replace(/-/g, "/")); // Substitui '-' por '/' para maior compatibilidade

            if (isNaN(sent.getTime())) {
                return 'Erro na Data'; 
            }
            
            const diffInSeconds = Math.floor((now - sent) / 1000);

            if (diffInSeconds < 5) return `Agora mesmo`;

            const minutes = Math.floor(diffInSeconds / 60);

            if (minutes === 0) {
                const seconds = diffInSeconds % 60;
                return `${seconds} segundos atrás`;
            } else if (minutes === 1) {
                return `há 1 minuto atrás`;
            } else {
                return `há ${minutes} minutos atrás`;
            }
        }
        
        // --- Ação: Atualizar Status do Pedido na Cozinha (Pronto) ---
        function updateOrderStatus(kitchenOrderId, newStatus) {
            fetch('api_print_queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', kitchen_order_id: kitchenOrderId, new_status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Após sucesso, recarrega a lista para remover o item concluído
                    loadKitchenOrders(); 
                } else {
                    console.error('Falha na atualização:', data.message);
                    alert('Falha ao atualizar o status! Verifique o console.');
                }
            })
            .catch(error => {
                console.error('Erro de comunicação:', error);
            });
        }
        
        // --- Lógica de Carregamento dos Pedidos ---
        function loadKitchenOrders() {
            fetch('api_print_queue.php?action=get_pending_prints')
                .then(response => {
                    if (!response.ok) throw new Error('API Error');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderKitchenOrders(data.data);
                    } else {
                        ordersContainer.innerHTML = '<div class="col-12 alert alert-info text-center">Nenhum pedido novo na fila.</div>';
                    }
                })
                .catch(error => {
                    ordersContainer.innerHTML = '<div class="col-12 alert alert-danger">Falha de comunicação com a API.</div>';
                    console.error('Erro ao buscar pedidos:', error);
                });
        }

        // --- Renderização dos Pedidos ---
        function renderKitchenOrders(groupedOrders) {
            let html = '';
            
            groupedOrders.forEach(order => {
                const timeText = getTimeAgo(order.time_sent);
                // Alerta visual após 5 minutos (300 segundos)
                const isOverdue = (new Date() - new Date(order.time_sent.replace(/-/g, "/"))) / 1000 >= 300;
                const timeColorClass = isOverdue ? 'bg-danger' : 'bg-warning text-dark';
                
                html += `
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card order-card h-100 shadow border-top-0">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <span>Mesa <strong>${order.table_number}</strong></span>
                                <span class="badge ${timeColorClass}">${timeText}</span>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    ${order.items.map(item => `
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span class="badge bg-dark text-white rounded-pill me-3">${item.quantity}x</span>
                                            <span class="flex-grow-1 fw-bold">${item.product_name}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                            <div class="card-footer text-center d-grid gap-2">
                                ${order.items.map(item => `
                                    <button class="btn btn-sm btn-outline-success mark-done-btn" 
                                            data-id="${item.kitchen_order_id}">
                                        Pronto: ${item.product_name}
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
            });
            ordersContainer.innerHTML = html;
        }

        // --- Event Listener para os Botões de Ação (Delegation) ---
        ordersContainer.addEventListener('click', function(event) {
            const button = event.target.closest('.mark-done-btn');
            if (button) {
                const kitchenOrderId = button.getAttribute('data-id');
                if (confirm(`Tem certeza que deseja marcar "${button.textContent}" como pronto?`)) {
                    updateOrderStatus(kitchenOrderId, 'done'); 
                }
            }
        });


        // --- Controle do Intervalo de Atualização ---
        function startUpdateInterval() {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(loadKitchenOrders, 5000);
        }

        // Inicializa o carregamento e o intervalo
        loadKitchenOrders();
        startUpdateInterval();
    });
</script>

<?php
require_once '../app/includes/footer.php';
?>