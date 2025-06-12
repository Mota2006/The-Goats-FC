<?php
// Database connection
if (!isset($conn)) {
    $conn = new mysqli("localhost", "root", "", "the goats fc");
    if ($conn->connect_error) {
        die("Erro na ligação: " . $conn->connect_error);
    }
}

// Ensure session is started before accessing $_SESSION variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['user_id'] ?? ''; // Assuming user_id is the correct session variable for the user's ID
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Handle payment save/update
if (isset($_POST['add_pagamento']) || isset($_POST['update_pagamento'])) {
    $pagamento_id = $_POST['pagamento_id'] ?? null; // Get payment ID for update
    $atleta_id = $_POST['atleta_id'];
    $valor = $_POST['valor'];
    $data_pagamento = $_POST['data_pagamento'];
    $referencia = $_POST['referencia'];
    $observacoes = $_POST['observacoes'];

    if ($pagamento_id) { // This is an update operation
        // Only admin and funcionario can update payments
        if (!in_array($current_role, ['admin', 'funcionario'])) {
            $_SESSION['error'] = "Você não tem permissão para atualizar pagamentos.";
            header("Location: pagamentos_view.php");
            exit();
        }

        $conn->begin_transaction();
        try {
            // 1. Fetch old payment details for finance update
            $stmt_old_payment = $conn->prepare("SELECT valor, referencia, atleta_id FROM pagamentos_atletas WHERE id = ?");
            if ($stmt_old_payment === false) {
                throw new Exception("Erro na preparação para buscar dados antigos do pagamento: " . $conn->error);
            }
            $stmt_old_payment->bind_param("i", $pagamento_id);
            $stmt_old_payment->execute();
            $old_payment_details_res = $stmt_old_payment->get_result();
            $old_payment_details = $old_payment_details_res->fetch_assoc();
            $stmt_old_payment->close();

            // Get player's name for the old finance description
            $stmt_old_player_name = $conn->prepare("SELECT name FROM players WHERE id = ?");
            if ($stmt_old_player_name === false) {
                throw new Exception("Erro na preparação para buscar nome do jogador (edição antiga): " . $conn->error);
            }
            $stmt_old_player_name->bind_param("i", $old_payment_details['atleta_id']);
            $stmt_old_player_name->execute();
            $old_player_name_res = $stmt_old_player_name->get_result();
            $old_player_name_row = $old_player_name_res->fetch_assoc();
            $old_player_name = $old_player_name_row ? $old_player_name_row['name'] : 'Atleta Desconhecido';
            $stmt_old_player_name->close();

            $old_description_to_match = "Pagamento de atleta: " . $old_player_name . " (Ref: " . $old_payment_details['referencia'] . ")";
            $old_amount_to_match = $old_payment_details['valor'];

            // Delete corresponding old entry from finance (if exists)
            $stmt_delete_old_finance = $conn->prepare("DELETE FROM finance WHERE type = 'Receita' AND amount = ? AND description = ?");
            if ($stmt_delete_old_finance === false) {
                throw new Exception("Erro na preparação da exclusão de receita antiga: " . $conn->error);
            }
            $stmt_delete_old_finance->bind_param("ds", $old_amount_to_match, $old_description_to_match);
            $stmt_delete_old_finance->execute();
            $stmt_delete_old_finance->close();

            // 2. Update pagamentos_atletas
            $stmt = $conn->prepare("UPDATE pagamentos_atletas SET atleta_id = ?, valor = ?, data_pagamento = ?, referencia = ?, observacoes = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Erro na preparação da atualização: " . $conn->error);
            }
            $stmt->bind_param("idsssi", $atleta_id, $valor, $data_pagamento, $referencia, $observacoes, $pagamento_id);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar pagamento: " . $stmt->error);
            }
            $stmt->close();

            // 3. Insert new entry into finance
            // Get player's name for the new finance description
            $stmt_player_name = $conn->prepare("SELECT name FROM players WHERE id = ?");
            if ($stmt_player_name === false) {
                throw new Exception("Erro na preparação para buscar nome do jogador (edição nova): " . $conn->error);
            }
            $stmt_player_name->bind_param("i", $atleta_id);
            $stmt_player_name->execute();
            $player_name_res = $stmt_player_name->get_result();
            $player_name_row = $player_name_res->fetch_assoc();
            $player_name = $player_name_row ? $player_name_row['name'] : 'Atleta Desconhecido';
            $stmt_player_name->close();

            $description = "Pagamento de atleta: " . $player_name . " (Ref: " . $referencia . ")";
            $type = 'Receita'; // Always 'Receita' for athlete payments

            $stmt_finance = $conn->prepare("INSERT INTO finance (type, amount, description) VALUES (?, ?, ?)");
            if ($stmt_finance === false) {
                throw new Exception("Erro na preparação da inserção de receita atualizada: " . $conn->error);
            }
            $stmt_finance->bind_param("sds", $type, $valor, $description);
            if (!$stmt_finance->execute()) {
                throw new Exception("Erro ao registar receita atualizada no financeiro: " . $stmt_finance->error);
            }
            $stmt_finance->close();

            $conn->commit();
            $_SESSION['success'] = "Pagamento atualizado com sucesso e finanças ajustadas!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Erro na atualização: " . $e->getMessage();
        }

    } else { // This is an insert operation (new payment)
        // Players can only add payments for themselves
        if ($current_role === 'jogador') {
            // Get the player's actual player_id from the players table using their user_id
            $stmt_get_player_id = $conn->prepare("SELECT id FROM players WHERE user_id = ? LIMIT 1");
            if ($stmt_get_player_id === false) {
                $_SESSION['error'] = "Erro na preparação para buscar ID do jogador: " . $conn->error;
                header("Location: pagamentos_view.php");
                exit();
            }
            $stmt_get_player_id->bind_param("i", $current_login_id);
            $stmt_get_player_id->execute();
            $res_player_id = $stmt_get_player_id->get_result();
            $player_row = $res_player_id->fetch_assoc();
            $stmt_get_player_id->close();

            // Ensure the selected athlete ID matches the logged-in player's ID
            if (!$player_row || $atleta_id != $player_row['id']) {
                $_SESSION['error'] = "Você só pode adicionar pagamentos para si mesmo.";
                header("Location: pagamentos_view.php");
                exit();
            }
        } elseif (!in_array($current_role, ['admin', 'funcionario'])) {
            // Only admin/funcionario/jogador (for self) can add payments
            $_SESSION['error'] = "Você não tem permissão para adicionar pagamentos.";
            header("Location: pagamentos_view.php");
            exit();
        }

        // Start transaction for atomicity
        $conn->begin_transaction();
        try {
            // 1. Insert into pagamentos_atletas
            $stmt = $conn->prepare("INSERT INTO pagamentos_atletas (atleta_id, valor, data_pagamento, referencia, observacoes) VALUES (?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("Erro na preparação da inserção de pagamento: " . $conn->error);
            }
            $stmt->bind_param("idsss", $atleta_id, $valor, $data_pagamento, $referencia, $observacoes);
            if (!$stmt->execute()) {
                throw new Exception("Erro ao registar pagamento: " . $stmt->error);
            }
            $stmt->close();

            // 2. Insert into finance as a 'Receita'
            // Get player's name for the finance description
            $stmt_player_name = $conn->prepare("SELECT name FROM players WHERE id = ?");
            if ($stmt_player_name === false) {
                throw new Exception("Erro na preparação para buscar nome do jogador: " . $conn->error);
            }
            $stmt_player_name->bind_param("i", $atleta_id);
            $stmt_player_name->execute();
            $player_name_res = $stmt_player_name->get_result();
            $player_name_row = $player_name_res->fetch_assoc();
            $player_name = $player_name_row ? $player_name_row['name'] : 'Atleta Desconhecido';
            $stmt_player_name->close();

            $description = "Pagamento de atleta: " . $player_name . " (Ref: " . $referencia . ")";
            $type = 'Receita'; // Always 'Receita' for athlete payments

            $stmt_finance = $conn->prepare("INSERT INTO finance (type, amount, description) VALUES (?, ?, ?)");
            if ($stmt_finance === false) {
                throw new Exception("Erro na preparação da inserção de receita: " . $conn->error);
            }
            $stmt_finance->bind_param("sds", $type, $valor, $description);
            if (!$stmt_finance->execute()) {
                throw new Exception("Erro ao registar receita no financeiro: " . $stmt_finance->error);
            }
            $stmt_finance->close();

            $conn->commit(); // Commit transaction if all successful
            $_SESSION['success'] = "Pagamento e receita registados com sucesso!";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback on error
            $_SESSION['error'] = "Erro na operação: " . $e->getMessage();
        }
    }
    header("Location: pagamentos_view.php"); // Redirect to the view page
    exit();
}

