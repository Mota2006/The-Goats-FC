<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

// Check if the current user is authorized to add/edit/delete partnerships
$is_authorized = isset($_SESSION['role_login']) && ($_SESSION['role_login'] === 'admin' || $_SESSION['role_login'] === 'funcionario');

// Insert new partnership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_partnership']) && $is_authorized) {
    $nome_entidade = $_POST['nome_entidade'];
    $type = $_POST['type'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $description = $_POST['description'];

    if (!empty($nome_entidade) && !empty($type) && !empty($data_inicio)) {
        $stmt = $conn->prepare("INSERT INTO parcerias (nome_entidade, tipo, data_inicio, data_fim, descricao) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nome_entidade, $type, $data_inicio, $data_fim, $description);
        $stmt->execute();
        $_SESSION['success'] = "Parceria adicionada com sucesso!";
        $stmt->close();
    } else {
        $_SESSION['error'] = "Preencha todos os campos obrigatórios corretamente.";
    }
    header("Location: parcerias_view.php");
    exit();
}

// Delete partnership
if (isset($_GET['delete']) && $is_authorized) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM parcerias WHERE id = $id");
    $_SESSION['success'] = "Parceria eliminada.";
    header("Location: parcerias_view.php");
    exit();
}

// Edit partnership - Fetch data
$editing = false;
$edit_data = null;
if (isset($_GET['edit']) && $is_authorized) {
    $id = (int) $_GET['edit'];
    $res = $conn->query("SELECT * FROM parcerias WHERE id = $id LIMIT 1");
    if ($res->num_rows === 1) {
        $edit_data = $res->fetch_assoc();
        $editing = true;
    } else {
        $_SESSION['error'] = "Parceria não encontrada.";
        header("Location: parcerias_view.php");
        exit();
    }
}

// Update partnership
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_partnership']) && $is_authorized) {
    $id = (int) $_POST['id'];
    $nome_entidade = $_POST['nome_entidade'];
    $type = $_POST['type'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $description = $_POST['description'];

    if (!empty($nome_entidade) && !empty($type) && !empty($data_inicio)) {
        $stmt = $conn->prepare("UPDATE parcerias SET nome_entidade=?, tipo=?, data_inicio=?, data_fim=?, descricao=? WHERE id=?");
        $stmt->bind_param("sssssi", $nome_entidade, $type, $data_inicio, $data_fim, $description, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Parceria atualizada!";
    } else {
        $_SESSION['error'] = "Preencha todos os campos obrigatórios corretamente.";
    }

    header("Location: parcerias_view.php");
    exit();
}

// Fetch all partnerships for display
$parcerias = $conn->query("SELECT * FROM parcerias ORDER BY data_inicio DESC");

// Close connection (optional here, as it will be closed in _view.php, but good practice)
// $conn->close();
?>