<?php

declare(strict_types=1);

function startQuestionTimer(): void
{
    if (isQuestionTimerRunning()) {
        return;
    }

    setGameValue('question_started_at', microtime(true));
}

function isQuestionTimerRunning(): bool
{
    return gameValue('question_started_at') !== null;
}

function elapsedQuestionTime(): float
{
    return microtime(true) - gameValue('question_started_at');
}

function stopQuestionTimer(): float
{
    $elapsed = elapsedQuestionTime();
    setGameValue('total_time_seconds', totalGameTime() + $elapsed);
    setGameValue('question_started_at', null);

    return $elapsed;
}

function totalGameTime(): float
{
    return gameValue('total_time_seconds');
}
