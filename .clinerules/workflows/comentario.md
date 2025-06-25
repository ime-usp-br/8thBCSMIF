---
description: "Guia para formular e postar o comentário de análise de AC no GitHub."
---

## Guia: Formular e Postar Comentário de Análise de AC para a Issue

O Cline deve construir o comando `gh api` exato para postar o comentário de validação no GitHub, com base na última análise de AC executada e no hash do último commit.

**Argumentos esperados:** `<ISSUE_NUMBER>`

### 1. Verificação de Pré-requisitos e Conteúdo da Última Análise de AC
O Cline deve verificar a existência de resultados de análise de AC e obter o caminho para o arquivo da última análise.

<execute_command>
<command>
if [ -d "llm_outputs/analyze-ac" ] && [ "$(ls -A llm_outputs/analyze-ac 2>/dev/null)" ]; then
    LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1)
    echo "✅ Última análise de AC encontrada: $LATEST_ANALYSIS"
    cat "$LATEST_ANALYSIS"
else
    echo "❌ Nenhum resultado de análise de AC encontrado em llm_outputs/analyze-ac."
    # O Cline deve ser instruído a parar e informar o usuário sobre a ausência de análise.
fi
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Hash do Último Commit
O Cline deve obter o hash completo e curto do último commit.

<execute_command>
<command>
COMMIT_HASH=$(git rev-parse HEAD)
COMMIT_SHORT=$(git rev-parse --short HEAD)
echo "Full: $COMMIT_HASH"
echo "Short: $COMMIT_SHORT"
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Verificação de Padrão de Comentários (Opcional)
O Cline pode verificar a quantidade de comentários existentes na issue para inferir o padrão, se necessário.

<execute_command>
<command>
COMMENT_COUNT=$(gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments | jq length 2>/dev/null || echo "0")
echo "Número de comentários na issue #$ISSUE_NUMBER: $COMMENT_COUNT"
if [ "$COMMENT_COUNT" -lt 3 ]; then
    echo "Verificando issues fechadas para exemplos de comentários..."
    gh issue list --state closed --label feature --limit 3
fi
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Ação: Construção e Postagem do Comentário
O Cline deve reformatar o conteúdo da análise de AC, adicionar o rodapé com o hash do commit atual e postar o comentário no GitHub.

<execute_command>
<command>
ISSUE_NUMBER=$ISSUE_NUMBER # Assume que ISSUE_NUMBER é uma variável de ambiente ou foi fornecida.
LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1)
COMMIT_HASH=$(git rev-parse HEAD)
COMMIT_SHORT=$(git rev-parse --short HEAD)

# 1. Copiar analyze-ac output
cp "$LATEST_ANALYSIS" /tmp/comment.txt

# 2. Adicionar rodapé com commit ATUAL
echo "" >> /tmp/comment.txt
echo "---" >> /tmp/comment.txt
echo "**Validação realizada no commit:** [$COMMIT_SHORT](https://github.com/ime-usp-br/8thBCSMIF/commit/$COMMIT_HASH)" >> /tmp/comment.txt

# 3. Verificar conteúdo antes de postar
echo "Conteúdo do comentário a ser postado:"
cat /tmp/comment.txt

# 4. Postar comentário
gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments -F body=@/tmp/comment.txt
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
