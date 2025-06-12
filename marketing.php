<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

// Check if the current user is authorized to add/edit/delete marketing campaigns
$is_authorized = isset($_SESSION['role_login']) && ($_SESSION['role_login'] === 'admin' || $_SESSION['role_login'] === 'funcionario');

// Insert new marketing campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_campaign']) && $is_authorized) {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    if (!empty($title) && !empty($type) && !empty($date)) {
        $stmt = $conn->prepare("INSERT INTO marketing (title, type, date, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $type, $date, $description);
        $stmt->execute();
        $_SESSION['success'] = "Campanha de marketing adicionada!";
        $stmt->close();
    } else {
        $_SESSION['error'] = "Preencha todos os campos corretamente.";
    }
    header("Location: marketing_view.php");
    exit();
}

// Delete marketing campaign
if (isset($_GET['delete']) && $is_authorized) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM marketing WHERE id = $id");
    $_SESSION['success'] = "Campanha eliminada.";
    header("Location: marketing_view.php");
    exit();
}

// Edit marketing campaign - Fetch data
$editing = false;
$edit_data = null;
if (isset($_GET['edit']) && $is_authorized) {
    $id = (int) $_GET['edit'];
    $res = $conn->query("SELECT * FROM marketing WHERE id = $id LIMIT 1");
    if ($res->num_rows === 1) {
        $edit_data = $res->fetch_assoc();
        $editing = true;
    } else {
        $_SESSION['error'] = "Campanha não encontrada.";
        header("Location: marketing_view.php");
        exit();
    }
}

// Update marketing campaign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_campaign']) && $is_authorized) {
    $id = (int) $_POST['id'];
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];
    $description = $_POST['description'];

    if (!empty($title) && !empty($type) && !empty($date)) {
        $stmt = $conn->prepare("UPDATE marketing SET title=?, type=?, date=?, description=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $type, $date, $description, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Campanha atualizada!";
    } else {
        $_SESSION['error'] = "Preencha todos os campos corretamente.";
    }

    header("Location: marketing_view.php");
    exit();
}

// Fetch all marketing campaigns for display
$marketing_campaigns = $conn->query("SELECT * FROM marketing ORDER BY date DESC, created_at DESC");

// Close connection (optional here, as it will be closed in _view.php, but good practice)
// $conn->close();
?>