<?php

declare(strict_types=1);

function findFirst(array $items, callable $condition): ?array
{
    $matches = array_values(array_filter($items, $condition));

    return $matches[0] ?? null;
}
