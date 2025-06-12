<?php
$conn = new mysqli("localhost", "root", "", "the goats fc");
if ($conn->connect_error) die("Erro na ligação: " . $conn->connect_error);

$teams = $conn->query("SELECT * FROM teams ORDER BY id ASC");
$selected_team = isset($_GET['team_id']) ? (int) $_GET['team_id'] : 0;

// Estatísticas do CLUBE INTEIRO (Resumo Global) - Usando a tabela 'matches'
// Esta query soma os resultados de todos os jogos na tabela 'matches'.
$global_club_stats = $conn->query("
    SELECT
        COUNT(*) AS jogos,
        SUM(CASE WHEN result = 'Vitória' THEN 1 ELSE 0 END) AS vitorias,
        SUM(CASE WHEN result = 'Empate' THEN 1 ELSE 0 END) AS empates,
        SUM(CASE WHEN result = 'Derrota' THEN 1 ELSE 0 END) AS derrotas,
        SUM(goals_for) AS golos_marcados,
        SUM(goals_against) AS golos_sofridos
    FROM matches
")->fetch_assoc();

$global_diferenca_golos = isset($global_club_stats['golos_marcados']) ? ($global_club_stats['golos_marcados'] - $global_club_stats['golos_sofridos']) : 0;

// Estatísticas da EQUIPA SELECIONADA (Resumo por Equipa)
$selected_team_stats = []; // Inicializa como array vazio
$selected_diferenca_golos = 0; // Inicializa a diferença de golos para a equipa selecionada

if ($selected_team > 0) { // Só executa se uma equipa for selecionada
    $selected_team_stats_query = $conn->prepare("
        SELECT
            COUNT(*) AS jogos,
            SUM(CASE WHEN result = 'Vitória' THEN 1 ELSE 0 END) AS vitorias,
            SUM(CASE WHEN result = 'Empate' THEN 1 ELSE 0 END) AS empates,
            SUM(CASE WHEN result = 'Derrota' THEN 1 ELSE 0 END) AS derrotas,
            SUM(goals_for) AS golos_marcados,
            SUM(goals_against) AS golos_sofridos
        FROM matches
        WHERE team_id = ?
    ");
    $selected_team_stats_query->bind_param("i", $selected_team);
    $selected_team_stats_query->execute();
    $selected_team_stats = $selected_team_stats_query->get_result()->fetch_assoc();

    $selected_diferenca_golos = isset($selected_team_stats['golos_marcados']) ? ($selected_team_stats['golos_marcados'] - $selected_team_stats['golos_sofridos']) : 0;
}

// Estatísticas individuais dos jogadores da equipa selecionada
$player_stats = null; // Inicializa como null
if ($selected_team > 0) { // Só executa se uma equipa for selecionada
    $player_stats_query = $conn->prepare("
        SELECT
            p.name,
            COUNT(DISTINCT ps.match_id) AS jogos,
            SUM(ps.minutes_played) AS minutos,
            SUM(ps.goals) AS golos,
            SUM(ps.yellow_cards) AS amarelos,
            SUM(ps.red_cards) AS vermelhos
        FROM players p
        INNER JOIN team_players tp ON p.id = tp.player_id
        LEFT JOIN player_stats ps ON p.id = ps.player_id
        WHERE tp.team_id = ?
        GROUP BY p.id
        ORDER BY p.name
    ");
    $player_stats_query->bind_param("i", $selected_team);
    $player_stats_query->execute();
    $player_stats = $player_stats_query->get_result();
}
?>