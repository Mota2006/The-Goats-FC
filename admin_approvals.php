<?php
// Database connection parameters
$host = 'localhost';
$db   = 'the goats fc';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $id_in_pending_table = $_POST['id_in_pending_table'];
    $user_type = $_POST['user_type'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $conn->begin_transaction();
        try {
            // 1. Get user details from the pending table
            $pending_table = '';
            $target_table = '';
            $columns = '';
            $bind_types = '';
            $select_stmt = null;

            switch ($user_type) {
                case 'jogador':
                    $pending_table = 'pending_players';
                    $target_table = 'players';
                    $columns = 'name, phone, email, birthdate, history, user_id';
                    $select_stmt = $conn->prepare("SELECT name, phone, email, birthdate, history, user_id FROM pending_players WHERE id = ?");
                    $bind_types = 'sssssi';
                    break;
                case 'treinador':
                    $pending_table = 'pending_treinadores';
                    $target_table = 'treinadores';
                    $columns = 'name, phone, email, birthdate, history, user_id';
                    $select_stmt = $conn->prepare("SELECT name, phone, email, birthdate, history, user_id FROM pending_treinadores WHERE id = ?");
                    $bind_types = 'sssssi';
                    break;
                case 'socio':
                    $pending_table = 'pending_socios';
                    $target_table = 'socios';
                    $columns = 'nome, data_nascimento, contacto, email, morada, nif, user_id';
                    $select_stmt = $conn->prepare("SELECT nome, data_nascimento, contacto, email, morada, nif, user_id FROM pending_socios WHERE id = ?");
                    $bind_types = 'ssssssi';
                    break;
                case 'funcionario': // Added for 'funcionario'
                    $pending_table = 'pending_funcionarios';
                    $target_table = 'funcionarios';
                    $columns = 'name, phone, email, birthdate, position, nif, user_id';
                    $select_stmt = $conn->prepare("SELECT name, phone, email, birthdate, position, nif, user_id FROM pending_funcionarios WHERE id = ?");
                    $bind_types = 'ssssssi';
                    break;
                default:
                    throw new Exception("Tipo de utilizador inválido.");
            }

            if (!$select_stmt) {
                throw new Exception("Erro na preparação da seleção de detalhes pendentes: " . $conn->error);
            }
            $select_stmt->bind_param("i", $id_in_pending_table);
            $select_stmt->execute();
            $result = $select_stmt->get_result();
            $details = $result->fetch_assoc();
            $select_stmt->close();

            if (!$details) {
                throw new Exception("Nenhum detalhe pendente encontrado para o ID fornecido.");
            }

            // 2. Insert details into the target table
            $insert_values_placeholder = implode(', ', array_fill(0, count(explode(', ', $columns)), '?'));
            $insert_stmt = $conn->prepare("INSERT INTO " . $target_table . " (" . $columns . ") VALUES (" . $insert_values_placeholder . ")");
            if (!$insert_stmt) {
                throw new Exception("Erro na preparação da inserção na tabela alvo: " . $conn->error);
            }

            // Prepare parameters for bind_param dynamically
            $params = array($bind_types);
            foreach (explode(', ', $columns) as $col_name) {
                $params[] = &$details[trim($col_name)];
            }
            call_user_func_array(array($insert_stmt, 'bind_param'), $params);

            if (!$insert_stmt->execute()) {
                throw new Exception("Erro ao inserir detalhes na tabela alvo: " . $insert_stmt->error);
            }
            $insert_stmt->close();

            // 3. Update user status in the 'users' table
            $update_user_stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
            if (!$update_user_stmt) {
                throw new Exception("Erro na preparação da atualização do estado do utilizador: " . $conn->error);
            }
            $update_user_stmt->bind_param("i", $user_id);
            if (!$update_user_stmt->execute()) {
                throw new Exception("Erro ao atualizar o estado do utilizador: " . $update_user_stmt->error);
            }
            $update_user_stmt->close();

            // 4. Delete from the pending table
            $delete_pending_stmt = $conn->prepare("DELETE FROM " . $pending_table . " WHERE id = ?");
            if (!$delete_pending_stmt) {
                throw new Exception("Erro na preparação da exclusão do registo pendente: " . $conn->error);
            }
            $delete_pending_stmt->bind_param("i", $id_in_pending_table);
            if (!$delete_pending_stmt->execute()) {
                throw new Exception("Erro ao excluir registo pendente: " . $delete_pending_stmt->error);
            }
            $delete_pending_stmt->close();

            $conn->commit();
            $_SESSION['success_approval'] = "Registo de " . $user_type . " aprovado com sucesso.";

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_approval'] = "Erro ao aprovar registo: " . $e->getMessage();
        }

    } elseif ($action === 'reject') {
        $conn->begin_transaction();
        try {
            // 1. Delete user from the 'users' table (which will cascade delete from pending tables if foreign key is set with ON DELETE CASCADE)
            $delete_user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if (!$delete_user_stmt) {
                throw new Exception("Erro na preparação da exclusão do utilizador: " . $conn->error);
            }
            $delete_user_stmt->bind_param("i", $user_id);
            if (!$delete_user_stmt->execute()) {
                throw new Exception("Erro ao excluir utilizador: " . $delete_user_stmt->error);
            }
            $delete_user_stmt->close();

            $conn->commit();
            $_SESSION['success_approval'] = "Registo de " . $user_type . " rejeitado e removido.";

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_approval'] = "Erro ao rejeitar registo: " . $e->getMessage();
        }
    }
    header("Location: admin_approvals_view.php");
    exit();
}

$conn->close();
?>