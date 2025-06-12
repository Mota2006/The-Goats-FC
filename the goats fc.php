<?php
session_start();

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>The Goats FC</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link rel="stylesheet" href="style.css">
    </head>
<body>
    <header>
        The Goats FC
    </header>

    <?php include 'navbar.php'; ?>

    <main>
        <section>
            <h2>Bem-vindo ao The Goats FC</h2>
            <p>Use o menu de navegação acima para gerir o clube.</p>
            <?php if ($current_user): ?>
                <p>Olá, <?= htmlspecialchars($current_user) ?>!</p>
                <p>O seu papel no clube é: <?= htmlspecialchars($current_role) ?></p>
            <?php else: ?>
                <p>Por favor, faça <a href="Login_Registo/login.html" style="color: var(--secondary-color); text-decoration: none; font-weight: 600;">login</a> ou <a href="Login_Registo/registo_view.php" style="color: var(--secondary-color); text-decoration: none; font-weight: 600;">registre-se</a> para aceder a todas as funcionalidades.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>