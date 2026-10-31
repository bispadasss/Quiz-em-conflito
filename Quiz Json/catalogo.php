<?php
/* ============================================================
   CATÁLOGO — lista todos os quizzes salvos em JSON
   (cada quiz é um arquivo na pasta "quizzes/", veja quiz_storage.php)
   ============================================================ */

require_once __DIR__ . '/quiz_storage.php';

function v(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

 $quizzes = listar_quizzes();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quizzes — QUIZ!</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  :root {
    --preto: #0b0b0d; --preto-2: #131316; --preto-3: #1b1b1f;
    --amarelo: #FFD60A; --amarelo-2: #FFC300; --amarelo-3: #f5a623;
    --cinza-texto: #b9b9c0; --branco: #fff;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--preto);
    color: var(--branco);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
  }
  body::before, body::after {
    content: ""; position: fixed; width: 500px; height: 500px;
    border-radius: 50%; filter: blur(160px); opacity: .12;
    z-index: 0; pointer-events: none;
  }
  body::before { background: var(--amarelo);   top: -150px; right: -100px; }
  body::after  { background: var(--amarelo-3); bottom: -200px; left: -150px; }

  .shape { position: fixed; z-index: 0; opacity: .4; pointer-events: none;
           animation: flutuar 7s ease-in-out infinite; }
  .shape.circulo  { width: 34px; height: 34px; border-radius: 50%; background: var(--amarelo); top: 30%; left: 3%; }
  .shape.quadrado { width: 30px; height: 30px; border: 5px solid var(--amarelo); border-radius: 8px;
                    transform: rotate(20deg); top: 65%; right: 4%; animation-delay: 2s; }
  @keyframes flutuar { 0%,100% { translate: 0 0; } 50% { translate: 0 -25px; } }

  header {
    position: sticky; top: 0; z-index: 10;
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 6%;
    background: rgba(11,11,13,.75); backdrop-filter: blur(12px);
    border-bottom: 1px solid #222226;
  }
  .logo { font-size: 1.9rem; font-weight: 900; color: var(--branco); text-decoration: none; }
  .logo span { color: var(--amarelo); }
  header nav { display: flex; gap: 30px; }
  header nav a { color: var(--cinza-texto); text-decoration: none; font-weight: 600; font-size: .95rem; transition: color .25s; }
  header nav a:hover, header nav a.ativo { color: var(--amarelo); }

  .btn {
    display: inline-block; padding: 12px 30px; border-radius: 50px;
    font-family: inherit; font-weight: 800; font-size: .95rem;
    text-decoration: none; cursor: pointer; transition: transform .2s, box-shadow .2s, background .2s, color .2s;
    border: none;
  }
  .btn-amarelo  { background: var(--amarelo); color: var(--preto); }
  .btn-amarelo:hover  { transform: translateY(-3px) scale(1.03); box-shadow: 0 10px 30px rgba(255,214,10,.35); }
  .btn-contorno { border: 2px solid var(--amarelo); color: var(--amarelo); background: transparent; }
  .btn-contorno:hover { background: var(--amarelo); color: var(--preto); transform: translateY(-3px); }

  .pagina { position: relative; z-index: 1; width: 100%; max-width: 1100px; margin: 0 auto; padding: 55px 6% 90px; }

  .titulo-pagina h1 { font-size: clamp(2rem, 4.5vw, 3rem); font-weight: 900; letter-spacing: -1px; }
  .titulo-pagina em { font-style: normal; color: var(--amarelo); }
  .titulo-pagina p  { color: var(--cinza-texto); margin: 10px 0 36px; }

  /* ------ Grade de cards ------ */
  .grade { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }

  .card-quiz {
    background: var(--preto-2); border: 1px solid #26262b; border-radius: 20px;
    padding: 24px; display: flex; flex-direction: column; gap: 14px;
    transition: transform .25s, border-color .25s, box-shadow .25s;
  }
  .card-quiz:hover {
    transform: translateY(-6px); border-color: rgba(255,214,10,.6);
    box-shadow: 0 18px 40px rgba(255,214,10,.08);
  }
  .card-info { flex: 1; display: flex; flex-direction: column; gap: 12px; }
  .qtd {
    align-self: flex-start;
    background: rgba(255,214,10,.1); border: 1px solid rgba(255,214,10,.4);
    color: var(--amarelo); border-radius: 50px; padding: 5px 14px;
    font-size: .72rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;
  }
  .card-quiz h3 { font-size: 1.15rem; font-weight: 800; line-height: 1.35; }
  .card-quiz p  { color: var(--cinza-texto); font-size: .92rem; line-height: 1.55; flex: 1; }
  .card-rodape { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .card-botoes { display: flex; gap: 8px; align-items: center; }
  .btn-ranking {
    padding: 10px 14px; font-size: .85rem;
    color: var(--cinza-texto); border-color: #3a3a42;
  }
  .btn-ranking:hover { border-color: var(--amarelo); color: var(--amarelo); background: transparent; }
  .data { color: #6a6a72; font-size: .8rem; font-weight: 600; }
  .btn-jogar { padding: 10px 22px; font-size: .85rem; }

  /* ------ Estado vazio ------ */
  .vazio { text-align: center; background: var(--preto-2); border: 1px dashed #33333a;
           border-radius: 24px; padding: 70px 30px; }
  .vazio-icone { font-size: 3rem; margin-bottom: 14px; }
  .vazio h2 { font-weight: 900; margin-bottom: 8px; }
  .vazio p  { color: var(--cinza-texto); margin-bottom: 26px; }

  footer {
    position: relative; z-index: 1; border-top: 1px solid #222226;
    padding: 24px 6%; text-align: center; background: var(--preto-2);
  }
  footer p { color: var(--cinza-texto); font-size: .85rem; }

  @media (max-width: 700px) {
    header nav { display: none; }
  }
</style>
</head>
<body>

  <div class="shape circulo"></div>
  <div class="shape quadrado"></div>

  <header>
    <a class="logo" href="index.html">QUIZ<span>!</span></a>
    <nav>
      <a href="index.html">Início</a>
      <a href="catalogo.php" class="ativo">Quizzes</a>
      <a href="criar_quiz.php">Criar quiz</a>
    </nav>
    <a class="btn btn-amarelo" href="criar_quiz.php">Criar quiz</a>
  </header>

  <main class="pagina">
    <div class="titulo-pagina">
      <h1>Explore os <em>quizzes</em></h1>
      <p>Escolha um tema, teste seus conhecimentos e divirta-se!</p>
    </div>

    <?php if (count($quizzes) === 0): ?>

      <div class="vazio">
        <div class="vazio-icone">📭</div>
        <h2>Nenhum quiz publicado ainda...</h2>
        <p>Seja o primeiro: crie um quiz e ele aparece aqui na hora.</p>
        <a class="btn btn-amarelo" href="criar_quiz.php">+ Criar o primeiro quiz</a>
      </div>

    <?php else: ?>

      <div class="grade">
        <?php foreach ($quizzes as $q): $qtd = contar_perguntas($q); ?>
        <article class="card-quiz">
          <div class="card-info">
            <span class="qtd"><?= $qtd ?> <?= $qtd === 1 ? 'pergunta' : 'perguntas' ?></span>
            <h3><?= v($q['titulo'] ?? 'Quiz sem título') ?></h3>
            <p><?= v(($q['descricao'] ?? '') !== '' ? $q['descricao'] : 'Sem descrição — mas promete ser bom!') ?></p>
          </div>
          <div class="card-rodape">
            <span class="data">
              <?= (!empty($q['criado_em'])) ? v(date('d/m/Y', strtotime($q['criado_em']))) : '—' ?>
            </span>
            <div class="card-botoes">
              <a class="btn btn-contorno btn-jogar btn-ranking" href="ranking.php?id=<?= (int)($q['id'] ?? 0) ?>" title="Ver ranking deste quiz">🏆</a>
              <a class="btn btn-amarelo btn-jogar" href="jogar.php?id=<?= (int)($q['id'] ?? 0) ?>">▶ Jogar</a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </main>

  <footer>
    <p>© 2025 QUIZ! — Todos os direitos reservados.</p>
  </footer>

</body>
</html>
