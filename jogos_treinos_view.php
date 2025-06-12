<?php
session_start();
require_once 'jogos_treinos.php';
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>The Goats FC - Agendar Eventos</title>
    <link rel="icon" type="image/x-icon" href="logo.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        Gestão do Clube
    </header>

    <?php include 'navbar.php'; ?>

    <main>
        <div id="notification" role="alert">
            <?php
            if (isset($_SESSION['success'])) {
                echo '<div class="notification success">' . htmlspecialchars($_SESSION['success']) . '</div>';
                unset($_SESSION['success']);
            }
            if (isset($_SESSION['error'])) {
                echo '<div class="notification error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
        </div>

        <section id="schedule-section" class="active" aria-label="Programação de Treinos e Jogos">
            <?php
            // Check if the current user's role allows scheduling
            if (isset($current_role) && ($current_role === 'treinador' || $current_role === 'admin' || $current_role === 'funcionario')):
            ?>
            <h2><?= $editing ? 'Editar Evento' : 'Programação de Treinos e Jogos' ?></h2>
                <div class="form-container">
                    <form method="post">
                        <input type="hidden" name="event_id" value="<?= $editing ? htmlspecialchars($edit_data['id']) : '' ?>">

                        <label for="type">Tipo</label>
                        <select name="type" id="type" onchange="toggleOpponentField()">
                            <option value="">-- Selecione --</option>
                            <option value="Treino" <?= ($editing && $edit_data['type'] == 'Treino') ? 'selected' : '' ?>>Treino</option>
                            <option value="Jogo" <?= ($editing && $edit_data['type'] == 'Jogo') ? 'selected' : '' ?>>Jogo</option>
                        </select>

                        <label for="team_id">Equipa</label>
                        <select name="team_id" id="team_id">
                            <option value="">-- Selecione --</option>
                            <?php
                            if (isset($teams) && $teams instanceof mysqli_result && $teams->num_rows > 0) {
                                $teams->data_seek(0);
                                while($t = $teams->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($t['id']) ?>"
                                    <?= (isset($edit_data['team_id']) && $edit_data['team_id'] == $t['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                            <?php
                                endwhile;
                            }
                            ?>
                        </select>

                        <label for="date">Data</label>
                        <input type="date" name="date" id="date" value="<?= $editing ? htmlspecialchars($edit_data['date']) : date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">

                        <label for="time">Hora</label>
                        <input type="time" name="time" id="time" value="<?= $editing ? htmlspecialchars($edit_data['time']) : date('H:i') ?>">

                        <div id="opponent-field" style="display: <?= ($editing && $edit_data['type'] == 'Jogo') ? 'block' : 'none' ?>;">
                            <label for="opponent">Adversário</label>
                            <input type="text" name="opponent" id="opponent" value="<?= $editing ? htmlspecialchars($edit_data['opponent']) : '' ?>" placeholder="Nome do adversário">
                        </div>

                        <label for="description">Descrição</label>
                        <textarea name="description" id="description" placeholder="Detalhes do evento"><?= $editing ? htmlspecialchars($edit_data['description']) : '' ?></textarea>

                        <div class="form-actions">
                            <button type="submit" name="add_event" class="submit-btn"><?= $editing ? 'Atualizar Evento' : 'Agendar' ?></button>
                            <?php if ($editing): ?>
                                <button type="button" class="submit-btn cancel" onclick="window.location.href='jogos_treinos_view.php'">Cancelar Edição</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php else: ?>
            <?php endif; ?>

            <?php if (!$editing): // Only show the table if not in editing mode ?>
                <h3>Eventos Agendados</h3>
                <div class="filter-controls">
                    <label for="filter_type">Filtrar por Tipo:</label>
                    <select id="filter_type" onchange="filterSchedule()">
                        <option value="all" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'all') ? 'selected' : '' ?>>Todos</option>
                        <option value="Treino" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'Treino') ? 'selected' : '' ?>>Treino</option>
                        <option value="Jogo" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'Jogo') ? 'selected' : '' ?>>Jogo</option>
                    </select>
                </div>

                <?php
                if (isset($schedule) && $schedule instanceof mysqli_result && $schedule->num_rows > 0):
                ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Equipa</th>
                                    <th>Data</th>
                                    <th>Hora</th>
                                    <th>Adversário</th>
                                    <th>Descrição</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $schedule->data_seek(0);
                                while($s = $schedule->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['type']) ?></td>
                                    <td><?= htmlspecialchars($s['team_name']) ?></td>
                                    <td><?= htmlspecialchars($s['date']) ?></td>
                                    <td><?= htmlspecialchars($s['time']) ?></td>
                                    <td><?= htmlspecialchars($s['opponent'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($s['description']) ?></td>
                                    <td>
                                        <?php if (isset($current_role) && ($current_role === 'treinador' || $current_role === 'admin' || $current_role === 'funcionario')): ?>
                                            <button type="button" class="submit-btn" onclick="window.location.href='jogos_treinos_view.php?edit_event=<?= $s['id'] ?>'">Editar</button>
                                            <button type="button" class="submit-btn cancel" onclick="if(confirm('Tem a certeza que quer eliminar este evento?')) window.location.href='jogos_treinos.php?delete=<?= $s['id'] ?>'">Eliminar</button>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Não há eventos agendados.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

    <script>
        function toggleOpponentField() {
            var type = document.getElementById('type').value;
            var opponentField = document.getElementById('opponent-field');
            if (type === 'Jogo') {
                opponentField.style.display = 'block';
            } else {
                opponentField.style.display = 'none';
                document.getElementById('opponent').value = ''; // Clear opponent field if not a game
            }
        }

        function filterSchedule() {
            var filterType = document.getElementById('filter_type').value;
            window.location.href = 'jogos_treinos_view.php?filter_type=' + filterType;
        }

        // Call on page load to set initial state
        document.addEventListener('DOMContentLoaded', toggleOpponentField);
    </script>
</body>
</html>

<?php
$conn->close();
?>