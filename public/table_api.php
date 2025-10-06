<?php
// public/table_api.php - API Exclusiva para Gerenciamento de Mesas (CRUD e Status)
require_once '../app/config/database.php';
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ação inválida.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Verifica se o usuário é Administrador (Apenas Admin pode fazer CRUD de mesas)
$userRole = $_SESSION['user_role'] ?? 'guest';
$isAdmin = ($userRole === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem gerenciar mesas.']);
    exit();
}

try {
    global $pdo; 
    
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? $action; 
    
    // --- Lógica para GET (Leitura Minimalista de Mesas) ---
    if ($action === 'get_all_tables_with_status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "
            SELECT 
                t.id AS table_id,
                t.table_number,
                t.status
            FROM tables t
            ORDER BY t.table_number ASC 
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $tables_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = ['success' => true, 'data' => $tables_data];
    }
    
    // --- Lógica para POST (CRUD e Ações de Manutenção - APENAS ADMIN) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Ação: create_table (Criação de Mesa)
        if ($action === 'create_table') {
            $table_number = $data['table_number'] ?? null;

            // VALIDAÇÃO: Aceita string, desde que não esteja vazia
            if (empty($table_number)) {
                $response = ['success' => false, 'message' => 'O nome da mesa é obrigatório.'];
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tables (table_number, status) VALUES (:table_number, 'available')");
                    $stmt->execute(['table_number' => $table_number]);
                    $response = ['success' => true, 'message' => 'Mesa "' . htmlspecialchars($table_number) . '" criada com sucesso.'];
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000' || $e->getCode() === '23505') { 
                        $response = ['success' => false, 'message' => 'Mesa com este nome já existe.'];
                    } else {
                        $response = ['success' => false, 'message' => 'Erro ao criar mesa: ' . $e->getMessage()];
                    }
                }
            }
        }

        // Ação: update_table (Edição do Número da Mesa)
        if ($action === 'update_table') {
            $table_id = $data['table_id'] ?? null;
            $new_table_number = $data['table_number'] ?? null;

            // VALIDAÇÃO: Aceita string, desde que não esteja vazia
            if (!$table_id || empty($new_table_number)) {
                $response = ['success' => false, 'message' => 'Dados de edição inválidos.'];
            } else {
                try {
                    // 1. Verificar se o NOVO nome já está em uso por OUTRA mesa
                    $stmt = $pdo->prepare("SELECT id FROM tables WHERE table_number = :table_number AND id != :table_id");
                    $stmt->execute(['table_number' => $new_table_number, 'table_id' => $table_id]);
                    if ($stmt->fetch()) {
                        $response = ['success' => false, 'message' => 'O novo nome da mesa já está em uso.'];
                    } else {
                        // 2. Atualizar o número
                        $stmt = $pdo->prepare("UPDATE tables SET table_number = :new_table_number WHERE id = :table_id");
                        $stmt->execute(['new_table_number' => $new_table_number, 'table_id' => $table_id]);
                        $response = ['success' => true, 'message' => 'Mesa atualizada para "' . htmlspecialchars($new_table_number) . '" com sucesso.'];
                    }
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => 'Erro ao atualizar mesa: ' . $e->getMessage()];
                }
            }
        }
        
        // Ação: update_table_status (MUDANÇA DE STATUS MANUAL)
        if ($action === 'update_table_status') {
            $table_id = $data['table_id'] ?? null;
            $new_status = $data['new_status'] ?? null;

            if (!$table_id || !in_array($new_status, ['available', 'occupied'])) {
                $response = ['success' => false, 'message' => 'Status inválido.'];
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE tables SET status = :new_status WHERE id = :table_id");
                    $stmt->execute(['new_status' => $new_status, 'table_id' => $table_id]);
                    $response = ['success' => true, 'message' => 'Status da mesa alterado para ' . ucfirst($new_status) . '.'];
                } catch (Exception $e) {
                    $response = ['success' => false, 'message' => 'Erro ao mudar status: ' . $e->getMessage()];
                }
            }
        }


        // Ação: delete_table (Exclusão de Mesa)
        if ($action === 'delete_table') {
            $table_id = $data['table_id'] ?? null;

            if (!$table_id) {
                $response = ['success' => false, 'message' => 'ID da mesa ausente.'];
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE table_id = :table_id AND status = 'pending'");
                    $stmt->execute(['table_id' => $table_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Mesa possui um pedido pendente. Feche-o ou use 'Liberar Forçado'.");
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM tables WHERE id = :table_id");
                    $stmt->execute(['table_id' => $table_id]);
                    
                    $pdo->commit();
                    $response = ['success' => true, 'message' => 'Mesa deletada com sucesso.'];
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $response = ['success' => false, 'message' => 'Erro ao deletar mesa: ' . $e->getMessage()];
                }
            }
        }
        
        // Ação: force_release_table (Liberação Forçada)
        if ($action === 'force_release_table') {
            $table_id = $data['table_id'] ?? null;

            if (!$table_id) {
                $response = ['success' => false, 'message' => 'ID da mesa ausente.'];
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE table_id = :table_id AND status = 'pending'");
                    $stmt->execute(['table_id' => $table_id]);

                    $stmt = $pdo->prepare("UPDATE tables SET status = 'available' WHERE id = :table_id");
                    $stmt->execute(['table_id' => $table_id]);

                    $pdo->commit();
                    $response = ['success' => true, 'message' => 'Mesa liberada e pedido cancelado.'];

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $response = ['success' => false, 'message' => 'Erro ao forçar liberação: ' . $e->getMessage()];
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