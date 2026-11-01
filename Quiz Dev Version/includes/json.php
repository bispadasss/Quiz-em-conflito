<?php

declare(strict_types=1);

function readJson(string $path): array
{
    $content = file_get_contents($path);

    if ($content === false || trim($content) === '') {
        return [];
    }

    return json_decode($content, true) ?? [];
}

function saveJson(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    file_put_contents($path, $json . "\n", LOCK_EX);
}
