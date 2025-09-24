<?php
// Inclui os arquivos de configuração e layout
require_once '../app/config/database.php';
require_once '../app/includes/header.php';
require_once '../app/includes/sidebar.php';

// Variável para armazenar mensagens de sucesso ou erro
$message = '';
$messageType = '';

// Lógica para Adicionar, Editar ou Excluir Produto (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Lógica de Adicionar/Editar Produto ---
    if (isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'edit')) {
        $id = $_POST['product_id'] ?? null;
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category = $_POST['category'];
        $current_image = $_POST['current_image'] ?? null;
        
        $image_path = $current_image; // Mantém a imagem atual por padrão

        // Processa o upload da imagem
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            
            // Cria o diretório de uploads se não existir
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Gera um nome de arquivo único para evitar conflitos
            $image_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('img_') . '.' . $image_ext;
            $image_path_new = $upload_dir . $image_name;

            // Move o arquivo temporário para o destino
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path_new)) {
                // Se a imagem antiga existir e não for a padrão, a deleta
                if ($current_image && file_exists($current_image) && !str_contains($current_image, 'default')) {
                    unlink($current_image);
                }
                $image_path = $image_path_new;
            } else {
                $message = "Erro ao mover a imagem para o servidor.";
                $messageType = 'danger';
            }
        }
        
        // Se não houve erro no upload, executa a query
        if (!$message) {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $description, $price, $category, $image_path])) {
                    $message = "Produto adicionado com sucesso!";
                    $messageType = 'success';
                } else {
                    $message = "Erro ao adicionar produto.";
                    $messageType = 'danger';
                }
            } elseif ($_POST['action'] === 'edit') {
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, image = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $price, $category, $image_path, $id])) {
                    $message = "Produto atualizado com sucesso!";
                    $messageType = 'success';
                } else {
                    $message = "Erro ao atualizar produto.";
                    $messageType = 'danger';
                }
            }
        }
    }

    // --- Lógica de Excluir Produto ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['product_id'];
        
        // Pega o caminho da imagem para deletá-la
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product && $product['image'] && file_exists($product['image'])) {
            unlink($product['image']);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Produto excluído com sucesso!";
            $messageType = 'success';
        } else {
            $message = "Erro ao excluir produto.";
            $messageType = 'danger';
        }
    }

    // Redireciona para evitar reenvio do formulário
    header('Location: menu.php?message=' . urlencode($message) . '&type=' . urlencode($messageType));
    exit();
}

// Lógica para Ler (Listar) todos os produtos
$stmt = $pdo->query("SELECT * FROM products ORDER BY category, name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Exibe a mensagem de feedback se houver
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
    $messageType = htmlspecialchars($_GET['type'] ?? 'info');
}
?>

<div class="content-wrapper">
    <h1 class="page-title">Gerenciar Cardápio</h1>
    <p class="page-subtitle">Adicione, edite e organize os itens do seu restaurante.</p>

    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="fas fa-plus me-2"></i>Adicionar Produto
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Imagem</th>
                        <th scope="col">Produto</th>
                        <th scope="col">Descrição</th>
                        <th scope="col">Preço</th>
                        <th scope="col">Categoria</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/60?text=Sem+Foto'); ?>" alt="Imagem do Produto" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                                </td>
                                <td>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                </td>
                                <td><?php echo htmlspecialchars($product['description']); ?></td>
                                <td>R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($product['category'])); ?></span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-info me-2 edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#productModal"
                                        data-id="<?php echo $product['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-desc="<?php echo htmlspecialchars($product['description']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                        data-category="<?php echo htmlspecialchars($product['category']); ?>"
                                        data-image="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="menu.php" method="POST" class="d-inline-block" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhum produto cadastrado no cardápio.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="productForm" method="POST" action="menu.php" enctype="multipart/form-data">
                <input type="hidden" name="action" id="action" value="add">
                <input type="hidden" name="product_id" id="product_id">
                <input type="hidden" name="current_image" id="current_image">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="productModalLabel">Adicionar Produto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Produto</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Preço</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoria</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="prato">Prato</option>
                            <option value="bebida">Bebida</option>
                            <option value="diversos">Diversos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagem do Produto</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <small class="form-text text-muted">Apenas arquivos JPG ou PNG são aceitos.</small>
                        <div id="image-preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productModal = document.getElementById('productModal');
        const form = document.getElementById('productForm');
        const modalTitle = document.getElementById('productModalLabel');
        const actionInput = document.getElementById('action');
        const productIdInput = document.getElementById('product_id');
        const nameInput = document.getElementById('name');
        const descInput = document.getElementById('description');
        const priceInput = document.getElementById('price');
        const categoryInput = document.getElementById('category');
        const currentImageInput = document.getElementById('current_image');
        const imagePreview = document.getElementById('image-preview');
        const imageInput = document.getElementById('image');
        const modalSubmitBtn = document.getElementById('modalSubmitBtn');

        // Lógica para preencher o modal ao clicar no botão de edição
        productModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const isEdit = button.classList.contains('edit-btn');
            
            // Limpa o formulário e a pré-visualização ao abrir o modal
            form.reset();
            imagePreview.innerHTML = '';
            
            if (isEdit) {
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const desc = button.getAttribute('data-desc');
                const price = button.getAttribute('data-price');
                const category = button.getAttribute('data-category');
                const image = button.getAttribute('data-image');

                modalTitle.textContent = 'Editar Produto';
                modalSubmitBtn.textContent = 'Atualizar';
                actionInput.value = 'edit';
                productIdInput.value = id;
                nameInput.value = name;
                descInput.value = desc;
                priceInput.value = price;
                categoryInput.value = category;
                currentImageInput.value = image; // Salva o caminho da imagem atual

                if (image) {
                    imagePreview.innerHTML = `<img src="${image}" class="img-fluid rounded" style="max-height: 150px;">`;
                }
            } else {
                modalTitle.textContent = 'Adicionar Produto';
                modalSubmitBtn.textContent = 'Salvar';
                actionInput.value = 'add';
                productIdInput.value = '';
                currentImageInput.value = '';
            }
        });

        // Pré-visualização da nova imagem
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 150px;">`;
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.innerHTML = '';
            }
        });
    });
</script>

<?php require_once '../app/includes/footer.php'; ?>