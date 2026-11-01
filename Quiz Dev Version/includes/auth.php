<?php

declare(strict_types=1);

function authenticate(string $name, string $password): ?array
{
    $user = findUserByName($name);

    if ($user === null) {
        return null;
    }

    if ($user['password'] !== $password) {
        return null;
    }

    return $user;
}

function registrationError(string $name, string $password): ?string
{
    if (!isValidName($name)) {
        return 'O nome deve ter entre ' . NAME_MIN_LENGTH . ' e ' . NAME_MAX_LENGTH . ' caracteres.';
    }

    if (!isValidPassword($password)) {
        return 'A senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.';
    }

    if (findUserByName($name) !== null) {
        return 'Esse nome já está em uso.';
    }

    return null;
}

function failAuthentication(string $message): never
{
    setFlash($message, 'error');
    redirect('index.php');
}

function handleRegister(string $name, string $password): array
{
    $error = registrationError($name, $password);

    if ($error !== null) {
        failAuthentication($error);
    }

    return registerUser($name, $password);
}

function handleLogin(string $name, string $password): array
{
    $user = authenticate($name, $password);

    if ($user === null) {
        failAuthentication('Nome ou senha inválidos.');
    }

    return $user;
}
