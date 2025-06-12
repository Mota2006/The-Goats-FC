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
$current_login_id = $_SESSION['user_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? '';

$editing = false;
$edit_data = null;

// Handle schedule save/update
if (isset($_POST['add_event'])) {
    $event_id = $_POST['event_id'] ?? null;
    $type = $_POST['type'];
    $team_id = $_POST['team_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $description = $_POST['description'];
    $opponent = $_POST['opponent'] ?? null; // Only applicable for 'Jogo' type

    // Authorization check for saving/updating: Ensure the coach is linked to the team if they are a coach
    if ($current_role === 'treinador') {
        $stmt_check_coach_team = $conn->prepare("SELECT COUNT(*) FROM team_coaches tc JOIN treinadores tr ON tc.coach_id = tr.id WHERE tr.user_id = ? AND tc.team_id = ?");
        if ($stmt_check_coach_team) {
            $stmt_check_coach_team->bind_param("ii", $current_login_id, $team_id);
            $stmt_check_coach_team->execute();
            $stmt_check_coach_team->bind_result($count_teams);
            $stmt_check_coach_team->fetch();
            $stmt_check_coach_team->close();
            if ($count_teams == 0) {
                $_SESSION['error'] = "Você não tem permissão para agendar eventos para esta equipa.";
                header("Location: jogos_treinos_view.php");
                exit();
            }
        } else {
            error_log("Erro na preparação para verificar equipa do treinador: " . $conn->error);
        }
    }


    if ($event_id) {
        // Update existing event
        $stmt = $conn->prepare("UPDATE schedule SET type = ?, team_id = ?, date = ?, time = ?, description = ?, opponent = ? WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Erro na preparação da atualização: " . $conn->error;
        } else {
            $stmt->bind_param("sissssi", $type, $team_id, $date, $time, $description, $opponent, $event_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Evento atualizado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao atualizar evento: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        // Add new event
        $stmt = $conn->prepare("INSERT INTO schedule (type, team_id, date, time, description, opponent) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $_SESSION['error'] = "Erro na preparação: " . $conn->error;
        } else {
            $stmt->bind_param("sissss", $type, $team_id, $date, $time, $description, $opponent);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Evento adicionado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao adicionar evento: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header("Location: jogos_treinos_view.php");
    exit();
}

// Handle event deletion
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];

    // Get the team_id associated with the event to check authorization
    $event_team_id_associated = null;
    $stmt_get_event_team_id = $conn->prepare("SELECT team_id FROM schedule WHERE id = ?");
    if ($stmt_get_event_team_id === false) {
        $_SESSION['error'] = "Erro na preparação para obter team_id do evento: " . $conn->error;
        header("Location: jogos_treinos_view.php");
        exit();
    }
    $stmt_get_event_team_id->bind_param("i", $id_to_delete);
    $stmt_get_event_team_id->execute();
    $res_event_team_id = $stmt_get_event_team_id->get_result();
    if ($row_event_team_id = $res_event_team_id->fetch_assoc()) {
        $event_team_id_associated = $row_event_team_id['team_id'];
    }
    $stmt_get_event_team_id->close();


    // Authorization: Admin and funcionario can delete any. Coach can only delete events for their linked teams.
    if ($current_role === 'treinador') {
        $stmt_check_coach_team = $conn->prepare("SELECT COUNT(*) FROM team_coaches tc JOIN treinadores tr ON tc.coach_id = tr.id WHERE tr.user_id = ? AND tc.team_id = ?");
        if ($stmt_check_coach_team) {
            $stmt_check_coach_team->bind_param("ii", $current_login_id, $event_team_id_associated);
            $stmt_check_coach_team->execute();
            $stmt_check_coach_team->bind_result($count_teams);
            $stmt_check_coach_team->fetch();
            $stmt_check_coach_team->close();
            if ($count_teams == 0) {
                $_SESSION['error'] = "Você não tem permissão para eliminar este evento.";
                header("Location: jogos_treinos_view.php");
                exit();
            }
        } else {
            error_log("Erro na preparação para verificar equipa do treinador para eliminar: " . $conn->error);
            $_SESSION['error'] = "Erro de autorização ao tentar eliminar evento.";
            header("Location: jogos_treinos_view.php");
            exit();
        }
    }


    $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ?");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação da exclusão: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Evento eliminado.";
        } else {
            $_SESSION['error'] = "Erro ao eliminar evento: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: jogos_treinos_view.php");
    exit();
}

// Logic to fetch event data for editing if 'edit_event' parameter is present
if (isset($_GET['edit_event'])) {
    $editing = true;
    $id = (int)$_GET['edit_event'];

    // Get the team_id associated with this event_id to compare with coach's linked teams
    $stmt_get_team_id_for_edit = $conn->prepare("SELECT team_id FROM schedule WHERE id = ?");
    if ($stmt_get_team_id_for_edit) {
        $stmt_get_team_id_for_edit->bind_param("i", $id);
        $stmt_get_team_id_for_edit->execute();
        $res_get_team_id_for_edit = $stmt_get_team_id_for_edit->get_result();
        $event_team_id_for_edit = null;
        if ($row_get_team_id_for_edit = $res_get_team_id_for_edit->fetch_assoc()) {
            $event_team_id_for_edit = $row_get_team_id_for_edit['team_id'];
        }
        $stmt_get_team_id_for_edit->close();
    } else {
        error_log("Erro na preparação para obter team_id do evento para edição: " . $conn->error);
    }

    // Authorization: Admin and funcionario can edit any. Coach can only edit events for their linked teams.
    if ($current_role === 'treinador') {
        $stmt_check_coach_team = $conn->prepare("SELECT COUNT(*) FROM team_coaches tc JOIN treinadores tr ON tc.coach_id = tr.id WHERE tr.user_id = ? AND tc.team_id = ?");
        if ($stmt_check_coach_team) {
            $stmt_check_coach_team->bind_param("ii", $current_login_id, $event_team_id_for_edit);
            $stmt_check_coach_team->execute();
            $stmt_check_coach_team->bind_result($count_teams);
            $stmt_check_coach_team->fetch();
            $stmt_check_coach_team->close();
            if ($count_teams == 0) {
                $_SESSION['error'] = "Você não tem permissão para editar este evento.";
                header("Location: jogos_treinos_view.php");
                exit();
            }
        } else {
            error_log("Erro na preparação para verificar equipa do treinador para editar: " . $conn->error);
            $_SESSION['error'] = "Erro de autorização ao tentar editar evento.";
            header("Location: jogos_treinos_view.php");
            exit();
        }
    }


    $stmt = $conn->prepare("SELECT * FROM schedule WHERE id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $edit_data = $res->fetch_assoc();
        } else {
            $_SESSION['error'] = "Evento não encontrado para edição.";
        }
        $stmt->close();
    }
}

// List teams for the dropdown
$sql_teams_dropdown = "SELECT id, name FROM teams ORDER BY id ASC";
if ($current_role === 'treinador') {
    // Get the coach_id first based on user_id
    $coach_id = null;
    $stmt_get_coach_id = $conn->prepare("SELECT id FROM treinadores WHERE user_id = ? LIMIT 1");
    if ($stmt_get_coach_id) {
        $stmt_get_coach_id->bind_param("i", $current_login_id);
        $stmt_get_coach_id->execute();
        $res_coach_id = $stmt_get_coach_id->get_result();
        if ($row_coach_id = $res_coach_id->fetch_assoc()) {
            $coach_id = $row_coach_id['id'];
        }
        $stmt_get_coach_id->close();
    } else {
        error_log("Erro na preparação para obter ID do treinador: " . $conn->error);
    }

    if ($coach_id !== null) {
        // Filter teams by the coach's linked teams
        $sql_teams_dropdown = "SELECT t.id, t.name FROM teams t JOIN team_coaches tc ON t.id = tc.team_id WHERE tc.coach_id = ? ORDER BY t.name ASC";
        $stmt_teams = $conn->prepare($sql_teams_dropdown);
        if ($stmt_teams) {
            $stmt_teams->bind_param("i", $coach_id);
            $stmt_teams->execute();
            $teams = $stmt_teams->get_result();
            $stmt_teams->close();
        } else {
            $teams = new mysqli_result(new mysqli());
            error_log("Error fetching teams for dropdown (coach filtered): " . $conn->error);
        }
    } else {
        $teams = new mysqli_result(new mysqli()); // No coach_id found, so no teams
    }
} else {
    $teams = $conn->query($sql_teams_dropdown);
}

if ($teams === false) {
    $teams = new mysqli_result(new mysqli());
    error_log("Error fetching teams for dropdown: " . $conn->error);
}

// List scheduled events
$sql_schedule = "SELECT s.*, t.name as team_name FROM schedule s JOIN teams t ON s.team_id = t.id";

// If current role is 'treinador', filter events by their linked teams
if ($current_role === 'treinador') {
    $coach_id = null;
    $stmt_get_coach_id = $conn->prepare("SELECT id FROM treinadores WHERE user_id = ? LIMIT 1");
    if ($stmt_get_coach_id) {
        $stmt_get_coach_id->bind_param("i", $current_login_id);
        $stmt_get_coach_id->execute();
        $res_coach_id = $stmt_get_coach_id->get_result();
        if ($row_coach_id = $res_coach_id->fetch_assoc()) {
            $coach_id = $row_coach_id['id'];
        }
        $stmt_get_coach_id->close();
    }

    if ($coach_id !== null) {
        $sql_schedule .= " JOIN team_coaches tc ON s.team_id = tc.team_id WHERE tc.coach_id = " . (int)$coach_id;
    } else {
        // If coach_id is not found, no events should be displayed for this coach.
        // This effectively makes the query return no results.
        $sql_schedule .= " WHERE 1=0";
    }
}


// Filter by type if set
$filter_type = $_GET['filter_type'] ?? 'all';
if ($filter_type !== 'all') {
    if (strpos($sql_schedule, 'WHERE') === false) {
        $sql_schedule .= " WHERE";
    } else {
        $sql_schedule .= " AND";
    }
    $sql_schedule .= " s.type = '" . $conn->real_escape_string($filter_type) . "'";
}

$sql_schedule .= " ORDER BY s.date DESC, s.time DESC";

$schedule = $conn->query($sql_schedule);

if ($schedule === false) {
    $schedule = new mysqli_result(new mysqli());
    error_log("Error fetching schedule: " . $conn->error);
}
?>