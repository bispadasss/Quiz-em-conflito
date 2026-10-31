# 📦 Como funciona o armazenamento em JSON — projeto QUIZ!

> Documento de estudo/explicação do projeto.
> Ele explica, em detalhes, como os quizzes criados pelas pessoas são
> guardados em **arquivos JSON** — um arquivo por quiz — sem usar MySQL.

---

## Sumário

1. [A ideia em uma frase](#1-a-ideia-em-uma-frase)
2. [O que mudou em relação ao MySQL](#2-o-que-mudou-em-relação-ao-mysql)
3. [Mapa dos arquivos do projeto](#3-mapa-dos-arquivos-do-projeto)
4. [A pasta `quizzes/` — o nosso "banco de dados"](#4-a-pasta-quizzes--o-nosso-banco-de-dados)
5. [Anatomia de um arquivo de quiz](#5-anatomia-de-um-arquivo-de-quiz)
6. [`quiz_storage.php` — o coração do armazenamento, função por função](#6-quiz_storagephp--o-coração-do-armazenamento-função-por-função)
7. [Fluxo completo #1 — quando alguém **cria** um quiz](#7-fluxo-completo-1--quando-alguém-cria-um-quiz)
8. [Fluxo completo #2 — quando alguém **joga** um quiz](#8-fluxo-completo-2--quando-alguém-joga-um-quiz)
9. [Fluxo completo #3 — o **catálogo**](#9-fluxo-completo-3--o-catálogo)
10. [Administrando os quizzes "na mão"](#10-administrando-os-quizzes-na-mão)
11. [Segurança](#11-segurança)
12. [Limitações do JSON (e quando um banco de dados volta a fazer sentido)](#12-limitações-do-json-e-quando-um-banco-de-dados-volta-a-fazer-sentido)
13. [Problemas comuns e soluções (FAQ)](#13-problemas-comuns-e-soluções-faq)
14. [Glossário rápido](#14-glossário-rápido)
15. [O sistema de ranking — pontuações por quiz](#15-o-sistema-de-ranking--pontuações-por-quiz)

---

## 1. A ideia em uma frase

**Cada quiz criado pelo formulário vira um arquivo `.json` dentro da pasta `quizzes/`, e o nome do arquivo é o ID do quiz.**

```
quizzes/
├── 1.json   ← quiz de exemplo
├── 2.json   ← segundo quiz criado
├── 3.json   ← terceiro quiz criado
└── ...
```

- Quer **salvar** um quiz? → cria um arquivo novo.
- Quer **ler** um quiz? → abre o arquivo `N.json`.
- Quer **listar** todos? → lê todos os `*.json` da pasta.
- Quer **apagar** um quiz? → deleta o arquivo. Pronto, sumiu do site.
- Quer **backup**? → copia a pasta `quizzes/`. É só ela que contém dados.

Ou seja: a pasta `quizzes/` faz o papel que o MySQL fazia — só que em vez de
tabelas e linhas, temos arquivos que você pode abrir, ler e editar com
qualquer editor de texto.

---

## 2. O que mudou em relação ao MySQL

| | Antes (MySQL) | Agora (JSON) |
|---|---|---|
| Onde ficavam os dados | Banco `quiz_db`, tabelas `quizzes`, `perguntas`, `alternativas` | Pasta `quizzes/`, um arquivo `.json` por quiz |
| Como se conectava | `new PDO("mysql:host=...")` com usuário e senha | Nenhuma conexão — só ler/escrever arquivos |
| Como se salvava | `INSERT` em 3 tabelas dentro de uma transação | `salvar_quiz()` grava 1 arquivo |
| Como se lia | `SELECT ... WHERE id = :id` | `carregar_quiz($id)` abre `N.json` |
| ID do quiz | `AUTO_INCREMENT` do banco | Maior nome de arquivo existente + 1 |
| Configuração necessária | XAMPP com MySQL ligado, banco criado | Nenhuma — só o PHP |
| Portabilidade (mudar de PC / enviar pra um amigo) | Exportar/importar dump SQL | Copiar a pasta `quizzes/` |

O que **não** mudou: todo o resto do site. O formulário de criação, o jogo
(estilo Kahoot, com tempo e pontos) e o visual continuam iguais. As páginas
apenas trocaram o "motor" de dados por baixo dos panos.

> 💡 Se você apagar as tabelas do MySQL (ou até desligar o MySQL), o site
> continua funcionando normalmente. Nenhuma página usa mais `PDO`.

---

## 3. Mapa dos arquivos do projeto

```
quiz-json/
├── index.html            ← página inicial (não mudou)
├── criar_quiz.php        ← formulário de criação  → usa salvar_quiz()
├── jogar.php             ← o jogo (estilo Kahoot) → usa carregar_quiz() e envia o resultado
├── catalogo.php          ← lista os quizzes       → usa listar_quizzes()
├── ranking.php           ← 🏆 ranking completo de um quiz → usa carregar_ranking()
├── salvar_resultado.php  ← recebe a pontuação no fim do jogo (AJAX)
├── quiz_storage.php      ← ⭐ armazenamento dos QUIZZES (explicado na seção 6)
├── ranking_storage.php   ← ⭐ armazenamento dos RANKINGS (explicado na seção 15)
├── quizzes/              ← 📁 os quizzes ficam aqui (1 arquivo por quiz)
│   ├── .htaccess         ← bloqueia acesso direto aos JSON pelo navegador
│   └── 1.json            ← um quiz de exemplo
└── rankings/             ← 🏆 as pontuações ficam aqui (1 arquivo por quiz)
    ├── .htaccess         ← mesma proteção
    └── 2.json            ← criado sozinho no 1º jogo do quiz 2
```

Regra de ouro do projeto:

> **Nenhuma página fala com os arquivos JSON diretamente.**
> Todas passam pelas camadas `quiz_storage.php` (quizzes) e
> `ranking_storage.php` (rankings), que são as únicas que sabem *como* os
> dados são guardados. Se um dia você quiser voltar para MySQL, trocar por
> SQLite etc., basta reescrever esses dois arquivos — as páginas nem ficam
> sabendo.

Cada página começa assim:

```php
require_once __DIR__ . '/quiz_storage.php';
```

| Página | Funções que usa |
|---|---|
| `criar_quiz.php` | `salvar_quiz()` |
| `jogar.php` | `carregar_quiz()` (+ envia o resultado para `salvar_resultado.php`) |
| `catalogo.php` | `listar_quizzes()` e `contar_perguntas()` |
| `ranking.php` | `carregar_ranking()` e `ordenar_jogadores()` |
| `salvar_resultado.php` | `carregar_quiz()`, `contar_perguntas()` e `salvar_resultado()` |

---

## 4. A pasta `quizzes/` — o nosso "banco de dados"

### Por que o nome do arquivo é o ID?

Porque o ID é o único identificador do quiz — é ele que aparece na URL
(`jogar.php?id=2`) e nos links. Guardar o quiz como `2.json` deixa a relação
óbvia:

```
jogar.php?id=2   →   abre quizzes/2.json
```

### O que tem dentro da pasta

- **`1.json`, `2.json`, ...** — os quizzes em si (um arquivo = um quiz completo,
  com todas as perguntas e alternativas dentro).
- **`.htaccess`** — arquivo de configuração do Apache (XAMPP usa Apache) que
  impede alguém de abrir `http://seusite/quizzes/2.json` direto no navegador.
  Os JSONs são lidos pelo PHP, não precisam ficar expostos.
  *(Detalhe: `.htaccess` começa com ponto porque é um arquivo "oculto" no
  Linux — alguns programas de FTP não enviam arquivos ocultos por padrão,
  então confira se ele foi para o servidor.)*

A pasta é **criada sozinha** na primeira vez que um quiz é salvo, então se
você apagá-la sem querer, não tem problema: na próxima criação ela volta.

---

## 5. Anatomia de um arquivo de quiz

Aqui está o arquivo de exemplo `quizzes/1.json` (o mesmo formato que o
sistema gera automaticamente):

```json
{
    "id": 1,
    "titulo": "Conhecimentos gerais — nível fácil",
    "descricao": "Um quiz de exemplo para você testar o armazenamento em JSON.",
    "criado_em": "2026-11-01 10:00:00",
    "perguntas": [
        {
            "enunciado": "Qual é a capital do Brasil?",
            "alternativas": [
                { "texto": "Brasília",       "correta": true  },
                { "texto": "São Paulo",      "correta": false },
                { "texto": "Rio de Janeiro", "correta": false },
                { "texto": "Salvador",       "correta": false }
            ]
        },
        {
            "enunciado": "Quantos lados tem um hexágono?",
            "alternativas": [
                { "texto": "5", "correta": false },
                { "texto": "6", "correta": true  },
                { "texto": "7", "correta": false },
                { "texto": "8", "correta": false }
            ]
        }
    ]
}
```

Campo por campo:

| Campo | Tipo | O que é | Quem preenche |
|---|---|---|---|
| `id` | número | O identificador do quiz. Igual ao nome do arquivo (sem o `.json`). | `salvar_quiz()` |
| `titulo` | texto | Título que aparece na tela inicial do jogo e no catálogo. | A pessoa no formulário |
| `descricao` | texto ou `null` | Descrição opcional. Se a pessoa não escreveu nada, fica `null`. | A pessoa no formulário |
| `criado_em` | texto `AAAA-MM-DD HH:MM:SS` | Data e hora em que o quiz foi publicado. | `salvar_quiz()` (automático) |
| `perguntas` | lista | Todas as perguntas, na ordem em que foram cadastradas. | A pessoa no formulário |
| `perguntas[n].enunciado` | texto | O texto da pergunta. | A pessoa |
| `perguntas[n].alternativas` | lista | As opções de resposta (4 no formulário, mas o código aceita qualquer quantidade). | A pessoa |
| `alternativas[n].texto` | texto | O texto da alternativa. | A pessoa |
| `alternativas[n].correta` | `true`/`false` | Marca qual é a resposta certa. **Só uma** deve ser `true`. | O radinho "Correta" do formulário |

Observações importantes:

- No MySQL a resposta certa era guardada como `1` ou `0`; aqui virou
  **booleano de verdade** (`true`/`false`) — mais legível pra quem abrir o
  arquivo.
- Todo o quiz (todas as perguntas) mora **dentro de um único arquivo**. Nada
  de tabela de perguntas e outra de alternativas: a estrutura de lista dentro
  de lista do JSON resolve isso naturalmente.
- O arquivo é gravado **identado e com acentos normais** (veja as flags de
  `json_encode` na seção 6.4) — dá para abrir no VS Code e entender tudo.

---

## 6. `quiz_storage.php` — o coração do armazenamento, função por função

Esse arquivo tem 7 funções curtas. Vamos uma por uma, com o trecho de código
real e a explicação.

### 6.1 `quizzes_dir()` — garante que a pasta existe e devolve o caminho dela

```php
function quizzes_dir(): string
{
    $dir = __DIR__ . '/quizzes';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}
```

- `__DIR__` é uma constante do PHP que significa **"a pasta onde este arquivo
  (`quiz_storage.php`) está"**. Isso faz o projeto funcionar em qualquer
  lugar, sem depender de caminho fixo.
- Se a pasta `quizzes/` não existir, `mkdir` cria ela na hora (o `@` na frente
  só esconde um aviso chato do PHP caso a criação falhe).
- Toda vez que alguma função precisa saber onde estão os arquivos, ela chama
  `quizzes_dir()` — assim o caminho está definido em **um único lugar**.

### 6.2 `caminho_quiz()` — transforma um ID em caminho de arquivo

```php
function caminho_quiz(int $id): string
{
    return quizzes_dir() . '/' . $id . '.json';
}
```

Simples: `caminho_quiz(2)` devolve algo como
`/var/www/quiz-json/quizzes/2.json`. É a tradução **ID → arquivo** que dá
nome às coisas.

### 6.3 `proximo_id()` — descobre qual o próximo número livre

```php
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
```

Como o MySQL não está mais aqui para "contar" os IDs (`AUTO_INCREMENT`), quem
conta somos nós:

1. `glob('*.json')` lista **todos os arquivos `.json`** da pasta `quizzes/`.
2. `basename($arquivo, '.json')` pega o nome do arquivo sem a pasta e sem a
   extensão → `"1"`, `"2"`, `"7"`.
3. `ctype_digit($nome)` só deixa passar nomes que sejam **puramente números**.
   Se um dia existir um `rascunho.json` solto na pasta, ele é ignorado e não
   quebra nada.
4. Guardamos o **maior** número encontrado e devolvemos **maior + 1**.

Exemplo: na pasta existem `1.json`, `2.json` e `7.json`
(você apagou os do meio, sem problema!) → `proximo_id()` devolve **8**.

> ⚠️ **Detalhe importante:** se você apagar o quiz de **maior** ID (por
> exemplo, o `7.json`), o próximo quiz criado vai **reutilizar** o número 7.
> Isso não quebra nada, mas saiba que IDs podem "voltar" — diferente do MySQL,
> que nunca reutiliza. Para evitar, crie um `.json` vazio de exemplo com o ID
> mais alto e nunca o apague.

### 6.4 `salvar_quiz()` — grava o quiz em um arquivo JSON novo

```php
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
```

Passo a passo do que acontece quando alguém clica em **"Publicar quiz"**:

1. **Garante a pasta** — chama `quizzes_dir()`; se `quizzes/` não existir,
   ela é criada agora.
2. **Marca a data** — `$quiz['criado_em'] = date('Y-m-d H:i:s')` grava a data
   e hora do momento (é isso que o catálogo mostra como "31/10/2026").
3. **Pede um ID** — chama `proximo_id()`.
4. **Cria o arquivo de um jeito esperto** — o segredo é o `fopen(..., 'x')`:
   - O modo **`'x'`** abre o arquivo **para criação**, mas **falha se ele já
     existir**. É uma operação *atômica* do sistema operacional: num
     milissegundo, ou o arquivo é criado pra você, ou a função retorna `false`.
   - Se falhou (alguém mais criou esse arquivo "na frente" de você — duas
     pessoas clicando ao mesmo tempo, por exemplo), o `while` soma 1 no ID e
     tenta o próximo. Ninguém sobrescreve o quiz de ninguém. 🔒
5. **Transforma o quiz em texto JSON** — `json_encode()` com três flags:
   | Flag | Efeito |
   |---|---|
   | `JSON_PRETTY_PRINT` | Grava **identado** (com quebras de linha e espaços), para humanos lerem |
   | `JSON_UNESCAPED_UNICODE` | Acentos ficam normais: `"São Paulo"` em vez de `"S\u00e3o Paulo"` |
   | `JSON_UNESCAPED_SLASHES` | Barras ficam normais (não vira `\/`) |
6. **Escreve e fecha** — `fwrite()` grava o texto; `fclose()` fecha o arquivo.
7. **Se algo der errado** (sem permissão na pasta, disco cheio...):
   - o arquivo vazio que ficou para trás é **apagado** (`unlink`) para não
     sujar a pasta;
   - uma exceção `RuntimeException` é lançada com uma mensagem em português —
     o `criar_quiz.php` captura essa mensagem e mostra na caixa vermelha de
     erros do formulário ("Erro ao salvar o quiz: ...").
8. **Devolve o ID** — o `criar_quiz.php` usa esse número na tela de sucesso
   ("ID do quiz: #2") e no botão "▶ Jogar agora".

### 6.5 `carregar_quiz()` — lê um quiz pelo ID

```php
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
```

1. Monta o caminho (`N.json`) e confere se o arquivo existe. Se não existe →
   devolve **`null`** (o `jogar.php` traduz isso para "Quiz não encontrado").
2. `file_get_contents()` lê o arquivo inteiro como texto.
3. `json_decode(..., true)` transforma o texto JSON de volta em **array do
   PHP** (o `true` é o que faz vir array associativo, ou seja, acessível por
   nome: `$dados['titulo']`).
4. Validação defensiva: se o arquivo existir mas o conteúdo for um JSON
   quebrado (você editou na mão e esqueceu uma vírgula 😅), `json_decode`
   devolve `null` e aqui também devolvemos `null` — o site mostra "Quiz não
   encontrado" em vez de uma tela de erro feia.

### 6.6 `listar_quizzes()` — todos os quizzes, do mais novo pro mais antigo

```php
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
```

1. Lista todos os `.json` (mesma técnica do `proximo_id`).
2. Decodifica cada um; **arquivos inválidos são pulados em silêncio** — um
   JSON corrompido nunca derruba o catálogo inteiro, ele só não aparece.
3. `usort` ordena pelo ID **decrescente** (o `$b` vem antes do `$a`):
   os quizzes mais recentes aparecem primeiro no catálogo. O operador `<=>`
   ("nave espacial") compara dois números e devolve `-1`, `0` ou `1`.

### 6.7 `contar_perguntas()` — ajudante do catálogo

```php
function contar_perguntas(array $quiz): int
{
    return isset($quiz['perguntas']) && is_array($quiz['perguntas'])
        ? count($quiz['perguntas'])
        : 0;
}
```

Só conta quantas perguntas o quiz tem, para o card mostrar
"3 perguntas" / "1 pergunta". Com a defensiva, um quiz malformado mostra
"0 perguntas" em vez de dar erro.

---

## 7. Fluxo completo #1 — quando alguém **cria** um quiz

Do clique ao arquivo no disco:

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. A pessoa preenche o formulário em criar_quiz.php             │
│    (título, descrição, perguntas, alternativas e o radinho      │
│     "Correta" de cada pergunta) e clica em "Publicar quiz ✔"    │
└───────────────────────────────┬─────────────────────────────────┘
                                │ POST (navegador envia os campos)
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. criar_quiz.php valida:                                       │
│    • título preenchido?                                         │
│    • pelo menos 1 pergunta?                                     │
│    • enunciado e as 4 alternativas preenchidos?                 │
│    • marcou qual é a correta?                                   │
│    Se falhou → mostra a caixa vermelha e NADA é salvo           │
└───────────────────────────────┬─────────────────────────────────┘
                                │ tudo ok
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. Monta o array do quiz no formato final:                      │
│    [ 'titulo'    => 'História do Brasil — nível médio',         │
│      'descricao' => '...' ou null,                              │
│      'perguntas' => [ ['enunciado' => '...',                    │
│                        'alternativas' => [                      │
│                           ['texto' => '1498', 'correta' => false],│
│                           ['texto' => '1500', 'correta' => true ],│
│                        ... ] ] ]                                │
└───────────────────────────────┬─────────────────────────────────┘
                                │ salvar_quiz($quiz)
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. quiz_storage.php:                                            │
│    cria a pasta se faltar → marca criado_em → acha o ID →       │
│    fopen modo 'x' (nunca sobrescreve) → json_encode → fwrite    │
└───────────────────────────────┬─────────────────────────────────┘
                                │
                                ▼
              📄 quizzes/2.json  aparece no disco!
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. Tela de sucesso: "Quiz publicado com sucesso! ID #2"         │
│    com botões ▶ Jogar agora / Ver no catálogo / Criar outro     │
└─────────────────────────────────────────────────────────────────┘
```

Um detalhe legal do passo 3: o formulário envia a alternativa certa como um
número (o `value` do radinho "Correta": 0, 1, 2 ou 3). O PHP compara esse
número com a posição de cada alternativa e converte para
`'correta' => true` na certa, `false` nas outras. É por isso que o JSON
guarda booleanos de verdade.

---

## 8. Fluxo completo #2 — quando alguém **joga** um quiz

```
jogar.php?id=2
      │
      ▼
carregar_quiz(2)  ──►  lê quizzes/2.json  ──►  array PHP
      │
      ▼
Monta o array $dados no formato que o jogo usa:
  [ 'pergunta'     => 'Em que ano...',
    'alternativas' => [ ['texto' => '1500', 'correta' => true], ... ] ]
      │
      ▼
shuffle() EMBARALHA as alternativas
(pra correta não ficar sempre na mesma posição)
      │
      ▼
json_encode(...) injeta os dados no JavaScript da página:
  const PERGUNTAS = [ ... ];
      │
      ▼
O jogo roda 100% no navegador: timer de 20s, pontos,
revelação da resposta certa — tudo com o array PERGUNTAS.
```

Pontos que valem destaque:

- **O arquivo JSON nunca é alterado durante o jogo.** Jogar é só leitura.
  Se 30 pessoas jogarem ao mesmo tempo, todas leem o mesmo `2.json`
  tranquilamente — leitura simultânea de arquivos não tem conflito.
- O **embaralhamento** (`shuffle`) acontece no servidor, a cada visita: cada
  jogador vê as alternativas em uma ordem diferente.
- Na hora de mandar os dados para o JavaScript, o `jogar.php` usa
  `json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | ...)`. Aquelas
  flags `JSON_HEX_*` são uma proteção: elas codificam caracteres perigosos
  (`<`, `>`, `&`, aspas) para que um texto malicioso digitado em uma pergunta
  nunca consiga "quebrar" o `<script>` da página.

---

## 9. Fluxo completo #3 — o **catálogo**

```
catalogo.php
      │
      ▼
listar_quizzes()  ──►  lê TODOS os *.json da pasta quizzes/
                       (ignora arquivos corrompidos)
                       ordena do ID maior pro menor (mais novo primeiro)
      │
      ▼
Para cada quiz, o card mostra:
  • badge "N perguntas"   ← contar_perguntas()
  • título
  • descrição (ou "Sem descrição — mas promete ser bom!" se for null)
  • data de criação (d/m/Y)
  • botão ▶ Jogar  →  jogar.php?id=N
      │
      ▼
Se a pasta estiver vazia → tela amigável
"📭 Nenhum quiz publicado ainda..." com botão pra criar o primeiro.
```

Como o catálogo lê a pasta **a cada visita**, qualquer arquivo que você
adicionar ou remover manualmente reflete no site **na hora**, sem cache e
sem precisar "dar refresh em banco" — essa é uma das vantagens mais gostosas
do armazenamento em arquivos.

---

## 10. Administrando os quizzes "na mão"

Porque JSON é só texto, você é o dono dos dados. Algumas operações úteis:

| O que fazer | Como |
|---|---|
| **Ver** um quiz | Abra `quizzes/N.json` no VS Code (ou qualquer editor) |
| **Apagar** um quiz | Delete o arquivo. Ele some do catálogo e do jogo na hora |
| **Backup** | Copie a pasta `quizzes/` inteira. É o único lugar com dados |
| **Mudar de servidor** | Copie a pasta `quizzes/` pro novo servidor. Fim |
| **Criar um quiz na mão** | Copie o `1.json` como modelo, salve com o próximo ID livre, ajuste os campos |
| **Editar um quiz** | Abra o `.json`, altere o texto e salve. Cuidado com a sintaxe! (veja abaixo) |

⚠️ **Cuidados ao editar na mão:**

- JSON é rigoroso: **vírgula sobrando ou faltando, aspas simples no lugar de
  duplas... e o arquivo quebra**. Um JSON quebrado não derruba o site (as
  funções ignoram arquivos inválidos), mas o quiz simplesmente **deixa de
  aparecer** no catálogo e o `jogar.php` diz "não encontrado".
- Dica: cole o conteúdo no site jsonlint.com ou no VS Code (ele aponta a
  linha do erro) antes de salvar.
- **Apagar o quiz de maior ID** faz o número ser reutilizado no próximo
  cadastro (explicado na seção 6.3).
- Ao enviar os arquivos por FTP, lembre de incluir o `.htaccess` (arquivos
  que começam com ponto costumam ficar ocultos).

---

## 11. Segurança

O que o sistema já faz para te proteger (e o que ainda fica por sua conta):

1. **`.htaccess` na pasta `quizzes/`** — bloqueia o acesso direto aos `.json`
   pelo navegador no Apache/XAMPP (`Require all denied`). Os dados são
   servidos pelo PHP, que decide o que mostrar. Em servidores nginx ou no
   `php -S` esse arquivo é ignorado — nesse caso o JSON ficaria acessível por
   URL direta, mas como ele só contém o conteúdo público dos quizzes, o
   impacto é só de exposição, não de invasão.

2. **Tudo que aparece na tela passa por `htmlspecialchars()`** — a função
   `v()` presente em todas as páginas converte `<`, `>`, `"` etc. em entidades
   HTML. Assim, se alguém digitar `<script>` no título do quiz, ele aparece
   como **texto** na tela, nunca é executado.

3. **JSON injetado no JavaScript usa flags `JSON_HEX_*`** — impede que um
   texto tipo `</script>` digitado numa pergunta feche o bloco de script da
   página (ataque clássico de XSS via dados).

4. **`fopen` no modo `'x'`** — impede sobrescrita acidental de quizzes já
   existentes, mesmo com acesso concorrente.

5. **Sobre o campo `correta`:** a resposta certa precisa chegar ao navegador
   para o jogo funcionar (a correção acontece no cliente). Isso **já era
   assim na versão com MySQL** — quem abrir o "ver código-fonte" consegue
   ver as respostas. Se um dia quiser anti-colinha de verdade, o caminho é
   mover a correção para o servidor (validar a resposta via AJAX). O
   armazenamento em JSON não muda nada disso — o `correta: true` ficaria
   escondido no servidor em qualquer esquema.

---

## 12. Limitações do JSON (e quando um banco de dados volta a fazer sentido)

Ser honesto sobre os limites ajuda a decidir o futuro do projeto:

| Situação | JSON aguenta bem? |
|---|---|
| Projeto pessoal, escola, portfólio, dezenas/centenas de quizzes | ✅ Com folga |
| Várias pessoas criando ao mesmo tempo (ocasional) | ✅ O modo `'x'` evita sobrescrita; no pior caso só muda o ID |
| Milhares de quizzes / buscas complexas ("quizzes do usuário X") | ⚠️ Dá, mas lê todos os arquivos a cada catálogo |
| Ranking, contas de usuário, pontuações salvas | ❌ Aí sim: banco de dados volta a fazer sentido |
| Edição concorrente **do mesmo** quiz | ❌ JSON não foi feito pra isso |

Em resumo: para a fase atual do projeto (armazenar e jogar quizzes criados
pela galera), arquivos JSON são **simples, transparentes, portáveis e sem
configuração**. Quando o projeto crescer para ter login, ranking ou milhares
de quizzes, a `quiz_storage.php` pode ser reescrita para MySQL/SQLite mantendo
as mesmas funções — e as páginas continuam funcionando sem mudanças.

---

## 13. Problemas comuns e soluções (FAQ)

**❓ "Apaguei o banco quiz_db do MySQL, o site quebra?"**
Não. Nenhuma página usa mais MySQL. Pode até desligar o serviço do MySQL.

**❓ "Criei um quiz mas ele não aparece no catálogo."**
Provavelmente o arquivo não foi gravado (permissão da pasta) ou o JSON está
inválido (se você editou na mão). Abra o arquivo e valide o JSON; veja o log
de erros do PHP. A mensagem de erro da tela de criação também ajuda.

**❓ "Aparece 'Não foi possível gravar o arquivo do quiz'."**
É permissão: o PHP não consegue escrever em `quizzes/`. No Linux/Mac,
`chmod 775 quizzes` (e confira o dono da pasta). No XAMPP do Windows isso
normalmente não acontece.

**❓ "Dois quizzes criados ao mesmo tempo se sobrescrevem?"**
Não. O `fopen` com modo `'x'` só cria o arquivo se ele não existir; se a
"disputa" acontecer, o segundo quiz automaticamente ganha o ID seguinte.

**❓ "Onde ficam as respostas certas?"**
No campo `"correta": true` de cada alternativa, dentro do `.json` do quiz.

**❓ "Como mudo o tempo de 20s por pergunta?"**
Isso não é do armazenamento — é a constante `TEMPO` no começo do `<script>`
do `jogar.php`.

**❓ "Posso renomear `2.json` para `10.json`?"**
Pode (desde que não exista já um `10.json`), mas prefira editar o campo
`"id"` junto para continuar batendo com o nome do arquivo. O catálogo usa o
`id` **de dentro** do JSON para o link de jogar.

**❓ "Cada quiz tem limite de perguntas?"**
Pelo armazenamento, não — o arquivo cresce conforme o quiz. O formulário
começa com 3 blocos, mas o botão "+ Adicionar pergunta" repete quantas
quiser.

---

## 14. Glossário rápido

| Termo | Significado |
|---|---|
| **JSON** | JavaScript Object Notation — formato de texto para guardar dados estruturados (`{ "chave": valor }`). Linguagem universal: PHP, JavaScript, Python... todos lêem |
| **`json_encode` / `json_decode`** | Funções do PHP: array → texto JSON (encode) e texto JSON → array (decode) |
| **`__DIR__`** | Constante do PHP com a pasta do arquivo atual; deixa os caminhos portáveis |
| **`require_once`** | Inclui um arquivo PHP no outro (e garante que só entra uma vez) |
| **`fopen` modo `'x'`** | Abre um arquivo para criação **falhando se ele já existir** — a base da proteção contra sobrescrita |
| **`glob()`** | Lista arquivos de uma pasta seguindo um padrão (`*.json`) |
| **`basename()`** | Devolve o nome do arquivo sem o caminho da pasta |
| **`ctype_digit()`** | Confere se uma string contém somente dígitos (`"12"` sim, `"abc"` não) |
| **`usort()` / `<=>`** | Ordena um array com uma função de comparação; o "nave espacial" compara dois valores |
| **`RuntimeException`** | Tipo de exceção lançada quando algo dá errado em tempo de execução (ex.: sem permissão para gravar) |
| **`null`** | "Nada" — usado quando a descrição está vazia ou quando o quiz não foi encontrado |
| **Array associativo** | Array do PHP com nomes em vez de números: `$quiz['titulo']` |
| **Flag** | Opção passada para uma função do PHP, escrita como constante (ex.: `JSON_PRETTY_PRINT`) |
| **`.htaccess`** | Arquivo de configuração do Apache por pasta; aqui usado para bloquear acesso direto aos JSONs |
| **`flock()`** | Trava de arquivo: faz fila para que duas gravações simultâneas não pisem uma na outra |
| **`fetch()`** | Função do JavaScript para chamar uma página do servidor em segundo plano (AJAX), sem recarregar a tela |

---

## 15. O sistema de ranking — pontuações por quiz

Cada quiz tem o **seu próprio ranking**, guardado em um arquivo JSON
separado na pasta `rankings/`:

```
jogar.php?id=2 (fim de jogo)  →  rankings/2.json
ranking.php?id=2              →  rankings/2.json
```

### 15.1 O arquivo de ranking

O arquivo `rankings/2.json` só é criado **na primeira vez que alguém joga o
quiz 2**. Antes disso, ele nem existe (o site mostra "Ninguém jogou este quiz
ainda..."). Estrutura:

```json
{
    "quiz_id": 2,
    "atualizado_em": "2026-11-01 12:00:00",
    "jogadores": [
        {
            "nome": "Carla",
            "pontos": 3000,
            "acertos": 3,
            "total": 3,
            "data": "2026-11-01 12:00:00"
        },
        {
            "nome": "Ana",
            "pontos": 2850,
            "acertos": 3,
            "total": 3,
            "data": "2026-11-01 11:59:00"
        }
    ]
}
```

| Campo | O que é |
|---|---|
| `quiz_id` | De qual quiz é esse ranking (bate com o nome do arquivo) |
| `atualizado_em` | Última vez que alguém jogou |
| `jogadores[n].nome` | Nome digitado pelo jogador na tela inicial (ou "Anônimo") |
| `jogadores[n].pontos` | Pontuação final do jogo |
| `jogadores[n].acertos` | Quantas perguntas acertou |
| `jogadores[n].total` | Quantas perguntas o quiz tinha **naquele momento** (congelado no arquivo, porque o quiz pode ganhar perguntas depois) |
| `jogadores[n].data` | Quando o jogo terminou |

### 15.2 O caminho da pontuação, do jogo até o arquivo

```
fim do jogo (tela final)
      │
      │  fetch() — JavaScript chama o servidor em segundo plano
      ▼
salvar_resultado.php
      │  valida: o quiz existe? acertos ≤ total? pontos ≤ total × 1000?
      │  (o total vem do ARQUIVO do quiz, não do navegador)
      ▼
salvar_resultado()  em ranking_storage.php
      │  abre rankings/2.json com tranca (flock)
      │  → adiciona o resultado → reordena → mantém só o top 50 → regrava
      ▼
devolve JSON com a posição do jogador + o top 10
      │
      ▼
jogar.php desenha a lista "🏆 Melhores resultados deste quiz"
na própria tela final, destacando a linha do jogador
```

### 15.3 Critérios de classificação

Quem aparece primeiro (função `ordenar_jogadores()`):

1. **Mais pontos** — o placar principal;
2. Empatou? **Mais acertos**;
3. Empatou de novo? **Quem alcançou a pontuação primeiro** (data mais antiga).

O arquivo guarda no máximo os **50 melhores** (`RANKING_LIMITE`) para não
crescer infinitamente. E a ordem é refeita no servidor toda vez que o
arquivo é lido, então mesmo que alguém edite o JSON na mão, o ranking
continua correto.

### 15.4 Onde o ranking aparece

| Lugar | O que mostra |
|---|---|
| **Tela final do jogo** (`jogar.php`) | Top 10 com medalhas, a linha do jogador destacada, "Você está em Nº X lugar" e botão para o ranking completo |
| **`ranking.php?id=N`** | Tabela completa: posição, jogador, pontos, acertos X/Y e data |
| **Catálogo** | Botão 🏆 em cada card → `ranking.php?id=N` |
| **Tela inicial do jogo** | Link "🏆 Ver ranking deste quiz" |

### 15.5 Validações anti-mentira (e o limite delas)

O `salvar_resultado.php` confere se a pontuação é *possível*: acertos entre
0 e o total de perguntas, pontos entre 0 e `total × 1000` (cada pergunta vale
no máximo 1000). Quem mandar `pontos: 999999` recebe um erro e não entra no
ranking.

Mas é honesto dizer: a pontuação é calculada **no navegador** (o jogo roda
no cliente), então alguém que abrir o console e chamar
`salvar_resultado.php` manualmente ainda consegue mandar um valor falso
dentro do intervalo possível. Isso **já era assim na versão com MySQL** — a
solução definitiva é mover a correção das respostas para o servidor (ideia
para uma próxima versão).

### 15.6 Administrando os rankings na mão

- **Zerar o ranking de um quiz** → apague `rankings/N.json` (ele volta a ser
  criado no próximo jogo);
- **Backup** → a pasta `rankings/` faz parte dos dados: copie ela junto com
  a `quizzes/`;
- **Remover um jogador** → abra o arquivo e delete o bloco `{ ... }` dele
  (cuidado com as vírgulas!);
- O `.htaccess` dentro de `rankings/` bloqueia o acesso direto aos arquivos
  pelo navegador (os rankings contêm nomes de pessoas — proteção em dobro).

---

*Documento gerado em 31/10/2026 (atualizado com o sistema de ranking), referente aos arquivos da pasta `quiz-json/`
(`quiz_storage.php`, `ranking_storage.php`, `criar_quiz.php`, `jogar.php`, `catalogo.php`, `ranking.php`, `salvar_resultado.php`).*
