# 🎮 Quiz Muito Louco! — Quiz Interativo e Sistema de Ranking

Projeto acadêmico desenvolvido para a disciplina de **Lógica de Programação**, com o objetivo de criar um quiz interativo de perguntas e respostas com pontuação acumulativa e sistema de classificação dos jogadores, inspirado no Kahoot.

---

## 📌 Sobre o Projeto

O sistema permite que os jogadores cadastrem uma conta, respondam a 10 perguntas sorteadas aleatoriamente de um banco de 45 questões, acumulem acertos e disputem posição no ranking geral. O desempate é feito pelo menor tempo total de partida.

A aplicação foi desenvolvida em **PHP procedural** (sem classes, com `declare(strict_types=1)`) para a lógica do sistema e **HTML5, CSS3 e JavaScript puro** para a interface web. Os dados são persistidos em **arquivos JSON**, sem banco de dados.

O projeto aplica conceitos fundamentais de programação: estruturas de dados (fila e pilha), algoritmos de busca e ordenação, manipulação de arquivos, controle de sessão e temporização.

---

## 🎯 Objetivos

- ✅ Desenvolver um jogo de perguntas e respostas interativo.
- ✅ Implementar um sistema de pontuação acumulativa por partida.
- ✅ Criar um ranking organizado pela pontuação dos jogadores.
- ✅ Aplicar estruturas de dados estudadas em Lógica de Programação (Fila e Pilha).
- ✅ Armazenar perguntas, usuários e resultados em arquivos JSON.
- ✅ Desenvolver uma interface web simples e intuitiva.
- ✅ Permitir revisar e desfazer a última resposta antes do envio final.

---

## 🛠️ Tecnologias

- **PHP (procedural):** processamento das respostas, regras do quiz, cálculo da pontuação e controle de sessão.
- **HTML5:** estrutura das páginas e formulários.
- **CSS3:** estilização e organização visual da interface (layout em card, gradientes, responsividade básica).
- **JavaScript puro:** atualização ao vivo do cronômetro da pergunta e estado do botão de envio.
- **JSON:** armazenamento de perguntas (`data/questions.json`) e usuários / histórico (`data/users.json`).
- **Apache .htaccess:** bloqueio do acesso direto aos diretórios `data/`, `includes/` e `templates/`.

---

## ⚙️ Funcionalidades Implementadas

- 🔐 Cadastro e login de jogadores por nome e senha (sessão PHP, com `session_regenerate_id`).
- 🧠 Sorteio aleatório de 10 perguntas a cada partida, a partir de um banco de 45 questões.
- ⏱️ Cronômetro por pergunta e tempo total da partida, exibidos em tempo real.
- ✅ Cálculo de acertos ao longo da partida.
- ↩️ Revisão da partida com o histórico completo de respostas.
- ↩️ **Desfazer a última resposta:** a pergunta volta a ficar disponível para resposta antes do envio final.
- 🏆 Ranking geral ordenado por acertos (decrescente) e, no empate, por menor tempo (crescente).
- 🥇 Destaque do jogador atual e indicação de novo recorde ao quebrar o próprio melhor desempenho.
- 💾 Persistência em arquivos JSON, com escrita travada (`LOCK_EX`) para evitar corrupção.
- 🚫 Proteção de diretórios sensíveis via `.htaccess`.
- 📄 Manuais em PDF na pasta `docs/` (manual do jogo e manual técnico).

---

## 🧠 Estruturas de Dados e Algoritmos