// Handle payment deletion
if (isset($_GET['delete'])) {
    // Only admin and funcionario can delete payments
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para eliminar pagamentos.";
        header("Location: pagamentos_view.php");
        exit();
    }

    $id_to_delete = (int)$_GET['delete'];

    $conn->begin_transaction();
    try {
        // First, get payment details to subtract from finance
        $stmt_get_payment = $conn->prepare("SELECT valor, referencia, atleta_id FROM pagamentos_atletas WHERE id = ?");
        if ($stmt_get_payment === false) {
            throw new Exception("Erro na preparação para buscar dados de pagamento: " . $conn->error);
        }
        $stmt_get_payment->bind_param("i", $id_to_delete);
        $stmt_get_payment->execute();
        $payment_details_res = $stmt_get_payment->get_result();
        $payment_details = $payment_details_res->fetch_assoc();
        $stmt_get_payment->close();

        if ($payment_details) {
            // Get player's name for the finance description
            $stmt_player_name = $conn->prepare("SELECT name FROM players WHERE id = ?");
            if ($stmt_player_name === false) {
                throw new Exception("Erro na preparação para buscar nome do jogador (deleção): " . $conn->error);
            }
            $stmt_player_name->bind_param("i", $payment_details['atleta_id']);
            $stmt_player_name->execute();
            $player_name_res = $stmt_player_name->get_result();
            $player_name_row = $player_name_res->fetch_assoc();
            $player_name = $player_name_row ? $player_name_row['name'] : 'Atleta Desconhecido';
            $stmt_player_name->close();

            $description_to_match = "Pagamento de atleta: " . $player_name . " (Ref: " . $payment_details['referencia'] . ")";
            $amount_to_match = $payment_details['valor'];

            // Delete corresponding entry from finance (if exists)
            $stmt_delete_finance = $conn->prepare("DELETE FROM finance WHERE type = 'Receita' AND amount = ? AND description = ?");
            if ($stmt_delete_finance === false) {
                throw new Exception("Erro na preparação da exclusão de receita: " . $conn->error);
            }
            $stmt_delete_finance->bind_param("ds", $amount_to_match, $description_to_match);
            $stmt_delete_finance->execute(); // Execute even if no match, no error
            $stmt_delete_finance->close();
        }

        // Delete from pagamentos_atletas
        $stmt = $conn->prepare("DELETE FROM pagamentos_atletas WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Erro na preparação da exclusão: " . $conn->error);
        }
        $stmt->bind_param("i", $id_to_delete);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao eliminar pagamento: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Pagamento e receita associada eliminados.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro na eliminação: " . $e->getMessage();
    }

    header("Location: pagamentos_view.php"); // Redirect to the view page
    exit();
}

