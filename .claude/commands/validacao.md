---
description: "Executa validação completa de AC seguindo workflow obrigatório do CLAUDE.md"
---

## Tarefa: Validação Completa de Acceptance Criteria (AC)

Seguindo a **Sequência Obrigatória** definida no `CLAUDE.md`, **Claude Code deve executar** todo o workflow de validação de um AC específico.

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### 1. **Análise e Contexto da Issue**
**Claude Code deve executar:**
!gh issue view $ARGUMENT_1

### 2. **Quality Checks Obrigatórios**
**Claude Code deve executar:**
!vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test && pytest -v --live

### 3. **Atualização de Contexto**
**Claude Code deve executar:**
!git add . && python3 scripts/generate_context.py --stages git

### 4. **Validação Crítica com analyze-ac**
**Claude Code deve executar:**
!printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ARGUMENT_1 -a $ARGUMENT_2 -sc

### 5. **Verificação de Resultados**
**Claude Code deve executar:**
!LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1) && tail -10 "$LATEST_ANALYSIS"

**⚠ ATENÇÃO:** Só prossiga para commit e documentação se a validação `analyze-ac` APROVAR o AC. Caso contrário, corrija as pendências identificadas.

**Próximos passos** (apenas se validação APROVADA):
1. Use `/commit` para criar commit formatado
2. Use `/postar-comentario` para documentar no GitHub