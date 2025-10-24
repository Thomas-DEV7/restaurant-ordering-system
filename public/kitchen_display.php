<?php
// public/kitchen_display.php - Tela de visualização da Cozinha (KDS)
$pageTitle = "Visualização Cozinha";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Definir a página atual para o sidebar
$currentPage = 'kitchen_display.php';
?>

<style>
    .order-card {
        transition: all 0.3s ease;
    }

    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2) !important;
    }

    .has-observation {
        border: 3px solid #ff6b6b !important;
        animation: pulse-border 2s infinite;
    }

    @keyframes pulse-border {

        0%,
        100% {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
        }

        50% {
            border-color: #ff8787;
            box-shadow: 0 0 0 10px rgba(255, 107, 107, 0);
        }
    }

    .observation-badge {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #ff6b6b;
        color: white;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        animation: bounce 1s infinite;
        box-shadow: 0 4px 8px rgba(255, 107, 107, 0.5);
        z-index: 10;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .observation-text {
        background: #fff3cd;
        border-left: 4px solid #ff6b6b;
        padding: 10px;
        margin: 10px 0;
        font-style: italic;
        font-weight: bold;
        color: #856404;
    }

    .btn-add-observation {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-add-observation:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
</style>

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

<!-- Modal de Observação -->
<div class="modal fade" id="observationModal" tabindex="-1" aria-labelledby="observationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="observationModalLabel">
                    <i class="fas fa-comment-dots me-2"></i>Adicionar Observação
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="observationText" class="form-label fw-bold">Observação do Pedido:</label>
                    <textarea class="form-control" id="observationText" rows="4"
                        placeholder="Ex: Cliente alérgico a amendoim, sem cebola, ponto da carne mal passado..."></textarea>
                    <small class="text-muted">Esta observação será exibida para toda a equipe da cozinha.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="saveObservationBtn">
                    <i class="fas fa-save me-1"></i>Salvar Observação
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ordersContainer = document.getElementById('kitchen-orders');
        const observationModal = new bootstrap.Modal(document.getElementById('observationModal'));
        const observationText = document.getElementById('observationText');
        const saveObservationBtn = document.getElementById('saveObservationBtn');
        let currentOrderId = null;
        let intervalId;

        // --- Função de Tempo Corrigida e Funcional (Evita NaN) ---
        function getTimeAgo(timestamp) {
            const now = new Date();
            const sent = new Date(timestamp.replace(/-/g, "/"));

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
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_status',
                        kitchen_order_id: kitchenOrderId,
                        new_status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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

        // --- Função para Salvar Observação ---
        // --- Função para Salvar Observação ---
        function saveObservation(orderId, observation) {
            fetch('api_print_queue.php', { // ⭐ MUDOU AQUI ⭐
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_order_observation',
                        order_id: orderId,
                        observation: observation
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        observationModal.hide();
                        observationText.value = '';
                        loadKitchenOrders();

                        // Feedback visual
                        showAlert('success', data.message);
                    } else {
                        showAlert('danger', 'Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro ao salvar observação:', error);
                    showAlert('danger', 'Erro de comunicação ao salvar observação.');
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
                const isOverdue = (new Date() - new Date(order.time_sent.replace(/-/g, "/"))) / 1000 >= 300;
                const timeColorClass = isOverdue ? 'bg-danger' : 'bg-warning text-dark';
                const hasObservation = order.observation && order.observation.trim() !== '';
                const cardClass = hasObservation ? 'has-observation' : '';

                html += `
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card order-card h-100 shadow border-top-0 ${cardClass}" style="position: relative;">
                            ${hasObservation ? '<div class="observation-badge"><i class="fas fa-exclamation"></i></div>' : ''}
                            
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <span>Mesa <strong>${order.table_number}</strong></span>
                                <span class="badge ${timeColorClass}">${timeText}</span>
                            </div>
                            
                            <div class="card-body">
                                ${hasObservation ? `
                                    <div class="observation-text">
                                        <i class="fas fa-comment-dots me-2"></i>
                                        <strong>OBS:</strong> ${order.observation}
                                    </div>
                                ` : ''}
                                
                                <ul class="list-group list-group-flush">
                                    ${order.items.map(item => `
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span class="badge bg-dark text-white rounded-pill me-3">${item.quantity}x</span>
                                            <span class="flex-grow-1 fw-bold">${item.product_name}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                            
                            <div class="card-footer text-center">
                                <button class="btn btn-sm btn-add-observation mb-2 w-100" 
                                        data-order-id="${order.order_id}"
                                        data-current-observation="${order.observation || ''}">
                                    <i class="fas fa-comment-medical me-1"></i>
                                    ${hasObservation ? 'Editar Observação' : 'Adicionar Observação'}
                                </button>
                                
                                <div class="d-grid gap-2">
                                    ${order.items.map(item => `
                                        <button class="btn btn-sm btn-outline-success mark-done-btn" 
                                                data-id="${item.kitchen_order_id}">
                                            <i class="fas fa-check me-1"></i>Pronto: ${item.product_name}
                                        </button>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            ordersContainer.innerHTML = html;
        }

        // --- Event Listener para os Botões de Ação (Delegation) ---
        ordersContainer.addEventListener('click', function(event) {
            // Botão de marcar como pronto
            const doneButton = event.target.closest('.mark-done-btn');
            if (doneButton) {
                const kitchenOrderId = doneButton.getAttribute('data-id');
                if (confirm(`Tem certeza que deseja marcar este item como pronto?`)) {
                    updateOrderStatus(kitchenOrderId, 'done');
                }
                return;
            }

            // Botão de adicionar/editar observação
            const obsButton = event.target.closest('.btn-add-observation');
            if (obsButton) {
                currentOrderId = obsButton.getAttribute('data-order-id');
                const currentObs = obsButton.getAttribute('data-current-observation');
                observationText.value = currentObs || '';
                observationModal.show();
            }
        });

        // --- Salvar Observação ---
        saveObservationBtn.addEventListener('click', function() {
            const observation = observationText.value.trim();
            if (currentOrderId) {
                saveObservation(currentOrderId, observation);
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