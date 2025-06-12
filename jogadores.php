<?php
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

$current_login_id = $_SESSION['user_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Handle player save/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Correctly get the player's ID from the form, which is `player_id` in the form
    $player_id = $_POST['player_id'] ?? null; // Changed from user_id to player_id

    // Authorization check for saving/updating players
    if (!in_array($current_role, ['admin', 'funcionario']) && !($current_role === 'jogador' && $player_id == $current_login_id)) {
        $_SESSION['error'] = "Você não tem permissão para realizar esta ação.";
        header("Location: jogadores_view.php");
        exit();
    }

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $birthdate = $_POST['birthdate'];
    $history = $_POST['history'];

    if ($player_id) { // If player_id exists, it's an update
        // Update existing player
        $stmt = $conn->prepare("UPDATE players SET name = ?, phone = ?, email = ?, birthdate = ?, history = ? WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Erro na preparação da atualização: " . $conn->error;
            header("Location: jogadores_view.php");
            exit();
        }
        // Bind the player's actual ID (from the 'id' column in the 'players' table)
        $stmt->bind_param("sssssi", $name, $phone, $email, $birthdate, $history, $player_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Jogador atualizado com sucesso!";

            // Removed team association update logic
        } else {
            $_SESSION['error'] = "Erro ao atualizar jogador: " . $stmt->error;
        }
        $stmt->close();
    }
    // The 'else' block for inserting new players is removed as registration is now handled by registo.php

    header("Location: jogadores_view.php");
    exit();
}

// Handle player deletion
if (isset($_GET['delete_player'])) {
    // Authorization check for deleting players
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para eliminar jogadores.";
        header("Location: jogadores_view.php");
        exit();
    }

    $player_id_to_delete = (int)$_GET['delete_player'];

    $conn->begin_transaction();
    try {
        // Delete from player_stats first (due to foreign key constraint)
        $stmt_stats = $conn->prepare("DELETE FROM player_stats WHERE player_id = ?");
        if ($stmt_stats === false) {
            throw new mysqli_sql_exception("Erro na preparação da exclusão das estatísticas do jogador: " . $conn->error);
        }
        $stmt_stats->bind_param("i", $player_id_to_delete);
        if (!$stmt_stats->execute()) {
            throw new mysqli_sql_exception("Erro ao excluir estatísticas do jogador: " . $stmt_stats->error);
        }
        $stmt_stats->close();


        // Delete from pagamentos_atletas (due to foreign key constraint)
        $stmt_pagamentos = $conn->prepare("DELETE FROM pagamentos_atletas WHERE atleta_id = ?");
        if ($stmt_pagamentos === false) {
            throw new mysqli_sql_exception("Erro na preparação da exclusão dos pagamentos do atleta: " . $conn->error);
        }
        $stmt_pagamentos->bind_param("i", $player_id_to_delete);
        if (!$stmt_pagamentos->execute()) {
            throw new mysqli_sql_exception("Erro ao excluir pagamentos do atleta: " . $stmt_pagamentos->error);
        }
        $stmt_pagamentos->close();


        // Delete from team_players
        $stmt_team = $conn->prepare("DELETE FROM team_players WHERE player_id = ?");
        if ($stmt_team === false) {
            throw new mysqli_sql_exception("Erro na preparação da exclusão da equipa de jogador: " . $conn->error);
        }
        $stmt_team->bind_param("i", $player_id_to_delete);
        if (!$stmt_team->execute()) {
            throw new mysqli_sql_exception("Erro ao excluir jogador da equipa: " . $stmt_team->error);
        }
        $stmt_team->close();

        // Get the user_id associated with the player before deleting the player
        $stmt_get_user_id = $conn->prepare("SELECT user_id FROM players WHERE id = ?");
        if ($stmt_get_user_id === false) {
            throw new mysqli_sql_exception("Erro na preparação para obter user_id: " . $conn->error);
        }
        $stmt_get_user_id->bind_param("i", $player_id_to_delete);
        if (!$stmt_get_user_id->execute()) {
            throw new mysqli_sql_exception("Erro ao obter user_id: " . $stmt_get_user_id->error);
        }
        $res_user_id = $stmt_get_user_id->get_result();
        $user_id_associated = null;
        if ($row_user_id = $res_user_id->fetch_assoc()) {
            $user_id_associated = $row_user_id['user_id'];
        }
        $stmt_get_user_id->close();


        // Then delete from players table
        $stmt_player = $conn->prepare("DELETE FROM players WHERE id = ?");
        if ($stmt_player === false) {
            throw new mysqli_sql_exception("Erro na preparação da exclusão do jogador: " . $conn->error);
        }
        $stmt_player->bind_param("i", $player_id_to_delete);
        if (!$stmt_player->execute()) {
            throw new mysqli_sql_exception("Erro ao excluir jogador: " . $stmt_player->error);
        }
        $stmt_player->close();

        // Finally, delete from users table if a user_id was associated
        if ($user_id_associated) {
            $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt_user === false) {
                throw new mysqli_sql_exception("Erro na preparação da exclusão do utilizador: " . $conn->error);
            }
            $stmt_user->bind_param("i", $user_id_associated);
            if (!$stmt_user->execute()) {
                throw new mysqli_sql_exception("Erro ao excluir utilizador: " . $stmt_user->error);
            }
            $stmt_user->close();
        }

        $conn->commit();
        $_SESSION['success'] = "Jogador eliminado com sucesso!";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro ao eliminar jogador: " . $e->getMessage();
    }

    header("Location: jogadores_view.php");
    exit();
}

// Logic to fetch player data for editing if 'edit_player' parameter is present
$editing = false;
$edit_data = null;
if (isset($_GET['edit_player'])) {
    $player_id_to_edit = (int) $_GET['edit_player']; // Renamed for clarity

    // Authorization check for fetching edit data
    // Here, $player_id_to_edit is the 'id' from the 'players' table
    // You need to get the user_id associated with this player_id to compare with $current_login_id
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM players WHERE id = ?");
    $stmt_get_user_id->bind_param("i", $player_id_to_edit);
    $stmt_get_user_id->execute();
    $res_get_user_id = $stmt_get_user_id->get_result();
    $player_user_id = null;
    if ($row_get_user_id = $res_get_user_id->fetch_assoc()) {
        $player_user_id = $row_get_user_id['user_id'];
    }
    $stmt_get_user_id->close();


    if (!in_array($current_role, ['admin', 'funcionario']) && !($current_role === 'jogador' && $player_user_id == $current_login_id)) {
        $_SESSION['error'] = "Você não tem permissão para editar este jogador.";
        header("Location: jogadores_view.php");
        exit();
    }

    $editing = true;
    // Modified SELECT statement to remove team_id join and fetch player's 'id' as well
    $stmt = $conn->prepare("SELECT p.* FROM players p WHERE p.id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
        header("Location: jogadores_view.php");
        exit();
    }
    $stmt->bind_param("i", $player_id_to_edit);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        $edit_data = $res->fetch_assoc();
    } else {
        $_SESSION['error'] = "Jogador não encontrado para edição.";
    }
    $stmt->close();
}
?>