<?php
/* ============================================================
   RANKING — mostra a tabela de melhores resultados de um quiz
   Uso: ranking.php?id=2  →  carrega rankings/2.json
   ============================================================ */

require_once __DIR__ . '/quiz_storage.php';
require_once __DIR__ . '/ranking_storage.php';

function v(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Página de erro bonitinha (mesmo tema do jogar.php)
function paginaErro(string $titulo, string $msg) {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">
    <title>QUIZ!</title><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <style>body{background:#0b0b0d;color:#fff;font-family:Poppins,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0}
    .cx{background:#131316;border:1px solid rgba(255,214,10,.5);border-radius:24px;padding:50px 40px;text-align:center;max-width:440px}
    h1{color:#FFD60A;font-size:1.6rem}p{color:#b9b9c0;margin:14px 0 26px}
    a{background:#FFD60A;color:#0b0b0d;text-decoration:none;font-weight:800;padding:12px 30px;border-radius:50px}</style></head>
    <body><div class="cx"><h1>⚠️ ' . htmlspecialchars($titulo) . '</h1><p>' . htmlspecialchars($msg) . '</p>
    <a href="catalogo.php">← Voltar ao catálogo</a></div></body></html>';
    exit;
}

// ---------- CARREGA O QUIZ + O RANKING ----------
 $id = (int)($_GET['id'] ?? 0);

$quiz = $id > 0 ? carregar_quiz($id) : null;
if (!$quiz) {
    paginaErro('Quiz não encontrado', 'Este ranking não existe ou o quiz foi removido.');
}

 $ranking   = carregar_ranking($id);
 $jogadores = $ranking['jogadores'];
 ordenar_jogadores($jogadores);

 $total    = contar_perguntas($quiz);
 $medalhas = ['🥇', '🥈', '🥉'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ranking — <?= v($quiz['titulo']) ?> — QUIZ!</title>
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
  header nav a:hover { color: var(--amarelo); }

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

  .pagina { position: relative; z-index: 1; width: 100%; max-width: 840px; margin: 0 auto; padding: 55px 6% 90px; }

  .titulo-pagina h1 { font-size: clamp(1.9rem, 4.5vw, 2.9rem); font-weight: 900; letter-spacing: -1px; }
  .titulo-pagina h1 em { font-style: normal; color: var(--amarelo); }
  .titulo-pagina p  { color: var(--cinza-texto); margin: 10px 0 8px; }
  .titulo-pagina .sub { margin-bottom: 34px; }
  .titulo-pagina .sub strong { color: var(--amarelo); }

  /* ------ Tabela do ranking ------ */
  .caixa-tabela {
    background: var(--preto-2); border: 1px solid #26262b; border-radius: 20px;
    padding: 10px; overflow-x: auto;
  }
  .tabela-ranking { width: 100%; border-collapse: collapse; min-width: 520px; }
  .tabela-ranking th {
    text-align: left; font-size: .72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 1px; color: var(--cinza-texto);
    padding: 14px 18px 10px; border-bottom: 1px solid #26262b;
  }
  .tabela-ranking td {
    padding: 14px 18px; border-bottom: 1px solid #1f1f24;
    font-size: .95rem;
  }
  .tabela-ranking tr:last-child td { border-bottom: none; }
  .tabela-ranking tbody tr { transition: background .2s; }
  .tabela-ranking tbody tr:hover { background: rgba(255,214,10,.05); }
  .tabela-ranking .pos { font-weight: 900; color: var(--amarelo); width: 64px; }
  .tabela-ranking .nome { font-weight: 700; }
  .tabela-ranking .pts  { font-weight: 800; color: var(--amarelo); }
  .tabela-ranking .data { color: #6a6a72; font-size: .82rem; }

  /* ------ Estado vazio ------ */
  .vazio { text-align: center; background: var(--preto-2); border: 1px dashed #33333a;
           border-radius: 24px; padding: 70px 30px; }
  .vazio-icone { font-size: 3rem; margin-bottom: 14px; }
  .vazio h2 { font-weight: 900; margin-bottom: 8px; }
  .vazio p  { color: var(--cinza-texto); margin-bottom: 26px; }

  .voltar { margin-top: 30px; display: flex; gap: 14px; flex-wrap: wrap; }

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
      <a href="catalogo.php">Quizzes</a>
      <a href="criar_quiz.php">Criar quiz</a>
    </nav>
    <a class="btn btn-amarelo" href="jogar.php?id=<?= (int)$id ?>">▶ Jogar</a>
  </header>

  <main class="pagina">
    <div class="titulo-pagina">
      <h1>Ranking: <em><?= v($quiz['titulo']) ?></em></h1>
      <p>Quem mais acertou nas perguntas deste quiz.</p>
      <p class="sub"><strong><?= count($jogadores) ?></strong> <?= count($jogadores) === 1 ? 'jogador registrado' : 'jogadores registrados' ?> · <?= $total ?> <?= $total === 1 ? 'pergunta' : 'perguntas' ?> no quiz</p>
    </div>

    <?php if (count($jogadores) === 0): ?>

      <div class="vazio">
        <div class="vazio-icone">🏆</div>
        <h2>Ninguém jogou este quiz ainda...</h2>
        <p>Seja o primeiro a entrar no ranking!</p>
        <a class="btn btn-amarelo" href="jogar.php?id=<?= (int)$id ?>">▶ Jogar agora</a>
      </div>

    <?php else: ?>

      <div class="caixa-tabela">
        <table class="tabela-ranking">
          <thead>
            <tr>
              <th>Posição</th>
              <th>Jogador</th>
              <th>Pontos</th>
              <th>Acertos</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jogadores as $i => $j): ?>
            <tr>
              <td class="pos"><?= $medalhas[$i] ?? (($i + 1) . 'º') ?></td>
              <td class="nome"><?= v($j['nome'] ?? 'Anônimo') ?></td>
              <td class="pts"><?= number_format((int)($j['pontos'] ?? 0), 0, ',', '.') ?></td>
              <td><?= (int)($j['acertos'] ?? 0) ?>/<?= (int)($j['total'] ?? $total) ?></td>
              <td class="data"><?= !empty($j['data']) ? v(date('d/m/Y H:i', strtotime($j['data']))) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <?php endif; ?>

    <div class="voltar">
      <a class="btn btn-amarelo" href="jogar.php?id=<?= (int)$id ?>">▶ Jogar este quiz</a>
      <a class="btn btn-contorno" href="catalogo.php">📚 Ver catálogo</a>
    </div>
  </main>

  <footer>
    <p>© 2025 QUIZ! — Todos os direitos reservados.</p>
  </footer>

</body>
</html>
