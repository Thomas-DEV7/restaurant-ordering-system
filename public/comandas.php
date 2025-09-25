<?php
// public/comandas.php
$pageTitle = "Comanda Digital";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Definir a página atual para o sidebar
$currentPage = 'comandas.php';
?>

<div class="content">
    <div class="container-fluid">
        <h1 class="mt-4">Comanda Digital</h1>
        <p>Gerencie os pedidos das mesas de forma eficiente.</p>

        <div id="tables-container" class="row">
            <div class="col-12 text-center my-5">
                <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
                <p class="mt-2">Carregando mesas...</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Comanda da Mesa <span id="modalTableNumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h4 class="mb-3">Cardápio</h4>
                        <div class="row" id="menu-items-list">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4 class="mb-3">Detalhes da Comanda</h4>
                        <div id="order-details-content">
                            <div class="alert alert-warning">Nenhum pedido em andamento para esta mesa.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

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
        const toastInstance = bootstrap.Toast.getOrCreateInstance(liveToast);

        // Função utilitária para exibir o Toast
        function showToast(message, type = 'success') {
            const toastTitle = document.getElementById('toastTitle');
            const toastBody = document.getElementById('toastBody');
            const toastIcon = document.getElementById('toastIcon');

            // Limpa classes de cor
            liveToast.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-info');
            toastIcon.className = 'me-2'; // Reset icon class

            if (type === 'success') {
                liveToast.classList.add('text-bg-success');
                toastTitle.textContent = 'Sucesso!';
                toastIcon.classList.add('fas', 'fa-check-circle');
            } else if (type === 'error') {
                liveToast.classList.add('text-bg-danger');
                toastTitle.textContent = 'Erro!';
                toastIcon.classList.add('fas', 'fa-times-circle');
            } else {
                liveToast.classList.add('text-bg-info');
                toastTitle.textContent = 'Atenção!';
                toastIcon.classList.add('fas', 'fa-info-circle');
            }

            toastBody.textContent = message;
            toastInstance.show();
        }

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
                        tablesContainer.innerHTML = `<div class="alert alert-danger">Erro API: ${data.message}</div>`;
                        showToast(`Falha ao carregar mesas: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    tablesContainer.innerHTML = `<div class="col-12 alert alert-danger">Erro ao carregar mesas. Verifique o console.</div>`;
                    console.error('Erro ao carregar mesas:', error);
                    showToast('Ocorreu um erro de comunicação ao carregar mesas.', 'error');
                });
        }

        function renderTables(tables) {
            let html = '';
            if (tables.length === 0) {
                html = '<div class="col-12 alert alert-info">Nenhuma mesa cadastrada.</div>';
            } else {
                tables.forEach(table => {
                    const statusClass = table.status === 'occupied' ? 'bg-danger' : 'bg-success';
                    const statusText = table.status === 'occupied' ? 'Ocupada' : 'Disponível';

                    html += `
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                            <div class="card table-card text-center cursor-pointer" 
                                data-bs-toggle="modal" 
                                data-bs-target="#orderModal"
                                data-table-id="${table.id}" 
                                data-table-number="${table.table_number}">
                                <div class="card-body">
                                    <i class="fas fa-utensils fa-3x mb-3"></i>
                                    <h4 class="card-title">Mesa ${table.table_number}</h4>
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
            fetch('api.php?action=get_menu')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderMenu(data.data);
                    } else {
                        menuItemsList.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    menuItemsList.innerHTML = `<div class="alert alert-danger">Erro ao carregar cardápio.</div>`;
                });
        }

        function renderMenu(products) {
            let html = '';
            products.forEach(product => {
                html += `
                    <div class="col-sm-6 mb-3">
                        <div class="card h-100 product-card cursor-pointer" data-id="${product.id}" data-price="${product.price}">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <h6 class="card-title">${product.name}</h6>
                                <p class="card-text text-muted mb-1">${product.description}</p>
                                <span class="fw-bold text-primary mt-auto">R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
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

            // Requisição AJAX para adicionar o item
            fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add_item',
                        table_id: tableId,
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro de rede ou servidor.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast(`1x ${productName} adicionado à Mesa ${modalTableNumber.textContent}.`, 'success');
                        loadOrderDetails(tableId);
                        loadTables(); // Atualiza o status da mesa
                    } else {
                        showToast(`Erro ao adicionar item: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showToast('Ocorreu um erro ao adicionar o item.', 'error');
                });
        });

        // --- 5. Carregar Detalhes da Comanda (Ação get_order_details) ---
        function loadOrderDetails(tableId) {
            orderDetailsContent.innerHTML = '<div class="text-center my-3"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

            fetch(`api.php?action=get_order_details&table_id=${tableId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Salva o order_id no modal para o botão de finalizar
                        if (data.data.order_id) {
                            orderModal.setAttribute('data-order-id', data.data.order_id);
                        } else {
                            orderModal.removeAttribute('data-order-id');
                        }
                        renderOrderDetails(data.data.items, data.data.total);
                    } else {
                        orderDetailsContent.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        showToast(`Erro ao carregar pedido: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar detalhes do pedido:', error);
                    orderDetailsContent.innerHTML = `<div class="alert alert-danger">Falha ao carregar os detalhes do pedido.</div>`;
                    showToast('Falha na comunicação ao carregar detalhes do pedido.', 'error');
                });
        }

        function renderOrderDetails(items, total) {
            if (items.length === 0) {
                orderDetailsContent.innerHTML = '<div class="alert alert-warning">Nenhum pedido em andamento para esta mesa.</div>';
                return;
            }

            const subtotal = parseFloat(total);
            const serviceTax = subtotal * 0.10;
            const totalWithTax = subtotal + serviceTax;

            let itemsHtml = `
                <ul class="list-group mb-3">
            `;

            // Renderiza cada item do pedido
            items.forEach(item => {
                const itemTotal = (parseFloat(item.quantity) * parseFloat(item.price)).toFixed(2).replace('.', ',');

                itemsHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2">${item.quantity}x</span>
                            ${item.name}
                        </div>
                        <span class="fw-bold">R$ ${itemTotal}</span>
                    </li>
                `;
            });

            itemsHtml += `
                </ul>
                
                <div class="card p-3 shadow-sm">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>R$ ${subtotal.toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Taxa de Serviço (10%):</span>
                        <span>R$ ${serviceTax.toFixed(2).replace('.', ',')}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5 text-primary">
                        <span>Total a Pagar:</span>
                        <span>R$ ${totalWithTax.toFixed(2).replace('.', ',')}</span>
                    </div>
                    
                    <div class="mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="paid10Percent" checked>
                            <label class="form-check-label" for="paid10Percent">
                                O cliente pagou a taxa de serviço (10%)?
                            </label>
                        </div>
                    </div>

                    <button class="btn btn-success w-100 mt-3" id="finalizeOrderBtn">
                        <i class="fas fa-money-bill-wave me-2"></i> Fechar Comanda
                    </button>
                </div>
            `;

            orderDetailsContent.innerHTML = itemsHtml;
        }

        // --- 6. Event Listener para Fechar Comanda (Finalizar Pedido) ---
        orderDetailsContent.addEventListener('click', function(event) {
            if (event.target.id === 'finalizeOrderBtn') {
                const tableId = orderModal.getAttribute('data-table-id');
                const orderId = orderModal.getAttribute('data-order-id');
                const paid10Percent = document.getElementById('paid10Percent').checked;

                if (!orderId) {
                    showToast("Nenhum pedido ativo encontrado para fechar.", 'error');
                    return;
                }

                if (!confirm("Confirmar fechamento da comanda e liberação da Mesa " + modalTableNumber.textContent + "?")) {
                    return;
                }

                fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'finalize_order',
                            order_id: orderId,
                            table_id: tableId,
                            paid_10_percent: paid10Percent
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            // Fecha o modal e recarrega a lista de mesas
                            const modalInstance = bootstrap.Modal.getInstance(orderModal);
                            modalInstance.hide();
                            loadTables();
                        } else {
                            showToast(`Erro ao fechar comanda: ${data.message}`, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        showToast('Ocorreu um erro na comunicação com o servidor ao fechar o pedido.', 'error');
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