---
description: "Guia para formular e postar o comentário de análise de AC no GitHub."
---

**Nota Importante:** Ao executar comandos manualmente ou adicionar novos comandos a este workflow, se o comando puder gerar uma saída que precise ser exibida ou que possa travar o terminal, utilize `| cat` ao final do comando. Exemplo: `seu-comando-aqui | cat`.

## Guia: Formular e Postar Comentário de Análise de AC para a Issue

O Cline deve construir o comando `gh api` exato para postar o comentário de validação no GitHub, com base na análise de AC executada e no hash do commit relevante.

**Argumentos esperados:** `<ISSUE_NUMBER> <AC>`

### 1. Obtenção de Detalhes da Issue no GitHub
O Cline deve obter os detalhes da issue para verificar sua existência e contexto.

<execute_command>
<command>
gh issue view $ISSUE_NUMBER | cat
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Obtenção dos Últimos 5 Commits
O Cline deve obter os hashes e mensagens dos últimos 5 commits para análise.

<execute_command>
<command>
git log -5 --pretty=format:"%H %h %B" | cat
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Obtenção de Comentários de Issue Fechada para Referência
O Cline deve obter os comentários da issue fechada mais recente para servir de referência de formato.

<execute_command>
<command>
CLOSED_ISSUE_NUMBER=$(gh issue list --state closed --limit 1 --json number | jq -r '.[0].number')
if [ -n "$CLOSED_ISSUE_NUMBER" ]; then
    echo "Comentários da issue fechada mais recente (#$CLOSED_ISSUE_NUMBER) para referência:"
    gh api repos/ime-usp-br/8thBCSMIF/issues/$CLOSED_ISSUE_NUMBER/comments | cat
else
    echo "Nenhuma issue fechada encontrada para referência de comentários."
fi
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Execução e Obtenção do Resultado da Validação do AC com `analyze-ac`
O Cline deve rodar o script `analyze-ac` para validar o AC e obter seu resultado.

<execute_command>
<command>
printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ISSUE_NUMBER -a "$AC" -sc | cat
LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1)
cat "$LATEST_ANALYSIS"
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 5. Verificação do Resultado da Validação e Preparação do Comentário
O Cline deve verificar se o AC foi atendido. Se não, o workflow deve ser encerrado. Se sim, o Cline deve copiar o resultado da análise do `analyze-ac` para um arquivo temporário e adicionar o rodapé com o commit relevante.

<execute_command>
<command>
# A LLM deve analisar a saída do comando anterior (cat "$LATEST_ANALYSIS")
# para determinar se o AC foi "Atendido".
# Se não foi "Atendido", a LLM deve informar o usuário e encerrar o workflow.
# Caso contrário, a LLM deve construir o conteúdo do comentário.
# As variáveis ISSUE_NUMBER, AC, FOUND_COMMIT_HASH e FOUND_COMMIT_SHORT
# devem ser definidas pela LLM com base nas saídas dos comandos anteriores.

# Copia o conteúdo da análise para o arquivo temporário.
cp "$LATEST_ANALYSIS" .git/COMMENT_EDITMSG_TEMP

# A LLM deve garantir que FOUND_COMMIT_HASH e FOUND_COMMIT_SHORT estejam definidos.
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 6. Leitura e Edição da Mensagem Temporária (Ação do Cline)
O Cline agora lerá o conteúdo do arquivo temporário e fará quaisquer edições necessárias para garantir que a mensagem siga o padrão do projeto.

<read_file>
<path>.git/COMMENT_EDITMSG_TEMP</path>
</read_file>

**Instruções para o Cline:**
1.  **Analise o conteúdo lido do `.git/COMMENT_EDITMSG_TEMP`.**
2.  **Adicione o rodapé ao final do arquivo temporário usando `replace_in_file`.**
    *   O SEARCH block deve ser vazio (ou apenas uma nova linha se necessário para garantir que esteja no final).
    *   O REPLACE block deve conter o rodapé.
    *   Exemplo de uso:
        ```xml
        <replace_in_file>
        <path>.git/COMMENT_EDITMSG_TEMP</path>
        <diff>
        ------- SEARCH

        =======

        ---
        **Validação realizada no commit:** [$FOUND_COMMIT_SHORT](https://github.com/ime-usp-br/8thBCSMIF/commit/$FOUND_COMMIT_HASH)
        +++++++ REPLACE

### 7. Postagem do Comentário Final (com Aprovação do Usuário)
O Cline irá ler a mensagem final do arquivo temporário e postá-la no GitHub.

<execute_command>
<command>
FINAL_COMMENT_BODY=$(cat .git/COMMENT_EDITMSG_TEMP)
gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments -F body="$FINAL_COMMENT_BODY"
</command>
<requires_approval>true</requires_approval>
</execute_command>

**Formato do Comentário (exemplo):**
```markdown
## Conclusão sobre o Critério de Aceite X (ACX) da Issue #123

**Critério de Aceite (ACX):** "Texto exato do critério..."

**Análise:**

1. A implementação do `ServiceController` agora inclui o método `xyz` que atende ao requisito.

2. O teste `tests/Feature/ServiceControllerTest.php` foi adicionado e valida o comportamento esperado.

3. Todos os quality checks passaram conforme exigido.

**Conclusão:**

O Critério de Aceite X (ACX) foi **Atendido**.
---
**Validação realizada no commit:** [commit_hash_aqui](https://github.com/owner/repo/commit/commit_hash_aqui)
