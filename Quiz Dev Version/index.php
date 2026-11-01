<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

if (loggedUserId() !== null) {
    redirect('pages/game/game.php');
}

$pageTitle = 'Entrar';

require ROOT_PATH . '/templates/header.php';
?>
<main class="card">
  <h1>Quiz Muito Louco!</h1>
  <p class="subtitle">
    Responda <?= QUESTIONS_PER_GAME ?> perguntas o mais rápido que conseguir.
    Quem acertar mais fica no topo do ranking; no empate, vence o mais rápido.
  </p>

  <form class="form" method="post" action="<?= url('authenticate.php') ?>">
    <label for="name">Nome</label>
    <input type="text" id="name" name="name" required autocomplete="username"
      minlength="<?= NAME_MIN_LENGTH ?>" maxlength="<?= NAME_MAX_LENGTH ?>">

    <label for="password">Senha</label>
    <input type="password" id="password" name="password" required autocomplete="current-password"
      minlength="<?= PASSWORD_MIN_LENGTH ?>">

    <div class="actions">
      <button class="button" type="submit" name="action" value="login">Entrar</button>
      <button class="button button-secondary" type="submit" name="action" value="register">Cadastrar</button>
    </div>
  </form>
</main>
<?php require ROOT_PATH . '/templates/footer.php'; ?>
