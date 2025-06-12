<?php
session_start(); // Ensure session is started

// Incluir a lógica
require_once 'pagamentos.php'; // Corrected to include pagamentos.php

// Fetch the player's name if in editing mode
$player_name_for_edit = '';
if ($editing && isset($edit_data['atleta_id'])) {
    $stmt_player_name = $conn->prepare("SELECT name FROM players WHERE id = ?");
    if ($stmt_player_name) {
        $stmt_player_name->bind_param("i", $edit_data['atleta_id']);
        $stmt_player_name->execute();
        $res_player_name = $stmt_player_name->get_result();
        if ($row_player_name = $res_player_name->fetch_assoc()) {
            $player_name_for_edit = $row_player_name['name'];
        }
        $stmt_player_name->close();
    }
}

// Fetch the current player's name if the role is 'jogador' and not in editing mode
$player_name_for_current_user = '';
$current_player_id = '';
if ($current_role === 'jogador' && !$editing) {
    $stmt_current_player = $conn->prepare("SELECT id, name FROM players WHERE user_id = ?");
    if ($stmt_current_player) {
        $stmt_current_player->bind_param("i", $current_login_id);
        $stmt_current_player->execute();
        $res_current_player = $stmt_current_player->get_result();
        if ($row_current_player = $res_current_player->fetch_assoc()) {
            $player_name_for_current_user = $row_current_player['name'];
            $current_player_id = $row_current_player['id'];
        }
        $stmt_current_player->close();
    }
}

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
        <section id="pagamento-section" class="active" aria-label="Pagamento dos Jogadores">
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

            <?php
            // Show the form only if:
            // 1. User is admin/funcionario (can add or edit)
            // 2. User is a player AND in editing mode (can only edit their own payments if allowed by pagamentos.php)
            // 3. User is a player AND NOT in editing mode (can add new payments for themselves)
            if ($editing || ($current_role === 'jogador' && !$editing) || in_array($current_role, ['admin', 'funcionario'])):
            ?>
                <h2><?= $editing ? 'Editar Pagamento' : 'Pagamento de Atletas' ?></h2>
                <form method="post">
                    <input type="hidden" name="pagamento_id" value="<?= $editing ? htmlspecialchars($edit_data['id']) : '' ?>">

                    <div class="input-group">
                        <label for="atleta_id">Atleta:</label>
                        <?php if ($current_role === 'jogador' && !$editing): ?>
                            <input type="hidden" name="atleta_id" value="<?= htmlspecialchars($current_player_id) ?>">
                            <input type="text" value="<?= htmlspecialchars($player_name_for_current_user) ?>" readonly>
                        <?php elseif ($editing): // If editing, show the player's name and keep the select disabled ?>
                            <input type="text" value="<?= htmlspecialchars($player_name_for_edit) ?>" readonly>
                            <input type="hidden" name="atleta_id" value="<?= htmlspecialchars($edit_data['atleta_id']) ?>">
                        <?php else: ?>
                            <select id="atleta_id" name="atleta_id" required>
                                <option value="">Selecione um atleta</option>
                                <?php
                                $players_list = $conn->query("SELECT id, name FROM players ORDER BY name ASC");
                                if ($players_list->num_rows > 0) {
                                    while ($player_row = $players_list->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($player_row['id']) . '">' . htmlspecialchars($player_row['name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="input-group">
                        <label for="data_pagamento">Data do Pagamento:</label>
                        <input type="date" id="data_pagamento" name="data_pagamento" value="<?= $editing ? htmlspecialchars($edit_data['data_pagamento']) : date('Y-m-d') ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="valor">Valor:</label>
                        <input type="number" step="0.01" id="valor" name="valor" value="<?= $editing ? htmlspecialchars($edit_data['valor']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="referencia">Referência:</label>
                        <input type="text" id="referencia" name="referencia" value="<?= $editing ? htmlspecialchars($edit_data['referencia']) : '' ?>" required>
                    </div>

                    <div class="input-group">
                        <label for="observacoes">Observações:</label>
                        <textarea id="observacoes" name="observacoes"><?= $editing ? htmlspecialchars($edit_data['observacoes']) : '' ?></textarea>
                    </div>

                    <button type="submit" name="<?= $editing ? 'update_pagamento' : 'add_pagamento' ?>" class="submit-btn"><?= $editing ? 'Salvar Alterações' : 'Registar Pagamento' ?></button>
                    <?php if ($editing): ?>
                        <button type="button" class="submit-btn" onclick="window.location.href='pagamentos_view.php'">Cancelar</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php if (!$editing): // Only show the table if not in editing mode ?>
                <h2>Pagamentos Registados</h2>
                <?php
                // Ensure $pagamentos is defined and holds results before trying to access its properties
                // This block is only executed when !$editing, so $pagamentos *should* be defined from pagamentos.php
                // However, to be extra safe and avoid the undefined variable error, we can add a check.
                if (isset($pagamentos) && $pagamentos->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Atleta</th>
                                <th>Data do Pagamento</th>
                                <th>Valor</th>
                                <th>Referência</th>
                                <th>Observações</th>
                                <?php if (in_array($current_role, ['admin', 'funcionario'])): // Only show actions column for admin/funcionario ?>
                                    <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset pagamentos result pointer to the beginning for displaying the table
                            $pagamentos->data_seek(0);
                            while($row = $pagamentos->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['data_pagamento']) ?></td>
                                <td><?= htmlspecialchars($row['valor']) ?> €</td>
                                <td><?= htmlspecialchars($row['referencia']) ?></td>
                                <td><?= htmlspecialchars($row['observacoes']) ?></td>
                                <?php if (in_array($current_role, ['admin', 'funcionario'])): // Only show actions buttons for admin/funcionario ?>
                                    <td>
                                        <button type="button" class="submit-btn" onclick="window.location.href='pagamentos_view.php?edit_pagamento=<?= $row['id'] ?>'">Editar</button>
                                        <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar este pagamento?')) window.location.href='pagamentos.php?delete=<?= $row['id'] ?>'">Eliminar</button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Não há pagamentos registados.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

<?php
$conn->close(); // Close the database connection
?>
