<?php
session_start();

// Include the logic
require_once 'jogadores.php';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>The Goats FC</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        Gestão do Clube
    </header>

    <?php include 'navbar.php'; ?>

    <main>
        <div id="notification" role="alert"></div>
        <?php
        // Display messages
        if (isset($_SESSION['success'])) {
            echo '<div class="notification success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="notification error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <section id="players-section" class="active" aria-label="Gestão de Jogadores">
            <?php if ($editing): // Only show edit form if editing a player ?>
                <h2>Edição de Jogador</h2>
                <form action="jogadores.php" method="post" id="player-form" aria-describedby="player-form-asc">
                    <p id="player-form-desc">Preencha ou edite as informações do jogador e clique em salvar.</p>
                    <input type="hidden" id="player-id" name="player_id" value="<?= $editing ? htmlspecialchars($edit_data['id']) : '' ?>">

                    <div class="input-group">
                        <label for="name">Nome:</label>
                        <input type="text" id="name" name="name" value="<?= $editing ? htmlspecialchars($edit_data['name']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="phone">Contacto:</label>
                        <input type="text" id="phone" name="phone" value="<?= $editing ? htmlspecialchars($edit_data['phone']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= $editing ? htmlspecialchars($edit_data['email']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="birthdate">Data de Nascimento:</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?= $editing ? htmlspecialchars($edit_data['birthdate']) : '' ?>"  max="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="history">Histórico:</label>
                        <textarea id="history" name="history" rows="5"><?= $editing ? htmlspecialchars($edit_data['history']) : '' ?></textarea>
                    </div>

                    <button type="submit" name="save" class="submit-btn">Salvar Alterações</button>
                    <button type="button" class="submit-btn" onclick="window.location.href='jogadores_view.php'">Cancelar</button>
                </form>
            <?php else: // Show list of players if not editing ?>
                <h2>Jogadores Registados</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contacto</th>
                                <th>Email</th>
                                <th>Histórico</th>
                                <?php if (in_array($current_role, ['admin', 'funcionario', 'jogador'])): ?>
                                    <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Re-fetch players after any operation (save, update, delete)
                            $sql = "SELECT p.* FROM players p ORDER BY p.id ASC";
                            $result = $conn->query($sql);
                            
                            while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['history'])) ?></td>
                                    <?php if (in_array($current_role, ['admin', 'funcionario']) || ($current_role === 'jogador' && $row['user_id'] == $current_login_id)): // Show actions if user is admin, funcionario, or the player themselves ?>
                                        <td>
                                            <?php if (in_array($current_role, ['admin', 'funcionario']) || ($current_role === 'jogador' && $row['user_id'] == $current_login_id)): ?>
                                                <button type="button" class="submit-btn" onclick="window.location.href='jogadores_view.php?edit_player=<?= $row['id'] ?>'">Editar</button>
                                            <?php endif; ?>
                                            <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                                <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar este jogador? Esta ação é irreversível.')) window.location.href='jogadores.php?delete_player=<?= $row['id'] ?>'">Eliminar</button>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

<?php
$conn->close();
?>