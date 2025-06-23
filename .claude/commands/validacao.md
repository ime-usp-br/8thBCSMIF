---
description: "Executa validação completa de AC seguindo workflow Claude Code autônomo"
---

## Tarefa: Validação Completa de Acceptance Criteria (AC)

**IMPORTANTE:** Este comando foi atualizado com base nas lições da conversa de sucesso AC1 Issue #50.

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### 1. **TodoWrite para Tracking Transparente**
**Claude Code deve usar TodoWrite** para criar tracking das tarefas de validação.

### 2. **Análise de Contexto da Issue**
**Claude Code deve executar:**
!gh issue view $ARGUMENT_1

### 3. **Verificação de Requisitos "Incrementais"**
**Claude Code deve verificar** se a issue contém palavras-chave "incremental", "adicionar", "modificar":
- Se SIM: **CRÍTICO** - usar métodos aditivos (attach, create) não substitutivos (sync, update)
- Validar que implementação não apaga/substitui dados existentes

### 4. **Quality Checks Obrigatórios**
**Claude Code deve executar:**
!vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test

### 5. **Atualização de Contexto (CRÍTICO)**
**Claude Code deve executar:**
!git add . && python3 scripts/generate_context.py --stages git

### 6. **Validação Automática com analyze-ac**
**Claude Code deve executar:**
!printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ARGUMENT_1 -a $ARGUMENT_2 -sc

### 7. **Verificação de Resultados**
**Claude Code deve executar:**
!LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1) && echo "=== RESULTADO ANALYZE-AC ===" && tail -5 "$LATEST_ANALYSIS"

**⚠ ATENÇÃO:** Só prossiga se analyze-ac mostrar "foi **Atendido**". Se reprovar, corrija e repita validação.

**✅ PRÓXIMOS PASSOS AUTOMÁTICOS** (apenas se APROVADO):
1. `/commit` - Criar commit com padrão projeto
2. `/git-push` - Push para remote
3. `/postar-comentario` - Documentar no GitHub com hash correto

**🔴 APRENDIZADO:** Sempre verificar se comportamento atende exatamente ao descrito no AC antes de aprovar.