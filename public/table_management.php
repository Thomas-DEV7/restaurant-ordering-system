<?php
// public/table_management.php - Gerenciamento de Mesas (CRUD para Admin, View para Atendente)
$pageTitle = "Gerenciamento de Mesas";
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

$currentPage = 'table_management.php';
$userRole = $_SESSION['user_role'] ?? 'guest';

// Permite Admin e Atendente acessar
if ($userRole !== 'admin' && $userRole !== 'atendente') {
    header('Location: dashboard.php');
    exit();
}

$isAdmin = ($userRole === 'admin'); 
?>

<div class="content-wrapper">
    <h1 class="page-title"><i class="fas fa-table me-2"></i> Gerenciamento de Mesas</h1>
    <p class="page-subtitle">Controle de status e manutenção (Ações visíveis apenas para Administrador).</p>

    <?php if ($isAdmin): ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" id="create-table-btn" data-bs-toggle="modal" data-bs-target="#tableModal">
            <i class="fas fa-plus me-1"></i> Criar Nova Mesa
        </button>
    </div>
    <?php endif; ?>

    <div class="card border-0 mt-4 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-white table-borderless table-hover align-middle">
                    <thead>
                        <tr class="table-secondary">
                            <th>Mesa</th>
                            <th>Status</th>
                            <?php if ($isAdmin): ?>
                                <th>Ações</th> 
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="tables-table-body">
                        </tbody>
                </table>
            </div>
            <div id="loading-message" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="mt-2">Carregando status das mesas...</p>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="tableModal" tabindex="-1" aria-labelledby="tableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="tableModalLabel">Criar Nova Mesa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="table-form">
                    <input type="hidden" id="table-id">
                    <div class="mb-3">
                        <label for="table-number-input" class="form-label">Número ou Nome da Mesa</label>
                        <input type="text" class="form-control" id="table-number-input" required placeholder="Ex: Balcão, 15A, Cliente VIP">
                    </div>
                    <div id="modal-alert" class="alert d-none" role="alert"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="save-table-btn">Salvar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.getElementById('tables-table-body');
        const loadingMessage = document.getElementById('loading-message');
        
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>; 

        let tableModal;
        if (isAdmin) {
            tableModal = new bootstrap.Modal(document.getElementById('tableModal'));
            document.getElementById('save-table-btn').addEventListener('click', handleTableSave);
            document.getElementById('create-table-btn').addEventListener('click', () => {
                document.getElementById('tableModalLabel').textContent = 'Criar Nova Mesa';
                document.getElementById('table-id').value = '';
                document.getElementById('table-number-input').value = '';
                document.getElementById('modal-alert').classList.add('d-none');
            });
        }
        
        function getStatusBadge(status) {
            let color = '';
            let text = '';
            switch (status) {
                case 'available':
                    color = 'bg-success';
                    text = 'Disponível';
                    break;
                case 'occupied':
                    color = 'bg-danger';
                    text = 'OCUPADA';
                    break;
                default:
                    color = 'bg-secondary';
                    text = 'Erro';
            }
            // Badge minimalista com padding
            return `<span class="badge ${color} py-2 px-3 fw-normal">${text}</span>`;
        }

        // --- FUNÇÕES CRUD E AÇÕES (MESMAS DA VERSÃO ANTERIOR) ---

        function handleTableSave(e) {
            e.preventDefault();

            const tableId = document.getElementById('table-id').value;
            const tableNumber = document.getElementById('table-number-input').value;
            const action = tableId ? 'update_table' : 'create_table';
            const saveTableBtn = document.getElementById('save-table-btn');
            const modalAlert = document.getElementById('modal-alert');
            
            const bodyData = { table_number: tableNumber, table_id: tableId };

            modalAlert.classList.add('d-none');
            saveTableBtn.disabled = true;

            fetch('table_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.assign(bodyData, { action: action }))
            })
            .then(response => response.json())
            .then(data => {
                saveTableBtn.disabled = false;
                if (data.success) {
                    modalAlert.textContent = data.message;
                    modalAlert.classList.remove('alert-danger');
                    modalAlert.classList.add('alert-success');
                    modalAlert.classList.remove('d-none');

                    setTimeout(() => {
                        tableModal.hide();
                        loadTablesManagement(); 
                    }, 1000);

                } else {
                    modalAlert.textContent = data.message;
                    modalAlert.classList.add('alert-danger');
                    modalAlert.classList.remove('d-none', 'alert-success');
                }
            })
            .catch(error => {
                saveTableBtn.disabled = false;
                modalAlert.textContent = 'Falha na comunicação com o servidor.';
                modalAlert.classList.add('alert-danger');
                modalAlert.classList.remove('d-none', 'alert-success');
                console.error('Erro CRUD:', error);
            });
        }
        
        function handleTableDelete(tableId, tableNumber) {
            if (!confirm(`Tem certeza que deseja DELETAR a Mesa ${tableNumber} permanentemente?`)) return;

            fetch('table_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_table', table_id: tableId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadTablesManagement(); 
                } else {
                    alert(`Erro ao deletar: ${data.message}`);
                }
            })
            .catch(error => {
                alert('Falha na comunicação com o servidor.');
            });
        }
        
        // Função para Mudar Status
        function handleStatusChange(tableId, currentStatus) {
            const newStatus = currentStatus === 'available' ? 'occupied' : 'available';
            const confirmMsg = `Deseja mudar o status da Mesa ${tableId} para "${newStatus.toUpperCase()}"?`;
            
            if (!confirm(confirmMsg)) return;

            fetch('table_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_table_status', table_id: tableId, new_status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadTablesManagement(); 
                } else {
                    alert(`Erro ao mudar status: ${data.message}`);
                }
            })
            .catch(error => {
                alert('Falha na comunicação com o servidor.');
            });
        }


        // --- CARREGAMENTO E RENDERIZAÇÃO ---
        function loadTablesManagement() {
            loadingMessage.classList.remove('d-none');
            tableBody.innerHTML = '';

            fetch('table_api.php?action=get_all_tables_with_status')
                .then(response => response.json())
                .then(data => {
                    loadingMessage.classList.add('d-none');
                    if (data.success && data.data.length > 0) {
                        renderTables(data.data);
                    } else {
                        const colspan = isAdmin ? 3 : 2; 
                        tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center text-muted">Nenhuma mesa cadastrada.</td></tr>`;
                    }
                })
                .catch(error => {
                    const colspan = isAdmin ? 3 : 2;
                    loadingMessage.classList.add('d-none');
                    tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-danger text-center">Erro ao carregar dados.</td></tr>`;
                });
        }

        function renderTables(tables) {
            let html = '';
            tables.forEach(table => {
                const isOccupied = table.status === 'occupied';
                
                let actionsColumn = '';

                if (isAdmin) {
                    // Botões de Ações CRUD e Liberação Forçada
                    actionsColumn = `
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Ações
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button class="dropdown-item edit-btn" data-id="${table.table_id}" data-table-number="${table.table_number}"><i class="fas fa-edit me-2 text-info"></i> Editar/Renomear</button></li>
                                    <li><button class="dropdown-item status-btn" data-id="${table.table_id}" data-status="${table.status}"><i class="fas fa-sync-alt me-2 text-secondary"></i> Mudar Status para ${isOccupied ? 'Disponível' : 'Ocupada'}</button></li>
                                    <li><hr class="dropdown-divider"></li>
                                    ${isOccupied ? `
                                        <li><button class="dropdown-item release-btn text-danger" data-id="${table.table_id}" data-table-number="${table.table_number}"><i class="fas fa-exclamation-triangle me-2"></i> Liberar Forçado</button></li>
                                    ` : `
                                        <li><button class="dropdown-item delete-btn text-danger" data-id="${table.table_id}" data-table-number="${table.table_number}"><i class="fas fa-trash me-2"></i> Deletar Mesa</button></li>
                                    `}
                                </ul>
                            </div>
                        </td>
                    `;
                }

                html += `
                    <tr>
                        <td class="fw-bold">Mesa ${table.table_number}</td>
                        <td>${getStatusBadge(table.status)}</td>
                        ${actionsColumn}
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        }

        // --- LISTENERS DE AÇÕES (Delegation) ---
        tableBody.addEventListener('click', function(event) {
            const target = event.target.closest('button');
            if (!target) return;
            
            const tableId = target.getAttribute('data-id');
            const tableNumber = target.getAttribute('data-table-number');
            const currentStatus = target.getAttribute('data-status');


            // 1. Liberação Forçada
            if (target.classList.contains('release-btn')) {
                if (confirm(`ATENÇÃO! Confirma liberação forçada da Mesa ${tableNumber}? O pedido será CANCELADO.`)) {
                    fetch('table_api.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'force_release_table', table_id: tableId })
                    })
                    .then(response => response.json())
                    .then(data => { alert(data.message); loadTablesManagement(); });
                }
                return;
            }
            
            // 2. Mudar Status Manual
            if (target.classList.contains('status-btn')) {
                handleStatusChange(tableId, currentStatus);
                return;
            }

            // 3. Edição (Abre o Modal)
            if (target.classList.contains('edit-btn')) {
                document.getElementById('tableModalLabel').textContent = `Editar Mesa ${tableNumber}`;
                document.getElementById('table-id').value = tableId;
                document.getElementById('table-number-input').value = tableNumber;
                document.getElementById('modal-alert').classList.add('d-none');
                tableModal.show();
                return;
            }

            // 4. Deleção
            if (target.classList.contains('delete-btn')) {
                handleTableDelete(tableId, tableNumber);
                return;
            }
        });


        // Inicializa a tela e define a atualização periódica
        loadTablesManagement();
        setInterval(loadTablesManagement, 15000); 
    });
</script>

<?php
require_once '../app/includes/footer.php';
?>