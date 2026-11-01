<?php

declare(strict_types=1);

function escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function url(string $path): string
{
    return BASE_URL . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function isPostRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function formatTime(?float $seconds): string
{
    if ($seconds === null) {
        return '-';
    }

    $minutes = intdiv((int) $seconds, 60);
    $remainingSeconds = number_format($seconds - $minutes * 60, 1, ',', '');

    if ($minutes === 0) {
        return $remainingSeconds . 's';
    }

    return $minutes . 'min ' . $remainingSeconds . 's';
}
