<?php
// public/comandas.php
$pageTitle = "Comanda Digital";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Definir a página atual para o sidebar
$currentPage = 'comandas.php';
?>

<style>
/* Melhorias mínimas mantendo o padrão existente */
.table-card {
    transition: all 0.3s ease;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    height: 100%;
}

.table-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-color: #bac8f3;
}

.table-icon {
    font-size: 2.5rem;
    color: #4e73df;
}

.product-card {
    transition: all 0.2s ease;
    border: 1px solid #e3e6f0;
    cursor: pointer;
}

.product-card:hover {
    border-color: #bac8f3;
    background-color: #f8f9fc;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-summary-card {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
}

@media (max-width: 768px) {
    .table-card {
        margin-bottom: 1rem;
    }
    
    .modal-dialog {
        margin: 1rem;
    }
    
    .order-item-mobile {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .item-controls-mobile {
        width: 100%;
        justify-content: space-between;
    }
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">Comanda Digital</h1>
                <p class="mb-0 text-muted">Gerencie os pedidos das mesas de forma eficiente</p>
            </div>
            <button class="btn btn-primary btn-sm mt-2 mt-sm-0" id="refreshBtn">
                <i class="fas fa-sync-alt fa-sm"></i> Atualizar
            </button>
        </div>

        <!-- Content Row -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Mesas Disponíveis</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" id="tables-container">
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                                <p class="text-muted">Carregando mesas...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="orderModalLabel">
                    <i class="fas fa-utensils me-2"></i>Comanda da Mesa <span id="modalTableNumber"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-end pe-3">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-book me-2"></i>Cardápio
                        </h5>
                        <div class="row g-2" id="menu-items-list">
                            <!-- Itens do cardápio serão carregados aqui -->
                        </div>
                    </div>
                    <div class="col-md-6 ps-3">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-receipt me-2"></i>Detalhes da Comanda
                        </h5>
                        <div id="order-details-content">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <p class="mb-0">Nenhum pedido em andamento para esta mesa.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-bell me-2" id="toastIcon"></i>
            <strong class="me-auto" id="toastTitle">Notificação</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastBody">
            Mensagem da ação.
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tablesContainer = document.getElementById('tables-container');
        const orderModal = document.getElementById('orderModal');
        const modalTableNumber = document.getElementById('modalTableNumber');
        const menuItemsList = document.getElementById('menu-items-list');
        const orderDetailsContent = document.getElementById('order-details-content');
        const liveToast = document.getElementById('liveToast');
        const refreshBtn = document.getElementById('refreshBtn');
        const toastInstance = bootstrap.Toast.getOrCreateInstance(liveToast);

        // Função utilitária para exibir o Toast
        function showToast(message, type = 'success') {
            const toastTitle = document.getElementById('toastTitle');
            const toastBody = document.getElementById('toastBody');
            const toastIcon = document.getElementById('toastIcon');

            liveToast.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-info', 'text-bg-warning');
            toastIcon.className = 'me-2';

            if (type === 'success') {
                liveToast.classList.add('text-bg-success');
                toastTitle.textContent = 'Sucesso!';
                toastIcon.classList.add('fas', 'fa-check-circle');
            } else if (type === 'error') {
                liveToast.classList.add('text-bg-danger');
                toastTitle.textContent = 'Erro!';
                toastIcon.classList.add('fas', 'fa-times-circle');
            } else if (type === 'warning') {
                liveToast.classList.add('text-bg-warning');
                toastTitle.textContent = 'Atenção!';
                toastIcon.classList.add('fas', 'fa-exclamation-triangle');
            } else {
                liveToast.classList.add('text-bg-info');
                toastTitle.textContent = 'Informação!';
                toastIcon.classList.add('fas', 'fa-info-circle');
            }

            toastBody.textContent = message;
            toastInstance.show();
        }

        // Botão de atualização
        refreshBtn.addEventListener('click', function() {
            const icon = refreshBtn.querySelector('i');
            icon.classList.add('fa-spin');
            loadTables();
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        });

        // --- 1. Carregar e Renderizar Mesas ---
        function loadTables() {
            fetch('api.php?action=get_tables')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro de rede ou servidor.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderTables(data.data);
                    } else {
                        tablesContainer.innerHTML = `
                            <div class="col-12">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Erro API: ${data.message}
                                </div>
                            </div>`;
                    }
                })
                .catch(error => {
                    tablesContainer.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Erro ao carregar mesas. ${error.message}
                            </div>
                        </div>`;
                });
        }

        function renderTables(tables) {
            let html = '';
            if (tables.length === 0) {
                html = `
                    <div class="col-12">
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-table fa-2x mb-2"></i>
                            <h5>Nenhuma mesa cadastrada</h5>
                            <p class="mb-0">Adicione mesas ao sistema para começar a usar.</p>
                        </div>
                    </div>`;
            } else {
                tables.forEach(table => {
                    const statusClass = table.status === 'occupied' ? 'bg-danger' : 'bg-success';
                    const statusText = table.status === 'occupied' ? 'Ocupada' : 'Disponível';
                    const statusIcon = table.status === 'occupied' ? 'fa-users' : 'fa-user-plus';

                    html += `
                        <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                            <div class="card table-card cursor-pointer" 
                                data-bs-toggle="modal" 
                                data-bs-target="#orderModal"
                                data-table-id="${table.id}" 
                                data-table-number="${table.table_number}">
                                <div class="card-body text-center py-4">
                                    <i class="fas ${statusIcon} table-icon mb-3"></i>
                                    <h5 class="card-title">Mesa ${table.table_number}</h5>
                                    <span class="badge ${statusClass}">${statusText}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            tablesContainer.innerHTML = html;
        }

        // --- 2. Lógica do Modal ---
        orderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tableId = button.getAttribute('data-table-id');
            const tableNumber = button.getAttribute('data-table-number');
            
            modalTableNumber.textContent = tableNumber;
            orderModal.setAttribute('data-table-id', tableId);

            loadMenu();
            loadOrderDetails(tableId); 
        });
        
        // --- 3. Carregar Cardápio (Ação get_menu) ---
        function loadMenu() {
            menuItemsList.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
                    <p class="text-muted">Carregando cardápio...</p>
                </div>`;
                
            fetch('api.php?action=get_menu')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderMenu(data.data);
                    } else {
                        menuItemsList.innerHTML = `
                            <div class="col-12">
                                <div class="alert alert-danger">${data.message}</div>
                            </div>`;
                    }
                })
                .catch(error => {
                    menuItemsList.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-danger">Erro ao carregar cardápio.</div>
                        </div>`;
                });
        }

        function renderMenu(products) {
            let html = '';
            if (products.length === 0) {
                html = `
                    <div class="col-12">
                        <div class="alert alert-warning text-center py-4">
                            <i class="fas fa-utensils fa-2x mb-2"></i>
                            <h5>Cardápio vazio</h5>
                            <p class="mb-0">Nenhum produto cadastrado no cardápio.</p>
                        </div>
                    </div>`;
            } else {
                products.forEach(product => {
                    html += `
                        <div class="col-lg-6 mb-3">
                            <div class="card product-card cursor-pointer h-100" data-id="${product.id}" data-price="${product.price}">
                                <div class="card-body">
                                    <h6 class="card-title text-primary">${product.name}</h6>
                                    <p class="card-text small text-muted">${product.description}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')}</span>
                                        <span class="badge bg-light text-dark">Adicionar</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            menuItemsList.innerHTML = html;
        }

        // --- 4. Adicionar Item ao Pedido (Ação add_item) ---
        menuItemsList.addEventListener('click', function(event) {
            const productCard = event.target.closest('.product-card');
            if (!productCard) return;

            const tableId = orderModal.getAttribute('data-table-id');
            const productId = productCard.getAttribute('data-id');
            const productName = productCard.querySelector('.card-title').textContent;
            
            if (!tableId || !productId) {
                showToast("Não foi possível identificar a mesa ou o produto.", 'error');
                return;
            }

            // Efeito visual simples
            productCard.style.transform = 'scale(0.98)';
            setTimeout(() => {
                productCard.style.transform = '';
            }, 200);

            // Requisição AJAX para adicionar o item
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add_item', table_id: tableId, product_id: productId, quantity: 1 })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro de rede ou servidor.');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(`"${productName}" adicionado à Mesa ${modalTableNumber.textContent}.`, 'success');
                    loadOrderDetails(tableId);
                    loadTables();
                } else {
                    showToast(`Erro ao adicionar item: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                showToast('Ocorreu um erro ao adicionar o item.', 'error');
            });
        });
        
        // --- 5. Carregar Detalhes da Comanda (Ação get_order_details) ---
        function loadOrderDetails(tableId) {
            orderDetailsContent.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin text-primary mb-2"></i>
                    <p class="text-muted">Carregando comanda...</p>
                </div>`;
            
            fetch(`api.php?action=get_order_details&table_id=${tableId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.data.order_id) {
                            orderModal.setAttribute('data-order-id', data.data.order_id);
                        } else {
                            orderModal.removeAttribute('data-order-id');
                        }
                        renderOrderDetails(data.data.items, data.data.total);
                    } else {
                        orderDetailsContent.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>${data.message}
                            </div>`;
                    }
                })
                .catch(error => {
                    orderDetailsContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Falha ao carregar os detalhes do pedido.
                        </div>`;
                });
        }
        
        // Renderiza detalhes da comanda
        function renderOrderDetails(items, total) {
            if (items.length === 0) {
                orderDetailsContent.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                        <p class="mb-0">Nenhum pedido em andamento para esta mesa.</p>
                    </div>`;
                return;
            }

            const subtotal = parseFloat(total);
            const serviceTax = subtotal * 0.10;
            const totalWithTax = subtotal + serviceTax;
            
            let itemsHtml = '';
            
            // Renderiza cada item do pedido
            items.forEach(item => {
                const itemTotal = (parseFloat(item.quantity) * parseFloat(item.price)).toFixed(2).replace('.', ',');
                
                itemsHtml += `
                    <div class="card mb-2">
                        <div class="card-body py-2 ${window.innerWidth < 768 ? 'order-item-mobile' : ''}">
                            <div class="d-flex justify-content-between align-items-center ${window.innerWidth < 768 ? 'item-controls-mobile' : ''}">
                                <div class="quantity-controls">
                                    <button class="btn btn-sm btn-outline-danger update-item-btn" data-id="${item.id}" data-change="-1">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="badge bg-primary mx-2">${item.quantity}</span>
                                    <button class="btn btn-sm btn-outline-success update-item-btn" data-id="${item.id}" data-change="1">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <span class="fw-medium">${item.name}</span>
                                <span class="fw-bold">R$ ${itemTotal}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            itemsHtml += `
                <div class="card order-summary-card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>R$ ${subtotal.toFixed(2).replace('.', ',')}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxa de Serviço (10%):</span>
                            <span>R$ ${serviceTax.toFixed(2).replace('.', ',')}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 text-primary">
                            <span>Total a Pagar:</span>
                            <span>R$ ${totalWithTax.toFixed(2).replace('.', ',')}</span>
                        </div>
                        
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="paid10Percent" checked>
                                <label class="form-check-label" for="paid10Percent">
                                    Taxa de serviço incluída
                                </label>
                            </div>
                        </div>

                        <button class="btn btn-success w-100 mt-3" id="finalizeOrderBtn">
                            <i class="fas fa-money-bill-wave me-2"></i> Fechar Comanda
                        </button>
                    </div>
                </div>
            `;
            
            orderDetailsContent.innerHTML = itemsHtml;
        }

        // --- 6. Event Listeners para Ações na Comanda ---
        orderDetailsContent.addEventListener('click', function(event) {
            
            // Listener para Incremento/Decremento
            const updateButton = event.target.closest('.update-item-btn');
            if (updateButton) {
                const tableId = orderModal.getAttribute('data-table-id');
                const itemId = updateButton.getAttribute('data-id');
                const change = parseInt(updateButton.getAttribute('data-change'));

                fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_item_quantity', order_item_id: itemId, change: change })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'info'); 
                        loadOrderDetails(tableId);
                    } else {
                        showToast(`Erro ao atualizar quantidade: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showToast('Erro de comunicação ao atualizar item.', 'error');
                });
                return;
            }

            // Listener para Finalizar Pedido
            if (event.target.id === 'finalizeOrderBtn' || event.target.closest('#finalizeOrderBtn')) {
                const tableId = orderModal.getAttribute('data-table-id');
                const orderId = orderModal.getAttribute('data-order-id'); 
                const paid10Percent = document.getElementById('paid10Percent') ? document.getElementById('paid10Percent').checked : true;

                if (!orderId) {
                    showToast("Nenhum pedido ativo encontrado para fechar.", 'error');
                    return;
                }

                if (!confirm("Confirmar fechamento da comanda e liberação da Mesa " + modalTableNumber.textContent + "?")) {
                    return;
                }

                fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'finalize_order', order_id: orderId, table_id: tableId, paid_10_percent: paid10Percent })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        const modalInstance = bootstrap.Modal.getInstance(orderModal);
                        modalInstance.hide();
                        loadTables(); 
                    } else {
                        showToast(`Erro ao fechar comanda: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showToast('Ocorreu um erro na comunicação ao fechar o pedido.', 'error');
                });
            }
        });

        // Inicializa o carregamento das mesas
        loadTables();
    });
</script>

<?php
require_once '../app/includes/footer.php';
?>