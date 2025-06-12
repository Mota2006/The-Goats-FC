<?php
session_start();

// Include the logic
require_once 'treinadores.php';
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

        <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador'])): /* Only show section to authorized roles*/?>
            <section id="coaches-section" class="active" aria-label="Gestão de Treinadores">
                
                <?php // *** MODIFICATION: Show form only when editing an existing coach. *** ?>
                <?php if ($editing): ?>
                    <h2>Edição de Treinador</h2>
                    <form action="treinadores.php" method="post" id="treinador-form" aria-describedby="treinador-form-desc">
                        <p id="treinador-form-desc">Edite as informações do treinador e clique em salvar.</p>
                        <input type="hidden" id="treinador-id" name="treinador_id" value="<?= htmlspecialchars($edit_data['id']) ?>">

                        <label for="name">Nome Completo:</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit_data['name']) ?>" required>

                        <label for="phone">Contacto:</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($edit_data['phone']) ?>">

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($edit_data['email']) ?>">

                        <label for="birthdate">Data de Nascimento:</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($edit_data['birthdate']) ?>">

                        <label for="history">Histórico (opcional):</label>
                        <textarea id="history" name="history"><?= htmlspecialchars($edit_data['history']) ?></textarea>

                        <div class="form-actions">
                            <button type="submit" name="save" class="submit-btn">Salvar</button>
                            <button type="button" class="submit-btn" onclick="window.location.href='treinadores_view.php'">Cancelar Edição</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (!$editing): /* Only show the list of coaches if not in edit mode */ ?>
                    <h3>Lista de Treinadores</h3>
                    <?php if ($treinadores && $treinadores->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Contacto</th>
                                    <th>Email</th>
                                    <th>Histórico</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $treinadores->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['history'])) ?></td>
                                    <td>
                                        <?php
                                        // A coach can only edit their own profile. Admin/Funcionario can edit any.
                                        $can_edit = (in_array($current_role, ['admin', 'funcionario']) || ($current_role === 'treinador' && $row['id'] == $current_treinador_id));
                                        // Only Admin/Funcionario can delete.
                                        $can_delete = in_array($current_role, ['admin', 'funcionario']);
                                        ?>
                                        <?php if ($can_edit): ?>
                                            <button type="button" class="submit-btn" onclick="window.location.href='treinadores_view.php?edit_treinador=<?= $row['id'] ?>'">Editar</button>
                                        <?php endif; ?>
                                        <?php if ($can_delete): ?>
                                            <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar este treinador? Esta ação é irreversível.')) window.location.href='treinadores.php?delete_treinador=<?= $row['id'] ?>'\">Eliminar</button>
                                        <?php endif; ?>
                                        <?php if (!$can_edit && !$can_delete): ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Não há treinadores registados.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <p>Você não tem permissão para aceder a esta página.</p>
        <?php endif; ?>
    </main>
</body>
</html>

<?php
$conn->close();
?>