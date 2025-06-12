<link rel="stylesheet" href="style.css">
<nav aria-label="Main Navigation" class="navbar">
    <div class="navbar-container">
        <div class="navbar-logo" tabindex="0">
            <a href="the goats fc.php" class="logo-link" aria-label="Home">
                <img src="logo_semfundo.png" alt="ClubeExemplo Logo" class="logo-image" />
            </a>
        </div>

        <div class="navbar-links">
            <?php if (in_array($current_role, ['admin', 'funcionario', 'jogador'])): ?>
                <a href="jogadores_view.php" class="nav-link">Jogadores</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'jogador'])): ?>
                <a href="pagamentos_view.php" class="nav-link">Pagamentos</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'socio'])): ?>
                <a href="socios_view.php" class="nav-link">Sócios</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'socio'])): ?>
                <a href="quotas_view.php" class="nav-link">Quotas</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador'])): ?>
                <a href="treinadores_view.php" class="nav-link">Treinadores</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                <a href="funcionarios_view.php" class="nav-link">Funcionário</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador', 'jogador', 'socio', 'adepto'])): ?>
                <a href="equipas_view.php" class="nav-link">Equipas</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador', 'jogador', 'socio', 'adepto'])): ?>
                <a href="jogos_treinos_view.php" class="nav-link">Treinos e Jogos</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'treinador'])): ?>
                <a href="estatisticas_view.php" class="nav-link">Estatísticas</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador', 'jogador', 'socio', 'adepto'])): ?>
                <a href="relatorios_view.php" class="nav-link">Relatórios</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                <a href="financeiro_view.php" class="nav-link">Finanças</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador', 'jogador', 'socio', 'adepto'])): ?>
                <a href="comunicacao_view.php" class="nav-link">Comunicação</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador', 'jogador', 'socio', 'adepto'])): ?>
                <a href="marketing_view.php" class="nav-link">Marketing</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin', 'funcionario', 'treinador', 'jogador', 'socio', 'adepto'])): ?>
                <a href="parcerias_view.php" class="nav-link">Entidades</a>
            <?php endif; ?>
            <?php if (in_array($current_role, ['admin'])): ?>
                <a href="admin_approvals_view.php" class="nav-link">ADMIN</a>
            <?php endif; ?>
            <a href="Login_Registo/logout.php" class="nav-link exit-link" aria-label="Logout">Exit</a>
        </div>
    </div>
</nav>

