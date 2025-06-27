---
description: "Guia para formular e postar o comentário de análise de AC no GitHub."
---

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

### 5. Verificação do Resultado da Validação e Construção do Comentário
O Cline deve verificar se o AC foi atendido. Se não, o workflow deve ser encerrado. Se sim, o Cline deve construir a mensagem de comentário com base na resposta do `analyze-ac` e nos comentários de referência, adicionando o rodapé com o commit relevante.

<execute_command>
<command>
# A LLM deve analisar a saída do comando anterior (cat "$LATEST_ANALYSIS")
# para determinar se o AC foi "Atendido".
# Se não foi "Atendido", a LLM deve informar o usuário e encerrar o workflow.
# Caso contrário, a LLM deve construir a variável COMMENT_BODY
# com base no conteúdo da análise e nos comentários de referência obtidos anteriormente.
# As variáveis ISSUE_NUMBER, AC, FOUND_COMMIT_HASH e FOUND_COMMIT_SHORT
# devem ser definidas pela LLM com base nas saídas dos comandos anteriores.

# Exemplo de como a LLM construiria o COMMENT_BODY (este é um placeholder para a lógica da LLM):
# COMMENT_BODY="## Conclusão sobre o Critério de Aceite $AC da Issue #$ISSUE_NUMBER<br/><br/>**Critério de Aceite ($AC):** \"Texto exato do critério...\"<br/><br/>**Análise:**<br/><br/>[Conteúdo da análise do analyze-ac formatado pela LLM]<br/><br/>**Conclusão:**<br/><br/>O Critério de Aceite $AC foi **Atendido**.<br/>---<br/>**Validação realizada no commit:** [$FOUND_COMMIT_SHORT](https://github.com/ime-usp-br/8thBCSMIF/commit/$FOUND_COMMIT_HASH)"

# A LLM deve então usar a variável COMMENT_BODY para postar o comentário.
# gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments -F body="$COMMENT_BODY"
echo "A LLM é responsável por analisar o resultado do analyze-ac, construir o comentário e postá-lo."
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
