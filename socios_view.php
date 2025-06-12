<?php
session_start();

// Incluir a lógica
require_once 'socios.php';
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

        <section id="players-section" class="active" aria-label="Gestão de Sócios">
            <?php
            // Only show the form if editing, or if admin/funcionario is logged in (for new entries, though registration is separate)
            // A 'socio' should only see their own edit form if they click 'Editar' for their own profile.
            // The form for adding new socios is removed.
            if ($editing): // Only show edit form if editing a socio
            ?>
                <h2>Edição de Sócio</h2>
                <form action="socios.php" method="post" id="socio-form" aria-describedby="socio-form-desc">
                    <p id="socio-form-desc">Preencha ou edite as informações do sócio e clique em salvar.</p>
                    <input type="hidden" id="socio-id" name="socio_id" value="<?= $editing ? htmlspecialchars($edit_data['id']) : '' ?>">

                    <div class="input-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?= $editing ? htmlspecialchars($edit_data['nome']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="data_nascimento">Data de Nascimento:</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" value="<?= $editing ? htmlspecialchars($edit_data['data_nascimento']) : '' ?>" required max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="input-group">
                        <label for="contacto">Contacto:</label>
                        <input type="text" id="contacto" name="contacto" value="<?= $editing ? htmlspecialchars($edit_data['contacto']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= $editing ? htmlspecialchars($edit_data['email']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="morada">Morada:</label>
                        <input type="text" id="morada" name="morada" value="<?= $editing ? htmlspecialchars($edit_data['morada']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="nif">NIF:</label>
                        <input type="text" id="nif" name="nif" value="<?= $editing ? htmlspecialchars($edit_data['nif']) : '' ?>" required>
                    </div>

                    <button type="submit" name="add_socio" class="submit-btn">Salvar Alterações</button>
                    <button type="button" class="submit-btn" onclick="window.location.href='socios_view.php'">Cancelar</button>
                </form>
            <?php endif; ?>

            <?php if (!$editing): // Show list of socios if not editing ?>
                <h2>Sócios Registados</h2>
                <?php if ($socios->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contacto</th>
                                <th>Email</th>
                                <th>Morada</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $socios->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['nome']) ?></td>
                                    <td><?= htmlspecialchars($s['contacto']) ?></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= htmlspecialchars($s['morada']) ?></td>
                                    <td>
                                        <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                            <button type="button" class="submit-btn" onclick="window.location.href='socios_view.php?edit_socio=<?= $s['id'] ?>'">Editar</button>
                                            <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar este sócio? Esta ação é irreversível.')) window.location.href='socios.php?delete=<?= $s['id'] ?>'">Eliminar</button>
                                        <?php elseif ($current_role === 'socio' && $s['user_id'] == $current_login_id): ?>
                                            <button type="button" class="submit-btn" onclick="window.location.href='socios_view.php?edit_socio=<?= $s['id'] ?>'">Editar</button>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Não há sócios registados.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

<?php
$conn->close();
?>