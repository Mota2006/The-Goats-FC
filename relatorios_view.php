<?php
session_start();
// Ligação à base de dados
$conn = new mysqli("localhost", "root", "", "the goats fc");

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Incluir a lógica
require_once 'relatorios.php';
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

    <section id="reports-section" class="active" aria-label="Relatórios">
        <h2>Relatórios</h2>
        <p>Estatísticas básicas das equipes e jogadores.</p>
        <div id="reports-output"></div>

        <h3>Resumo Global do Clube (Todos os Jogos)</h3>
        <p>Total de jogos: <?= $global_club_stats['jogos'] ?></p>
        <p>Vitórias: <?= $global_club_stats['vitorias'] ?> | Empates: <?= $global_club_stats['empates'] ?> | Derrotas: <?= $global_club_stats['derrotas'] ?></p>
        <p>Golos marcados: <?= $global_club_stats['golos_marcados'] ?> | Golos sofridos: <?= $global_club_stats['golos_sofridos'] ?></p>
        <p>Diferença de golos: <strong><?= $global_diferenca_golos ?></strong></p>

        <h2>Resumo por Equipa</h2>
        <form method="GET" action="relatorios_view.php">
          <label>Selecionar Equipa
            <select name="team_id" onchange="this.form.submit()">
              <option value="">-- Selecione --</option>
              <?php
              $teams->data_seek(0); // Reset pointer for the team select
              while ($team = $teams->fetch_assoc()): ?>
                <option value="<?= $team['id'] ?>" <?= $team['id'] == $selected_team ? 'selected' : '' ?>>
                  <?= htmlspecialchars($team['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </label>
        </form>

        <?php if ($selected_team > 0 && !empty($selected_team_stats)): // Only show team stats if a team is selected and stats exist ?>
            <p>Total de jogos: <?= $selected_team_stats['jogos'] ?></p>
            <p>Vitórias: <?= $selected_team_stats['vitorias'] ?> | Empates: <?= $selected_team_stats['empates'] ?> | Derrotas: <?= $selected_team_stats['derrotas'] ?></p>
            <p>Golos marcados: <?= $selected_team_stats['golos_marcados'] ?> | Golos sofridos: <?= $selected_team_stats['golos_sofridos'] ?></p>
            <p>Diferença de golos: <strong><?= $selected_diferenca_golos ?></strong></p>
        <?php elseif ($selected_team > 0): ?>
            <p>Não há estatísticas de jogos para a equipa selecionada.</p>
        <?php else: ?>
            <p>Selecione uma equipa para ver o seu resumo.</p>
        <?php endif; ?>

        <h3>Desempenho Individual dos Jogadores</h3>
        <?php if ($selected_team > 0 && $player_stats !== null): ?>
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Jogos</th>
                    <th>Minutos</th>
                    <th>Golos</th>
                    <th>Amarelos</th>
                    <th>Vermelhos</th>
                </tr>
                <?php if ($player_stats->num_rows > 0): ?>
                    <?php while ($row = $player_stats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= $row['jogos'] ?></td>
                            <td><?= $row['minutos'] ?></td>
                            <td><?= $row['golos'] ?></td>
                            <td><?= $row['amarelos'] ?></td>
                            <td><?= $row['vermelhos'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Não há estatísticas de jogadores para esta equipa.</td>
                    </tr>
                <?php endif; ?>
            </table>
        <?php else: ?>
            <p>Selecione uma equipa para ver o desempenho individual dos jogadores.</p>
        <?php endif; ?>
    </section>
  </main>
</body>
</html>

<?php
$conn->close();
?>