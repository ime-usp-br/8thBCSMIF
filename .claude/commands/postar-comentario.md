---
description: "Formula o comando para postar o comentário de análise de AC no GitHub."
---

## Tarefa: Formular e Postar Comentário de Análise de AC para a Issue #$ARGUMENTS

Com base na última análise de AC executada (cujo conteúdo está abaixo), no padrão de comentários verificado na etapa anterior (`/project:workflow:6a-verificar-comentarios`), e no hash do último commit, sua tarefa é construir o comando `gh api` exato para postar o comentário de validação no GitHub.

**1. Verificação de Pré-requisitos:**
**Claude Code deve executar:**
!if [ -d "llm_outputs/analyze-ac" ] && [ "$(ls -A llm_outputs/analyze-ac 2>/dev/null)" ]; then ls -t llm_outputs/analyze-ac/*.txt | head -1; else exit 1; fi

**2. Conteúdo da Última Análise de AC:**
**Claude Code deve executar:**
!LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1) && cat "$LATEST_ANALYSIS"

**3. Hash do Último Commit:**
**Claude Code deve executar:**
!git log -1 --pretty=format:%H

**4. Verificação de Padrão de Comentários (CRÍTICO):**
**Claude Code deve executar:**
!COMMENT_COUNT=$(gh api repos/:owner/:repo/issues/$ARGUMENTS/comments | jq length 2>/dev/null || echo "0") && if [ "$COMMENT_COUNT" -lt 3 ]; then gh issue list --state closed --label feature --limit 3; fi

**5. Ação:**
**Claude Code deve seguir** estritamente as regras do `CLAUDE.md`:
- **REFORMATAR, NÃO COPIAR:** Adapte o conteúdo da análise acima ao padrão de comentários observado.
- **CONSISTÊNCIA ABSOLUTA:** Use o mesmo título, seções e formatação dos outros comentários.
- **RASTREABILIDADE:** Inclua o hash do commit no rodapé, formatado como um link.

**Claude Code deve executar:**
1. Criar arquivo `/tmp/comment.txt` usando ferramenta Write (NUNCA HEREDOC)
2. Executar comando `gh api` para postar o comentário

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
```

**Comando Final:**
**Claude Code deve executar:**
```bash
gh api repos/:owner/:repo/issues/$ARGUMENTS/comments -F body=@/tmp/comment.txt
```