<?php
// treinadores.php
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
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
$treinador_name_for_current_user = ''; // Initialize for display in view mode for 'treinador'
$current_treinador_id = null; // Initialize current treinador ID

// Determine the treinador_id and name associated with the user_id if the current role is 'treinador'
if ($current_role === 'treinador') {
    $stmt_get_treinador_id = $conn->prepare("SELECT id, name FROM treinadores WHERE user_id = ? LIMIT 1");
    if ($stmt_get_treinador_id) {
        $stmt_get_treinador_id->bind_param("i", $current_login_id);
        $stmt_get_treinador_id->execute();
        $res_treinador_id = $stmt_get_treinador_id->get_result();
        if ($row_treinador_id = $res_treinador_id->fetch_assoc()) {
            $current_treinador_id = $row_treinador_id['id'];
            $treinador_name_for_current_user = $row_treinador_id['name'];
        }
        $stmt_get_treinador_id->close();
    }
}

// Handle coach update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $treinador_id = $_POST['treinador_id'] ?? null;

    // Authorization check for saving/updating coaches
    // A coach can only update their own profile. Admin/Funcionario can update any.
    if (!in_array($current_role, ['admin', 'funcionario']) && !($current_role === 'treinador' && $treinador_id == $current_treinador_id)) {
        $_SESSION['error'] = "Você não tem permissão para realizar esta ação.";
        header("Location: treinadores_view.php");
        exit();
    }

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $birthdate = $_POST['birthdate'];
    $history = $_POST['history'];

    if ($treinador_id) {
        // Update existing coach
        $stmt = $conn->prepare("UPDATE treinadores SET name = ?, phone = ?, email = ?, birthdate = ?, history = ? WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Erro na preparação da atualização: " . $conn->error;
        } else {
            $stmt->bind_param("sssssi", $name, $phone, $email, $birthdate, $history, $treinador_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Treinador atualizado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao atualizar treinador: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        // *** MODIFICATION: Block for adding new coaches removed. ***
        // If the form is submitted without an ID, it's an invalid action now.
        $_SESSION['error'] = "Ação inválida. Apenas a edição de treinadores é permitida.";
    }
    header("Location: treinadores_view.php"); // Redirect to the view page
    exit();
}

// Handle coach deletion
if (isset($_GET['delete_treinador'])) {
    $id_to_delete = (int)$_GET['delete_treinador'];

    // Authorization check for deletion: Only admin/funcionario can delete any coach.
    if (!in_array($current_role, ['admin', 'funcionario'])) {
        $_SESSION['error'] = "Você não tem permissão para eliminar este treinador.";
        header("Location: treinadores_view.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        // Remove coach from any associated teams first
        $stmt_team_coaches = $conn->prepare("DELETE FROM team_coaches WHERE coach_id = ?");
        if ($stmt_team_coaches === false) {
            throw new mysqli_sql_exception("Erro na preparação para remover treinador das equipas: " . $conn->error);
        }
        $stmt_team_coaches->bind_param("i", $id_to_delete);
        $stmt_team_coaches->execute();
        $stmt_team_coaches->close();

        // Delete the coach's record
        $stmt_treinador = $conn->prepare("DELETE FROM treinadores WHERE id = ?");
        if ($stmt_treinador === false) {
            throw new mysqli_sql_exception("Erro na preparação para eliminar treinador: " . $conn->error);
        }
        $stmt_treinador->bind_param("i", $id_to_delete);
        if (!$stmt_treinador->execute()) {
            throw new mysqli_sql_exception("Erro ao eliminar treinador: " . $stmt_treinador->error);
        }
        $stmt_treinador->close();

        $conn->commit();
        $_SESSION['success'] = "Treinador eliminado com sucesso!";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro ao eliminar treinador: " . $e->getMessage();
    }

    header("Location: treinadores_view.php");
    exit();
}

// Logic to fetch coach data for editing if 'edit_treinador' parameter is present
$editing = false;
$edit_data = null;
if (isset($_GET['edit_treinador'])) {
    $id_to_edit = (int) $_GET['edit_treinador'];

    // Authorization check for fetching edit data
    // A coach can only fetch their own profile for editing. Admin/Funcionario can fetch any.
    if (!in_array($current_role, ['admin', 'funcionario']) && !($current_role === 'treinador' && $id_to_edit == $current_treinador_id)) {
        $_SESSION['error'] = "Você não tem permissão para editar este treinador.";
        header("Location: treinadores_view.php");
        exit();
    }

    $editing = true;
    $stmt = $conn->prepare("SELECT * FROM treinadores WHERE id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id_to_edit);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $edit_data = $res->fetch_assoc();
        } else {
            $_SESSION['error'] = "Treinador não encontrado para edição.";
        }
        $stmt->close();
    }
}

// Listar treinadores
// Always fetch all trainers for listing, regardless of role.
$sql_treinadores = "SELECT t.*, u.id as user_id FROM treinadores t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.name ASC";

$treinadores = $conn->query($sql_treinadores);

if ($treinadores === false) {
    $_SESSION['error'] = "Erro ao buscar treinadores: " . $conn->error;
    $treinadores = null; // Ensure $treinadores is null if query fails
}