<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

if (!isPostRequest()) {
    redirect('index.php');
}

$name = trim((string) filter_input(INPUT_POST, 'name'));
$password = (string) filter_input(INPUT_POST, 'password');
$isRegistration = filter_input(INPUT_POST, 'action') === 'register';

$user = $isRegistration ? handleRegister($name, $password) : handleLogin($name, $password);

startUserSession($user);
setFlash('Bem-vindo, ' . $user['name'] . '!');
redirect('pages/game/game.php');
