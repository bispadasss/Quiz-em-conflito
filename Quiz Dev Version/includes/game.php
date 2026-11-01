<?php

declare(strict_types=1);

function gameValue(string $key): mixed
{
    return $_SESSION['game'][$key];
}

function setGameValue(string $key, mixed $value): void
{
    $_SESSION['game'][$key] = $value;
}

function startGame(): void
{
    $_SESSION['game'] = [
        'question_ids' => drawQuestionIds(QUESTIONS_PER_GAME),
        'current_index' => 0,
        'correct_answers' => 0,
        'question_started_at' => null,
        'total_time_seconds' => 0.0,
    ];
}

function isGameInProgress(): bool
{
    return isset($_SESSION['game']);
}

function totalQuestions(): int
{
    return count(gameValue('question_ids'));
}

function currentQuestionNumber(): int
{
    return gameValue('current_index') + 1;
}

function currentQuestionId(): int
{
    $questionIds = gameValue('question_ids');

    return $questionIds[gameValue('current_index')];
}

function isGameOver(): bool
{
    return gameValue('current_index') >= totalQuestions();
}

function correctAnswersCount(): int
{
    return gameValue('correct_answers');
}

function registerCorrectAnswer(): void
{
    setGameValue('correct_answers', correctAnswersCount() + 1);
}

function advanceQuestion(): void
{
    setGameValue('current_index', gameValue('current_index') + 1);
}
