<?php
// Database connection
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "the goats fc");
    if ($conn->connect_error) {
        die("Erro na ligação: " . $conn->connect_error);
    }
}

// Ensure $_SESSION variables are available
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['user_id'] ?? ''; // Use user_id as previously established for consistency
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

$editing = false;
$edit_data = null;
$socio_name_for_edit = ''; // Initialize for display in edit mode
$current_socio_id = null; // Initialize $current_socio_id to null

// Determine the socio_id associated with the user_id if the current role is 'socio'
if ($current_role === 'socio') {
    $stmt_get_socio_id = $conn->prepare("SELECT id FROM socios WHERE user_id = ? LIMIT 1");
    if ($stmt_get_socio_id) {
        $stmt_get_socio_id->bind_param("i", $current_login_id);
        $stmt_get_socio_id->execute();
        $res_socio_id = $stmt_get_socio_id->get_result();
        if ($row_socio_id = $res_socio_id->fetch_assoc()) {
            $current_socio_id = $row_socio_id['id'];
        }
        $stmt_get_socio_id->close();
    }
}

// Handle quota save/update
if (isset($_POST['add_quota']) || isset($_POST['update_quota'])) {
    $quota_id = $_POST['quota_id'] ?? null; // Get quota ID for update
    $socio_id = $_POST['socio_id'];
    $mes_ano = $_POST['mes_ano'];
    $data_pagamento = $_POST['data_pagamento'];
    $valor = $_POST['valor'];
    $estado = $_POST['estado'];

    if ($quota_id) { // This is an update operation
        // Only admin and funcionario can update quotas
        if (!in_array($current_role, ['admin', 'funcionario'])) {
            $_SESSION['error'] = "Você não tem permissão para atualizar quotas.";
            header("Location: quotas_view.php");
            exit();
        }

        $conn->begin_transaction();
        try {
            // 1. Fetch old quota details for finance update
            $stmt_old_quota = $conn->prepare("SELECT valor, mes_ano, socio_id FROM quotas_socios WHERE id = ?");
            if ($stmt_old_quota === false) {
                throw new Exception("Erro na preparação para buscar dados antigos da quota: " . $conn->error);
            }
            $stmt_old_quota->bind_param("i", $quota_id);
            $stmt_old_quota->execute();
            $old_quota_details_res = $stmt_old_quota->get_result();
            $old_quota_details = $old_quota_details_res->fetch_assoc();
            $stmt_old_quota->close();

            // Get socio's name for the old finance description
            $stmt_old_socio_name = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
            if ($stmt_old_socio_name === false) {
                throw new Exception("Erro na preparação para buscar nome do sócio (edição antiga): " . $conn->error);
            }
            $stmt_old_socio_name->bind_param("i", $old_quota_details['socio_id']);
            $stmt_old_socio_name->execute();
            $old_socio_name_res = $stmt_old_socio_name->get_result();
            $old_socio_name_row = $old_socio_name_res->fetch_assoc();
            $old_socio_name = $old_socio_name_row ? $old_socio_name_row['nome'] : 'Sócio Desconhecido';
            $stmt_old_socio_name->close();

            $old_description_to_match = "Pagamento de Quota - Sócio: " . $old_socio_name . " (" . $old_quota_details['mes_ano'] . ")";
            $old_amount_to_match = $old_quota_details['valor'];

            // Delete corresponding old entry from finance (if exists)
            $stmt_delete_old_finance = $conn->prepare("DELETE FROM finance WHERE type = 'Receita' AND amount = ? AND description = ?");
            if ($stmt_delete_old_finance === false) {
                throw new Exception("Erro na preparação da exclusão de receita antiga (quota): " . $conn->error);
            }
            $stmt_delete_old_finance->bind_param("ds", $old_amount_to_match, $old_description_to_match);
            $stmt_delete_old_finance->execute();
            $stmt_delete_old_finance->close();


            // 2. Update quotas_socios
            $stmt = $conn->prepare("UPDATE quotas_socios SET socio_id = ?, mes_ano = ?, data_pagamento = ?, valor = ?, estado = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Erro na preparação da atualização da quota: " . $conn->error);
            }
            $stmt->bind_param("issdsi", $socio_id, $mes_ano, $data_pagamento, $valor, $estado, $quota_id);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar quota: " . $stmt->error);
            }
            $stmt->close();

            // 3. Insert new entry into finance
            // Get socio's name for the new finance description
            $stmt_socio_name = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
            if ($stmt_socio_name === false) {
                throw new Exception("Erro na preparação para buscar nome do sócio (edição nova): " . $conn->error);
            }
            $stmt_socio_name->bind_param("i", $socio_id);
            $stmt_socio_name->execute();
            $socio_name_res = $stmt_socio_name->get_result();
            $socio_name_row = $socio_name_res->fetch_assoc();
            $socio_name = $socio_name_row ? $socio_name_row['nome'] : 'Sócio Desconhecido';
            $stmt_socio_name->close();

            $description = "Pagamento de Quota - Sócio: " . $socio_name . " (" . $mes_ano . ")";
            $type = 'Receita'; // Always 'Receita' for quotas

            $stmt_finance = $conn->prepare("INSERT INTO finance (type, amount, description) VALUES (?, ?, ?)");
            if ($stmt_finance === false) {
                throw new Exception("Erro na preparação da inserção de receita atualizada (quota): " . $conn->error);
            }
            $stmt_finance->bind_param("sds", $type, $valor, $description);
            if (!$stmt_finance->execute()) {
                throw new Exception("Erro ao registar receita de quota atualizada no financeiro: " . $stmt_finance->error);
            }
            $stmt_finance->close();

            $conn->commit();
            $_SESSION['success'] = "Quota atualizada com sucesso e finanças ajustadas!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Erro na atualização da quota: " . $e->getMessage();
        }

    } else { // This is an insert operation (new quota)
        // If current role is 'socio', ensure they can only add quotas for themselves
        if ($current_role === 'socio') {
            if ($socio_id != $current_socio_id) {
                $_SESSION['error'] = "Você só pode adicionar quotas para si mesmo.";
                header("Location: quotas_view.php");
                exit();
            }
        } elseif (!in_array($current_role, ['admin', 'funcionario'])) {
            $_SESSION['error'] = "Você não tem permissão para adicionar quotas.";
            header("Location: quotas_view.php");
            exit();
        }

        // Start transaction for atomicity
        $conn->begin_transaction();
        try {
            // 1. Insert into quotas_socios
            $stmt = $conn->prepare("INSERT INTO quotas_socios (socio_id, mes_ano, data_pagamento, valor, estado) VALUES (?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Erro na preparação da inserção de quota: " . $conn->error);
            }
            $stmt->bind_param("issds", $socio_id, $mes_ano, $data_pagamento, $valor, $estado);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao registar quota: " . $stmt->error);
            }
            $stmt->close();

            // 2. Insert into finance as a 'Receita'
            // Get socio's name for the finance description
            $stmt_socio_name = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
            if ($stmt_socio_name === false) {
                throw new Exception("Erro na preparação para buscar nome do sócio: " . $conn->error);
            }
            $stmt_socio_name->bind_param("i", $socio_id);
            $stmt_socio_name->execute();
            $socio_name_res = $stmt_socio_name->get_result();
            $socio_name_row = $socio_name_res->fetch_assoc();
            $socio_name = $socio_name_row ? $socio_name_row['nome'] : 'Sócio Desconhecido';
            $stmt_socio_name->close();

            $description = "Pagamento de Quota - Sócio: " . $socio_name . " (" . $mes_ano . ")";
            $type = 'Receita'; // Always 'Receita' for quotas

            $stmt_finance = $conn->prepare("INSERT INTO finance (type, amount, description) VALUES (?, ?, ?)");
            if ($stmt_finance === false) {
                throw new Exception("Erro na preparação da inserção de receita de quota: " . $conn->error);
            }
            $stmt_finance->bind_param("sds", $type, $valor, $description);
            if (!$stmt_finance->execute()) {
                throw new Exception("Erro ao registar receita de quota no financeiro: " . $stmt_finance->error);
            }
            $stmt_finance->close();

            $conn->commit(); // Commit transaction if all successful
            $_SESSION['success'] = "Quota e receita registadas com sucesso!";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback on error
            $_SESSION['error'] = "Erro na operação da quota: " . $e->getMessage();
        }
    }
    header("Location: quotas_view.php"); // Redirect to the view page
    exit();
}

