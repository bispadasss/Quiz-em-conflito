<?php
/* ============================================================
   SALVAR RESULTADO — recebe a pontuação no fim do jogo (AJAX)
   e grava no ranking do quiz (rankings/{id}.json)

   Recebe (JSON no corpo do POST, enviado pelo jogar.php):
       { "id": 2, "nome": "Ana", "pontos": 2850, "acertos": 3 }

   Devolve (JSON):
       { "ok": true,
         "posicao": 1,
         "ranking": [ { "nome": "...", "pontos": ..., "acertos": ...,
                        "total": ..., "data": "..." }, ... ]  (top 10)
       }
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/quiz_storage.php';
require_once __DIR__ . '/ranking_storage.php';

function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// só aceita POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    responder(['ok' => false, 'erro' => 'Método não permitido.'], 405);
}

// lê o JSON enviado pelo JavaScript
$entrada = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    responder(['ok' => false, 'erro' => 'Dados inválidos.'], 400);
}

$quizId  = (int)($entrada['id'] ?? 0);
$nome    = trim((string)($entrada['nome'] ?? ''));
$pontos  = (int)($entrada['pontos'] ?? -1);
$acertos = (int)($entrada['acertos'] ?? -1);

// ---- validações ----
if ($quizId <= 0) {
    responder(['ok' => false, 'erro' => 'Quiz não informado.'], 400);
}

$quiz = carregar_quiz($quizId);
if (!$quiz) {
    responder(['ok' => false, 'erro' => 'Quiz não encontrado.'], 404);
}

// o total de perguntas vem DO ARQUIVO do quiz, não do navegador —
// assim o cliente não pode mentir sobre o tamanho do quiz
$total = contar_perguntas($quiz);
if ($total === 0) {
    responder(['ok' => false, 'erro' => 'Este quiz não tem perguntas.'], 400);
}

// pontuações impossíveis são rejeitadas:
// cada pergunta vale no máximo 1000 pts, então o teto é total * 1000
if ($acertos < 0 || $acertos > $total) {
    responder(['ok' => false, 'erro' => 'Quantidade de acertos inválida.'], 400);
}
if ($pontos < 0 || $pontos > $total * 1000) {
    responder(['ok' => false, 'erro' => 'Pontuação inválida.'], 400);
}

if ($nome === '') {
    $nome = 'Anônimo';
}
if (function_exists('mb_substr')) {
    if (mb_strlen($nome) > 30) $nome = mb_substr($nome, 0, 30);
} else {
    if (strlen($nome) > 30) $nome = substr($nome, 0, 30);
}

// ---- grava no ranking ----
$r = salvar_resultado($quizId, $nome, $pontos, $acertos, $total);

responder([
    'ok'      => true,
    'posicao' => $r['posicao'],
    'ranking' => array_slice($r['ranking']['jogadores'], 0, 10),
]);
