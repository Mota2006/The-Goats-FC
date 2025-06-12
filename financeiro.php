<?php
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) die("Erro na ligação: " . $conn->connect_error);

// Inserir nova transação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_finance'])) {
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];

    if (!empty($type) && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO finance (type, amount, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $type, $amount, $description);
        $stmt->execute();
        $_SESSION['success'] = "Registo financeiro adicionado!";
        $stmt->close();
    } else {
        $_SESSION['error'] = "Preencha todos os campos corretamente.";
    }
    header("Location: financeiro_view.php");
    exit();
}

// Eliminar transação
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Verificar se é um pagamento que não pode ser alterado
    $res = $conn->query("SELECT type, description FROM finance WHERE id = $id LIMIT 1");
    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $isPayment = ($row['type'] === 'Receita' && 
                     (stripos($row['description'], 'jogador') !== false || 
                      stripos($row['description'], 'atleta') !== false ||
                      stripos($row['description'], 'sócio') !== false ||
                      stripos($row['description'], 'socio') !== false ||
                      stripos($row['description'], 'pagamento') !== false));
        
        if ($isPayment) {
            $_SESSION['error'] = "Não é permitido eliminar este tipo de registo.";
            header("Location: financeiro_view.php");
            exit();
        }
    }
    
    $conn->query("DELETE FROM finance WHERE id = $id");
    $_SESSION['success'] = "Registo eliminado.";
    header("Location: financeiro_view.php");
    exit();
}

// Editar transação
$editing = false;
$edit_data = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $res = $conn->query("SELECT * FROM finance WHERE id = $id LIMIT 1");
    if ($res->num_rows === 1) {
        $edit_data = $res->fetch_assoc();
        // Verificar se é um pagamento que não pode ser alterado
        $isPayment = ($edit_data['type'] === 'Receita' && 
                     (stripos($edit_data['description'], 'jogador') !== false || 
                      stripos($edit_data['description'], 'atleta') !== false ||
                      stripos($edit_data['description'], 'sócio') !== false ||
                      stripos($edit_data['description'], 'socio') !== false ||
                      stripos($edit_data['description'], 'pagamento') !== false));
        
        if ($isPayment) {
            $_SESSION['error'] = "Não é permitido editar este tipo de registo.";
            header("Location: financeiro_view.php");
            exit();
        }
        $editing = true;
    } else {
        $_SESSION['error'] = "Registo não encontrado.";
        header("Location: financeiro_view.php");
        exit();
    }
}

// Atualizar registo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_finance'])) {
    $id = (int) $_POST['id'];
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];

    if (!empty($type) && $amount > 0) {
        $stmt = $conn->prepare("UPDATE finance SET type=?, amount=?, description=? WHERE id=?");
        $stmt->bind_param("sdsi", $type, $amount, $description, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Registo atualizado!";
    } else {
        $_SESSION['error'] = "Preencha todos os campos corretamente.";
    }

    header("Location: financeiro_view.php");
    exit();
}

// Calcular saldo
$sum_result = $conn->query("
    SELECT 
        SUM(CASE WHEN type = 'Receita' THEN amount ELSE 0 END) AS total_receitas,
        SUM(CASE WHEN type = 'Despesa' THEN amount ELSE 0 END) AS total_despesas
    FROM finance
");

$totais = $sum_result->fetch_assoc();
$receitas = floatval($totais['total_receitas']);
$despesas = floatval($totais['total_despesas']);
$saldo = $receitas - $despesas;

?>