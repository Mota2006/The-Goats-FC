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

// Ensure user is logged in and has appropriate role for access
$current_login_id = $_SESSION['user_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Initialize variables
$editing = false;
$funcionario_data = [];
$funcionarios_list = [];

// Handle employee save/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Correctly get the employee's ID from the form, which is `funcionario_id` in the form
    $funcionario_id = $_POST['funcionario_id'] ?? null;

    // Authorization check for saving/updating employees
    // Only 'admin' or 'funcionario' (if they are editing their own profile) can edit
    if (!in_array($current_role, ['admin']) && !($current_role === 'funcionario' && $funcionario_id == $current_login_id)) {
        $_SESSION['error'] = "Você não tem permissão para realizar esta ação.";
        header("Location: funcionarios_view.php");
        exit();
    }

    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $birthdate = $_POST['birthdate'];
    $position = $_POST['position'];
    $nif = $_POST['nif'];

    if ($funcionario_id) { // If funcionario_id exists, it's an update
        // Update existing employee
        $stmt = $conn->prepare("UPDATE funcionarios SET name = ?, phone = ?, email = ?, birthdate = ?, position = ?, nif = ? WHERE id = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Erro na preparação da atualização: " . $conn->error;
        } else {
            $stmt->bind_param("ssssssi", $name, $phone, $email, $birthdate, $position, $nif, $funcionario_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Dados do funcionário atualizados com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao atualizar dados do funcionário: " . $stmt->error;
            }
            $stmt->close();
        }
    } else { // It's a new employee registration (unlikely to be done directly from this page, but good to have)
        // This part would typically be handled by the 'registo.php'
        $_SESSION['error'] = "A criação de novos funcionários é feita através da página de Registo.";
    }

    header("Location: funcionarios_view.php");
    exit();
}

// Handle employee deletion
if (isset($_GET['delete_funcionario'])) {
    $funcionario_id_to_delete = $_GET['delete_funcionario'];

    // Authorization check for deleting employees
    // Only 'admin' can delete employees
    if ($current_role !== 'admin') {
        $_SESSION['error'] = "Você não tem permissão para eliminar funcionários.";
        header("Location: funcionarios_view.php");
        exit();
    }

    // First, get the user_id associated with this funcionario_id
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM funcionarios WHERE id = ?");
    if ($stmt_get_user_id === false) {
        $_SESSION['error'] = "Erro na preparação para buscar user_id: " . $conn->error;
        header("Location: funcionarios_view.php");
        exit();
    }
    $stmt_get_user_id->bind_param("i", $funcionario_id_to_delete);
    $stmt_get_user_id->execute();
    $res_get_user_id = $stmt_get_user_id->get_result();
    $user_id_to_delete = null;
    if ($row_get_user_id = $res_get_user_id->fetch_assoc()) {
        $user_id_to_delete = $row_get_user_id['user_id'];
    }
    $stmt_get_user_id->close();

    if ($user_id_to_delete) {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Delete from 'funcionarios' table
            $stmt_delete_funcionario = $conn->prepare("DELETE FROM funcionarios WHERE id = ?");
            if ($stmt_delete_funcionario === false) {
                throw new Exception("Erro na preparação da exclusão do funcionário: " . $conn->error);
            }
            $stmt_delete_funcionario->bind_param("i", $funcionario_id_to_delete);
            $stmt_delete_funcionario->execute();
            $stmt_delete_funcionario->close();

            // Delete from 'users' table (which should cascade delete from 'funcionarios' if FK is set with ON DELETE CASCADE)
            // But we already deleted from funcionarios to ensure we have the user_id.
            // So now, just delete the user.
            $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt_delete_user === false) {
                throw new Exception("Erro na preparação da exclusão do utilizador: " . $conn->error);
            }
            $stmt_delete_user->bind_param("i", $user_id_to_delete);
            $stmt_delete_user->execute();
            $stmt_delete_user->close();

            $conn->commit();
            $_SESSION['success'] = "Funcionário e utilizador associado eliminados com sucesso.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Erro ao eliminar funcionário: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Funcionário não encontrado para eliminação.";
    }

    header("Location: funcionarios_view.php");
    exit();
}


// Handle fetching employee data for editing
if (isset($_GET['edit_funcionario'])) {
    $funcionario_id_to_edit = $_GET['edit_funcionario'];

    // Need to get the user_id associated with this funcionario_id to compare with $current_login_id
    $stmt_get_user_id = $conn->prepare("SELECT user_id FROM funcionarios WHERE id = ?");
    if ($stmt_get_user_id === false) {
        $_SESSION['error'] = "Erro na preparação para buscar user_id do funcionário para edição: " . $conn->error;
        header("Location: funcionarios_view.php");
        exit();
    }
    $stmt_get_user_id->bind_param("i", $funcionario_id_to_edit);
    $stmt_get_user_id->execute();
    $res_get_user_id = $stmt_get_user_id->get_result();
    $funcionario_user_id = null;
    if ($row_get_user_id = $res_get_user_id->fetch_assoc()) {
        $funcionario_user_id = $row_get_user_id['user_id'];
    }
    $stmt_get_user_id->close();


    if (!in_array($current_role, ['admin']) && !($current_role === 'funcionario' && $funcionario_user_id == $current_login_id)) {
        $_SESSION['error'] = "Você não tem permissão para editar este funcionário.";
        header("Location: funcionarios_view.php");
        exit();
    }

    $editing = true;
    // SELECT statement to fetch employee's data
    $stmt = $conn->prepare("SELECT f.*, u.username FROM funcionarios f JOIN users u ON f.user_id = u.id WHERE f.id = ? LIMIT 1");
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação para buscar dados de edição: " . $conn->error;
        header("Location: funcionarios_view.php");
        exit();
    }
    $stmt->bind_param("i", $funcionario_id_to_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $funcionario_data = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Funcionário não encontrado.";
        $editing = false;
        header("Location: funcionarios_view.php");
        exit();
    }
    $stmt->close();
}


// Fetch all employees for the list (always fetch)
// Only admin can see all employees. Funcionario can only see their own.
if ($current_role === 'admin') {
    $funcionarios_query = "SELECT f.*, u.username FROM funcionarios f JOIN users u ON f.user_id = u.id ORDER BY f.name ASC";
    $funcionarios_result = $conn->query($funcionarios_query);
    if ($funcionarios_result) {
        while ($row = $funcionarios_result->fetch_assoc()) {
            $funcionarios_list[] = $row;
        }
        $funcionarios_result->free();
    } else {
        $_SESSION['error'] = "Erro ao carregar lista de funcionários: " . $conn->error;
    }
} elseif ($current_role === 'funcionario' && $current_login_id) {
    // A 'funcionario' can only see their own data
    $funcionarios_query = "SELECT f.*, u.username FROM funcionarios f JOIN users u ON f.user_id = u.id WHERE f.user_id = ?";
    $stmt = $conn->prepare($funcionarios_query);
    if ($stmt === false) {
        $_SESSION['error'] = "Erro na preparação da consulta para funcionário: " . $conn->error;
    } else {
        $stmt->bind_param("i", $current_login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            if ($row = $result->fetch_assoc()) {
                $funcionarios_list[] = $row; // Add only their own data
            }
            $result->free();
        }
        $stmt->close();
    }
}
?>