<?php
session_start();
// Ligação à base de dados
$conn = new mysqli("localhost", "root", "", "the goats fc");

$current_user = $_SESSION['username'] ?? '';
$current_login_id = $_SESSION['login_id'] ?? '';
$current_role = $_SESSION['role_login'] ?? ''; // Use role_login here

// Incluir a lógica
require_once 'financeiro.php';
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

    <!-- Finance Management -->
    <section id="finance-section" class="active" aria-label="Gestão Financeira">
      <h2>Gestão Financeira</h2>
      <form action="financeiro.php" method="post" id="finance-form" aria-describedby="finance-form-desc">
        <input type="hidden" id="finance-id" name="finance-id" />
        <p id="finance-form-desc"><?= $editing ? 'Editar Registo Financeiro' : 'Novo Registo Financeiro' ?></p>
        <?php if ($editing): ?>
          <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>
        <div>
          <label for="finance-type">Tipo</label>
          <select id="finance-type" name="type" required>
            <option value="">-- Selecione --</option>
            <option value="Receita" <?= $editing && $edit_data['type'] === 'Receita' ? 'selected' : '' ?>>Receita</option>
            <option value="Despesa" <?= $editing && $edit_data['type'] === 'Despesa' ? 'selected' : '' ?>>Despesa</option>
          </select>
        </div>
        <div>
          <label>Montante<input type="number" id="finance-amount" step="0.01" min="0" name="amount" required value="<?= $editing ? $edit_data['amount'] : '' ?>"></label>
        </div>
        <div>
          <label>Descrição<textarea name="description" id="finance-desc" placeholder="Descrição detalhada" rows="3" cols="40"><?= $editing ? htmlspecialchars($edit_data['description']) : '' ?></textarea></label>
        </div>
        <div>
          <button type="submit" class="submit-btn" name="<?= $editing ? 'update_finance' : 'save_finance' ?>">
            <?= $editing ? 'Atualizar Registo' : 'Adicionar Registo' ?>
          </button>
          <?php if ($editing): ?>
            <button type="button" class="submit-btn" onclick="window.location.href='financeiro_view.php'">Cancelar</button>
          <?php endif; ?>
        </div>
      </form>
      <?php if (!$editing): // Only show the table if not in editing mode ?>
        <h3>Histórico Financeiro</h3>
        <div style="margin-bottom: 20px;">
          <label for="finance-filter">Filtrar por Tipo:</label>
          <select id="finance-filter" onchange="filterFinanceRecords()">
            <option value="all">Todos</option>
            <option value="Receita">Receita</option>
            <option value="Despesa">Despesa</option>
          </select>
        </div>
        <table id="finance-table" aria-live="polite" aria-relevant="additions removals">
          <thead>
            <tr><th>Tipo</th><th>Valor (€)</th><th>Descrição</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php 
              // Obter todos os registos
              $transactions = $conn->query("SELECT * FROM finance ORDER BY id DESC");
              while ($row = $transactions->fetch_assoc()): 
              $isPayment = ($row['type'] === 'Receita' && 
                (stripos($row['description'], 'jogador') !== false || 
                stripos($row['description'], 'atleta') !== false ||
                stripos($row['description'], 'sócio') !== false ||
                stripos($row['description'], 'socio') !== false ||
                stripos($row['description'], 'pagamento') !== false));
              ?>
              <tr>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= number_format($row['amount'], 2, ',', '.') ?></td>
                <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                <td>
                  <?php if (!$isPayment): ?>
                    <button type="button" class="submit-btn" onclick="window.location.href='financeiro_view.php?edit=<?= $row['id'] ?>'">Editar</button>
                    <button type="button" class="submit-btn" onclick="if(confirm('Tem a certeza que quer eliminar este registo?')) window.location.href='financeiro_view.php?delete=<?= $row['id'] ?>'">Eliminar</button>
                  <?php else: ?>
                    <span style="color: #999;">N/A</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <h4>Resumo</h4>
        <p><strong>Total de Receitas:</strong> € <?= number_format($receitas, 2, ',', '.') ?></p>
        <p><strong>Total de Despesas:</strong> € <?= number_format($despesas, 2, ',', '.') ?></p>
        <p><strong>Saldo Atual:</strong>
          <span style="font-weight: bold; color: <?= $saldo >= 0 ? 'green' : 'red' ?>">
            € <?= number_format($saldo, 2, ',', '.') ?>
          </span>
        </p>
      <?php endif; ?>  
    </section>
  </main>
  <script>
    function filterFinanceRecords() {
        const filterValue = document.getElementById('finance-filter').value;
        const table = document.getElementById('finance-table');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
            const row = rows[i];
            const typeCell = row.cells[0]; // Assuming 'Tipo' is the first column (index 0)

            if (filterValue === 'all' || typeCell.textContent === filterValue) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        }
    }

    // Call filterFinanceRecords on page load to set initial state (e.g., show "Todos")
    document.addEventListener('DOMContentLoaded', filterFinanceRecords);
  </script>
</body>
</html>

<?php
$conn->close();
?>