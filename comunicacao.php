<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

// Prepare to get user_id corresponding to current login_id
$current_login_id = $_SESSION['login_id'] ?? 0;
$current_user_role = $_SESSION['role_login'] ?? '';
$is_admin = ($current_user_role === 'admin');
$current_username = $_SESSION['username'] ?? ''; // Obter o username da sessão

$user_id_stmt = $conn->prepare("SELECT user_id FROM login WHERE login_id = ?");
$user_id_stmt->bind_param("i", $current_login_id);
$user_id_stmt->execute();
$user_id_result = $user_id_stmt->get_result();
$current_user_id = 0;
if ($row = $user_id_result->fetch_assoc()) {
    $current_user_id = $row['user_id'];
}
$user_id_stmt->close();

// Selecionar usernames para o dropdown, excluindo o utilizador atual e o admin
$utilizadores = $conn->prepare("
    SELECT username FROM users
    WHERE id != ? AND username != 'admin'
    ORDER BY username
");
$utilizadores->bind_param("i", $current_user_id);
$utilizadores->execute();
$res_utilizadores = $utilizadores->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_target = trim($_POST['recipient']);
    $content = trim($_POST['content']);
    $sender_username = $current_username;

    if (!empty($recipient_target) && !empty($content)) {
        // Verifica se é uma mensagem para um grupo
        if (strpos($recipient_target, 'group_') === 0) {
            $role = substr($recipient_target, 6); // Extrai o nome do grupo (ex: 'jogador')
            
            // Procura todos os utilizadores nesse grupo, exceto o próprio remetente
            $users_stmt = $conn->prepare("SELECT username FROM users WHERE role = ? AND username != ?");
            $users_stmt->bind_param("ss", $role, $sender_username);
            $users_stmt->execute();
            $recipients_result = $users_stmt->get_result();

            if ($recipients_result->num_rows > 0) {
                $conn->begin_transaction();
                try {
                    $insert_stmt = $conn->prepare("INSERT INTO messages (sender, recipient, content) VALUES (?, ?, ?)");
                    $sent_count = 0;
                    while ($user_row = $recipients_result->fetch_assoc()) {
                        $recipient_username = $user_row['username'];
                        $insert_stmt->bind_param("sss", $sender_username, $recipient_username, $content);
                        $insert_stmt->execute();
                        if ($insert_stmt->affected_rows > 0) {
                            $sent_count++;
                        }
                    }
                    $conn->commit();
                    $_SESSION['success'] = "Mensagem enviada para $sent_count membro(s) do grupo '$role'.";
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    $_SESSION['error'] = "Erro ao enviar mensagem para o grupo: " . $exception->getMessage();
                }
                $insert_stmt->close();
            } else {
                $_SESSION['error'] = "Nenhum utilizador encontrado no grupo selecionado.";
            }
            $users_stmt->close();
        } else {
            // Lógica original para enviar para um único destinatário
            $stmt = $conn->prepare("INSERT INTO messages (sender, recipient, content) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $sender_username, $recipient_target, $content);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $_SESSION['success'] = "Mensagem enviada com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao enviar mensagem.";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error'] = "Preencha todos os campos.";
    }

    header("Location: comunicacao_view.php");
    exit();
}

// Mensagens recebidas
$mensagens_recebidas = $conn->prepare("
    SELECT * FROM messages WHERE recipient = ? ORDER BY date DESC
");
$mensagens_recebidas->bind_param("s", $current_username);
$mensagens_recebidas->execute();
$res_recebidas = $mensagens_recebidas->get_result();

// Mensagens enviadas
$mensagens_enviadas = $conn->prepare("
    SELECT * FROM messages WHERE sender = ? ORDER BY date DESC
");
$mensagens_enviadas->bind_param("s", $current_username);
$mensagens_enviadas->execute();
$res_enviadas = $mensagens_enviadas->get_result();

// Todas as mensagens (admin apenas)
if ($is_admin) {
    $res_todas = $conn->query("SELECT * FROM messages ORDER BY date DESC");
}
?>