<?php
// public/api.php
require_once '../app/config/database.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ação inválida.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Definir um waiter_id (atendente) para testes se o usuário não estiver logado
$waiter_id = $_SESSION['user_id'] ?? 1;

try {
    // É crucial ter acesso à conexão PDO, definida em database.php
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Falha na inicialização do PDO. Verifique database.php");
    }

    // --- Lógica para POST (Adicionar item, Finalizar Pedido) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        // --- Ação: add_item ---
        if ($action === 'add_item') {
            $table_id = $data['table_id'] ?? null;
            $product_id = $data['product_id'] ?? null;
            $quantity = $data['quantity'] ?? 1;

            if (!$table_id || !$product_id) {
                $response = ['success' => false, 'message' => 'Mesa ou produto não especificado.'];
                echo json_encode($response);
                exit;
            }

            $pdo->beginTransaction();

            try {
                // 1. Obter Preço do Produto
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = :product_id");
                $stmt->execute(['product_id' => $product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Produto não encontrado.");
                }
                $price = $product['price'];

                // 2. Encontrar Pedido Pendente (ou criar um novo)
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = :table_id AND status = 'pending'");
                $stmt->execute(['table_id' => $table_id]);
                $order_id = $stmt->fetchColumn();

                if (!$order_id) {
                    // Cria um novo pedido (RETURNING id é sintaxe PostgreSQL)
                    $stmt = $pdo->prepare("INSERT INTO orders (table_id, waiter_id) VALUES (:table_id, :waiter_id) RETURNING id");
                    $stmt->execute(['table_id' => $table_id, 'waiter_id' => $waiter_id]);
                    $order_id = $stmt->fetchColumn();

                    // Atualiza o status da mesa para 'occupied'
                    $stmt = $pdo->prepare("UPDATE tables SET status = 'occupied' WHERE id = :table_id");
                    $stmt->execute(['table_id' => $table_id]);
                }

                // 3. Adicionar Item na Tabela order_items e capturar o ID
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price) RETURNING id");
                $stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $price
                ]);

                $order_item_id = $stmt->fetchColumn();

                // 4. Adiciona o item à fila de impressão (kitchen_orders)
                $stmt = $pdo->prepare("INSERT INTO kitchen_orders (order_item_id, status) VALUES (:item_id, 'sent')");
                $stmt->execute(['item_id' => $order_item_id]);


                $pdo->commit();
                $response = ['success' => true, 'message' => 'Item adicionado e enviado para a cozinha!', 'order_id' => $order_id];
            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Erro ao adicionar item: ' . $e->getMessage()];
            }
        }

        // --- Ação: finalize_order ---
        if ($action === 'finalize_order') {
            $order_id = $data['order_id'] ?? null;
            $table_id = $data['table_id'] ?? null;
            $paid_10_percent = $data['paid_10_percent'] ?? false;

            if (!$order_id || !$table_id) {
                $response = ['success' => false, 'message' => 'IDs de pedido ou mesa ausentes.'];
                echo json_encode($response);
                exit;
            }

            $pdo->beginTransaction();

            try {
                // 1. Finaliza o Pedido (completa o status e registra o 10%)
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', paid_10_percent = :paid_10 WHERE id = :order_id");
                $stmt->execute([
                    'paid_10' => $paid_10_percent ? 'TRUE' : 'FALSE', // PostgreSQL usa TRUE/FALSE
                    'order_id' => $order_id
                ]);

                // 2. Libera a Mesa
                $stmt = $pdo->prepare("UPDATE tables SET status = 'available' WHERE id = :table_id");
                $stmt->execute(['table_id' => $table_id]);

                $pdo->commit();
                $response = ['success' => true, 'message' => 'Comanda fechada com sucesso! Mesa liberada.'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Erro ao finalizar comanda: ' . $e->getMessage()];
            }
        }
    }

    // --- Lógica para GET (Buscar Dados) ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // --- Ação: get_tables ---
        if ($action === 'get_tables') {
            $stmt = $pdo->prepare("SELECT id, table_number, status FROM tables ORDER BY id");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $tables];
        }

        // --- Ação: get_menu ---
        if ($action === 'get_menu') {
            $stmt = $pdo->prepare("SELECT id, name, price, description, category, image FROM products WHERE status = 'active' ORDER BY category, name");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $products];
        }

        // --- Ação: get_all_orders_summary ---
        if ($action === 'get_all_orders_summary') {
            // Busca todos os pedidos pendentes e os itens associados
            $stmt = $pdo->prepare("
                SELECT 
                    o.id AS order_id, 
                    t.table_number, 
                    u.name AS waiter_name,
                    o.order_time,
                    o.status,
                    SUM(oi.price * oi.quantity) AS total_value
                FROM orders o
                JOIN tables t ON o.table_id = t.id
                JOIN users u ON o.waiter_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.status = 'pending' OR o.status = 'completed' /* Inclui pedidos pendentes e fechados recentemente */
                GROUP BY o.id, t.table_number, u.name, o.order_time, o.status
                ORDER BY o.order_time DESC
            ");
            $stmt->execute();
            $orders_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'data' => $orders_summary];
        }

        // --- Ação: get_order_details ---
        if ($action === 'get_order_details') {
            $table_id = $_GET['table_id'] ?? null;

            if (!$table_id) {
                $response = ['success' => false, 'message' => 'Mesa não especificada.'];
                echo json_encode($response);
                exit;
            }

            // 1. Obter ID do pedido pendente
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = :table_id AND status = 'pending'");
            $stmt->execute(['table_id' => $table_id]);
            $order_id = $stmt->fetchColumn();

            $items = [];
            $total = 0;

            if ($order_id) {
                // 2. Obter todos os itens da comanda
                $stmt = $pdo->prepare("
                    SELECT 
                        oi.id, 
                        oi.quantity, 
                        oi.price, 
                        p.name, 
                        p.description
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = :order_id
                    ORDER BY oi.id ASC
                ");
                $stmt->execute(['order_id' => $order_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 3. Calcular o Total
                $total = array_reduce($items, function ($sum, $item) {
                    return $sum + (floatval($item['quantity']) * floatval($item['price']));
                }, 0);
            }

            $response = ['success' => true, 'data' => [
                'order_id' => $order_id,
                'items' => $items,
                'total' => number_format($total, 2, '.', '') // Formato padronizado
            ]];
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Erro de banco de dados: ' . $e->getMessage()];
} catch (Exception $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
}

echo json_encode($response);
exit;
