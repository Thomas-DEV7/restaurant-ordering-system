<?php
// public/api.php - Backend central de todas as ações da comanda
require_once '../app/config/database.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ação inválida.'];
$action = $_GET['action'] ?? '';
$waiter_id = $_SESSION['user_id'] ?? 1;

try {
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception("Falha na inicialização do PDO. Verifique database.php");
    }

    // --- Lógica para POST (Adicionar item, Finalizar Pedido, Atualizar Quantidade) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // LÊ O CORPO JSON DA REQUISIÇÃO E DEFINE A AÇÃO PARA POST
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
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = :product_id");
                $stmt->execute(['product_id' => $product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Produto não encontrado.");
                }
                $price = $product['price'];

                $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = :table_id AND status = 'pending'");
                $stmt->execute(['table_id' => $table_id]);
                $order_id = $stmt->fetchColumn();

                if (!$order_id) {
                    $stmt = $pdo->prepare("INSERT INTO orders (table_id, waiter_id) VALUES (:table_id, :waiter_id) RETURNING id");
                    $stmt->execute(['table_id' => $table_id, 'waiter_id' => $waiter_id]);
                    $order_id = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("UPDATE tables SET status = 'occupied' WHERE id = :table_id");
                    $stmt->execute(['table_id' => $table_id]);
                }

                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price) RETURNING id");
                $stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'price' => $price
                ]);

                $order_item_id = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO kitchen_orders (order_item_id, status) VALUES (:item_id, 'sent')");
                $stmt->execute(['item_id' => $order_item_id]);


                $pdo->commit();
                $response = ['success' => true, 'message' => 'Item adicionado e enviado para a cozinha!', 'order_id' => $order_id];
            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Erro ao adicionar item: ' . $e->getMessage()];
            }
        } // Fim da ação add_item

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
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', paid_10_percent = :paid_10 WHERE id = :order_id");
                $stmt->execute([
                    'paid_10' => $paid_10_percent ? 'TRUE' : 'FALSE',
                    'order_id' => $order_id
                ]);

                $stmt = $pdo->prepare("UPDATE tables SET status = 'available' WHERE id = :table_id");
                $stmt->execute(['table_id' => $table_id]);

                $pdo->commit();
                $response = ['success' => true, 'message' => 'Comanda fechada com sucesso! Mesa liberada.'];
            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Erro ao finalizar comanda: ' . $e->getMessage()];
            }
        } // Fim da ação finalize_order

        // --- Ação: update_item_quantity ---
        if ($action === 'update_item_quantity') {
            $order_item_id = $data['order_item_id'] ?? null;
            $change = $data['change'] ?? 0; // Pode ser +1 ou -1

            if (!$order_item_id || $change === 0) {
                $response = ['success' => false, 'message' => 'ID do item ou mudança de quantidade inválida.'];
                echo json_encode($response);
                exit;
            }

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("SELECT quantity FROM order_items WHERE id = :id");
                $stmt->execute(['id' => $order_item_id]);
                $current_quantity = $stmt->fetchColumn();

                if ($current_quantity === false) {
                    throw new Exception("Item de pedido não encontrado.");
                }

                $new_quantity = $current_quantity + $change;

                if ($new_quantity <= 0) {
                    // Remove o item se a nova quantidade for zero ou negativa
                    $stmt = $pdo->prepare("DELETE FROM order_items WHERE id = :id");
                    $stmt->execute(['id' => $order_item_id]);
                    $response = ['success' => true, 'message' => 'Item removido.'];
                } else {
                    // Atualiza a quantidade
                    $stmt = $pdo->prepare("UPDATE order_items SET quantity = :new_quantity WHERE id = :id");
                    $stmt->execute(['new_quantity' => $new_quantity, 'id' => $order_item_id]);
                    $response = ['success' => true, 'message' => 'Quantidade atualizada.'];
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Erro ao atualizar item: ' . $e->getMessage()];
            }
        } // Fim da ação update_item_quantity

    } // Fim do bloco POST

    // --- Lógica para GET (Buscar Dados) ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        // Ação: get_tables
        if ($action === 'get_tables') {
            $stmt = $pdo->prepare("SELECT id, table_number, status FROM tables ORDER BY id");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $tables];
        }

        // Ação: get_menu
        if ($action === 'get_menu') {
            $stmt = $pdo->prepare("SELECT id, name, price, description, category, image FROM products WHERE status = 'active' ORDER BY category, name");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $products];
        }

        // Ação: get_order_details
        if ($action === 'get_order_details') {
            $table_id = $_GET['table_id'] ?? null;

            if (!$table_id) {
                $response = ['success' => false, 'message' => 'Mesa não especificada.'];
                echo json_encode($response);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM orders WHERE table_id = :table_id AND status = 'pending'");
            $stmt->execute(['table_id' => $table_id]);
            $order_id = $stmt->fetchColumn();

            $items = [];
            $total = 0;

            if ($order_id) {
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

                $total = array_reduce($items, function ($sum, $item) {
                    return $sum + (floatval($item['quantity']) * floatval($item['price']));
                }, 0);
            }

            $response = ['success' => true, 'data' => [
                'order_id' => $order_id,
                'items' => $items,
                'total' => number_format($total, 2, '.', '')
            ]];
        }

        // Ação: get_all_orders_summary
        if ($action === 'get_all_orders_summary') {
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
                WHERE o.status = 'pending' OR o.status = 'completed'
                GROUP BY o.id, t.table_number, u.name, o.order_time, o.status
                ORDER BY o.order_time DESC
            ");
            $stmt->execute();
            $orders_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'data' => $orders_summary];
        }
        // --- Ação: get_daily_summary ---
        if ($action === 'get_daily_summary') {

            $sql = "
                WITH CompletedOrders AS (
                    SELECT 
                        o.id AS order_id,
                        o.paid_10_percent,
                        SUM(oi.price * oi.quantity) AS subtotal
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.status = 'completed'
                    AND DATE_TRUNC('day', o.order_time) = DATE_TRUNC('day', NOW())
                    GROUP BY o.id, o.paid_10_percent
                )
                SELECT
                    COALESCE(COUNT(order_id), 0) AS total_orders_closed,
                    COALESCE(SUM(subtotal), 0.00) AS total_subtotal,
                    COALESCE(SUM(subtotal * 0.10), 0.00) AS total_10_percent_due,
                    COALESCE(SUM(CASE WHEN paid_10_percent = TRUE THEN subtotal * 0.10 ELSE 0 END), 0.00) AS total_10_percent_received,
                    COALESCE(SUM(subtotal * 1.10), 0.00) AS total_billed_amount
                FROM CompletedOrders
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'data' => $summary];
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
