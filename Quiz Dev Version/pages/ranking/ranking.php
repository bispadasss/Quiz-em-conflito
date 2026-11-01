<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/boot.php';

requireLogin();

$rankedUsers = rankedUsers();
$loggedUserId = loggedUserId();

$pageTitle = 'Ranking';
$pageStyle = 'pages/ranking/css/ranking.css';

require ROOT_PATH . '/templates/header.php';
?>
<main class="card card-wide">
  <h1>Ranking</h1>
  <p class="subtitle">Mais acertos primeiro; no empate, vence o menor tempo.</p>

  <?php if ($rankedUsers === []): ?>
    <p class="empty">Ninguém jogou ainda. Seja o primeiro!</p>
  <?php endif; ?>

  <?php if ($rankedUsers !== []): ?>
    <table class="ranking">
      <thead>
        <tr>
          <th>#</th>
          <th>Jogador</th>
          <th>Acertos</th>
          <th>Tempo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rankedUsers as $position => $user): ?>
          <tr class="<?= $user['id'] === $loggedUserId ? 'is-current-user' : '' ?>">
            <td><?= $position + 1 ?>º</td>
            <td><?= escape($user['name']) ?></td>
            <td><?= $user['best_correct_answers'] ?>/<?= QUESTIONS_PER_GAME ?></td>
            <td><?= formatTime($user['best_time_seconds']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>
<?php require ROOT_PATH . '/templates/footer.php'; ?>