// Logic to fetch payment data for editing if 'edit_pagamento' parameter is present
$editing = false;
$edit_data = null;
if (isset($_GET['edit_pagamento'])) {
    // Only admin and funcionario can edit payments
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para editar pagamentos.";
        header("Location: pagamentos_view.php");
        exit();
    }

    $editing = true;
    $id = (int)$_GET['edit_pagamento'];
    $stmt = $conn->prepare("SELECT * FROM pagamentos_atletas WHERE id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $edit_data = $res->fetch_assoc();
        } else {
            $_SESSION['error'] = "Pagamento não encontrado para edição.";
        }
        $stmt->close();
    }
}

// Fetch players for the dropdown based on role
if ($current_role === 'jogador') {
    $stmt_players = $conn->prepare("SELECT id, name, user_id FROM players WHERE user_id = ? ORDER BY name");
    if ($stmt_players === false) {
        die("Erro na preparação da consulta de jogadores: " . $conn->error);
    }
    $stmt_players->bind_param("i", $current_login_id);
    $stmt_players->execute();
    $players = $stmt_players->get_result();
    $stmt_players->close();
} else {
    // For admin/funcionario, show all players
    $players = $conn->query("SELECT id, name, user_id FROM players ORDER BY name");
}


// Fetch all payments for display based on role
if ($current_role === 'jogador') {
    $stmt_pagamentos = $conn->prepare("SELECT pa.*, p.name FROM pagamentos_atletas pa JOIN players p ON pa.atleta_id = p.id WHERE p.user_id = ? ORDER BY data_pagamento DESC");
    if ($stmt_pagamentos === false) {
        die("Erro na preparação da consulta de pagamentos: " . $conn->error);
    }
    $stmt_pagamentos->bind_param("i", $current_login_id);
    $stmt_pagamentos->execute();
    $pagamentos = $stmt_pagamentos->get_result();
    $stmt_pagamentos->close();
} else {
    $pagamentos = $conn->query("SELECT pa.*, p.name FROM pagamentos_atletas pa JOIN players p ON pa.atleta_id = p.id ORDER BY data_pagamento DESC");
}
?>