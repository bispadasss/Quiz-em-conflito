<?php

declare(strict_types=1);

function hasPlayed(array $user): bool
{
    return $user['best_time_seconds'] !== null;
}

function isBetterGame(array $user, int $correctAnswers, float $time): bool
{
    if (!hasPlayed($user)) {
        return true;
    }

    if ($correctAnswers !== $user['best_correct_answers']) {
        return $correctAnswers > $user['best_correct_answers'];
    }

    return $time < $user['best_time_seconds'];
}

function saveBestGame(int $userId, int $correctAnswers, float $time): void
{
    $user = findUserById($userId);

    if ($user === null || !isBetterGame($user, $correctAnswers, $time)) {
        return;
    }

    $user['best_correct_answers'] = $correctAnswers;
    $user['best_time_seconds'] = round($time, 2);
    replaceUser($user);
}

function compareRanking(array $first, array $second): int
{
    return [$second['best_correct_answers'], $first['best_time_seconds']]
        <=> [$first['best_correct_answers'], $second['best_time_seconds']];
}

function rankedUsers(): array
{
    $players = array_filter(listUsers(), 'hasPlayed');
    usort($players, 'compareRanking');

    return $players;
}
