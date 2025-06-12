<?php
session_start();
// Database connection
$conn = new mysqli("localhost", "root", "", "the goats fc");

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Include the logic file
require_once 'marketing.php';

// Check if the current user is authorized to add/edit/delete marketing campaigns
$is_authorized = isset($_SESSION['role_login']) && ($_SESSION['role_login'] === 'admin' || $_SESSION['role_login'] === 'funcionario');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>The Goats FC - Gestão de Marketing</title>
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

    <section id="marketing-section" class="active" aria-label="Gestão de Marketing">
      <h2>Gestão de Marketing</h2>

      <?php if ($is_authorized): // Only show form if authorized ?>
      <form action="marketing.php" method="post" id="marketing-form" aria-describedby="marketing-form-desc">
        <input type="hidden" id="campaign-id" name="id" value="<?= $editing ? $edit_data['id'] : '' ?>" />
        <p id="marketing-form-desc"><?= $editing ? 'Editar Campanha de Marketing' : 'Nova Campanha de Marketing' ?></p>
        <div>
          <label for="campaign-title">Título da Campanha</label>
          <input type="text" id="campaign-title" name="title" required value="<?= $editing ? htmlspecialchars($edit_data['title']) : '' ?>">
        </div>
        <div>
          <label for="campaign-type">Tipo</label>
          <select id="campaign-type" name="type" required>
            <option value="">-- Selecione --</option>
            <option value="Promoção" <?= $editing && $edit_data['type'] === 'Promoção' ? 'selected' : '' ?>>Promoção</option>
            <option value="Parceria" <?= $editing && $edit_data['type'] === 'Parceria' ? 'selected' : '' ?>>Parceria</option>
            <option value="Evento" <?= $editing && $edit_data['type'] === 'Evento' ? 'selected' : '' ?>>Evento</option>
            <option value="Outro" <?= $editing && $edit_data['type'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
          </select>
        </div>
        <div>
          <label for="campaign-date">Data</label>
          <input type="date" id="campaign-date" name="date" required value="<?= $editing ? $edit_data['date'] : '' ?>">
        </div>
        <div>
          <label for="campaign-desc">Descrição</label>
          <textarea name="description" id="campaign-desc" placeholder="Descrição detalhada da campanha" rows="5" cols="40"><?= $editing ? htmlspecialchars($edit_data['description']) : '' ?></textarea>
        </div>
        <div>
          <button type="submit" class="submit-btn" name="<?= $editing ? 'update_campaign' : 'save_campaign' ?>">
            <?= $editing ? 'Atualizar Campanha' : 'Guardar Campanha' ?>
          </button>
          <?php if ($editing): ?>
            <button type="button" class="submit-btn" onclick="window.location.href='marketing_view.php'">Cancelar</button>
          <?php endif; ?>
        </div>
      </form>
      <?php endif; ?>

      <?php if (!$editing): // Only show the table if not in editing mode ?>
        <h3>Campanhas Agendadas</h3>
        <div style="margin-bottom: 20px;">
          <label for="marketing-filter">Filtrar por Tipo:</label>
          <select id="marketing-filter" onchange="filterMarketingRecords()">
            <option value="all">Todos</option>
            <option value="Promoção">Promoção</option>
            <option value="Parceria">Parceria</option>
            <option value="Evento">Evento</option>
            <option value="Outro">Outro</option>
          </select>
        </div>
        <table id="marketing-table" aria-live="polite" aria-relevant="additions removals">
          <thead>
            <tr>
              <th>Título</th>
              <th>Tipo</th>
              <th>Data</th>
              <th>Descrição</th>
              <?php if ($is_authorized): // Only show Actions column if authorized ?>
              <th>Ações</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php
              if ($marketing_campaigns->num_rows > 0) {
                  while ($row = $marketing_campaigns->fetch_assoc()):
            ?>
              <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                <?php if ($is_authorized): ?>
                <td>
                  <button type="button" class="submit-btn" onclick="window.location.href='marketing_view.php?edit=<?= $row['id'] ?>'">Editar</button>
                  <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar esta campanha?')) window.location.href='marketing_view.php?delete=<?= $row['id'] ?>'">Eliminar</button>
                </td>
                <?php endif; ?>
              </tr>
            <?php
                  endwhile;
              } else {
                  echo '<tr><td colspan="' . ($is_authorized ? '5' : '4') . '">Nenhuma campanha encontrada.</td></tr>';
              }
            ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
  <script>
    function filterMarketingRecords() {
        const filterValue = document.getElementById('marketing-filter').value;
        const table = document.getElementById('marketing-table');
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

    document.addEventListener('DOMContentLoaded', filterMarketingRecords);
  </script>
</body>
</html>

<?php
$conn->close();
?>