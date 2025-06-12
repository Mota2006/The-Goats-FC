<?php
session_start();
// Ligação à base de dados
$conn = new mysqli("localhost", "root", "", "the goats fc");

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Incluir a lógica
require_once 'estatisticas.php';
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

    <section id="add-game-statistics-section" class="active" aria-label="Adicionar Estatísticas de Jogo">
        <h2>Adicionar Jogo</h2>
        <form method="get" action="estatisticas_view.php">
            <label>Selecionar Jogo
                <select name="schedule_id" onchange="this.form.submit()">
                    <option value="">-- Selecione --</option>
                    <?php
                    $games->data_seek(0); // Reset the pointer of $games result set before looping
                    while ($g = $games->fetch_assoc()):
                        $selected = (isset($_GET['schedule_id']) && $_GET['schedule_id'] == $g['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $g['id'] ?>" <?= $selected ?>>
                            <?= htmlspecialchars($g['date'] . " - " . $g['team_name'] . " vs " . $g['opponent']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
        </form>

        <?php if (isset($schedule) && $schedule !== null): // Certifique-se que $schedule não é nulo ?>
            <form method="post" action="estatisticas.php">
                <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule['id']) ?>"> <input type="hidden" name="team_id_for_match" value="<?= htmlspecialchars($schedule['team_id']) ?>"> <input type="hidden" name="opponent" value="<?= htmlspecialchars($schedule['opponent']) ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($schedule['date']) ?>">
                <div>
                    <h3 style="text-align: center;">Resultado</h3>
                    <div style="text-align: center;">
                        <label style="display: inline-block; margin-right: 20px;">Sua equipa<input type="number" min="0" name="goals_for" required></label>
                        <label style="display: inline-block;">Adversário<input type="number" min="0" name="goals_against" required></label>
                    </div>
                </div>
                <h3>Estatísticas dos Jogadores</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Jogador</th>
                            <th>Minutos</th>
                            <th>Golos</th>
                            <th>Amarelos</th>
                            <th>Vermelhos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($players && $players->num_rows > 0) { // Verifique se $players tem resultados
                            $players->data_seek(0); // Reset the pointer of $players result set before looping
                            while ($row = $players->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><input type="number" name="players[<?= $row['id'] ?>][minutes]" min="0" value="0"></td>
                                    <td><input type="number" name="players[<?= $row['id'] ?>][goals]" min="0" value="0"></td>
                                    <td><input type="number" name="players[<?= $row['id'] ?>][yellow]" min="0" value="0"></td>
                                    <td><input type="number" name="players[<?= $row['id'] ?>][red]" min="0" value="0"></td>
                                </tr>
                            <?php endwhile;
                        } else { ?>
                            <tr>
                                <td colspan="5">Nenhum jogador encontrado para esta equipa.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <div style="text-align: center;">
                    <button type="submit" name="save_statistics" class="submit-btn">Guardar Estatísticas</button>
                </div>
            </form>
        <?php elseif (isset($_GET['schedule_id'])): ?>
            <p>O jogo selecionado não foi encontrado ou não tem informações de equipa.</p>
        <?php else: ?>
            <p>Selecione um jogo da agenda para adicionar estatísticas.</p>
        <?php endif; ?>
    </section>

    <section id="player-statistics-section" aria-label="Estatísticas de Jogadores por Equipa">
        <h2>Estatísticas de Jogadores por Equipa</h2>
        <form method="get" action="estatisticas_view.php">
            <label>Selecionar Equipa
                <select name="team_id" onchange="this.form.submit()">
                    <option value="">-- Escolher --</option>
                    <?php
                    $teams->data_seek(0); // Reset the pointer of $teams result set before looping
                    while ($t = $teams->fetch_assoc()):
                        $selected = (isset($_GET['team_id']) && $_GET['team_id'] == $t['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $t['id'] ?>" <?= $selected ?>>
                            <?= htmlspecialchars($t['name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
        </form>

        <?php if (isset($player_stats) && $player_stats->num_rows > 0): ?>
            <h3>Estatísticas Detalhadas dos Jogadores</h3>
            <table>
                <thead>
                    <tr>
                        <th>Jogador</th>
                        <th>Jogos</th>
                        <th>Minutos Jogados</th>
                        <th>Golos</th>
                        <th>Cartões Amarelos</th>
                        <th>Cartões Vermelhos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $player_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['jogos']) ?></td>
                            <td><?= htmlspecialchars($row['minutos']) ?></td>
                            <td><?= htmlspecialchars($row['golos']) ?></td>
                            <td><?= htmlspecialchars($row['amarelos']) ?></td>
                            <td><?= htmlspecialchars($row['vermelhos']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php elseif (isset($_GET['team_id']) && $player_stats && $player_stats->num_rows == 0): // Certifique-se que $player_stats não é nulo antes de verificar num_rows?>
            <p>Nenhum jogador encontrado para esta equipa ou sem estatísticas registadas.</p>
        <?php endif; ?>
    </section>
  </main>
</body>
</html>

<?php
$conn->close();
?>