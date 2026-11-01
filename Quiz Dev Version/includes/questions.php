<?php

declare(strict_types=1);

function listQuestions(): array
{
    return readJson(QUESTIONS_FILE);
}

function findQuestionById(int $id): ?array
{
    return findFirst(listQuestions(), fn (array $question) => $question['id'] === $id);
}

function drawQuestionIds(int $amount): array
{
    $ids = array_column(listQuestions(), 'id');
    shuffle($ids);

    return array_slice($ids, 0, $amount);
}

function isValidOption(array $question, int $option): bool
{
    return array_key_exists($option, $question['options']);
}

function isCorrectAnswer(array $question, int $option): bool
{
    return $question['answer'] === $option;
}

function correctOptionText(array $question): string
{
    return $question['options'][$question['answer']];
}
