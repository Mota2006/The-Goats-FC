<?php
session_start();
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Handle adding a player to a team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_player_to_team'])) {
    // Authorization check for adding players
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para adicionar jogadores a equipas.";
        header("Location: equipas_view.php");
        exit();
    }

    $player_id_to_add = $_POST['player_id'];
    $target_team_id = $_POST['target_team_id'];

    // First, remove player from any existing team
    $delete_existing_assignment = $conn->prepare("DELETE FROM team_players WHERE player_id = ?");
    $delete_existing_assignment->bind_param("i", $player_id_to_add);
    $delete_existing_assignment->execute();
    $delete_existing_assignment->close();

    // Then, add player to the new team
    $insert_new_assignment = $conn->prepare("INSERT INTO team_players (team_id, player_id) VALUES (?, ?)");
    $insert_new_assignment->bind_param("ii", $target_team_id, $player_id_to_add);
    $insert_new_assignment->execute();
    $insert_new_assignment->close();

    // Redirect to prevent form resubmission and show updated list
    header("Location: equipas_view.php?team_id=" . htmlspecialchars($target_team_id));
    exit();
}

// Handle adding a coach to a team
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coach_to_team'])) {
    // Authorization check for adding coaches
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para adicionar treinadores a equipas.";
        header("Location: equipas_view.php");
        exit();
    }

    $coach_id_to_add = $_POST['coach_id'];
    $target_team_id = $_POST['target_team_id'];

    // Optional: Remove coach from any existing team first
    $delete_existing_coach_assignment = $conn->prepare("DELETE FROM team_coaches WHERE coach_id = ?");
    $delete_existing_coach_assignment->bind_param("i", $coach_id_to_add);
    $delete_existing_coach_assignment->execute();
    $delete_existing_coach_assignment->close();

    // Add coach to the new team
    $insert_new_coach_assignment = $conn->prepare("INSERT INTO team_coaches (team_id, coach_id) VALUES (?, ?)");
    $insert_new_coach_assignment->bind_param("ii", $target_team_id, $coach_id_to_add);
    if ($insert_new_coach_assignment->execute()) {
        $_SESSION['success'] = "Treinador adicionado à equipa com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar treinador à equipa: " . $insert_new_coach_assignment->error;
    }
    $insert_new_coach_assignment->close();

    header("Location: equipas_view.php?team_id=" . htmlspecialchars($target_team_id));
    exit();
}

// Handle removing a player from a team
if (isset($_GET['remove_player_from_team'])) {
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para remover jogadores de equipas.";
        header("Location: equipas_view.php");
        exit();
    }

    $player_id_to_remove = $_GET['remove_player_from_team'];
    $team_id = $_GET['team_id'];

    $delete_assignment = $conn->prepare("DELETE FROM team_players WHERE player_id = ? AND team_id = ?");
    $delete_assignment->bind_param("ii", $player_id_to_remove, $team_id);
    if ($delete_assignment->execute()) {
        $_SESSION['success'] = "Jogador removido da equipa com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao remover jogador da equipa: " . $delete_assignment->error;
    }
    $delete_assignment->close();

    header("Location: equipas_view.php?team_id=" . htmlspecialchars($team_id));
    exit();
}

// Handle removing a coach from a team
if (isset($_GET['remove_coach_from_team'])) {
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para remover treinadores de equipas.";
        header("Location: equipas_view.php");
        exit();
    }

    $coach_id_to_remove = $_GET['remove_coach_from_team'];
    $team_id = $_GET['team_id'];

    $delete_assignment = $conn->prepare("DELETE FROM team_coaches WHERE coach_id = ? AND team_id = ?");
    $delete_assignment->bind_param("ii", $coach_id_to_remove, $team_id);
    if ($delete_assignment->execute()) {
        $_SESSION['success'] = "Treinador removido da equipa com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao remover treinador da equipa: " . $delete_assignment->error;
    }
    $delete_assignment->close();

    header("Location: equipas_view.php?team_id=" . htmlspecialchars($team_id));
    exit();
}
?>