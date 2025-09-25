<?php
// public/comandas.php
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Verifica se o usuário é um atendente ou admin
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'atendente') {
    header('Location: dashboard.php');
    exit();
}

// Busca todas as mesas do banco de dados
$stmt = $pdo->prepare("SELECT * FROM tables ORDER BY table_number ASC");
$stmt->execute();
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="content-wrapper">
    <h1 class="page-title">Comanda Digital</h1>
    <p class="page-subtitle">Gerencie os pedidos das mesas de forma eficiente.</p>

    <div class="row g-4 mt-4">
        <?php if (count($tables) > 0): ?>
            <?php foreach ($tables as $table): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 p-3 text-center table-card cursor-pointer"
                        data-bs-toggle="modal"
                        data-bs-target="#orderModal"
                        data-table-id="<?php echo htmlspecialchars($table['id']); ?>"
                        data-table-number="<?php echo htmlspecialchars($table['table_number']); ?>">
                        <div class="card-body">
                            <i class="fas fa-utensils fa-2x mb-3 text-secondary"></i>
                            <h5 class="card-title mb-0">
                                Mesa <?php echo htmlspecialchars($table['table_number']); ?>
                            </h5>
                            <?php
                            $status = $table['status'];
                            $badgeClass = ($status == 'occupied') ? 'bg-danger' : 'bg-success';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?> mt-2"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">Nenhuma mesa cadastrada.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="orderModalLabel">Comanda da Mesa <span id="modalTableNumber"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h4 class="mb-3">Cardápio</h4>
                        <div id="menu-items-list" class="row">
                            <p class="text-center text-muted">Carregando cardápio...</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h4 class="mb-3">Detalhes da Comanda</h4>
                        <div id="order-details-content">
                            <p class="text-center text-muted">Selecione um item do cardápio para adicionar.</p>
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

<?php require_once '../app/includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderModal = document.getElementById('orderModal');
        const modalTitle = document.getElementById('modalTableNumber');

        orderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const tableNumber = button.getAttribute('data-table-number');
            modalTitle.textContent = tableNumber;

            // Lógica AJAX para carregar a comanda e o cardápio virá aqui
        });
    });
</script>