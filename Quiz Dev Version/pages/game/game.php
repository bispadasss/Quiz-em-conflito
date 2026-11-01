<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/boot.php';

requireLogin();

if (!isGameInProgress()) {
    startGame();
}

startQuestionTimer();

$question = findQuestionById(currentQuestionId());
$elapsedSeconds = round(elapsedQuestionTime(), 1);
$questionNumber = currentQuestionNumber();
$totalQuestions = totalQuestions();

$pageTitle = 'Pergunta ' . $questionNumber;
$pageStyle = 'pages/game/css/game.css';
$pageScript = 'pages/game/js/game.js';

require ROOT_PATH . '/templates/header.php';
?>
<main class="card">
  <div class="game-status">
    <span>Pergunta <?= $questionNumber ?> de <?= $totalQuestions ?></span>
    <span class="timer" data-elapsed="<?= $elapsedSeconds ?>"><?= formatTime($elapsedSeconds) ?></span>
  </div>
  <progress class="progress" value="<?= $questionNumber - 1 ?>" max="<?= $totalQuestions ?>"></progress>

  <h1 class="question"><?= escape($question['question']) ?></h1>

  <form class="answer-form" method="post" action="<?= url('pages/game/answer.php') ?>">
    <input type="hidden" name="question_id" value="<?= $question['id'] ?>">

    <?php foreach ($question['options'] as $index => $option): ?>
      <label class="option">
        <input type="radio" name="option" value="<?= $index ?>" required>
        <span><?= escape($option) ?></span>
      </label>
    <?php endforeach; ?>

    <button class="button" type="submit">Responder</button>
  </form>
</main>
<?php require ROOT_PATH . '/templates/footer.php'; ?>
