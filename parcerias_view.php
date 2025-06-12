<?php
session_start();
// Database connection
$conn = new mysqli("localhost", "root", "", "the goats fc");

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? '';

// Include the logic file
require_once 'parcerias.php';

// Check if the current user is authorized to add/edit/delete partnerships
$is_authorized = isset($_SESSION['role_login']) && ($_SESSION['role_login'] === 'admin' || $_SESSION['role_login'] === 'funcionario');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>The Goats FC - Gestão de Entidades</title>
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
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
    </div>

    <section id="parcerias-section" class="active" aria-label="Gestão de Entidades">
      <h2>Gestão de Entidades</h2>

      <?php if ($is_authorized): // Only show form if authorized ?>
      <form action="parcerias.php" method="post" id="parcerias-form" aria-describedby="parcerias-form-desc">
        <input type="hidden" id="partnership-id" name="id" value="<?= $editing ? $edit_data['id'] : '' ?>" />
        <p id="parcerias-form-desc"><?= $editing ? 'Editar Parceria Existente' : 'Nova Parceria' ?></p>
        <div>
          <label for="partnership-name">Nome da Entidade</label>
          <input type="text" id="partnership-name" name="nome_entidade" required value="<?= $editing ? htmlspecialchars($edit_data['nome_entidade']) : '' ?>">
        </div>
        <div>
          <label for="partnership-type">Tipo</label>
          <select id="partnership-type" name="type" required>
            <option value="">-- Selecione --</option>
            <option value="Protocolo" <?= $editing && $edit_data['tipo'] === 'Protocolo' ? 'selected' : '' ?>>Protocolo</option>
            <option value="Patrocínio" <?= $editing && $edit_data['tipo'] === 'Patrocínio' ? 'selected' : '' ?>>Patrocínio</option>
            <option value="Outra" <?= $editing && $edit_data['tipo'] === 'Outra' ? 'selected' : '' ?>>Outra</option>
          </select>
        </div>
        <div>
          <label for="partnership-start-date">Data Início</label>
          <input type="date" id="partnership-start-date" name="data_inicio" required value="<?= $editing ? $edit_data['data_inicio'] : '' ?>">
        </div>
        <div>
          <label for="partnership-end-date">Data Fim</label>
          <input type="date" id="partnership-end-date" name="data_fim" value="<?= $editing ? $edit_data['data_fim'] : '' ?>">
        </div>
        <div>
          <label for="partnership-desc">Descrição</label>
          <textarea name="description" id="partnership-desc" placeholder="Descrição detalhada da parceria" rows="5" cols="40"><?= $editing ? htmlspecialchars($edit_data['descricao']) : '' ?></textarea>
        </div>
        <div>
          <button type="submit" class="submit-btn" name="<?= $editing ? 'update_partnership' : 'save_partnership' ?>">
            <?= $editing ? 'Atualizar Parceria' : 'Guardar' ?>
          </button>
          <?php if ($editing): ?>
            <button type="button" class="submit-btn" onclick="window.location.href='parcerias_view.php'">Cancelar</button>
          <?php endif; ?>
        </div>
      </form>
      <?php endif; ?>

      <?php if (!$editing): // Only show the table if not in editing mode ?>
        <h3>Entidades</h3>
        <div style="margin-bottom: 20px;">
          <label for="partnership-filter">Filtrar por Tipo:</label>
          <select id="partnership-filter" onchange="filterPartnershipRecords()">
            <option value="all">Todos</option>
            <option value="Protocolo">Protocolo</option>
            <option value="Patrocínio">Patrocínio</option>
            <option value="Outra">Outra</option>
          </select>
        </div>
        <table id="parcerias-table" aria-live="polite" aria-relevant="additions removals">
          <thead>
            <tr>
              <th>Entidade</th>
              <th>Tipo</th>
              <th>Período</th>
              <th>Descrição</th>
              <?php if ($is_authorized): // Only show Actions column if authorized ?>
              <th>Ações</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php
              if ($parcerias->num_rows > 0) {
                  while ($row = $parcerias->fetch_assoc()):
            ?>
              <tr>
                <td><?= htmlspecialchars($row['nome_entidade']) ?></td>
                <td><?= htmlspecialchars($row['tipo']) ?></td>
                <td><?= htmlspecialchars($row['data_inicio']) ?> a <?= htmlspecialchars($row['data_fim']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['descricao'])) ?></td>
                <?php if ($is_authorized): ?>
                <td>
                  <button type="button" class="submit-btn" onclick="window.location.href='parcerias_view.php?edit=<?= $row['id'] ?>'">Editar</button>
                  <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar esta parceria?')) window.location.href='parcerias_view.php?delete=<?= $row['id'] ?>'">Eliminar</button>
                </td>
                <?php endif; ?>
              </tr>
            <?php
                  endwhile;
              } else {
                  echo '<tr><td colspan="' . ($is_authorized ? '5' : '4') . '">Nenhuma parceria encontrada.</td></tr>';
              }
            ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
  <script>
    function filterPartnershipRecords() {
        const filterValue = document.getElementById('partnership-filter').value;
        const table = document.getElementById('parcerias-table');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
            const row = rows[i];
            const typeCell = row.cells[1]; // 'Tipo' is the second column (index 1)

            if (filterValue === 'all' || typeCell.textContent === filterValue) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        }
    }

    document.addEventListener('DOMContentLoaded', filterPartnershipRecords);
  </script>
</body>
</html>

<?php
$conn->close();
?>