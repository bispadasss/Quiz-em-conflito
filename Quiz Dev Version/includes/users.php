<?php

declare(strict_types=1);

function listUsers(): array
{
    return readJson(USERS_FILE);
}

function saveUsers(array $users): void
{
    saveJson(USERS_FILE, array_values($users));
}

function findUserByName(string $name): ?array
{
    $normalizedName = mb_strtolower($name);

    return findFirst(listUsers(), fn (array $user) => mb_strtolower($user['name']) === $normalizedName);
}

function findUserById(int $id): ?array
{
    return findFirst(listUsers(), fn (array $user) => $user['id'] === $id);
}

function nextUserId(array $users): int
{
    return max([0, ...array_column($users, 'id')]) + 1;
}

function isValidName(string $name): bool
{
    $length = mb_strlen($name);

    return $length >= NAME_MIN_LENGTH && $length <= NAME_MAX_LENGTH;
}

function isValidPassword(string $password): bool
{
    return mb_strlen($password) >= PASSWORD_MIN_LENGTH;
}

function registerUser(string $name, string $password): array
{
    $users = listUsers();
    $user = [
        'id' => nextUserId($users),
        'name' => $name,
        'password' => $password,
        'best_correct_answers' => 0,
        'best_time_seconds' => null,
    ];

    saveUsers([...$users, $user]);

    return $user;
}

function replaceUser(array $updatedUser): void
{
    $users = array_map(
        fn (array $user) => $user['id'] === $updatedUser['id'] ? $updatedUser : $user,
        listUsers()
    );

    saveUsers($users);
}
