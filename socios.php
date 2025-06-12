<?php
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

// Ensure $_SESSION variables are available
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_login_id = $_SESSION['user_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Handle socio save/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_socio'])) {
    $socio_id = $_POST['socio_id'] ?? null;

    // Determine the user_id associated with the socio_id for authorization
    $socio_user_id = null;
    if ($socio_id) {
        $stmt_get_user_id = $conn->prepare("SELECT user_id FROM socios WHERE id = ?");
        $stmt_get_user_id->bind_param("i", $socio_id);
        $stmt_get_user_id->execute();
        $res_get_user_id = $stmt_get_user_id->get_result();
        if ($row_get_user_id = $res_get_user_id->fetch_assoc()) {
            $socio_user_id = $row_get_user_id['user_id'];
        }
        $stmt_get_user_id->close();
    }


    // Authorization check for saving/updating socios
    // Admin and funcionario can edit/save any. Socio can only edit their own profile.
    if (!in_array($current_role, ['admin', 'funcionario']) && !($current_role === 'socio' && $socio_user_id == $current_login_id)) {
        $_SESSION['error'] = "Você não tem permissão para realizar esta ação.";
        header("Location: socios_view.php");
        exit();
    }

    $nome = $_POST['nome'];
    $data_nascimento = $_POST['data_nascimento'];
    $contacto = $_POST['contacto'];
    $email = $_POST['email'];
    $morada = $_POST['morada'];
    $nif = $_POST['nif'];

    if ($socio_id) {
        // Update existing socio
        $stmt = $conn->prepare("UPDATE socios SET nome = ?, data_nascimento = ?, contacto = ?, email = ?, morada = ?, nif = ? WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Erro na preparação da atualização: " . $conn->error;
        } else {
            $stmt->bind_param("ssssssi", $nome, $data_nascimento, $contacto, $email, $morada, $nif, $socio_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Sócio atualizado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao atualizar sócio: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    // The 'else' block for inserting new socios is removed as registration is now handled by registo.php
    header("Location: socios_view.php");
    exit();
}

// Handle socio deletion
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];

    // Get the user_id associated with the socio before deleting the socio
    $socio_user_id_associated = null;
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM socios WHERE id = ?");
    if ($stmt_get_user_id === false) {
        $_SESSION['error'] = "Erro na preparação para obter user_id do sócio: " . $conn->error;
        header("Location: socios_view.php");
        exit();
    }
    $stmt_get_user_id->bind_param("i", $id_to_delete);
    $stmt_get_user_id->execute();
    $res_user_id = $stmt_get_user_id->get_result();
    if ($row_user_id = $res_user_id->fetch_assoc()) {
        $socio_user_id_associated = $row_user_id['user_id'];
    }
    $stmt_get_user_id->close();


    // Authorization check for deleting socios
    // Admin and funcionario can delete any. Socio cannot delete.
    if (!in_array($current_role, ['admin', 'funcionario'])) { // Removed socio self-delete as per typical app logic
        $_SESSION['error'] = "Você não tem permissão para eliminar sócios.";
        header("Location: socios_view.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        // Delete from quotas_socios first (due to foreign key constraint)
        $stmt_quotas = $conn->prepare("DELETE FROM quotas_socios WHERE socio_id = ?");
        if ($stmt_quotas === false) {
            throw new mysqli_sql_exception("Erro na preparação da exclusão das quotas: " . $conn->error);
        }
        $stmt_quotas->bind_param("i", $id_to_delete);
        if (!$stmt_quotas->execute()) {
            throw new mysqli_sql_exception("Erro ao excluir quotas do sócio: " . $stmt_quotas->error);
        }
        $stmt_quotas->close();

        // Then delete from socios table
        $stmt_socio = $conn->prepare("DELETE FROM socios WHERE id = ?");
        if ($stmt_socio === false) {
            throw new mysqli_sql_exception("Erro na preparação da exclusão do sócio: " . $conn->error);
        }
        $stmt_socio->bind_param("i", $id_to_delete);
        if (!$stmt_socio->execute()) {
            throw new mysqli_sql_exception("Erro ao excluir sócio: " . $stmt_socio->error);
        }
        $stmt_socio->close();

        // Finally, delete from users table if a user_id was associated
        if ($socio_user_id_associated) {
            $stmt_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt_user === false) {
                throw new mysqli_sql_exception("Erro na preparação da exclusão do utilizador: " . $conn->error);
            }
            $stmt_user->bind_param("i", $socio_user_id_associated);
            if (!$stmt_user->execute()) {
                throw new mysqli_sql_exception("Erro ao excluir utilizador: " . $stmt_user->error);
            }
            $stmt_user->close();
        }

        $conn->commit();
        $_SESSION['success'] = "Sócio eliminado com sucesso!";
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro ao eliminar sócio: " . $e->getMessage();
    }

    header("Location: socios_view.php");
    exit();
}

// Logic to fetch socio data for editing if 'edit_socio' parameter is present
$editing = false;
$edit_data = null;
if (isset($_GET['edit_socio'])) {
    $id_to_edit = (int) $_GET['edit_socio']; // Renamed for clarity

    // Get the user_id associated with this socio_id to compare with $current_login_id
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM socios WHERE id = ?");
    $stmt_get_user_id->bind_param("i", $id_to_edit);
    $stmt_get_user_id->execute();
    $res_get_user_id = $stmt_get_user_id->get_result();
    $socio_user_id_for_edit = null;
    if ($row_get_user_id = $res_get_user_id->fetch_assoc()) {
        $socio_user_id_for_edit = $row_get_user_id['user_id'];
    }
    $stmt_get_user_id->close();

    // Authorization check for fetching edit data
    // Admin and funcionario can edit any. Socio can only edit their own profile.
    if (!in_array($current_role, ['admin', 'funcionario']) && !($current_role === 'socio' && $socio_user_id_for_edit == $current_login_id)) {
        $_SESSION['error'] = "Você não tem permissão para editar este sócio.";
        header("Location: socios_view.php");
        exit();
    }

    $editing = true;
    $stmt = $conn->prepare("SELECT * FROM socios WHERE id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
        header("Location: socios_view.php");
        exit();
    } else {
        $stmt->bind_param("i", $id_to_edit);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 1) {
            $edit_data = $res->fetch_assoc();
        } else {
            $_SESSION['error'] = "Sócio não encontrado para edição.";
        }
        $stmt->close();
    }
}

// Listar sócios
// For 'socio' role, only fetch their own record if not editing.
// If an admin/funcionario is viewing, fetch all socios.
$sql_socios = "SELECT * FROM socios ORDER BY nome ASC";
if ($current_role === 'socio' && !$editing) {
    // If a socio is viewing their own page and not in edit mode, they should only see their own data
    $sql_socios = "SELECT * FROM socios WHERE user_id = " . (int)$current_login_id;
}
$socios = $conn->query($sql_socios);

?>