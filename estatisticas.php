<?php
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) die("Erro na ligação: " . $conn->connect_error);

// Obter jogadores para o formulário
$players = $conn->query("
    SELECT p.id, p.name
    FROM players p
    ORDER BY p.name
");

$games = $conn->query("
    SELECT s.id, s.date, s.opponent, t.name AS team_name
    FROM schedule s
    LEFT JOIN teams t ON s.team_id = t.id
    WHERE s.type = 'Jogo'
    ORDER BY s.date DESC
");

// Obter equipas para o formulário
$teams = $conn->query("SELECT id, name FROM teams ORDER BY id ASC");

// Verifica se um jogo foi selecionado
$schedule = null; // Inicializa schedule como null
if (isset($_GET['schedule_id'])) {
    $game_id = (int) $_GET['schedule_id'];

    // Obter dados do jogo
    $schedule_query = $conn->prepare("SELECT * FROM schedule WHERE id = ?");
    $schedule_query->bind_param("i", $game_id);
    $schedule_query->execute();
    $schedule = $schedule_query->get_result()->fetch_assoc();
    $schedule_query->close();

    if ($schedule) {
        $team_id_from_schedule = $schedule['team_id']; // Captura o team_id do jogo agendado

        // Obter jogadores da equipa que jogou
        $players_query = $conn->prepare("
            SELECT p.id, p.name
            FROM players p
            INNER JOIN team_players tp ON p.id = tp.player_id
            WHERE tp.team_id = ?
            ORDER BY p.name
        ");
        $players_query->bind_param("i", $team_id_from_schedule);
        $players_query->execute();
        $players = $players_query->get_result();
        $players_query->close();
    }
}

// Verifica se uma equipa foi selecionada para estatísticas de jogadores
$player_stats = null;
if (isset($_GET['team_id'])) {
    $selected_team = (int) $_GET['team_id'];

    $player_stats_query = $conn->prepare("
        SELECT p.name, COUNT(ps.match_id) AS jogos, SUM(ps.minutes_played) AS minutos,
                SUM(ps.goals) AS golos, SUM(ps.yellow_cards) AS amarelos, SUM(ps.red_cards) AS vermelhos
        FROM players p
        JOIN team_players tp ON p.id = tp.player_id
        LEFT JOIN player_stats ps ON p.id = ps.player_id
        WHERE tp.team_id = ?
        GROUP BY p.id, p.name
        ORDER BY p.name
    ");
    $player_stats_query->bind_param("i", $selected_team);
    $player_stats_query->execute();
    $player_stats = $player_stats_query->get_result();
    $player_stats_query->close();
}


// Inserção de jogo e remoção do schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_statistics'])) {
    $schedule_id_to_delete = (int) $_POST['schedule_id']; // Adicionado para capturar o ID do jogo da schedule
    $team_id_for_match = (int) $_POST['team_id_for_match']; // Adicionado para capturar o team_id
    $opponent = $_POST['opponent'];
    $date = $_POST['date'];
    $goals_for = (int) $_POST['goals_for'];
    $goals_against = (int) $_POST['goals_against'];
    $result = $goals_for > $goals_against ? 'Vitória' : ($goals_for === $goals_against ? 'Empate' : 'Derrota');

    // 1. Inserir jogo na tabela matches (AGORA COM team_id)
    $stmt = $conn->prepare("INSERT INTO matches (team_id, opponent, date, result, goals_for, goals_against) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssii", $team_id_for_match, $opponent, $date, $result, $goals_for, $goals_against);
    $stmt->execute();
    $match_id = $stmt->insert_id;
    $stmt->close();

    // 2. Inserir estatísticas dos jogadores
    foreach ($_POST['players'] as $player_id => $stats) {
        $minutes = (int) $stats['minutes'];
        $goals = (int) $stats['goals'];
        $yellow = (int) $stats['yellow'];
        $red = (int) $stats['red'];

        $stmt = $conn->prepare("INSERT INTO player_stats (player_id, match_id, goals, yellow_cards, red_cards, minutes_played) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiii", $player_id, $match_id, $goals, $yellow, $red, $minutes);
        $stmt->execute();
        $stmt->close();
    }

    // 3. Remover o jogo da tabela 'schedule'
    $delete_stmt = $conn->prepare("DELETE FROM schedule WHERE id = ?");
    $delete_stmt->bind_param("i", $schedule_id_to_delete);
    $delete_stmt->execute();
    $delete_stmt->close();
    header("Location: estatisticas_view.php");
    exit();
}
?>