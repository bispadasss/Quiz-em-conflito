<?php
/* ============================================================
   QUIZ STORAGE — armazenamento de quizzes em arquivos JSON
   ------------------------------------------------------------
   Cada quiz criado pela galera vira UM arquivo JSON:
       quizzes/1.json
       quizzes/2.json
       quizzes/3.json ...

   Estrutura de cada arquivo:
   {
     "id": 1,
     "titulo": "...",
     "descricao": "..." (ou null se não tiver),
     "criado_em": "2026-11-01 10:00:00",
     "perguntas": [
       {
         "enunciado": "...",
         "alternativas": [
           { "texto": "...", "correta": true  },
           { "texto": "...", "correta": false }
         ]
       }
     ]
   }

   É só incluir este arquivo nas páginas:
       require_once __DIR__ . '/quiz_storage.php';
   ============================================================ */

/* Pasta onde ficam os arquivos JSON (é criada sozinha se não existir) */
function quizzes_dir(): string
{
    $dir = __DIR__ . '/quizzes';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

/* Caminho do arquivo JSON de um quiz (ex.: quizzes/1.json) */
function caminho_quiz(int $id): string
{
    return quizzes_dir() . '/' . $id . '.json';
}

/* Descobre o próximo ID livre (maior ID já salvo + 1) */
function proximo_id(): int
{
    $maior = 0;
    foreach (glob(quizzes_dir() . '/*.json') ?: [] as $arquivo) {
        $nome = basename($arquivo, '.json');
        if (ctype_digit($nome) && (int)$nome > $maior) {
            $maior = (int)$nome;
        }
    }
    return $maior + 1;
}

/* Salva o quiz em um arquivo JSON próprio e devolve o ID gerado */
function salvar_quiz(array $quiz): int
{
    quizzes_dir(); // garante que a pasta "quizzes" existe

    $quiz['criado_em'] = date('Y-m-d H:i:s');
    $id = proximo_id();

    while (true) {
        // modo 'x': cria o arquivo só se ele ainda NÃO existir (nunca sobrescreve um quiz)
        $fp = @fopen(caminho_quiz($id), 'x');
        if (is_resource($fp)) {
            break;
        }
        $id++;
    }

    $quiz['id'] = $id;
    $json = json_encode(
        $quiz,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        fclose($fp);
        @unlink(caminho_quiz($id));
        throw new RuntimeException('Não foi possível gerar o JSON do quiz.');
    }

    if (fwrite($fp, $json) === false) {
        fclose($fp);
        @unlink(caminho_quiz($id));
        throw new RuntimeException('Não foi possível gravar o arquivo do quiz. '
            . 'Verifique as permissões da pasta "quizzes".');
    }

    fclose($fp);
    return $id;
}

/* Carrega um quiz pelo ID — devolve null se o arquivo não existir ou for inválido */
function carregar_quiz(int $id): ?array
{
    $arquivo = caminho_quiz($id);
    if (!is_file($arquivo)) {
        return null;
    }

    $dados = json_decode((string)file_get_contents($arquivo), true);
    if (!is_array($dados) || !isset($dados['id'])) {
        return null;
    }

    return $dados;
}

/* Lista todos os quizzes salvos — do mais novo para o mais antigo */
function listar_quizzes(): array
{
    $lista = [];
    foreach (glob(quizzes_dir() . '/*.json') ?: [] as $arquivo) {
        $dados = json_decode((string)file_get_contents($arquivo), true);
        if (is_array($dados) && isset($dados['id'])) {
            $lista[] = $dados;
        }
    }

    usort($lista, function ($a, $b) {
        return (int)$b['id'] <=> (int)$a['id'];
    });

    return $lista;
}

/* Conta quantas perguntas um quiz tem (usado no catálogo) */
function contar_perguntas(array $quiz): int
{
    return isset($quiz['perguntas']) && is_array($quiz['perguntas'])
        ? count($quiz['perguntas'])
        : 0;
}
