<?php
session_start();

// Include the logic file
require_once 'funcionarios.php';

// Redirect if not logged in or unauthorized
if (!isset($_SESSION['user_id']) || (!in_array($current_role, ['admin', 'funcionario']))) {
    $_SESSION['error'] = "Você não tem permissão para aceder a esta página.";
    header("Location: login_view.php"); // Redirect to login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>The Goats FC - Gestão de Funcionários</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        Gestão do Clube
    </header>

    <?php include 'navbar.php'; // Assuming you have a navbar.php for navigation ?>

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

        <section id="funcionarios-section" class="active" aria-label="Gestão de Funcionários">
            <?php if ($editing): // Only show edit form if editing an employee ?>
                <h2>Edição de Funcionário</h2>
                <form action="funcionarios.php" method="post">
                    <input type="hidden" name="funcionario_id" value="<?= htmlspecialchars($funcionario_data['id'] ?? '') ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($funcionario_data['user_id'] ?? '') ?>">

                    <label for="name">Nome:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($funcionario_data['name'] ?? '') ?>" required>

                    <label for="username">Nome de Utilizador:</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($funcionario_data['username'] ?? '') ?>" disabled title="O nome de utilizador não pode ser alterado nesta página.">

                    <label for="phone">Contato Telemóvel:</label>
                    <input type="number" id="phone" name="phone" value="<?= htmlspecialchars($funcionario_data['phone'] ?? '') ?>" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($funcionario_data['email'] ?? '') ?>" required>

                    <label for="birthdate">Data de Nascimento:</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($funcionario_data['birthdate'] ?? '') ?>" max="<?= date('Y-m-d') ?>" required>

                    <label for="position">Cargo:</label>
                    <input type="text" id="position" name="position" value="<?= htmlspecialchars($funcionario_data['position'] ?? '') ?>" required>

                    <label for="nif">NIF:</label>
                    <input type="text" id="nif" name="nif" value="<?= htmlspecialchars($funcionario_data['nif'] ?? '') ?>" required>

                    <button type="submit" name="save" class="submit-btn">Guardar Alterações</button>
                    <button type="button" class="submit-btn" onclick="window.location.href='funcionarios_view.php'">Cancelar</button>
                </form>
            <?php else: // Show list of employees ?>
                <h2>Lista de Funcionários</h2>
                <?php if (empty($funcionarios_list)): ?>
                    <p class="no-records">Nenhum funcionário encontrado.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contato</th>
                                <th>Email</th>
                                <th>Cargo</th>
                                <?php if (in_array($current_role, ['admin', 'funcionario'])): // Only show actions if admin or the funcionario themselves ?>
                                    <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($funcionarios_list as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['position']) ?></td>
                                    <?php if (in_array($current_role, ['admin', 'funcionario']) || ($current_role === 'funcionario' && $row['user_id'] == $current_login_id)): ?>
                                        <td>
                                            <?php if (in_array($current_role, ['admin']) || ($current_role === 'funcionario' && $row['user_id'] == $current_login_id)): ?>
                                                <button type="button" class="submit-btn" onclick="window.location.href='funcionarios_view.php?edit_funcionario=<?= $row['id'] ?>'">Editar</button>
                                            <?php endif; ?>
                                            <?php if (in_array($current_role, ['admin'])): // Only admin can delete ?>
                                                <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar este funcionário? Esta ação é irreversível e irá remover a conta de utilizador associada.')) window.location.href='funcionarios.php?delete_funcionario=<?= $row['id'] ?>'">Eliminar</button>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

<?php
$conn->close();
?>