| Estrutura ou Conceito | Aplicação no Projeto |
| --- | --- |
| **Fila (FIFO)** | `includes/fila.php` — organização da sequência de perguntas da partida. As perguntas são enfileiradas na ordem do sorteio e desenfileiradas uma a uma na mesma ordem em que aparecem para o jogador. Implementada de forma procedural, com funções puras que não mutam a fila recebida. |
| **Pilha (LIFO)** | `includes/pilha.php` — histórico de respostas da partida. Cada resposta é empilhada no topo e pode ser desempilhada pelo botão **Desfazer**, devolvendo a pergunta para a frente da fila e revertendo acertos e tempo registrados. |
| **Vetor 1D** | Armazenamento das alternativas de cada pergunta, dos IDs sorteados e dos campos de cada usuário. |
| **Busca linear** | `includes/collection.php` (`findFirst`) — localização de usuário por nome, por ID e de pergunta por ID, com predicado (`callable`). |
| **Ordenação** | `includes/ranking.php` — `usort` com comparador `compareRanking` que ordena por dois critérios: mais acertos primeiro e, no empate, menor tempo. |
| **Persistência em arquivos** | `includes/json.php` — leitura e escrita de JSON em `data/users.json` e `data/questions.json`, com `JSON_PRETTY_PRINT`, `JSON_UNESCAPED_UNICODE` e `LOCK_EX`. |
| **Sessão PHP** | Estado do jogador logado e da partida em andamento (fila, pilha, acertos e tempo). |

> **Observação:** a versão atual não utiliza matriz 2D nem algoritmos clássicos de ordenação (Bubble Sort, Selection Sort, QuickSort). A ordenação do ranking é feita pela função nativa `usort` com comparador de múltiplas chaves.

---

## 🔄 Fluxo do Jogo

```
index.php (login / cadastro)
       │
       │  authenticate.php
       ▼
pages/game/game.php ──► pages/game/answer.php (registra resposta, avança fila)
       │                                       │
       │  fila vazia?                           │ ainda há perguntas?
       ▼                                       └──► game.php (próxima pergunta)
pages/game/revisao.php
       │
       │  desfazer.php (desempilha a última resposta)
       │  finalizar.php (salva resultado)
       └───────────────────────────────────────────────┤
                                                        ▼
                                            pages/game/result.php
                                                        │
                                                        ▼
                                            pages/ranking/ranking.php
```

1. O jogador entra na partida; 10 perguntas são sorteadas e enfileiradas (FIFO).
2. A cada resposta, a pergunta sai da fila e o registro entra na pilha (LIFO) com a opção escolhida, se foi correta e o tempo gasto.
3. Quando a fila esvazia, o jogador vai para a revisão, onde pode desfazer a última resposta (pilha) ou finalizar a partida.
4. Ao finalizar, o resultado é comparado com o melhor desempenho anterior do usuário; se for melhor, o recorde é atualizado em `users.json`.
5. O ranking mostra todos os jogadores que já concluíram pelo menos uma partida.

---

## 📂 Estrutura de Arquivos

```
Quiz/
├── .htaccess                      # bloqueia acesso direto a data/, includes/ e templates/
├── index.php                      # tela de login / cadastro
├── authenticate.php               # processa login e cadastro (POST)
├── logout.php                     # encerra a sessão
├── assets/
│   ├── global.css                 # estilos globais (topbar, card, botões, flash)
│   └── script.js                  # desaparecimento automático das mensagens flash
├── data/
│   ├── questions.json             # banco com 45 perguntas (4 alternativas cada)
│   └── users.json                 # usuários cadastrados + melhor pontuação/tempo
├── docs/
│   ├── manual-do-jogo.pdf         # instruções para os jogadores
│   └── manual-tecnico.pdf         # documentação técnica do projeto
├── includes/
│   ├── boot.php                   # bootstrap: constantes, session_start e includes
│   ├── auth.php                   # autenticação, registro e mensagens de erro
│   ├── session.php                # controle de sessão do usuário e flash messages
│   ├── users.php                  # CRUD de usuários em JSON
│   ├── questions.php              # leitura e sorteio de perguntas
│   ├── game.php                   # estado da partida (fila + pilha + acertos + tempo)
│   ├── timer.php                  # cronometragem por pergunta e total
│   ├── result.php                 # montagem do resultado e atualização de recorde
│   ├── ranking.php                # ordenação e filtro de jogadores ranqueados
│   ├── fila.php                   # Fila (FIFO) procedural com funções puras
│   ├── pilha.php                  # Pilha (LIFO) procedural com funções puras
│   ├── collection.php             # utilitário de busca (findFirst)
│   ├── json.php                   # leitura/escrita de JSON
│   └── view.php                   # helpers de view (escape, url, redirect, formatTime)
├── pages/
│   ├── game/
│   │   ├── game.php               # exibe a pergunta atual
│   │   ├── answer.php             # registra a resposta
│   │   ├── revisao.php            # revisão da partida (histórico em pilha)
│   │   ├── desfazer.php           # desfaz a última resposta
│   │   ├── finalizar.php          # finaliza e salva o resultado
│   │   ├── result.php             # tela de fim de jogo (com badge de recorde)
│   │   ├── css/game.css
│   │   └── js/game.js             # cronômetro ao vivo + estado do botão
│   └── ranking/
│       ├── ranking.php            # tabela do ranking
│       └── css/ranking.css
└── templates/
    ├── header.php
    └── footer.php
```

