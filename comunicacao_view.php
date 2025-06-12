<?php
session_start();
// Ligação à base de dados
$conn = new mysqli("localhost", "root", "", "the goats fc");

// Incluir a lógica
require_once 'comunicacao.php';

$current_user = $_SESSION['username'] ?? ''; // Certifique-se de que o username está na sessão
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>The Goats FC</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link rel="stylesheet" href="style.css">
    <script>
        // JavaScript for displaying and clearing notifications
        document.addEventListener('DOMContentLoaded', function() {
            const notificationDiv = document.getElementById('notification');
            <?php if (isset($_SESSION['success'])): ?>
                notificationDiv.textContent = "<?= htmlspecialchars($_SESSION['success']) ?>";
                notificationDiv.className = 'notification success';
                notificationDiv.style.display = 'block';
                setTimeout(() => {
                    notificationDiv.style.display = 'none';
                    notificationDiv.textContent = '';
                }, 5000); // Hide after 5 seconds
                <?php unset($_SESSION['success']); ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                notificationDiv.textContent = "<?= htmlspecialchars($_SESSION['error']) ?>";
                notificationDiv.className = 'notification error';
                notificationDiv.style.display = 'block';
                setTimeout(() => {
                    notificationDiv.style.display = 'none';
                    notificationDiv.textContent = '';
                }, 5000); // Hide after 5 seconds
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
    </script>
    <style>
        /* Basic styling for notifications */
        .notification {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            display: none; /* Hidden by default */
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php $is_admin = ($current_role === 'admin'); ?>
    <header>
        Gestão do Clube
    </header>

    <?php include 'navbar.php'; ?>

    <main>
        <div id="notification" role="alert"></div>

        <section id="communication-section" class="active" aria-label="Comunicação">
            <h2>Comunicação Interna</h2>
            <?php if (!$is_admin): ?>
                <form action="comunicacao.php" method="post">
                    <label>Destinatário
                        <select name="recipient" required>
                            <option value="">-- Selecione Destinatário ou Grupo --</option>
                            <optgroup label="Grupos">
                                <option value="group_jogador">Todos os Jogadores</option>
                                <option value="group_treinador">Todos os Treinadores</option>
                                <option value="group_socio">Todos os Sócios</option>
                                <option value="group_funcionario">Todos os Funcionários</option>
                                <option value="group_adepto">Todos os Adeptos</option>
                            </optgroup>
                            <optgroup label="Utilizadores Individuais">
                                <?php
                                // Reset the pointer for utilizadores result set if it's already been iterated
                                if ($res_utilizadores->num_rows > 0) {
                                    $res_utilizadores->data_seek(0);
                                }
                                while ($u = $res_utilizadores->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($u['username']) ?>">
                                    <?= htmlspecialchars($u['username']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </label>
                    <label>Mensagem
                        <textarea name="content" rows="4" cols="50" required></textarea>
                    </label>
                    <div>
                        <button type="submit" class="submit-btn" name="send_message">Enviar</button>
                    </div>
                </form>
                <h2>Mensagens Recebidas</h2>
                <table>
                    <tr>
                        <th>Data</th>
                        <th>De</th>
                        <th>Mensagem</th>
                    </tr>
                    <?php while ($row = $res_recebidas->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['sender']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                <h2>Mensagens Enviadas</h2>
                <table>
                    <tr>
                        <th>Data</th>
                        <th>Para</th>
                        <th>Mensagem</th>
                    </tr>
                    <?php while ($row = $res_enviadas->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['recipient']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <h2>Todas as Mensagens (Admin)</h2>
                <table>
                    <tr>
                        <th>Data</th>
                        <th>De</th>
                        <th>Para</th>
                        <th>Mensagem</th>
                    </tr>
                    <?php
                    // Reset the pointer for res_todas result set if it's already been iterated
                    if (isset($res_todas) && $res_todas->num_rows > 0) {
                        $res_todas->data_seek(0);
                    }
                    while (isset($res_todas) && $row = $res_todas->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['sender']) ?></td>
                            <td><?= htmlspecialchars($row['recipient']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

<?php
$conn->close();
?>