<?php
// public/api_print_queue.php
// API consumida pelo KDS e/ou serviço de impressão externo.

require_once '../app/config/database.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ação inválida.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    global $pdo;
    
    // --- Lógica para GET (Buscar Fila) ---
    if ($action === 'get_pending_prints' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Busca todos os itens que foram 'sent' (enviados) e ainda não foram 'done'
        $stmt = $pdo->prepare("
            SELECT 
                k.id as kitchen_order_id,
                -- Formato TO_CHAR é usado para garantir compatibilidade com new Date() do JavaScript
                TO_CHAR(k.time_sent, 'YYYY-MM-DD HH24:MI:SS') as time_sent, 
                k.status as item_status,
                t.table_number,
                oi.quantity,
                p.name AS product_name,
                o.id AS order_id
            FROM kitchen_orders k
            JOIN order_items oi ON k.order_item_id = oi.id
            JOIN orders o ON oi.order_id = o.id
            JOIN tables t ON o.table_id = t.id
            JOIN products p ON oi.product_id = p.id
            WHERE k.status IN ('sent', 'in_progress') -- Mostra itens em espera e em preparo
            ORDER BY k.time_sent ASC
        ");
        $stmt->execute();
        $pending_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupa os itens por pedido (ORDER_ID e TABLE_NUMBER)
        $grouped_orders = [];
        foreach ($pending_items as $item) {
            $key = $item['order_id'] . '-' . $item['table_number'];
            if (!isset($grouped_orders[$key])) {
                $grouped_orders[$key] = [
                    'order_id' => $item['order_id'],
                    'table_number' => $item['table_number'],
                    'time_sent' => $item['time_sent'],
                    'items' => [],
                    'kitchen_order_ids' => [] 
                ];
            }
            // Adiciona o item à lista do pedido, mantendo o ID da fila de cozinha
            $grouped_orders[$key]['items'][] = [
                'quantity' => $item['quantity'],
                'product_name' => $item['product_name'],
                'kitchen_order_id' => $item['kitchen_order_id'],
                'item_status' => $item['item_status']
            ];
            $grouped_orders[$key]['kitchen_order_ids'][] = $item['kitchen_order_id'];
        }

        $response = ['success' => true, 'data' => array_values($grouped_orders)];
    }

    // --- Lógica para POST (Ações de Status) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        // --- Ação POST: update_status (Usada pelo KDS) ---
        if ($action === 'update_status') {
            $kitchen_order_id = $data['kitchen_order_id'] ?? null;
            $new_status = $data['new_status'] ?? null;

            if (!$kitchen_order_id || !in_array($new_status, ['in_progress', 'done', 'printed'])) {
                $response = ['success' => false, 'message' => 'IDs ou status inválidos para atualização.'];
            } else {
                $stmt = $pdo->prepare("UPDATE kitchen_orders SET status = :new_status WHERE id = :id");
                
                if ($stmt->execute(['new_status' => $new_status, 'id' => $kitchen_order_id])) {
                    $response = ['success' => true, 'message' => 'Status atualizado para ' . $new_status . '.'];
                } else {
                    $response = ['success' => false, 'message' => 'Falha ao atualizar o status no banco de dados.'];
                }
            }
        }
        
        // --- Ação POST: mark_as_printed (Usada por Impressoras/Sistemas Externos) ---
        if ($action === 'mark_as_printed') {
            $kitchen_order_ids = $data['kitchen_order_ids'] ?? [];

            if (empty($kitchen_order_ids)) {
                $response = ['success' => false, 'message' => 'Nenhum ID fornecido para marcar como impresso.'];
            } else {
                $placeholders = implode(',', array_fill(0, count($kitchen_order_ids), '?'));
                $stmt = $pdo->prepare("UPDATE kitchen_orders SET status = 'printed' WHERE id IN ($placeholders)");
                
                if ($stmt->execute($kitchen_order_ids)) {
                    $response = ['success' => true, 'message' => count($kitchen_order_ids) . ' itens marcados como impressos.'];
                } else {
                    $response = ['success' => false, 'message' => 'Falha ao atualizar o status.'];
                }
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