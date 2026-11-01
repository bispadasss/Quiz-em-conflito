<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/boot.php';

requireLogin();

if (!isPostRequest() || !isGameInProgress() || !isQuestionTimerRunning()) {
    redirect('pages/game/game.php');
}

// Descarta envios de uma aba antiga, que responderiam a pergunta errada.
if (filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT) !== currentQuestionId()) {
    redirect('pages/game/game.php');
}

$question = findQuestionById(currentQuestionId());
$option = filter_input(INPUT_POST, 'option', FILTER_VALIDATE_INT);

if (!is_int($option) || !isValidOption($question, $option)) {
    setFlash('Escolha uma das opções.', 'error');
    redirect('pages/game/game.php');
}

$elapsed = stopQuestionTimer();
$isCorrect = isCorrectAnswer($question, $option);

if ($isCorrect) {
    registerCorrectAnswer();
}

setFlash(answerFeedback($question, $isCorrect, $elapsed), $isCorrect ? 'success' : 'error');
advanceQuestion();

if (isGameOver()) {
    finishGame(loggedUserId());
    redirect('pages/game/result.php');
}

redirect('pages/game/game.php');
