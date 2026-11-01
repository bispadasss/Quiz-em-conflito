<?php

declare(strict_types=1);

function answerFeedback(array $question, bool $isCorrect, float $elapsed): string
{
    if ($isCorrect) {
        return 'Acertou! (' . formatTime($elapsed) . ')';
    }

    return 'Errou! A resposta era "' . correctOptionText($question) . '".';
}

function buildGameResult(array $user): array
{
    $correctAnswers = correctAnswersCount();
    $time = totalGameTime();

    return [
        'correct_answers' => $correctAnswers,
        'total_questions' => totalQuestions(),
        'time_seconds' => $time,
        'is_new_record' => isBetterGame($user, $correctAnswers, $time),
    ];
}

function finishGame(int $userId): void
{
    // O resultado é montado antes de salvar, senão a partida nunca seria "novo recorde".
    $_SESSION['last_result'] = buildGameResult(findUserById($userId));
    saveBestGame($userId, correctAnswersCount(), totalGameTime());
    unset($_SESSION['game']);
}

function lastGameResult(): ?array
{
    return $_SESSION['last_result'] ?? null;
}