// Handle quota deletion
if (isset($_GET['delete'])) {
    // Only admin and funcionario can delete quotas
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para eliminar quotas.";
        header("Location: quotas_view.php");
        exit();
    }

    $id_to_delete = (int)$_GET['delete'];

    $conn->begin_transaction();
    try {
        // First, get quota details to remove from finance
        $stmt_get_quota = $conn->prepare("SELECT valor, mes_ano, socio_id FROM quotas_socios WHERE id = ?");
        if ($stmt_get_quota === false) {
            throw new Exception("Erro na preparação para buscar dados da quota: " . $conn->error);
        }
        $stmt_get_quota->bind_param("i", $id_to_delete);
        $stmt_get_quota->execute();
        $quota_details_res = $stmt_get_quota->get_result();
        $quota_details = $quota_details_res->fetch_assoc();
        $stmt_get_quota->close();

        if ($quota_details) {
            // Get socio's name for the finance description
            $stmt_socio_name = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
            if ($stmt_socio_name === false) {
                throw new Exception("Erro na preparação para buscar nome do sócio (deleção): " . $conn->error);
            }
            $stmt_socio_name->bind_param("i", $quota_details['socio_id']);
            $stmt_socio_name->execute();
            $socio_name_res = $stmt_socio_name->get_result();
            $socio_name_row = $socio_name_res->fetch_assoc();
            $socio_name = $socio_name_row ? $socio_name_row['nome'] : 'Sócio Desconhecido';
            $stmt_socio_name->close();

            $description_to_match = "Pagamento de Quota - Sócio: " . $socio_name . " (" . $quota_details['mes_ano'] . ")";
            $amount_to_match = $quota_details['valor'];

            // Delete corresponding entry from finance (if exists)
            $stmt_delete_finance = $conn->prepare("DELETE FROM finance WHERE type = 'Receita' AND amount = ? AND description = ?");
            if ($stmt_delete_finance === false) {
                throw new Exception("Erro na preparação da exclusão de receita (quota): " . $conn->error);
            }
            $stmt_delete_finance->bind_param("ds", $amount_to_match, $description_to_match);
            $stmt_delete_finance->execute(); // Execute even if no match, no error
            $stmt_delete_finance->close();
        }

        // Delete from quotas_socios
        $stmt = $conn->prepare("DELETE FROM quotas_socios WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Erro na preparação da exclusão da quota: " . $conn->error);
        }
        $stmt->bind_param("i", $id_to_delete);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao eliminar quota: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Quota e receita associada eliminadas.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro na eliminação da quota: " . $e->getMessage();
    }

    header("Location: quotas_view.php"); // Redirect to the view page
    exit();
}

