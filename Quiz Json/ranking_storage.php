<?php
/* ============================================================
   RANKING STORAGE — pontuações dos jogadores, um JSON por quiz
   ------------------------------------------------------------
   Cada quiz tem o SEU PRÓPRIO ranking, guardado em:
       rankings/{id_do_quiz}.json
       rankings/1.json  ← ranking do quiz 1
       rankings/2.json  ← ranking do quiz 2 ...

   Estrutura de cada arquivo:
   {
     "quiz_id": 2,
     "atualizado_em": "2026-11-01 12:00:00",
     "jogadores": [
       { "nome": "Ana",   "pontos": 2850, "acertos": 3, "total": 3,
         "data": "2026-11-01 11:59:00" },
       { "nome": "Bruno", "pontos": 1999, "acertos": 2, "total": 3,
         "data": "2026-11-01 12:01:30" }
     ]
   }

   É só incluir nas páginas:
       require_once __DIR__ . '/ranking_storage.php';
   ============================================================ */

// Quantos jogadores ficam guardados no ranking (os melhores)
const RANKING_LIMITE = 50;

/* Pasta onde ficam os arquivos de ranking (criada sozinha se faltar) */
function rankings_dir(): string
{
    $dir = __DIR__ . '/rankings';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/* Caminho do arquivo de ranking de um quiz (ex.: rankings/2.json) */
function caminho_ranking(int $quizId): string
{
    return rankings_dir() . '/' . $quizId . '.json';
}

/* Coloca os jogadores na ordem de classificação:
   1º critério: mais pontos
   2º critério: mais acertos (desempate)
   3º critério: quem chegou na pontuação primeiro (data mais antiga) */
function ordenar_jogadores(array &$jogadores): void
{
    usort($jogadores, function ($a, $b) {
        $pa = (int)($a['pontos']  ?? 0);
        $pb = (int)($b['pontos']  ?? 0);
        if ($pa !== $pb) {
            return $pb <=> $pa; // mais pontos primeiro
        }

        $aa = (int)($a['acertos'] ?? 0);
        $ab = (int)($b['acertos'] ?? 0);
        if ($aa !== $ab) {
            return $ab <=> $aa; // mais acertos primeiro
        }

        return strcmp((string)($a['data'] ?? ''), (string)($b['data'] ?? ''));
    });
}

/* Carrega o ranking de um quiz. Se não existir, devolve estrutura vazia */
function carregar_ranking(int $quizId): array
{
    $padrao = ['quiz_id' => $quizId, 'atualizado_em' => null, 'jogadores' => []];

    $arquivo = caminho_ranking($quizId);
    if (!is_file($arquivo)) {
        return $padrao;
    }

    $dados = json_decode((string)file_get_contents($arquivo), true);
    if (!is_array($dados) || !isset($dados['jogadores']) || !is_array($dados['jogadores'])) {
        return $padrao;
    }

    $padrao['jogadores']     = $dados['jogadores'];
    $padrao['atualizado_em'] = $dados['atualizado_em'] ?? null;
    return $padrao;
}

/* Registra o resultado de um jogo e devolve a posição + o ranking atualizado */
function salvar_resultado(int $quizId, string $nome, int $pontos, int $acertos, int $total): array
{
    rankings_dir();

    $agora     = date('Y-m-d H:i:s');
    $resultado = [
        'nome'    => $nome,
        'pontos'  => $pontos,
        'acertos' => $acertos,
        'total'   => $total,
        'data'    => $agora,
    ];

    // 'c+' = abre para leitura/escrita e CRIA o arquivo se ele não existir.
    // flock(LOCK_EX) tranca o arquivo: se dois jogadores terminarem o quiz
    // no mesmo segundo, um espera o outro — ninguém perde a pontuação.
    $fp = fopen(caminho_ranking($quizId), 'c+');
    flock($fp, LOCK_EX);

    $conteudo = stream_get_contents($fp);
    $ranking  = json_decode((string)$conteudo, true);
    if (!is_array($ranking) || !isset($ranking['jogadores']) || !is_array($ranking['jogadores'])) {
        $ranking = ['quiz_id' => $quizId, 'atualizado_em' => $agora, 'jogadores' => []];
    }

    // adiciona o novo resultado e reordena
    $ranking['jogadores'][] = $resultado;
    ordenar_jogadores($ranking['jogadores']);

    // descobre em que posição o jogador ficou (1 = primeiro lugar)
    $posicao = null;
    foreach ($ranking['jogadores'] as $i => $j) {
        if ($j === $resultado) {
            $posicao = $i + 1;
            break;
        }
    }

    // mantém o arquivo enxuto: só os melhores continuam gravados
    if (count($ranking['jogadores']) > RANKING_LIMITE) {
        $ranking['jogadores'] = array_slice($ranking['jogadores'], 0, RANKING_LIMITE);
    }
    if ($posicao !== null && $posicao > RANKING_LIMITE) {
        $posicao = null; // a pontuação não entrou no top 50
    }

    $ranking['quiz_id']       = $quizId;
    $ranking['atualizado_em'] = $agora;

    // regrava o arquivo por cima (ainda com a tranca ativa)
    ftruncate($fp, 0);
    rewind($fp);
    fwrite(
        $fp,
        json_encode($ranking, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return ['posicao' => $posicao, 'ranking' => $ranking];
}

/* Devolve só os primeiros colocados de um quiz (padrão: top 10) */
function top_jogadores(int $quizId, int $limite = 10): array
{
    $ranking = carregar_ranking($quizId);
    ordenar_jogadores($ranking['jogadores']); // garante a ordem mesmo se alguém editou o arquivo na mão
    return array_slice($ranking['jogadores'], 0, max(0, $limite));
}