A pasta `docs/` contém os manuais em PDF entregues junto com o projeto. O arquivo `.htaccess` impede o download direto de `data/`, `includes/` e `templates/` quando o projeto é servido por um servidor Apache.

---

## 📊 Sistema de Ranking

O ranking organiza os jogadores pela melhor partida registrada. Os critérios de ordenação, em ordem de prioridade, são:

1. **Mais acertos** (decrescente);
2. No empate, **menor tempo total** (crescente).

Cada usuário guarda apenas a sua melhor partida em `users.json`:

```json
{
  "id": 1,
  "name": "Antonio Avc",
  "password": "123456",
  "best_correct_answers": 10,
  "best_time_seconds": 0.01
}
```

A função `compareRanking` implementa o comparador de múltiplas chaves usando o operador spaceship `<=>` do PHP:

```php
return [$second['best_correct_answers'], $first['best_time_seconds']]
    <=> [$first['best_correct_answers'], $second['best_time_seconds']];
```

Apenas usuários que já concluíram uma partida aparecem no ranking (filtro `hasPlayed`).

---

## 🚀 Como Executar

### Requisitos

- **PHP 8.0+** (uso de `declare(strict_types=1)`, tipos `mixed`, `never` e operador `<=>`).
- Servidor web com suporte a PHP (Apache com `mod_rewrite`. Embora o `.htaccess` seja opcional, ele protege os diretórios sensíveis).
- Permissão de escrita na pasta `data/` (para salvar usuários e ranking).

### Passos

1. Copie a pasta `Quiz/` para o *DocumentRoot* do servidor web (ex.: `htdocs/` no XAMPP, `/var/www/html/` no Apache).
2. Certifique-se de que a constante `BASE_URL` em `includes/boot.php` corresponde ao caminho da aplicação (por padrão `/Quiz/`).
3. Garanta permissão de escrita em `data/`:

   ```bash
   chmod -R 755 data/
   ```

4. Acesse pelo navegador: `http://localhost/Quiz/index.php`.

### Alternativa via servidor embutido do PHP

```bash
cd Quiz
php -S localhost:8000
```

> Neste modo, o `.htaccess` é ignorado, então os diretórios `data/`, `includes/` e `templates/` ficam acessíveis diretamente por URL — recomendado apenas para desenvolvimento local.

---

## 📚 Contexto Acadêmico

- **Disciplina:** Lógica de Programação
- **Projeto:** Proposta 5 — Quiz Interativo e Sistema de Ranking
- **Interface escolhida:** Aplicação web simplificada
- **Linguagem principal:** PHP
- **Tecnologias de interface:** HTML5, CSS3 e JavaScript puro
- **Persistência:** arquivos JSON

---

## 👨‍💻 Autores

- Bruno Batista
- Guilherme Moreira
- Lucas Bispo
