<?php
// Include the logic file
require_once 'equipas.php'; // Include the new logic file

// Get selected team ID from GET parameter if available
$selected_team_id = $_GET['team_id'] ?? null;
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

        <section id="teams-section" class="active" aria-label="Gestão de Equipes">
            <h2>Gestão de Equipes</h2>

            <?php if (!$selected_team_id): // Show the team list only if no team is selected for details ?>
                <h3>Equipas Registadas</h3>
                <table id="all-teams-table" aria-live="polite" aria-relevant="additions removals">
                    <thead>
                        <tr><th>Equipe</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php
                            $teams_result = $conn->query("SELECT id, name FROM teams ORDER BY id ASC");
                            if ($teams_result->num_rows > 0) {
                                while($team_row = $teams_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($team_row['name']) ?></td>
                                    <td>
                                        <button type="button" class="submit-btn" onclick="window.location.href='equipas_view.php?team_id=<?= $team_row['id'] ?>'">Detalhes</button>
                                    </td>
                                </tr>
                            <?php endwhile;
                            } else {
                                echo '<tr><td colspan="2">Não há equipas registadas.</td></tr>';
                            }
                        ?>
                    </tbody>
                </table>
            <?php endif; // End of condition for showing team list ?>

            <?php if ($selected_team_id): // Show team details if a team is selected ?>
                <?php
                $stmt_selected_team = $conn->prepare("SELECT name FROM teams WHERE id = ?");
                $selected_team_name = "Equipa Desconhecida";
                if ($stmt_selected_team) {
                    $stmt_selected_team->bind_param("i", $selected_team_id);
                    $stmt_selected_team->execute();
                    $res_selected_team = $stmt_selected_team->get_result();
                    if ($row_selected_team = $res_selected_team->fetch_assoc()) {
                        $selected_team_name = htmlspecialchars($row_selected_team['name']);
                    }
                    $stmt_selected_team->close();
                }
                ?>
                <h3>Detalhes da Equipa: <?= $selected_team_name ?></h3>
                <button type="button" class="submit-btn" onclick="window.location.href='equipas_view.php'">Voltar à Lista de Equipas</button>

                <h4>Jogadores na Equipa</h4>
                <?php
                $players_in_team_sql = "SELECT p.id, p.name FROM players p JOIN team_players tp ON p.id = tp.player_id WHERE tp.team_id = ?";
                $stmt_players_in_team = $conn->prepare($players_in_team_sql);
                if ($stmt_players_in_team) {
                    $stmt_players_in_team->bind_param("i", $selected_team_id);
                    $stmt_players_in_team->execute();
                    $players_in_team_result = $stmt_players_in_team->get_result();

                    if ($players_in_team_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome do Jogador</th>
                                    <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                        <th>Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($player_row = $players_in_team_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($player_row['name']) ?></td>
                                        <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                            <td>
                                                <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer remover este jogador da equipa?')) window.location.href='equipas.php?remove_player_from_team=<?= $player_row['id'] ?>&team_id=<?= $selected_team_id ?>'">Remover</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Não há jogadores nesta equipa.</p>
                    <?php endif;
                    $stmt_players_in_team->close();
                }
                ?>

                <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                    <h4>Adicionar Jogador à Equipa</h4>
                    <form action="equipas.php" method="post" class="inline-form">
                        <input type="hidden" name="target_team_id" value="<?= htmlspecialchars($selected_team_id) ?>">
                        <select name="player_id" id="player-to-add" required>
                            <option value="">-- Selecione um jogador --</option>
                            <?php
                                // Fetch players not currently assigned to this specific team
                                $unassigned_players_sql = "SELECT p.id, p.name FROM players p LEFT JOIN team_players tp ON p.id = tp.player_id AND tp.team_id = ? WHERE tp.player_id IS NULL ORDER BY p.name";
                                $stmt_unassigned_players = $conn->prepare($unassigned_players_sql);
                                if ($stmt_unassigned_players) {
                                    $stmt_unassigned_players->bind_param("i", $selected_team_id);
                                    $stmt_unassigned_players->execute();
                                    $unassigned_players_result = $stmt_unassigned_players->get_result();

                                    if ($unassigned_players_result->num_rows > 0):
                                        while($player_row = $unassigned_players_result->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($player_row['id']) ?>"><?= htmlspecialchars($player_row['name']) ?></option>
                                    <?php endwhile;
                                    else: ?>
                                        <option value="" disabled>Todos os jogadores estão atribuídos a esta equipa ou não há jogadores disponíveis.</option>
                                    <?php endif;
                                    $stmt_unassigned_players->close();
                                }
                            ?>
                        </select>
                        <button type="submit" name="add_player_to_team" class="submit-btn">Adicionar Jogador</button>
                    </form>
                <?php endif; ?>

                <h4>Treinadores na Equipa</h4>
                <?php
                $coaches_in_team_sql = "SELECT t.id, t.name FROM treinadores t JOIN team_coaches tc ON t.id = tc.coach_id WHERE tc.team_id = ?";
                $stmt_coaches_in_team = $conn->prepare($coaches_in_team_sql);
                if ($stmt_coaches_in_team) {
                    $stmt_coaches_in_team->bind_param("i", $selected_team_id);
                    $stmt_coaches_in_team->execute();
                    $coaches_in_team_result = $stmt_coaches_in_team->get_result();

                    if ($coaches_in_team_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome do Treinador</th>
                                    <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                        <th>Ações</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($coach_row = $coaches_in_team_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($coach_row['name']) ?></td>
                                        <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                            <td>
                                                <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer remover este treinador da equipa?')) window.location.href='equipas.php?remove_coach_from_team=<?= $coach_row['id'] ?>&team_id=<?= $selected_team_id ?>'">Remover</button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Não há treinadores nesta equipa.</p>
                    <?php endif;
                    $stmt_coaches_in_team->close();
                }
                ?>

                <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                    <h4>Adicionar Treinador à Equipa</h4>
                    <form action="equipas.php" method="post" class="inline-form">
                        <input type="hidden" name="target_team_id" value="<?= htmlspecialchars($selected_team_id) ?>">
                        <select name="coach_id" id="coach-to-add" required>
                            <option value="">-- Selecione um treinador --</option>
                            <?php
                                // Fetch coaches not currently assigned to this specific team
                                $unassigned_coaches_sql = "SELECT t.id, t.name FROM treinadores t LEFT JOIN team_coaches tc ON t.id = tc.coach_id AND tc.team_id = ? WHERE tc.coach_id IS NULL ORDER BY t.name";
                                $stmt_unassigned_coaches = $conn->prepare($unassigned_coaches_sql);
                                if ($stmt_unassigned_coaches) {
                                    $stmt_unassigned_coaches->bind_param("i", $selected_team_id);
                                    $stmt_unassigned_coaches->execute();
                                    $unassigned_coaches_result = $stmt_unassigned_coaches->get_result();

                                    if ($unassigned_coaches_result->num_rows > 0):
                                        while($coach_row = $unassigned_coaches_result->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($coach_row['id']) ?>"><?= htmlspecialchars($coach_row['name']) ?></option>
                                    <?php endwhile;
                                    else: ?>
                                        <option value="" disabled>Todos os treinadores estão atribuídos a esta equipa ou não há treinadores disponíveis.</option>
                                    <?php endif;
                                    $stmt_unassigned_coaches->close();
                                }
                            ?>
                        </select>
                        <button type="submit" name="add_coach_to_team" class="submit-btn">Adicionar Treinador</button>
                    </form>
                <?php endif; ?>

            <?php endif; // End of condition for showing team details ?>

            <?php /* The "Adicionar Nova Equipa" form was here and has been removed as per request. */ ?>

        </section>
    </main>
</body>
</html>

<?php
$conn->close();
?>