<?php
session_start();
// Database connection parameters
$host = 'localhost';
$db   = 'the goats fc';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}

$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Fetch pending players
$players_query = "SELECT pp.*, u.username, u.role FROM pending_players pp JOIN users u ON pp.user_id = u.id WHERE u.status = 'pending'";
$players_result = $conn->query($players_query);

// Fetch pending coaches
$treinadores_query = "SELECT pt.*, u.username, u.role FROM pending_treinadores pt JOIN users u ON pt.user_id = u.id WHERE u.status = 'pending'";
$treinadores_result = $conn->query($treinadores_query);

// Fetch pending socios
$socios_query = "SELECT ps.*, u.username, u.role FROM pending_socios ps JOIN users u ON ps.user_id = u.id WHERE u.status = 'pending'";
$socios_result = $conn->query($socios_query);

// Fetch pending funcionarios
$funcionarios_query = "SELECT pf.*, u.username, u.role FROM pending_funcionarios pf JOIN users u ON pf.user_id = u.id WHERE u.status = 'pending'";
$funcionarios_result = $conn->query($funcionarios_query);

$has_pending = false; // Flag to check if any pending registrations exist
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Aprovações de Registo - The Goats FC</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="../logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        h3 {
            color: #555;
            margin-top: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .pending-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .pending-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            flex: 1 1 calc(50% - 20px);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .user-info p {
            margin: 5px 0;
            color: #666;
        }
        .user-info strong {
            color: #333;
        }
        .user-actions {
            margin-top: 15px;
            text-align: right;
        }
        .approve-btn, .reject-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .approve-btn {
            background-color: #28a745;
            color: white;
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .approve-btn:hover {
            background-color: #218838;
        }
        .reject-btn:hover {
            background-color: #c82333;
        }
        .no-pending {
            text-align: center;
            color: #777;
            margin-top: 50px;
            font-size: 1.1em;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Aprovações de Registo</h2>

        <?php
        if (isset($_SESSION['success_approval'])) {
            echo '<div class="message success-message">' . $_SESSION['success_approval'] . '</div>';
            unset($_SESSION['success_approval']);
        }
        if (isset($_SESSION['error_approval'])) {
            echo '<div class="message error-message">' . $_SESSION['error_approval'] . '</div>';
            unset($_SESSION['error_approval']);
        }
        ?>

        <h3>Novos Registos de Jogadores Pendentes</h3>
        <?php if ($players_result->num_rows > 0) : $has_pending = true; ?>
            <div class="pending-list">
                <?php while ($row = $players_result->fetch_assoc()) : ?>
                    <div class="pending-item">
                        <div class="user-info">
                            <p><strong>Utilizador:</strong> <?= htmlspecialchars($row['username']) ?> (ID: <?= $row['user_id'] ?>)</p>
                            <p><strong>Nome:</strong> <?= htmlspecialchars($row['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                            <p><strong>Data de Nascimento:</strong> <?= htmlspecialchars($row['birthdate']) ?></p>
                            <p><strong>Histórico:</strong> <?= htmlspecialchars($row['history']) ?></p>
                            <p><strong>Data de Registo:</strong> <?= htmlspecialchars($row['registration_date']) ?></p>
                        </div>
                        <div class="user-actions">
                            <form action="admin_approvals.php" method="post">
                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                <input type="hidden" name="id_in_pending_table" value="<?= $row['id'] ?>">
                                <input type="hidden" name="user_type" value="jogador">
                                <button type="submit" name="action" value="approve" class="approve-btn">Aprovar</button>
                                <button type="submit" name="action" value="reject" class="reject-btn">Rejeitar</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>Nenhum registo de jogador pendente.</p>
        <?php endif; ?>

        <h3>Novos Registos de Treinadores Pendentes</h3>
        <?php if ($treinadores_result->num_rows > 0) : $has_pending = true; ?>
            <div class="pending-list">
                <?php while ($row = $treinadores_result->fetch_assoc()) : ?>
                    <div class="pending-item">
                        <div class="user-info">
                            <p><strong>Utilizador:</strong> <?= htmlspecialchars($row['username']) ?> (ID: <?= $row['user_id'] ?>)</p>
                            <p><strong>Nome:</strong> <?= htmlspecialchars($row['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                            <p><strong>Data de Nascimento:</strong> <?= htmlspecialchars($row['birthdate']) ?></p>
                            <p><strong>Histórico:</strong> <?= htmlspecialchars($row['history']) ?></p>
                            <p><strong>Data de Registo:</strong> <?= htmlspecialchars($row['registration_date']) ?></p>
                        </div>
                        <div class="user-actions">
                            <form action="admin_approvals.php" method="post">
                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                <input type="hidden" name="id_in_pending_table" value="<?= $row['id'] ?>">
                                <input type="hidden" name="user_type" value="treinador">
                                <button type="submit" name="action" value="approve" class="approve-btn">Aprovar</button>
                                <button type="submit" name="action" value="reject" class="reject-btn">Rejeitar</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>Nenhum registo de treinador pendente.</p>
        <?php endif; ?>

        <h3>Novos Registos de Sócios Pendentes</h3>
        <?php if ($socios_result->num_rows > 0) : $has_pending = true; ?>
            <div class="pending-list">
                <?php while ($row = $socios_result->fetch_assoc()) : ?>
                    <div class="pending-item">
                        <div class="user-info">
                            <p><strong>Utilizador:</strong> <?= htmlspecialchars($row['username']) ?> (ID: <?= $row['user_id'] ?>)</p>
                            <p><strong>Nome:</strong> <?= htmlspecialchars($row['nome']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                            <p><strong>Data de Nascimento:</strong> <?= htmlspecialchars($row['data_nascimento']) ?></p>
                            <p><strong>Contacto:</strong> <?= htmlspecialchars($row['contacto']) ?></p>
                            <p><strong>Morada:</strong> <?= htmlspecialchars($row['morada']) ?></p>
                            <p><strong>NIF:</strong> <?= htmlspecialchars($row['nif']) ?></p>
                            <p><strong>Data de Registo:</strong> <?= htmlspecialchars($row['registration_date']) ?></p>
                        </div>
                        <div class="user-actions">
                            <form action="admin_approvals.php" method="post">
                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                <input type="hidden" name="id_in_pending_table" value="<?= $row['id'] ?>">
                                <input type="hidden" name="user_type" value="socio">
                                <button type="submit" name="action" value="approve" class="approve-btn">Aprovar</button>
                                <button type="submit" name="action" value="reject" class="reject-btn">Rejeitar</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>Nenhum registo de sócio pendente.</p>
        <?php endif; ?>

        <h3>Novos Registos de Funcionários Pendentes</h3>
        <?php if ($funcionarios_result->num_rows > 0) : $has_pending = true; ?>
            <div class="pending-list">
                <?php while ($row = $funcionarios_result->fetch_assoc()) : ?>
                    <div class="pending-item">
                        <div class="user-info">
                            <p><strong>Utilizador:</strong> <?= htmlspecialchars($row['username']) ?> (ID: <?= $row['user_id'] ?>)</p>
                            <p><strong>Nome:</strong> <?= htmlspecialchars($row['name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                            <p><strong>Data de Nascimento:</strong> <?= htmlspecialchars($row['birthdate']) ?></p>
                            <p><strong>Cargo:</strong> <?= htmlspecialchars($row['position']) ?></p>
                            <p><strong>NIF:</strong> <?= htmlspecialchars($row['nif']) ?></p>
                            <p><strong>Data de Registo:</strong> <?= htmlspecialchars($row['registration_date']) ?></p>
                        </div>
                        <div class="user-actions">
                            <form action="admin_approvals.php" method="post">
                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                <input type="hidden" name="id_in_pending_table" value="<?= $row['id'] ?>">
                                <input type="hidden" name="user_type" value="funcionario">
                                <button type="submit" name="action" value="approve" class="approve-btn">Aprovar</button>
                                <button type="submit" name="action" value="reject" class="reject-btn">Rejeitar</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p>Nenhum registo de funcionário pendente.</p>
        <?php endif; ?>


        <?php if (!$has_pending) : ?>
            <p class="no-pending">Nenhum registo pendente para aprovação.</p>
        <?php endif; ?>
    </div>
</body>
</html>