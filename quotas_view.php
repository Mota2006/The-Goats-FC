<?php
session_start(); // Ensure session is started

// Incluir a lógica
require_once 'quotas.php'; // Corrected to include quotas.php

// Fetch the socio's name if in editing mode
$socio_name_for_edit = '';
if ($editing && isset($edit_data['socio_id'])) {
    $stmt_socio_name = $conn->prepare("SELECT nome FROM socios WHERE id = ?");
    if ($stmt_socio_name) {
        $stmt_socio_name->bind_param("i", $edit_data['socio_id']);
        $stmt_socio_name->execute();
        $res_socio_name = $stmt_socio_name->get_result();
        if ($row_socio_name = $res_socio_name->fetch_assoc()) {
            $socio_name_for_edit = $row_socio_name['nome'];
        }
        $stmt_socio_name->close();
    }
}

// Fetch the current socio's name if the role is 'socio' and not in editing mode
$socio_name_for_current_user = '';
$current_socio_id_value = '';
if ($current_role === 'socio' && !$editing) {
    $stmt_current_socio = $conn->prepare("SELECT id, nome FROM socios WHERE user_id = ?");
    if ($stmt_current_socio) {
        $stmt_current_socio->bind_param("i", $current_login_id);
        $stmt_current_socio->execute();
        $res_current_socio = $stmt_current_socio->get_result();
        if ($row_current_socio = $res_current_socio->fetch_assoc()) {
            $socio_name_for_current_user = $row_current_socio['nome'];
            $current_socio_id_value = $row_current_socio['id'];
        }
        $stmt_current_socio->close();
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
        <section id="quotas-section" class="active" aria-label="Pagamento dos Sócios">
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

            <h2><?= $editing ? 'Editar Quota' : 'Gestão de Quotas' ?></h2>
            <form method="post">
                <input type="hidden" name="quota_id" value="<?= $editing ? htmlspecialchars($edit_data['id']) : '' ?>">

                <label for="socio_id">Sócio</label>
                <?php if ($current_role === 'socio' && !$editing): ?>
                    <input type="hidden" name="socio_id" value="<?= htmlspecialchars($current_socio_id_value) ?>">
                    <input type="text" value="<?= htmlspecialchars($socio_name_for_current_user) ?>" readonly>
                <?php elseif ($editing): // If editing, display socio name and keep socio_id as hidden ?>
                    <input type="text" value="<?= htmlspecialchars($socio_name_for_edit) ?>" readonly>
                    <input type="hidden" name="socio_id" value="<?= htmlspecialchars($edit_data['socio_id']) ?>">
                <?php else: // If adding, show dropdown ?>
                    <select name="socio_id" id="socio_id">
                        <option value="">-- Selecione --</option>
                        <?php
                        // Check if $socios is a valid mysqli_result object before iterating
                        $sql_socios_dropdown = "SELECT id, nome FROM socios ORDER BY nome ASC";
                        $socios = $conn->query($sql_socios_dropdown);

                        if (isset($socios) && $socios instanceof mysqli_result && $socios->num_rows > 0) {
                            $socios->data_seek(0); // Reset pointer if already fetched
                            while($s = $socios->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($s['id']) ?>"
                                <?= (isset($edit_data['socio_id']) && $edit_data['socio_id'] == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nome']) ?>
                            </option>
                        <?php
                            endwhile;
                        }
                        ?>
                    </select>
                <?php endif; ?>

                <label for="mes_ano">Mês/Ano</label>
                <input type="month" name="mes_ano" id="mes_ano" value="<?= $editing ? htmlspecialchars($edit_data['mes_ano']) : date('Y-m') ?>">

                <label for="valor">Valor</label>
                <input type="number" step="0.01" name="valor" id="valor" value="<?= $editing ? htmlspecialchars($edit_data['valor']) : '' ?>">

                <label for="data_pagamento">Data de Pagamento</label>
                <input type="date" name="data_pagamento" id="data_pagamento" value="<?= $editing ? htmlspecialchars($edit_data['data_pagamento']) : date('Y-m-d') ?>">

                <label for="estado">Estado</label>
                <select name="estado" id="estado">
                    <option value="Pago" <?= ($editing && $edit_data['estado'] == 'Pago') ? 'selected' : '' ?>>Pago</option>
                    <option value="Em Falta" <?= ($editing && $edit_data['estado'] == 'Em Falta') ? 'selected' : '' ?>>Em Falta</option>
                </select>

                <div class="form-actions">
                    <button type="submit" name="add_quota" class="submit-btn"><?= $editing ? 'Atualizar Quota' : 'Adicionar Quota' ?></button>
                    <?php if ($editing): ?>
                        <button type="button" class="submit-btn" onclick="window.location.href='quotas_view.php'">Cancelar Edição</button>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!$editing): // Only show the table if not in editing mode ?>
                <h3>Histórico de Quotas</h3>
                <?php
                // Check if $quotas is a valid mysqli_result object before using its properties
                if (isset($quotas) && $quotas instanceof mysqli_result && $quotas->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Sócio</th>
                                <th>Mês</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Estado</th>
                                <?php if (in_array($current_role, ['admin', 'funcionario'])): // Only show actions column for admin/funcionario ?>
                                    <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reset quotas result pointer to the beginning for displaying the table
                            $quotas->data_seek(0);
                            while($q = $quotas->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($q['nome']) ?></td>
                                <td><?= htmlspecialchars($q['mes_ano']) ?></td>
                                <td><?= htmlspecialchars($q['data_pagamento']) ?></td>
                                <td><?= htmlspecialchars($q['valor']) ?> €</td>
                                <td><?= htmlspecialchars($q['estado']) ?></td>
                                <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                    <td>
                                        <button type="button" class="submit-btn" onclick="window.location.href='quotas_view.php?edit_quota=<?= $q['id'] ?>'">Editar</button>
                                        <?php if (in_array($current_role, ['admin', 'funcionario'])): ?>
                                            <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar esta quota?')) window.location.href='quotas.php?delete=<?= $q['id'] ?>'">Eliminar</button>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Não há quotas registadas.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

<?php
$conn->close(); // Close the database connection
?>
