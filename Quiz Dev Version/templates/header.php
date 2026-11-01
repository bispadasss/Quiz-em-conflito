<?php $flash = pullFlash(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= escape($pageTitle) ?> | Quiz Muito Louco!</title>
  <link rel="stylesheet" href="<?= url('assets/global.css') ?>">
  <?php if (isset($pageStyle)): ?>
    <link rel="stylesheet" href="<?= url($pageStyle) ?>">
  <?php endif; ?>
</head>

<body>
  <header class="topbar">
    <a class="brand" href="<?= url('index.php') ?>">Quiz Muito Louco!</a>
    <?php if (loggedUserId() !== null): ?>
      <nav class="nav">
        <a href="<?= url('pages/game/game.php') ?>">Jogar</a>
        <a href="<?= url('pages/ranking/ranking.php') ?>">Ranking</a>
        <a href="<?= url('logout.php') ?>">Sair</a>
      </nav>
    <?php endif; ?>
  </header>

  <?php if ($flash !== null): ?>
    <p class="flash flash-<?= escape($flash['type']) ?>"><?= escape($flash['message']) ?></p>
  <?php endif; ?>
