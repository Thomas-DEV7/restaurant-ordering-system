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


        // --- Ação: get_daily_summary (RELATÓRIO FINANCEIRO DIÁRIO) ---
        if ($action === 'get_daily_summary') {
            try {
                // Data de hoje
                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd = date('Y-m-d 23:59:59');

                // --- A) TOTAL DE PEDIDOS FECHADOS HOJE ---
                $sql_orders = "
            SELECT COUNT(id) as total_orders_closed
            FROM orders 
            WHERE status = 'completed'
            AND order_time BETWEEN :today_start AND :today_end
        ";
                $stmt_orders = $pdo->prepare($sql_orders);
                $stmt_orders->execute(['today_start' => $todayStart, 'today_end' => $todayEnd]);
                $orders_data = $stmt_orders->fetch(PDO::FETCH_ASSOC);

                // --- B) VENDAS BRUTAS (TOTAL DOS ITENS) ---
                $sql_subtotal = "
            SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_subtotal
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            AND o.order_time BETWEEN :today_start AND :today_end
        ";
                $stmt_subtotal = $pdo->prepare($sql_subtotal);
                $stmt_subtotal->execute(['today_start' => $todayStart, 'today_end' => $todayEnd]);
                $subtotal_data = $stmt_subtotal->fetch(PDO::FETCH_ASSOC);

                // --- C) TAXA DE SERVIÇO (10%) ---
                // Total devido (10% das vendas brutas)
                $total_10_percent_due = (float)($subtotal_data['total_subtotal'] ?? 0) * 0.10;

                // Total já recebido (pedidos marcados como paid_10_percent)
                $sql_received = "
            SELECT COALESCE(SUM(oi.quantity * oi.price), 0) as total_received
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            AND o.paid_10_percent = 1
            AND o.order_time BETWEEN :today_start AND :today_end
        ";
                $stmt_received = $pdo->prepare($sql_received);
                $stmt_received->execute(['today_start' => $todayStart, 'today_end' => $todayEnd]);
                $received_data = $stmt_received->fetch(PDO::FETCH_ASSOC);

                $total_10_percent_received = (float)($received_data['total_received'] ?? 0) * 0.10;

                // --- D) TOTAL FATURADO (VENDAS BRUTAS + TAXA RECEBIDA) ---
                $total_billed_amount = (float)($subtotal_data['total_subtotal'] ?? 0) + $total_10_percent_received;

                // Resposta JSON
                $response = [
                    'success' => true,
                    'data' => [
                        'total_orders_closed' => (int)($orders_data['total_orders_closed'] ?? 0),
                        'total_subtotal' => (float)($subtotal_data['total_subtotal'] ?? 0),
                        'total_10_percent_due' => $total_10_percent_due,
                        'total_10_percent_received' => $total_10_percent_received,
                        'total_billed_amount' => $total_billed_amount,
                        'report_date' => date('d/m/Y')
                    ]
                ];
            } catch (PDOException $e) {
                $response = [
                    'success' => false,
                    'message' => 'Erro de banco de dados: ' . $e->getMessage()
                ];
            }
        }


        // --- Ação: get_sales_dashboard (OTIMIZADA PARA VENDAS) ---
        if ($action === 'get_sales_dashboard') {
            try {
                $period = isset($_GET['period']) ? intval($_GET['period']) : 7;

                // Datas importantes
                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd = date('Y-m-d 23:59:59');
                $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $monthStart = date('Y-m-01 00:00:00');
                $periodStart = date('Y-m-d 00:00:00', strtotime("-$period days"));

                // --- A) MÉTRICAS DE VENDAS POR PERÍODO ---
                // Vendas de HOJE
                $sql_today = "
            SELECT 
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
                COUNT(DISTINCT o.id) as orders
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            AND o.order_time BETWEEN :today_start AND :today_end
        ";
                $stmt_today = $pdo->prepare($sql_today);
                $stmt_today->execute(['today_start' => $todayStart, 'today_end' => $todayEnd]);
                $today_sales = $stmt_today->fetch(PDO::FETCH_ASSOC);

                // Vendas da SEMANA
                $sql_week = "
            SELECT 
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
                COUNT(DISTINCT o.id) as orders
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            AND o.order_time >= :week_start
        ";
                $stmt_week = $pdo->prepare($sql_week);
                $stmt_week->execute(['week_start' => $weekStart]);
                $week_sales = $stmt_week->fetch(PDO::FETCH_ASSOC);

                // Vendas do MÊS
                $sql_month = "
            SELECT 
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
                COUNT(DISTINCT o.id) as orders
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            AND o.order_time >= :month_start
        ";
                $stmt_month = $pdo->prepare($sql_month);
                $stmt_month->execute(['month_start' => $monthStart]);
                $month_sales = $stmt_month->fetch(PDO::FETCH_ASSOC);

                $sales_metrics = [
                    'today' => [
                        'amount' => (float)($today_sales['revenue'] ?? 0),
                        'orders' => (int)($today_sales['orders'] ?? 0)
                    ],
                    'week' => [
                        'amount' => (float)($week_sales['revenue'] ?? 0),
                        'orders' => (int)($week_sales['orders'] ?? 0)
                    ],
                    'month' => [
                        'amount' => (float)($month_sales['revenue'] ?? 0),
                        'orders' => (int)($month_sales['orders'] ?? 0)
                    ]
                ];

                // --- B) VENDAS DIÁRIAS PARA GRÁFICO ---
                $sql_daily = "
            SELECT
                DATE(o.order_time) as date,
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
                COUNT(DISTINCT o.id) as orders
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            AND o.order_time >= :period_start
            GROUP BY DATE(o.order_time)
            ORDER BY date ASC
            LIMIT :period
        ";
                $stmt_daily = $pdo->prepare($sql_daily);
                $stmt_daily->bindValue('period_start', $periodStart, PDO::PARAM_STR);
                $stmt_daily->bindValue('period', $period, PDO::PARAM_INT);
                $stmt_daily->execute();
                $daily_sales = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

                // --- C) PRATO MAIS VENDIDO ---
                $sql_top_dish = "
            SELECT 
                p.name,
                COALESCE(SUM(oi.quantity), 0) as quantity,
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status = 'completed'
            AND p.category = 'prato'
            AND o.order_time >= :month_start
            GROUP BY p.id, p.name
            ORDER BY quantity DESC
            LIMIT 1
        ";
                $stmt_top_dish = $pdo->prepare($sql_top_dish);
                $stmt_top_dish->execute(['month_start' => $monthStart]);
                $top_dish = $stmt_top_dish->fetch(PDO::FETCH_ASSOC);

                // --- D) BEBIDA MAIS VENDIDA ---
                $sql_top_drink = "
            SELECT 
                p.name,
                COALESCE(SUM(oi.quantity), 0) as quantity,
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status = 'completed'
            AND p.category = 'bebida'
            AND o.order_time >= :month_start
            GROUP BY p.id, p.name
            ORDER BY quantity DESC
            LIMIT 1
        ";
                $stmt_top_drink = $pdo->prepare($sql_top_drink);
                $stmt_top_drink->execute(['month_start' => $monthStart]);
                $top_drink = $stmt_top_drink->fetch(PDO::FETCH_ASSOC);

                // --- E) TOP 5 PRODUTOS MAIS VENDIDOS ---
                $sql_top_products = "
            SELECT 
                p.name,
                COALESCE(SUM(oi.quantity), 0) as quantity,
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
                p.category
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status = 'completed'
            AND o.order_time >= :month_start
            GROUP BY p.id, p.name, p.category
            ORDER BY quantity DESC
            LIMIT 5
        ";
                $stmt_top_products = $pdo->prepare($sql_top_products);
                $stmt_top_products->execute(['month_start' => $monthStart]);
                $top_products = $stmt_top_products->fetchAll(PDO::FETCH_ASSOC);

                // --- F) VENDAS POR CATEGORIA ---
                $sql_category = "
            SELECT 
                p.category,
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue,
                COALESCE(SUM(oi.quantity), 0) as quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status = 'completed'
            AND o.order_time >= :month_start
            GROUP BY p.category
            ORDER BY revenue DESC
        ";
                $stmt_category = $pdo->prepare($sql_category);
                $stmt_category->execute(['month_start' => $monthStart]);
                $category_sales = $stmt_category->fetchAll(PDO::FETCH_ASSOC);

                // --- G) MÉTRICAS ADICIONAIS ---
                // Ticket médio do mês
                $avg_ticket = $sales_metrics['month']['orders'] > 0 ?
                    $sales_metrics['month']['amount'] / $sales_metrics['month']['orders'] : 0;

                // Total de produtos vendidos no mês
                $sql_total_products = "
            SELECT COALESCE(SUM(oi.quantity), 0) as total
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            AND o.order_time >= :month_start
        ";
                $stmt_total_products = $pdo->prepare($sql_total_products);
                $stmt_total_products->execute(['month_start' => $monthStart]);
                $total_products = $stmt_total_products->fetch(PDO::FETCH_ASSOC);

                // Média de itens por pedido
                $avg_items = $sales_metrics['month']['orders'] > 0 ?
                    ($total_products['total'] ?? 0) / $sales_metrics['month']['orders'] : 0;

                $additional_metrics = [
                    'avg_ticket' => $avg_ticket,
                    'total_products_sold' => (int)($total_products['total'] ?? 0),
                    'avg_items_per_order' => $avg_items,
                    'busy_hours' => '12:00-14:00'
                ];

                // Resposta JSON
                $response = [
                    'success' => true,
                    'data' => [
                        'sales_metrics' => $sales_metrics,
                        'daily_sales' => $daily_sales,
                        'period_comparison' => $sales_metrics,
                        'top_dish' => $top_dish,
                        'top_drink' => $top_drink,
                        'top_products' => $top_products,
                        'category_sales' => $category_sales,
                        'additional_metrics' => $additional_metrics
                    ]
                ];
            } catch (PDOException $e) {
                $response = [
                    'success' => false,
                    'message' => 'Erro de banco de dados: ' . $e->getMessage()
                ];
            }
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
