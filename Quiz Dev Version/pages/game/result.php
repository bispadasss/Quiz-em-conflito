<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/boot.php';

requireLogin();

$result = lastGameResult();

if ($result === null) {
    redirect('pages/game/game.php');
}

$pageTitle = 'Resultado';
$pageStyle = 'pages/game/css/game.css';

require ROOT_PATH . '/templates/header.php';
?>
<main class="card result">
  <h1>Fim de jogo!</h1>

  <?php if ($result['is_new_record']): ?>
    <p class="badge">Novo recorde!</p>
  <?php endif; ?>

  <p class="score">
    <strong><?= $result['correct_answers'] ?></strong> de <?= $result['total_questions'] ?> acertos
  </p>
  <p class="total-time">Tempo total: <?= formatTime($result['time_seconds']) ?></p>

  <div class="actions">
    <a class="button" href="<?= url('pages/game/game.php') ?>">Jogar novamente</a>
    <a class="button button-secondary" href="<?= url('pages/ranking/ranking.php') ?>">Ver ranking</a>
  </div>
</main>
<?php require ROOT_PATH . '/templates/footer.php'; ?>