// Logic to fetch quota data for editing if 'edit_quota' parameter is present
if (isset($_GET['edit_quota'])) {
    // Only admin and funcionario can edit quotas
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para editar quotas.";
        header("Location: quotas_view.php");
        exit();
    }

    $editing = true;
    $id = (int)$_GET['edit_quota'];
    $stmt = $conn->prepare("SELECT * FROM quotas_socios WHERE id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $edit_data = $res->fetch_assoc();
            // Fetch socio name for display in the edit form
            $stmt_socio_name_edit = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
            if ($stmt_socio_name_edit) {
                $stmt_socio_name_edit->bind_param("i", $edit_data['socio_id']);
                $stmt_socio_name_edit->execute();
                $res_socio_name_edit = $stmt_socio_name_edit->get_result();
                if ($row_socio_name_edit = $res_socio_name_edit->fetch_assoc()) {
                    $socio_name_for_edit = $row_socio_name_edit['nome'];
                }
                $stmt_socio_name_edit->close();
            }
        } else {
            $_SESSION['error'] = "Quota não encontrada para edição.";
        }
        $stmt->close();
    }
}

// Fetch socios for the dropdown
$sql_socios_dropdown = "SELECT id, nome FROM socios ORDER BY nome ASC";
// If current role is 'socio', only show their own name in dropdown (if they are adding their own quota)
if ($current_role === 'socio' && $current_socio_id !== null) { // Only filter if $current_socio_id is known
    $sql_socios_dropdown = "SELECT id, nome FROM socios WHERE user_id = " . (int)$current_login_id . " ORDER BY nome ASC";
}
$socios = $conn->query($sql_socios_dropdown);
if ($socios === false) {
    // Handle error if query fails
    $socios = new mysqli_result(new mysqli()); // Provide an empty result set to avoid errors
    error_log("Error fetching socios for dropdown: " . $conn->error);
}


// Listar quotas
$sql_quotas = "SELECT qs.*, s.nome FROM quotas_socios qs JOIN socios s ON qs.socio_id = s.id";

// If current role is 'socio' and we have a valid $current_socio_id, filter by it.
// Ensure $current_socio_id is not null before using it in the query.
if ($current_role === 'socio' && !$editing && $current_socio_id !== null) {
    $sql_quotas .= " WHERE qs.socio_id = " . (int)$current_socio_id;
}
$sql_quotas .= " ORDER BY qs.data_pagamento DESC";
$quotas = $conn->query($sql_quotas);
?>